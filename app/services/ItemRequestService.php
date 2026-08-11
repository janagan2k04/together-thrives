<?php
namespace Services;

use Core\Database;
use Core\FileUploader;
use Core\Logger;
use Models\Notification;
use Models\Reuse\DistributionCooldown;
use Models\Reuse\GsVerification;
use Models\Reuse\ItemRequest;
use Models\Reuse\ReuseItem;

/**
 * The rules around asking a donor for an item.
 *
 * Three of them are stated on the screens themselves and enforced here rather
 * than in the controller: one request per person per item, a cooldown on the
 * category after you receive something, and priority for verified
 * disaster-affected requests.
 *
 * Every refusal returns a message instead of throwing, because all of them are
 * ordinary outcomes a requester should see explained, not 500s.
 */
class ItemRequestService
{
    private ReuseItem            $items;
    private ItemRequest          $requests;
    private GsVerification       $gsLetters;
    private DistributionCooldown $cooldowns;
    private Notification         $notifications;

    public function __construct()
    {
        $this->items         = new ReuseItem();
        $this->requests      = new ItemRequest();
        $this->gsLetters     = new GsVerification();
        $this->cooldowns     = new DistributionCooldown();
        $this->notifications = new Notification();
    }

    /**
     * Why this member may not ask for this item, or null when they may.
     * Called before showing the form and again before writing it.
     */
    public function blockedReason(array $item, int $requesterId): ?string
    {
        if ((int) $item['donor_id'] === $requesterId) {
            return 'This is your own listing.';
        }
        if ($item['status'] !== ITEM_AVAILABLE) {
            return 'This item is no longer open to requests.';
        }
        if ($this->requests->forRequester((int) $item['id'], $requesterId)) {
            return 'You have already requested this item.';
        }
        if ($this->cooldowns->active($requesterId, (int) $item['category_id'])) {
            return 'You recently received an item in this category. '
                 . 'Please wait until your cooldown ends so others get a turn.';
        }
        return null;
    }

    /**
     * @param array|null $gsLetter entry from $_FILES, when claiming priority
     * @return int the new request id
     */
    public function create(array $item, int $requesterId, array $data, ?array $gsLetter): int
    {
        $disasterAffected = !empty($data['is_disaster_affected']);

        // Stored before the transaction — a moved file cannot be rolled back.
        $letterPath = ($disasterAffected && $gsLetter)
            ? FileUploader::store($gsLetter, 'gs-letters', ['pdf', 'jpg', 'png'], 5)
            : null;

        try {
            return Database::transaction(function () use ($item, $requesterId, $data, $disasterAffected, $letterPath) {
                $gsId = null;
                if ($letterPath !== null) {
                    $gsId = $this->gsLetters->create([
                        'user_id'     => $requesterId,
                        'letter_path' => $letterPath,
                        'status'      => MOD_PENDING,
                    ]);
                }

                $requestId = $this->requests->create([
                    'item_id'              => (int) $item['id'],
                    'requester_id'         => $requesterId,
                    'reason'               => ($data['reason'] ?? '') ?: null,
                    'is_disaster_affected' => $disasterAffected ? 1 : 0,
                    'gs_verification_id'   => $gsId,
                    'status'               => 'pending',
                ]);

                $this->notifications->create([
                    'user_id'  => (int) $item['donor_id'],
                    'title'    => 'New request for your item',
                    'body'     => 'Someone has asked for “' . $item['title'] . '”.',
                    'category' => 'donation',
                    'link'     => '/my/reuse/' . $item['id'] . '/requests',
                ]);

                Logger::audit("Request {$requestId} raised by user {$requesterId} for item {$item['id']}");
                return $requestId;
            });
        } catch (\Throwable $e) {
            FileUploader::delete($letterPath);
            throw $e;
        }
    }

    /**
     * The donor picks a recipient: that request is selected, the rest are
     * turned down, and the item is held so nobody else asks for it.
     */
    public function select(array $item, array $request): void
    {
        Database::transaction(function () use ($item, $request) {
            $this->setStatus((int) $request['id'], 'selected');

            foreach ($this->requests->forItem((int) $item['id']) as $other) {
                if ((int) $other['id'] !== (int) $request['id'] && $other['status'] === 'pending') {
                    $this->setStatus((int) $other['id'], 'rejected');
                    $this->notify(
                        (int) $other['requester_id'],
                        'Item given to someone else',
                        '“' . $item['title'] . '” has gone to another requester.',
                        '/reuse/' . $item['id']
                    );
                }
            }

            $this->items->update((int) $item['id'], ['status' => ITEM_RESERVED]);

            $this->notify(
                (int) $request['requester_id'],
                'Your request was accepted',
                'The donor has selected you for “' . $item['title'] . '”. Arrange pickup with them.',
                '/reuse/' . $item['id']
            );
        });

        Logger::audit("Request {$request['id']} selected for item {$item['id']}");
    }

    /** Turn one request down without choosing anybody else yet. */
    public function reject(array $item, array $request): void
    {
        Database::transaction(function () use ($item, $request) {
            $this->setStatus((int) $request['id'], 'rejected');
            $this->notify(
                (int) $request['requester_id'],
                'Request not selected',
                'The donor could not accept your request for “' . $item['title'] . '”.',
                '/reuse/' . $item['id']
            );
        });
    }

    /**
     * The item has changed hands. This is where the cooldown starts, so a
     * recipient cannot immediately queue for the same category again.
     */
    public function markDonated(array $item, array $request): void
    {
        Database::transaction(function () use ($item, $request) {
            $this->items->update((int) $item['id'], ['status' => ITEM_DONATED]);
            $this->cooldowns->start((int) $request['requester_id'], (int) $item['category_id']);

            $this->notify(
                (int) $request['requester_id'],
                'Donation completed',
                'The donor marked “' . $item['title'] . '” as handed over. '
                . 'A ' . DistributionCooldown::WEEKS . '-week cooldown now applies to this category.',
                '/reuse/' . $item['id']
            );
        });

        Logger::audit("Item {$item['id']} handed over to user {$request['requester_id']}");
    }

    /**
     * Safe through the model: `item_requests` does have an `updated_at`, so
     * the timestamp Model::update() appends has somewhere to go — unlike the
     * other child tables in this module.
     */
    private function setStatus(int $requestId, string $status): void
    {
        $this->requests->update($requestId, ['status' => $status]);
    }

    private function notify(int $userId, string $title, string $body, string $link): void
    {
        $this->notifications->create([
            'user_id'  => $userId,
            'title'    => $title,
            'body'     => $body,
            'category' => 'donation',
            'link'     => $link,
        ]);
    }
}
