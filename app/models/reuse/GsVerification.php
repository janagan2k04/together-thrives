<?php
namespace Models\Reuse;

use Core\Database;
use Core\Model;

/**
 * A Grama Niladhari letter proving someone is disaster-affected, attached to a
 * request so the donor can give it priority.
 *
 * The table has `deleted_at` but no `updated_at`, so soft delete works while
 * the inherited update() does not - Model::update() always writes
 * `updated_at = NOW()`. Status changes go through Database::run() below.
 */
class GsVerification extends Model
{
    protected string $table = 'gs_verifications';
    protected array  $fillable = [
        'user_id', 'letter_path', 'gn_division', 'status', 'valid_until', 'reviewed_by',
    ];

    /** The most recent letter this member uploaded, whatever its state. */
    public function latestFor(int $userId): ?array
    {
        return Database::one(
            'SELECT * FROM gs_verifications
              WHERE user_id = ? AND deleted_at IS NULL
           ORDER BY created_at DESC LIMIT 1',
            [$userId]
        );
    }

    /**
     * The administrator's review queue, newest claim last so the longest wait
     * is dealt with first.
     *
     * The joins to `item_requests` are LEFT: a letter is written in the same
     * transaction as the request that carries it, but the request can later be
     * soft deleted, and a letter with no live request still has to be reviewable
     * rather than vanish from the queue.
     */
    public function queue(string $status = MOD_PENDING): array
    {
        return Database::all(
            "SELECT g.*,
                    u.full_name, u.email, u.nic,
                    ct.name AS city,
                    r.id AS request_id, r.status AS request_status,
                    i.id AS item_id, i.title AS item_title,
                    d.full_name AS donor_name
               FROM gs_verifications g
               JOIN users u             ON u.id  = g.user_id
          LEFT JOIN cities ct           ON ct.id = u.city_id
          LEFT JOIN item_requests r     ON r.gs_verification_id = g.id
                                       AND r.deleted_at IS NULL
          LEFT JOIN reuse_items i       ON i.id  = r.item_id
          LEFT JOIN users d             ON d.id  = i.donor_id
              WHERE g.status = ? AND g.deleted_at IS NULL
           ORDER BY g.created_at ASC",
            [$status]
        );
    }

    /** Letters waiting on a decision - drives the badge in the admin sidebar. */
    public function pendingCount(): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM gs_verifications
              WHERE status = ? AND deleted_at IS NULL',
            [MOD_PENDING]
        );
    }

    /**
     * Reviewed by an administrator. See the note above on update() - this is a
     * raw statement because the table has no `updated_at` for Model::update()
     * to write to.
     */
    public function decide(int $id, string $status, int $reviewerId, ?string $validUntil = null): bool
    {
        return Database::run(
            'UPDATE gs_verifications
                SET status = ?, reviewed_by = ?, valid_until = ?
              WHERE id = ? AND deleted_at IS NULL',
            [$status, $reviewerId, $validUntil, $id]
        )->rowCount() > 0;
    }
}
