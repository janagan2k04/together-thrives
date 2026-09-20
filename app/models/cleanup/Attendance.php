<?php
namespace Models\Cleanup;

use Core\Database;
use Core\Model;

/**
 * Check-in and check-out for one registration.
 *
 * Child table: no `deleted_at` and no `updated_at`, so soft delete is off and
 * the inherited update() cannot be used - Model::update() always appends
 * `updated_at = NOW()`. Every write here goes through Database::run().
 *
 * `registration_id` is UNIQUE, so a volunteer has at most one attendance row per
 * campaign and checkIn() is written as an upsert rather than a blind insert - the
 * organiser can press Check In twice without a duplicate-key error.
 *
 * Nothing writes `checked_out_at` yet. That is deliberate: the screen the group
 * designed has a Check In button and no Check Out, so a volunteer-hours figure
 * would be a number nobody can count. It is left NULL rather than guessed.
 */
class Attendance extends Model
{
    protected string $table = 'attendance';
    protected array  $fillable = ['registration_id', 'checked_in_at', 'checked_out_at', 'marked_by'];
    protected bool   $softDelete = false;

    /** Mark one registration as arrived. Safe to call twice. */
    public function checkIn(int $registrationId, int $markedBy): void
    {
        Database::run(
            'INSERT INTO attendance (registration_id, checked_in_at, marked_by, created_at)
             VALUES (?, NOW(), ?, NOW())
             ON DUPLICATE KEY UPDATE checked_in_at = COALESCE(checked_in_at, NOW()),
                                     marked_by     = VALUES(marked_by)',
            [$registrationId, $markedBy]
        );
    }

    /** One registration's attendance row, or null when nobody has arrived. */
    public function forRegistration(int $registrationId): ?array
    {
        return Database::one(
            'SELECT * FROM attendance WHERE registration_id = ? LIMIT 1',
            [$registrationId]
        );
    }

    public function isCheckedIn(int $registrationId): bool
    {
        return (bool) Database::value(
            'SELECT COUNT(*) FROM attendance
              WHERE registration_id = ? AND checked_in_at IS NOT NULL',
            [$registrationId]
        );
    }

    /** How many volunteers have arrived at one campaign. */
    public function countForEvent(int $eventId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*)
               FROM attendance a
               JOIN event_registrations r ON r.id = a.registration_id
              WHERE r.event_id = ? AND a.checked_in_at IS NOT NULL',
            [$eventId]
        );
    }
}
