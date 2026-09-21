<?php
namespace Models\Donation;

use Core\Database;
use Core\Model;

/**
 * A donor promising a quantity of one requested item.
 *
 * `reserved` means promised, `delivered` means it arrived, `confirmed` means the
 * requester checked it in. Only the requester moves a row past `reserved` - a
 * donor cannot confirm their own delivery.
 *
 * The quantity lives in two places on purpose: on this row, and summed into
 * `requested_items.qty_reserved` so the progress bar on the request does not
 * need a join to draw. Those two must never disagree, which is why every write
 * here moves both inside one transaction.
 *
 * `requested_items` has a `deleted_at` but no `created_at` or `updated_at`, so
 * `Model::update()` fails on it - its writes go through Database::run() below.
 */
class ItemReservation extends Model
{
    protected string $table = 'item_reservations';
    protected array  $fillable = [
        'requested_item_id', 'donor_id', 'quantity', 'status', 'confirmed_by', 'confirmed_at',
    ];

    /** `status` ENUM, worded as the screens show it. */
    public const STATUSES = [
        'reserved'  => 'Reserved',
        'delivered' => 'Delivered',
        'confirmed' => 'Received',
        'cancelled' => 'Cancelled',
    ];

    public const TONES = [
        'reserved'  => 'amber',
        'delivered' => 'blue',
        'confirmed' => 'green',
        'cancelled' => 'red',
    ];

    /**
     * Reserve a quantity, and move `qty_reserved` with it.
     *
     * The UPDATE carries its own guard: `qty_reserved + ? <= qty_required`
     * means two donors reserving the last of something at the same moment
     * cannot both succeed. The check happens in the database, where it is
     * atomic, rather than in PHP where it is a race.
     *
     * Returns the reservation id, or 0 when there was not enough left.
     */
    public function reserve(int $requestedItemId, int $donorId, int $quantity): int
    {
        return Database::transaction(function () use ($requestedItemId, $donorId, $quantity) {
            $moved = Database::run(
                'UPDATE requested_items
                    SET qty_reserved = qty_reserved + ?
                  WHERE id = ? AND deleted_at IS NULL
                    AND qty_reserved + ? <= qty_required',
                [$quantity, $requestedItemId, $quantity]
            )->rowCount();

            if ($moved === 0) {
                return 0;
            }

            return $this->create([
                'requested_item_id' => $requestedItemId,
                'donor_id'          => $donorId,
                'quantity'          => $quantity,
                'status'            => 'reserved',
            ]);
        });
    }

    /**
     * The donor takes a promise back, and `qty_reserved` comes back with it.
     *
     * Only a row still at `reserved` can be withdrawn. Once the requester has
     * confirmed it the goods are counted in `qty_received`, and rolling the
     * reservation back would leave the request claiming items that arrived
     * against a promise nobody made.
     *
     * The guard is on the UPDATE, not in PHP: the requester pressing Confirm
     * received at the same moment the donor presses Undo is a race, and the two
     * outcomes are not the same. Whichever statement runs first wins, and the
     * other changes nothing.
     *
     * Returns true when the reservation was actually withdrawn.
     */
    public function cancel(int $id, int $donorId): bool
    {
        return (bool) Database::transaction(function () use ($id, $donorId) {
            $moved = Database::run(
                "UPDATE item_reservations
                    SET status = 'cancelled', updated_at = NOW()
                  WHERE id = ? AND donor_id = ? AND status = 'reserved' AND deleted_at IS NULL",
                [$id, $donorId]
            )->rowCount();

            if ($moved === 0) {
                return false;
            }

            $row = Database::one(
                'SELECT requested_item_id, quantity FROM item_reservations WHERE id = ? LIMIT 1',
                [$id]
            );

            // GREATEST(), so a row that has somehow been decremented already
            // cannot drive an UNSIGNED column below zero and abort the write.
            Database::run(
                'UPDATE requested_items
                    SET qty_reserved = GREATEST(0, CAST(qty_reserved AS SIGNED) - ?)
                  WHERE id = ?',
                [(int) $row['quantity'], (int) $row['requested_item_id']]
            );

            return true;
        });
    }

    /** This donor's own live promises against one request, for the Undo list. */
    public function forDonorAndRequest(int $donorId, int $requestId): array
    {
        return Database::all(
            "SELECT v.id, v.quantity, v.status, v.created_at, i.item_name, i.unit
               FROM item_reservations v
               JOIN requested_items i ON i.id = v.requested_item_id
              WHERE v.donor_id = ? AND i.request_id = ?
                AND v.deleted_at IS NULL AND v.status <> 'cancelled'
           ORDER BY v.created_at DESC",
            [$donorId, $requestId]
        );
    }

    /**
     * The requester confirms a delivery arrived.
     *
     * `qty_received` moves here and `qty_reserved` stays where it is: the goods
     * were promised and then delivered, and rolling the reservation back would
     * free the same quantity to be promised again.
     */
    public function confirm(int $id, int $requesterId): void
    {
        Database::transaction(function () use ($id, $requesterId) {
            $row = Database::one(
                'SELECT * FROM item_reservations WHERE id = ? AND deleted_at IS NULL LIMIT 1',
                [$id]
            );

            if (!$row || $row['status'] === 'confirmed') {
                return;
            }

            Database::run(
                'UPDATE requested_items SET qty_received = qty_received + ? WHERE id = ?',
                [(int) $row['quantity'], (int) $row['requested_item_id']]
            );

            $this->update($id, [
                'status'       => 'confirmed',
                'confirmed_by' => $requesterId,
                'confirmed_at' => date('Y-m-d H:i:s'),
            ]);
        });
    }

    /** One reservation with the request it belongs to, for ownership checks. */
    public function findWithRequest(int $id): ?array
    {
        return Database::one(
            'SELECT v.*, i.request_id, i.item_name, i.unit, r.requester_id, r.title
               FROM item_reservations v
               JOIN requested_items i   ON i.id = v.requested_item_id
               JOIN donation_requests r ON r.id = i.request_id
              WHERE v.id = ? AND v.deleted_at IS NULL
              LIMIT 1',
            [$id]
        );
    }

    /** Everyone who has promised something against one request. */
    public function forRequest(int $requestId): array
    {
        return Database::all(
            "SELECT v.id, v.quantity, v.status, v.created_at,
                    i.item_name, i.unit,
                    u.id AS donor_id, u.full_name AS donor
               FROM item_reservations v
               JOIN requested_items i ON i.id = v.requested_item_id
               JOIN users u           ON u.id = v.donor_id
              WHERE i.request_id = ? AND v.deleted_at IS NULL AND v.status <> 'cancelled'
           ORDER BY v.created_at DESC",
            [$requestId]
        );
    }

    /** Everything this donor has promised, newest first. */
    public function forDonor(int $donorId): array
    {
        return Database::all(
            "SELECT v.id, v.quantity, v.status, v.created_at,
                    i.item_name, i.unit,
                    r.id AS request_id, r.title, r.request_type, r.affected_area,
                    r.status AS request_status,
                    u.full_name AS requester
               FROM item_reservations v
               JOIN requested_items i   ON i.id = v.requested_item_id
               JOIN donation_requests r ON r.id = i.request_id
               JOIN users u             ON u.id = r.requester_id
              WHERE v.donor_id = ? AND v.deleted_at IS NULL AND v.status <> 'cancelled'
           ORDER BY v.created_at DESC",
            [$donorId]
        );
    }

    /**
     * Distinct requests this donor has supported - by giving items, by giving
     * money, or both.
     *
     * The fund half is not an afterthought: this counts what the tile beside it
     * calls "Requests Supported", and a donor who has only ever paid into a fund
     * has supported every one of those requests. Counting reservations alone
     * showed them a total of 0 next to a Funds Donated figure that was not.
     *
     * UNION, not UNION ALL: a request given both items and money is one request
     * supported, not two.
     */
    public function requestCountFor(int $donorId): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM (
                 SELECT i.request_id
                   FROM item_reservations v
                   JOIN requested_items i ON i.id = v.requested_item_id
                  WHERE v.donor_id = ? AND v.deleted_at IS NULL AND v.status <> 'cancelled'
                 UNION
                 SELECT f.request_id
                   FROM fund_donations f
                  WHERE f.donor_id = ? AND f.deleted_at IS NULL AND f.status = 'paid'
             ) AS supported",
            [$donorId, $donorId]
        );
    }

    /** Total units of anything this donor has promised. */
    public function itemCountFor(int $donorId): int
    {
        return (int) Database::value(
            "SELECT COALESCE(SUM(quantity), 0) FROM item_reservations
              WHERE donor_id = ? AND deleted_at IS NULL AND status <> 'cancelled'",
            [$donorId]
        );
    }
}
