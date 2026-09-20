<?php
namespace Models\Cleanup;

use Core\Database;
use Core\Model;

/**
 * What one cleanup campaign collected, by waste type.
 *
 * The organiser adds a line per type from the campaign detail screen after the
 * day, which is why `disposal_status` starts at `pending` - the waste is
 * recorded first and disposed of later.
 *
 * `cleanup_impacts` holds the published summary of the same thing and has no
 * writer: it is one row per event with a `published_at`, which is a decision the
 * group has not taken. The panel totals these rows instead of printing a stored
 * summary, so the figure is always the sum of what is actually recorded.
 */
class WasteRecord extends Model
{
    protected string $table = 'waste_records';
    protected array  $fillable = ['event_id', 'waste_type', 'quantity_kg', 'disposal_status'];

    /** `disposal_status` ENUM, worded as the panel shows it. */
    public const DISPOSAL = [
        'pending'   => 'Awaiting disposal',
        'scheduled' => 'Disposal scheduled',
        'disposed'  => 'Disposed',
    ];

    public function forEvent(int $eventId): array
    {
        return Database::all(
            'SELECT * FROM waste_records
              WHERE event_id = ? AND deleted_at IS NULL
           ORDER BY id ASC',
            [$eventId]
        );
    }

    /** Total kilograms recorded against one campaign. */
    public function totalFor(int $eventId): float
    {
        return (float) Database::value(
            'SELECT COALESCE(SUM(quantity_kg), 0) FROM waste_records
              WHERE event_id = ? AND deleted_at IS NULL',
            [$eventId]
        );
    }
}
