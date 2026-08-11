<?php
namespace Models\Reuse;

use Core\Database;
use Core\Model;

/**
 * Someone asking a donor for a listed item.
 *
 * `item_requests` has a UNIQUE key on (item_id, requester_id): one person may
 * ask for one item once. A withdrawn request still occupies that slot, which is
 * deliberate — it stops a requester from re-submitting to jump the queue.
 */
class ItemRequest extends Model
{
    protected string $table = 'item_requests';
    protected array  $fillable = [
        'item_id', 'requester_id', 'reason', 'is_disaster_affected',
        'gs_verification_id', 'financial_statement_path', 'status',
    ];

    /** Values allowed by the status ENUM in schema.sql. */
    public const STATUSES = [
        'pending'   => 'Pending',
        'selected'  => 'Selected',
        'rejected'  => 'Not selected',
        'withdrawn' => 'Withdrawn',
    ];

    /** This person's request for this item, whatever state it is in. */
    public function forRequester(int $itemId, int $requesterId): ?array
    {
        return Database::one(
            'SELECT * FROM item_requests
              WHERE item_id = ? AND requester_id = ? AND deleted_at IS NULL
              LIMIT 1',
            [$itemId, $requesterId]
        );
    }

    /**
     * The queue the donor reviews. Disaster-affected requests come first — the
     * fair-distribution rule the design states on the donate form.
     */
    public function forItem(int $itemId): array
    {
        return Database::all(
            "SELECT r.*, u.full_name AS requester_name, u.email AS requester_email,
                    u.phone AS requester_phone, ct.name AS requester_city,
                    g.status AS gs_status, g.letter_path AS gs_letter
               FROM item_requests r
               JOIN users u              ON u.id = r.requester_id
          LEFT JOIN cities ct            ON ct.id = u.city_id
          LEFT JOIN gs_verifications g   ON g.id = r.gs_verification_id
              WHERE r.item_id = ? AND r.deleted_at IS NULL
           ORDER BY r.is_disaster_affected DESC, r.created_at ASC",
            [$itemId]
        );
    }

    /** Everything this member has asked for, for their own activity list. */
    public function forRequesterList(int $requesterId): array
    {
        return Database::all(
            "SELECT r.*, i.title, i.status AS item_status, i.donor_id
               FROM item_requests r
               JOIN reuse_items i ON i.id = r.item_id
              WHERE r.requester_id = ? AND r.deleted_at IS NULL
           ORDER BY r.created_at DESC",
            [$requesterId]
        );
    }
}
