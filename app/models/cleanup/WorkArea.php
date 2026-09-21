<?php
namespace Models\Cleanup;

use Core\Database;
use Core\Model;

/**
 * A zone within one cleanup campaign - "Zone A - Riverbank" and the like.
 *
 * The organiser creates zones on the attendance screen and assigns confirmed
 * volunteers to them. The table has `deleted_at` but no `updated_at`, so the
 * inherited update() cannot be used here either; nothing needs it.
 *
 * `team_leader_id` is nullable and stays NULL until the organiser names one,
 * which is why the volunteer table prints "-" rather than inventing a leader.
 */
class WorkArea extends Model
{
    protected string $table = 'work_areas';
    protected array  $fillable = ['event_id', 'name', 'team_leader_id'];

    /** Every zone on one campaign, oldest first - the order they were created. */
    public function forEvent(int $eventId): array
    {
        return Database::all(
            'SELECT w.*, u.full_name AS leader
               FROM work_areas w
          LEFT JOIN users u ON u.id = w.team_leader_id
              WHERE w.event_id = ? AND w.deleted_at IS NULL
           ORDER BY w.id ASC',
            [$eventId]
        );
    }

    /** True when the zone belongs to that campaign - never trust the form's id. */
    public function belongsToEvent(int $id, int $eventId): bool
    {
        return (bool) Database::value(
            'SELECT COUNT(*) FROM work_areas
              WHERE id = ? AND event_id = ? AND deleted_at IS NULL',
            [$id, $eventId]
        );
    }
}
