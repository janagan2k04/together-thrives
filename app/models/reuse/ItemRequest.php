<?php
namespace Models\Reuse;

use Core\Database;
use Core\Model;

/**
 * Someone asking a donor for a listed item.
 *
 * `item_requests` has a UNIQUE key on (item_id, requester_id): one person may
 * ask for one item once. A withdrawn request still occupies that slot, which is
 * deliberate - it stops a requester from re-submitting to jump the queue.
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
     * The queue the donor reviews. Disaster-affected requests come first - the
     * fair-distribution rule the design states on the donate form.
     *
     * Within that, a claim an administrator has actually verified outranks one
     * that is merely asserted. Before GS letters were reviewed the two were
     * indistinguishable, which made the priority worth no more than the tick
     * box that set it.
     */
    public function forItem(int $itemId): array
    {
        return Database::all(
            "SELECT r.*, u.full_name AS requester_name, u.email AS requester_email,
                    u.phone AS requester_phone, ct.name AS requester_city,
                    g.status AS gs_status, g.letter_path AS gs_letter,
                    g.valid_until AS gs_valid_until
               FROM item_requests r
               JOIN users u              ON u.id = r.requester_id
          LEFT JOIN cities ct            ON ct.id = u.city_id
          LEFT JOIN gs_verifications g   ON g.id = r.gs_verification_id
              WHERE r.item_id = ? AND r.deleted_at IS NULL
           ORDER BY (r.is_disaster_affected = 1 AND g.status = ?) DESC,
                    r.is_disaster_affected DESC,
                    r.created_at ASC",
            [$itemId, MOD_APPROVED]
        );
    }

    /**
     * Everything this member has asked for, for their own activity list.
     *
     * The LEFT JOIN on `reuse_feedback` is what lets the screen tell "you have
     * been given this, rate it" apart from "you have already rated it" without
     * a second query per row.
     */
    public function forRequesterList(int $requesterId): array
    {
        return Database::all(
            "SELECT r.*,
                    i.title, i.status AS item_status, i.donor_id,
                    i.deleted_at AS item_deleted_at,
                    u.full_name AS donor_name,
                    c.name  AS category,
                    ct.name AS city,
                    (SELECT im.id FROM item_images im
                      WHERE im.item_id = i.id
                   ORDER BY im.is_primary DESC, im.id ASC LIMIT 1) AS image_id,
                    f.id     AS feedback_id,
                    f.rating AS feedback_rating
               FROM item_requests r
               JOIN reuse_items i     ON i.id  = r.item_id
               JOIN users u           ON u.id  = i.donor_id
               JOIN item_categories c ON c.id  = i.category_id
               JOIN cities ct         ON ct.id = i.city_id
          LEFT JOIN reuse_feedback f   ON f.item_id      = i.id
                                      AND f.recipient_id = r.requester_id
                                      AND f.deleted_at IS NULL
              WHERE r.requester_id = ? AND r.deleted_at IS NULL
           ORDER BY r.created_at DESC",
            [$requesterId]
        );
    }

    /** How many of this member's requests are still waiting on a donor. */
    public function pendingCountFor(int $requesterId): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM item_requests
              WHERE requester_id = ? AND status = 'pending' AND deleted_at IS NULL",
            [$requesterId]
        );
    }
}
