<?php
namespace Models\Reuse;

use Core\Database;
use Core\Model;

/**
 * A recipient's rating of a donation. Shown on the public donor profile.
 * The table has `deleted_at` but no `updated_at`, so the inherited update()
 * cannot be used — a review is written once and not edited.
 */
class ReuseFeedback extends Model
{
    protected string $table = 'reuse_feedback';
    protected array  $fillable = ['item_id', 'recipient_id', 'rating', 'comment'];

    /** Reviews of everything this donor has given away, newest first. */
    public function forDonor(int $donorId, int $limit = 10): array
    {
        return Database::all(
            "SELECT f.rating, f.comment, f.created_at,
                    u.full_name AS recipient_name, i.title AS item_title
               FROM reuse_feedback f
               JOIN reuse_items i ON i.id = f.item_id
               JOIN users u       ON u.id = f.recipient_id
              WHERE i.donor_id = ? AND f.deleted_at IS NULL
           ORDER BY f.created_at DESC
              LIMIT " . (int) $limit,
            [$donorId]
        );
    }

    /** Average rating and review count, or nulls when nobody has rated yet. */
    public function summaryFor(int $donorId): array
    {
        $row = Database::one(
            "SELECT ROUND(AVG(f.rating), 1) AS average, COUNT(*) AS reviews
               FROM reuse_feedback f
               JOIN reuse_items i ON i.id = f.item_id
              WHERE i.donor_id = ? AND f.deleted_at IS NULL",
            [$donorId]
        ) ?: [];

        return [
            'average' => $row['average'] !== null ? (float) $row['average'] : null,
            'reviews' => (int) ($row['reviews'] ?? 0),
        ];
    }
}
