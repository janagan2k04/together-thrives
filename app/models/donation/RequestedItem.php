<?php
namespace Models\Donation;

use Core\Database;
use Core\Model;

/**
 * One line on an item donation request - "300 packs of Food & Beverages".
 *
 * A child of `donation_requests`, written on the same form as its parent, so it
 * is part of Member 2's CRUD entity rather than a thing of its own.
 *
 * `requested_items` HAS a `deleted_at` column (unlike most child tables in this
 * schema), so soft delete stays on: a line that donors have already reserved
 * against must not vanish from their reservation history when the requester
 * edits the list.
 */
class RequestedItem extends Model
{
    protected string $table = 'requested_items';
    protected array  $fillable = [
        'request_id', 'item_name', 'unit', 'qty_required', 'qty_reserved', 'qty_received',
    ];

    /**
     * The longest item name the form accepts.
     *
     * `item_name` is VARCHAR(120) and the requester types it. There is no fixed
     * category list any more: the eight the create form used to offer were a
     * guess at what an appeal needs, and an appeal for school shoes or a water
     * pump had to be filed under the nearest wrong one. The column was always
     * free text; the form is now too.
     */
    public const MAX_NAME = 120;

    /** Lines on one request, in the order they were added. */
    public function forRequest(int $requestId): array
    {
        return Database::all(
            'SELECT id, request_id, item_name, unit, qty_required, qty_reserved, qty_received
               FROM requested_items
              WHERE request_id = ? AND deleted_at IS NULL
           ORDER BY id ASC',
            [$requestId]
        );
    }

    /**
     * Lines for several requests at once, grouped by request id.
     *
     * The browse grid draws item chips on every card, and asking per card would
     * be one query per row.
     *
     * @param int[] $requestIds
     * @return array<int, array<int, array>>
     */
    public function forRequests(array $requestIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $requestIds)));
        if (!$ids) {
            return [];
        }

        $rows = Database::all(
            'SELECT request_id, item_name, unit, qty_required, qty_reserved, qty_received
               FROM requested_items
              WHERE request_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')
                AND deleted_at IS NULL
           ORDER BY id ASC',
            $ids
        );

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['request_id']][] = $row;
        }
        return $grouped;
    }

    /** The distinct category names on a request, for the manage list's chips. */
    public function categoriesFor(int $requestId): array
    {
        return array_column(
            Database::all(
                'SELECT DISTINCT item_name
                   FROM requested_items
                  WHERE request_id = ? AND deleted_at IS NULL
               ORDER BY item_name ASC',
                [$requestId]
            ),
            'item_name'
        );
    }

    /**
     * Replace the whole list for a request.
     *
     * The form posts the lines as one block, so the simplest correct update is
     * to soft delete the current set and insert the new one. Lines that donors
     * have reserved against keep their row - soft delete leaves the reservation
     * foreign key intact.
     *
     * The insert is written out rather than going through `Model::create()`,
     * because that always writes a `created_at` and this table has no such
     * column: the frozen schema gives `requested_items` only `deleted_at`.
     * Same reason `update()` is not used anywhere on this model.
     */
    public function replaceFor(int $requestId, array $lines): void
    {
        Database::run(
            'UPDATE requested_items SET deleted_at = NOW()
              WHERE request_id = ? AND deleted_at IS NULL',
            [$requestId]
        );

        foreach ($lines as $line) {
            Database::run(
                'INSERT INTO requested_items (request_id, item_name, unit, qty_required)
                 VALUES (?, ?, ?, ?)',
                [$requestId, $line['item_name'], $line['unit'], $line['qty_required']]
            );
        }
    }
}
