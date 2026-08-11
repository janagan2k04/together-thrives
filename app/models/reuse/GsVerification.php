<?php
namespace Models\Reuse;

use Core\Database;
use Core\Model;

/**
 * A Grama Niladhari letter proving someone is disaster-affected, attached to a
 * request so the donor can give it priority.
 *
 * The table has `deleted_at` but no `updated_at`, so soft delete works while
 * the inherited update() does not — Model::update() always writes
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

    /** Reviewed by an administrator. See the note above on update(). */
    public function decide(int $id, string $status, int $reviewerId): bool
    {
        return Database::run(
            'UPDATE gs_verifications SET status = ?, reviewed_by = ? WHERE id = ?',
            [$status, $reviewerId, $id]
        )->rowCount() > 0;
    }
}
