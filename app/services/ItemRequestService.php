<?php
namespace Services;

use Core\Database;
use Core\FileUploader;
use Core\Logger;
use Models\Notification;
use Models\Reuse\DistributionCooldown;
use Models\Reuse\GsVerification;
use Models\Reuse\ItemRequest;
use Models\Reuse\ReuseFeedback;
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
    /** How long an approved GS letter stays valid for. */
    public const LETTER_VALID_MONTHS = 6;

    private ReuseItem            $items;
    private ItemRequest          $requests;
    private GsVerification       $gsLetters;
    private DistributionCooldown $cooldowns;
    private Notification         $notifications;
    private ReuseFeedback        $feedback;

    public function __construct()
    {
        $this->items         = new ReuseItem();
        $this->requests      = new ItemRequest();
        $this->gsLetters     = new GsVerification();
        $this->cooldowns     = new DistributionCooldown();
        $this->notifications = new Notification();
        $this->feedback      = new ReuseFeedback();
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

        // Stored before the transaction - a moved file cannot be rolled back.
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
     * The donor confirms the item changed hands.
     *
     * Either side can close a donation - see markReceived() - because only one
     * of the two is standing in front of the item when it moves, and which one
     * that is varies. Whoever confirms first completes it; the second finds the
     * button gone.
     */
    public function markDonated(array $item, array $request): void
    {
        $this->complete($item, $request);

        $this->notify(
            (int) $request['requester_id'],
            'Donation completed',
            'The donor marked “' . $item['title'] . '” as handed over. '
            . 'A ' . DistributionCooldown::WEEKS . '-week cooldown now applies to this category.',
            '/reuse/' . $item['id']
        );

        Logger::audit("Item {$item['id']} handed over to user {$request['requester_id']}");
    }

    /**
     * The recipient confirms the item reached them.
     *
     * The same close as markDonated(), notifying the other party. The cooldown
     * still starts against the recipient, which is why this cannot simply be the
     * donor's button relabelled - the two differ only in who is told.
     */
    public function markReceived(array $item, array $request): void
    {
        $this->complete($item, $request);

        $this->notify(
            (int) $item['donor_id'],
            'Your donation was received',
            'The recipient confirmed they collected “' . $item['title'] . '”.',
            '/my/reuse/' . $item['id'] . '/requests'
        );

        Logger::audit("Item {$item['id']} confirmed received by user {$request['requester_id']}");
    }

    /**
     * The item has changed hands. This is where the cooldown starts, so a
     * recipient cannot immediately queue for the same category again.
     */
    private function complete(array $item, array $request): void
    {
        Database::transaction(function () use ($item, $request) {
            $this->items->update((int) $item['id'], ['status' => ITEM_DONATED]);
            $this->cooldowns->start((int) $request['requester_id'], (int) $item['category_id']);
        });
    }

    /**
     * Why this member may not confirm they received this item, or null when
     * they may. Checked before drawing the button and again before the write -
     * the form can be replayed, and the donor may have closed it first.
     */
    public function receiptBlockedReason(array $item, array $request, int $recipientId): ?string
    {
        if ((int) $request['requester_id'] !== $recipientId) {
            return 'You can only confirm a donation you were selected for.';
        }
        if ($request['status'] !== 'selected') {
            return 'This request was not the one the donor selected.';
        }
        if ($item['status'] === ITEM_DONATED) {
            return 'This donation is already marked as complete.';
        }
        if ($item['status'] !== ITEM_RESERVED) {
            return 'This listing is not being held for you.';
        }
        return null;
    }

    /**
     * Why this member may not rate this donation, or null when they may.
     *
     * A review is the last step of the request lifecycle, so the rules live
     * next to the rest of it: only the person who was actually given the item
     * may rate it, only after the donor confirmed the handover, and only once.
     */
    public function feedbackBlockedReason(array $item, array $request, int $recipientId): ?string
    {
        if ((int) $request['requester_id'] !== $recipientId) {
            return 'You can only review a donation you received.';
        }
        if ($request['status'] !== 'selected') {
            return 'This request was not the one the donor selected.';
        }
        if ($item['status'] !== ITEM_DONATED) {
            return 'You can review this donation once the donor marks it as handed over.';
        }
        if ($this->feedback->existsFor((int) $item['id'], $recipientId)) {
            return 'You have already reviewed this donation.';
        }
        return null;
    }

    /**
     * The recipient rates the donor. Written once and never edited -
     * `reuse_feedback` has no `updated_at`, so Model::update() would fail on it
     * anyway, and a review that can be rewritten later is worth less.
     */
    public function leaveFeedback(array $item, int $recipientId, int $rating, ?string $comment): void
    {
        Database::transaction(function () use ($item, $recipientId, $rating, $comment) {
            $this->feedback->create([
                'item_id'      => (int) $item['id'],
                'recipient_id' => $recipientId,
                'rating'       => $rating,
                'comment'      => $comment ?: null,
            ]);

            $this->notify(
                (int) $item['donor_id'],
                'You received a review',
                'A recipient rated your donation of “' . $item['title'] . '” '
                . $rating . ' out of 5.',
                '/reuse/donor/' . $item['donor_id']
            );
        });

        Logger::audit("Feedback {$rating}/5 left by user {$recipientId} on item {$item['id']}");
    }

    /**
     * An administrator decides on a Grama Niladhari letter.
     *
     * Until something called this, a letter was stored `pending` and never read
     * again, so "verified disaster-affected" on a donor's queue meant no more
     * than "claimed, and attached a file". This is what makes the badge true.
     *
     * An approval carries an expiry: being disaster-affected is a circumstance,
     * not a permanent status, and a letter that never lapses would let one
     * household hold priority for ever.
     */
    public function decideLetter(array $letter, string $status, int $reviewerId): void
    {
        $validUntil = $status === MOD_APPROVED
            ? date('Y-m-d', strtotime('+' . self::LETTER_VALID_MONTHS . ' months'))
            : null;

        Database::transaction(function () use ($letter, $status, $reviewerId, $validUntil) {
            $this->gsLetters->decide((int) $letter['id'], $status, $reviewerId, $validUntil);

            [$title, $body] = $status === MOD_APPROVED
                ? [
                    'Your GS letter was verified',
                    'Donors will now see your disaster-affected status as verified, '
                    . 'until ' . format_date($validUntil) . '.',
                ]
                : [
                    'Your GS letter was not accepted',
                    'The letter you uploaded could not be verified. You can attach a '
                    . 'new one to your next request.',
                ];

            $this->notify((int) $letter['user_id'], $title, $body, '/my/activities', 'verification');
        });

        Logger::audit("GS letter {$letter['id']} {$status} by admin {$reviewerId}");
    }

    /**
     * Safe through the model: `item_requests` does have an `updated_at`, so
     * the timestamp Model::update() appends has somewhere to go - unlike the
     * other child tables in this module.
     */
    private function setStatus(int $requestId, string $status): void
    {
        $this->requests->update($requestId, ['status' => $status]);
    }

    private function notify(int $userId, string $title, string $body, string $link, string $category = 'donation'): void
    {
        $this->notifications->create([
            'user_id'  => $userId,
            'title'    => $title,
            'body'     => $body,
            'category' => $category,
            'link'     => $link,
        ]);
    }
}
