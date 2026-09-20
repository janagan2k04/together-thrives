<?php
namespace Models\Cleanup;

use Core\Database;
use Core\Model;

/**
 * A volunteer's rating of one campaign they attended.
 *
 * `UNIQUE KEY uq_feedback (event_id, user_id)` means one rating per volunteer
 * per campaign, so the controller checks `forUserAndEvent()` before inserting
 * and offers an edit rather than a second row.
 *
 * The table stores exactly two scores - an overall `rating` and a
 * `safety_rating` - which is why the form asks for two and not the five the
 * Figma drew. `is_anonymous` hides the name from the organiser, not the row.
 */
class EventFeedback extends Model
{
    protected string $table = 'event_feedback';
    protected array  $fillable = [
        'event_id', 'user_id', 'rating', 'safety_rating', 'comment', 'is_anonymous',
    ];

    /**
     * Replace an existing rating.
     *
     * The table has `deleted_at` but no `updated_at`, so the inherited update()
     * cannot be used - Model::update() always appends `updated_at = NOW()` and
     * would fail with Unknown column. Written out here instead.
     */
    public function updateRating(int $id, array $data): void
    {
        Database::run(
            'UPDATE event_feedback
                SET rating = :rating, safety_rating = :safety_rating,
                    comment = :comment, is_anonymous = :is_anonymous
              WHERE id = :id',
            [
                'rating'        => $data['rating'],
                'safety_rating' => $data['safety_rating'],
                'comment'       => $data['comment'],
                'is_anonymous'  => $data['is_anonymous'],
                'id'            => $id,
            ]
        );
    }

    public function forUserAndEvent(int $userId, int $eventId): ?array
    {
        return Database::one(
            'SELECT * FROM event_feedback
              WHERE user_id = ? AND event_id = ? AND deleted_at IS NULL
              LIMIT 1',
            [$userId, $eventId]
        );
    }

    /**
     * Every rating of one campaign, for the organiser.
     *
     * An anonymous row keeps its scores and loses its name - that is the whole
     * promise the checkbox makes, so it is kept here rather than left to the
     * view to remember.
     */
    public function forEvent(int $eventId): array
    {
        return Database::all(
            "SELECT f.rating, f.safety_rating, f.comment, f.created_at,
                    CASE WHEN f.is_anonymous = 1 THEN NULL ELSE u.full_name END AS volunteer
               FROM event_feedback f
               JOIN users u ON u.id = f.user_id
              WHERE f.event_id = ? AND f.deleted_at IS NULL
           ORDER BY f.created_at DESC",
            [$eventId]
        );
    }

    /** Average overall rating, or null when nobody has rated it yet. */
    public function averageFor(int $eventId): ?float
    {
        $avg = Database::value(
            'SELECT AVG(rating) FROM event_feedback
              WHERE event_id = ? AND deleted_at IS NULL',
            [$eventId]
        );

        return $avg === null ? null : round((float) $avg, 1);
    }
}
