<?php
namespace Services;

use Core\Database;
use Core\Logger;
use Models\Donation\DonationRequest;
use Models\Donation\RequestedItem;

/**
 * Business rules for donation requests.
 *
 * An item request is a row in `donation_requests` plus one or more rows in
 * `requested_items`; a fund request is the parent row alone. Both halves have
 * to land together or not at all, so every write goes through a transaction.
 */
class DonationService
{
    /** The Figma's item table tops out here, and an unbounded list is a DoS. */
    public const MAX_ITEM_LINES = 20;

    private DonationRequest $requests;
    private RequestedItem   $items;

    public function __construct()
    {
        $this->requests = new DonationRequest();
        $this->items    = new RequestedItem();
    }

    /**
     * @param array $lines item lines, already normalised by normaliseLines()
     * @return int the new request id
     */
    public function create(int $requesterId, string $requesterType, array $data, array $lines): int
    {
        return Database::transaction(function () use ($requesterId, $requesterType, $data, $lines) {
            $id = $this->requests->create(
                $this->columns($data) + [
                    'requester_id'   => $requesterId,
                    'requester_type' => $requesterType,
                    // Every new request waits for an administrator. The create
                    // form and the public list both promise donors that.
                    'status'         => REQUEST_PENDING,
                ]
            );

            if ($data['request_type'] === 'items') {
                $this->items->replaceFor($id, $lines);
            }

            Logger::audit("Donation request {$id} created by user {$requesterId}");

            return $id;
        });
    }

    /**
     * Editing an approved request sends it back to the queue: the affected
     * area, the dates and the target are exactly the facts an administrator
     * checked, so letting an edit through silently would publish unreviewed
     * details to donors.
     */
    public function update(array $request, array $data, array $lines): void
    {
        Database::transaction(function () use ($request, $data, $lines) {
            $columns = $this->columns($data);

            if ($request['status'] === REQUEST_OPEN) {
                $columns['status'] = REQUEST_PENDING;
            }

            $this->requests->update((int) $request['id'], $columns);

            if ($data['request_type'] === 'items') {
                $this->items->replaceFor((int) $request['id'], $lines);
            }

            Logger::audit("Donation request {$request['id']} updated");
        });
    }

    /**
     * Cancel, not erase. The row is soft deleted and marked cancelled so the
     * requester's history - and any reservation or payment already made against
     * it - survives.
     */
    public function cancel(int $id): void
    {
        Database::transaction(function () use ($id) {
            $this->requests->update($id, ['status' => REQUEST_CANCELLED]);
            $this->requests->delete($id);

            Logger::audit("Donation request {$id} cancelled");
        });
    }

    /**
     * The columns that come off the form, with the two type-specific ones
     * blanked for the type they do not belong to.
     *
     * `pickup_point` is required for an item request and meaningless for a fund
     * one; `target_amount` is the reverse. The schema makes both nullable for
     * exactly that reason, and clearing the other one matters on an edit that
     * switches type - otherwise a fund request would keep a stale pickup point.
     */
    private function columns(array $data): array
    {
        $isItems = $data['request_type'] === 'items';

        return [
            'request_type'     => $data['request_type'],
            'title'            => $data['title'],
            'description'      => $data['description'],
            'disaster_details' => ($data['disaster_details'] ?? '') ?: null,
            'affected_area'    => $data['affected_area'],
            'city_id'          => (int) $data['city_id'],
            'starts_on'        => $data['starts_on'],
            'ends_on'          => $data['ends_on'],
            'pickup_point'     => $isItems ? $data['pickup_point'] : null,
            'target_amount'    => $isItems ? null : (float) $data['target_amount'],
        ];
    }

    /**
     * Turn the form's three parallel arrays into item lines, dropping blank
     * rows.
     *
     * The quantity decides whether a row counts, not the name: the form's
     * category cell is a `<select>` that always posts something, so the spare
     * rows it offers for adding items would otherwise each look like a filled
     * row with a missing quantity. Leaving the quantity at zero is how the form
     * tells a requester to skip a row, and this is the rule that honours it.
     *
     * @return array{0: array<int, array{item_name: string, unit: string, qty_required: int}>, 1: ?string}
     *         the lines, and an error message when the set is unusable
     */
    public static function normaliseLines(array $names, array $units, array $quantities): array
    {
        $lines = [];

        foreach ($names as $i => $name) {
            $name = trim((string) $name);
            $qty  = (int) ($quantities[$i] ?? 0);
            $unit = trim((string) ($units[$i] ?? ''));

            if ($qty <= 0) {
                continue;
            }
            // A quantity with nothing beside it is the one thing that cannot be
            // stored: `item_name` is NOT NULL and a donor would be asked to
            // reserve 300 of an unnamed thing.
            if ($name === '') {
                return [[], 'Name every item you have given a quantity for.'];
            }

            $lines[] = [
                'item_name'    => mb_substr($name, 0, RequestedItem::MAX_NAME),
                'unit'         => $unit !== '' ? mb_substr($unit, 0, 30) : 'units',
                'qty_required' => $qty,
            ];
        }

        if (!$lines) {
            return [[], 'Add at least one item you need.'];
        }
        if (count($lines) > self::MAX_ITEM_LINES) {
            return [[], 'A request can list at most ' . self::MAX_ITEM_LINES . ' items.'];
        }

        return [$lines, null];
    }
}
