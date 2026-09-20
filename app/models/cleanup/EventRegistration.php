<?php
namespace Models\Cleanup;

use Core\Database;
use Core\Model;

/**
 * A volunteer's sign-up for one cleanup campaign.
 *
 * The lifecycle the organiser's attendance screen drives:
 *
 *   pending    - the volunteer signed up, the organiser has not decided yet.
 *   confirmed  - the organiser approved them. Only a confirmed volunteer can be
 *                checked in, which is why Approve and Check In are two buttons
 *                and not one.
 *   cancelled  - withdrawn. Counted out of "Registered" everywhere.
 *
 * `UNIQUE KEY uq_event_user (event_id, user_id)` is what stops a double
 * registration, so `alreadyJoined()` below is a courtesy check for the form, not
 * the guarantee - the database is.
 *
 * Check-in lives in its own table (`attendance`), one row per registration, so
 * every read here LEFT JOINs it. A volunteer with no attendance row has simply
 * not arrived.
 */
class EventRegistration extends Model
{
    protected string $table = 'event_registrations';
    protected array  $fillable = [
        'event_id', 'user_id', 'emergency_contact_name', 'emergency_contact_phone',
        'skills', 'status', 'work_area_id',
    ];

    /** `status` ENUM, worded as the organiser screen shows it. */
    public const STATUSES = [
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Every volunteer on one campaign, with their check-in and work area.
     *
     * The four filters are the chips across the top of the screen. `checked_in`
     * and `not_checked` are attendance states rather than registration states,
     * which is why they are not simply `status = ?`.
     */
    public function forEvent(int $eventId, array $filters = []): array
    {
        // Cancelled sign-ups are left out, because every count beside this list
        // leaves them out - `registered` on the campaign, the "All (n)" chip and
        // "Showing n of n" all come from countFor(). A row in the table that no
        // count admits to is how a correct list reads as a wrong one.
        $where  = "r.event_id = ? AND r.deleted_at IS NULL AND r.status <> 'cancelled'";
        $params = [$eventId];

        $state = $filters['state'] ?? '';
        if ($state === 'confirmed' || $state === 'pending') {
            $where   .= ' AND r.status = ?';
            $params[] = $state;
        } elseif ($state === 'checked_in') {
            $where .= ' AND a.checked_in_at IS NOT NULL';
        } elseif ($state === 'not_checked') {
            $where .= ' AND a.checked_in_at IS NULL';
        }

        if (!empty($filters['work_area'])) {
            $where   .= ' AND r.work_area_id = ?';
            $params[] = (int) $filters['work_area'];
        }

        if (!empty($filters['q'])) {
            $where   .= ' AND (u.full_name LIKE ? OR u.nic LIKE ? OR u.phone LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return Database::all(
            "SELECT r.id, r.status, r.skills, r.work_area_id, r.created_at,
                    u.id AS user_id, u.full_name, u.phone, u.nic,
                    ct.name AS city,
                    w.name AS work_area,
                    a.checked_in_at, a.checked_out_at
               FROM event_registrations r
               JOIN users u       ON u.id = r.user_id
          LEFT JOIN cities ct     ON ct.id = u.city_id
          LEFT JOIN work_areas w  ON w.id = r.work_area_id AND w.deleted_at IS NULL
          LEFT JOIN attendance a  ON a.registration_id = r.id
              WHERE {$where}
           ORDER BY r.created_at ASC",
            $params
        );
    }

    /** One registration with the campaign it belongs to, for ownership checks. */
    public function findWithEvent(int $id): ?array
    {
        return Database::one(
            'SELECT r.*, e.organization_id, e.title AS event_title
               FROM event_registrations r
               JOIN cleanup_events e ON e.id = r.event_id
              WHERE r.id = ? AND r.deleted_at IS NULL
              LIMIT 1',
            [$id]
        );
    }

    /** The volunteer's own row for a campaign, whatever state it is in. */
    public function forUserAndEvent(int $userId, int $eventId): ?array
    {
        return Database::one(
            'SELECT * FROM event_registrations
              WHERE user_id = ? AND event_id = ? AND deleted_at IS NULL
              LIMIT 1',
            [$userId, $eventId]
        );
    }

    /** True once the member has a live sign-up, so the form can say so first. */
    public function alreadyJoined(int $userId, int $eventId): bool
    {
        $row = $this->forUserAndEvent($userId, $eventId);
        return $row !== null && $row['status'] !== 'cancelled';
    }

    /** Places taken, counted the way cardColumns() counts them. */
    public function countFor(int $eventId): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM event_registrations
              WHERE event_id = ? AND deleted_at IS NULL AND status <> 'cancelled'",
            [$eventId]
        );
    }

    /**
     * The campaigns this member has signed up for, newest first.
     *
     * Used by My Activities and the community dashboard, so it carries enough
     * of the campaign to draw a card without a second query.
     */
    public function forUser(int $userId, int $limit = PER_PAGE): array
    {
        return Database::all(
            "SELECT r.id, r.status, r.created_at,
                    e.id AS event_id, e.title, e.event_date, e.start_time, e.end_time,
                    e.meeting_point, e.status AS event_status,
                    ct.name AS city,
                    o.org_name AS organiser,
                    a.checked_in_at
               FROM event_registrations r
               JOIN cleanup_events e   ON e.id = r.event_id
               JOIN cities ct          ON ct.id = e.city_id
               JOIN organizations o    ON o.id = e.organization_id
          LEFT JOIN attendance a       ON a.registration_id = r.id
              WHERE r.user_id = ? AND r.deleted_at IS NULL AND r.status <> 'cancelled'
           ORDER BY e.event_date DESC, r.id DESC
              LIMIT " . (int) $limit,
            [$userId]
        );
    }

    /**
     * Campaigns this member attended - a confirmed sign-up with a check-in.
     *
     * This is the honest answer to "how many cleanups have you joined": turning
     * up is what counts, not signing up.
     */
    public function attendedCount(int $userId): int
    {
        return (int) Database::value(
            "SELECT COUNT(*)
               FROM event_registrations r
               JOIN attendance a ON a.registration_id = r.id
              WHERE r.user_id = ? AND r.deleted_at IS NULL
                AND a.checked_in_at IS NOT NULL",
            [$userId]
        );
    }
}
