<?php
namespace Models\Blood;

use Core\Database;
use Core\Model;

/**
 * An urgent need for blood, raised for a named patient.
 *
 * Not a `blood_campaigns` row: a camp is scheduled and has a venue, an urgent
 * request has a hospital and a deadline. That is why the create form offers the
 * two as a link to another screen rather than as a radio button.
 *
 * The table had no writer until 2026-08-18 - the form validated the input and
 * discarded it, and the public tab drew sample rows. It stores real rows now,
 * created `pending`, because the group rule is that nothing a user posts reaches
 * the public before an administrator has approved it.
 */
class CriticalRequest extends Model
{
    protected string $table = 'critical_blood_requests';
    protected array  $fillable = [
        'requester_id', 'patient_name', 'blood_group', 'units_needed', 'priority',
        'hospital', 'address', 'ward', 'city_id', 'needed_by', 'contact_phone',
        'notes', 'status',
    ];

    /** Labels for the status ENUM, worded as the screens show them. */
    public const STATUSES = [
        MOD_PENDING       => 'Pending Approval',
        EVENT_PUBLISHED   => 'Published',
        REQUEST_FULFILLED => 'Fulfilled',
        REQUEST_EXPIRED   => 'Expired',
        MOD_REJECTED      => 'Rejected',
    ];

    /** Pill colour per status. Keys match STATUSES; classes live in admin.css. */
    public const TONES = [
        MOD_PENDING       => 'pending',
        EVENT_PUBLISHED   => 'active',
        REQUEST_FULFILLED => 'neutral',
        REQUEST_EXPIRED   => 'neutral',
        MOD_REJECTED      => 'rejected',
    ];

    /** The statuses a donor is allowed to see on the public hub. */
    public const PUBLIC_STATUSES = [EVENT_PUBLISHED];

    /** Priority ENUM, worded as the create form and the cards show it. */
    public const PRIORITIES = [
        'urgent' => 'Urgent',
        'high'   => 'High Priority',
        'normal' => 'Normal',
    ];

    /** Card accent per priority. `.priority--*` classes live in blood.css. */
    private const TONES_BY_PRIORITY = ['urgent' => 'red', 'high' => 'amber', 'normal' => 'blue'];

    // ---------- public browse ----------

    /**
     * Approved, still-open requests for the donor hub, most urgent first.
     *
     * A request whose deadline has passed drops off - the section promises
     * donors something they can still act on, and a patient who needed blood
     * yesterday is not a call to action.
     */
    public function browse(int $limit = 12): array
    {
        $ph = implode(',', array_fill(0, count(self::PUBLIC_STATUSES), '?'));

        $rows = Database::all(
            "SELECT r.*, ct.name AS city, u.full_name AS requester_name
               FROM critical_blood_requests r
               JOIN cities ct ON ct.id = r.city_id
               JOIN users  u  ON u.id  = r.requester_id
              WHERE r.deleted_at IS NULL
                AND r.status IN ({$ph})
                AND r.needed_by >= NOW()
           ORDER BY FIELD(r.priority, 'urgent', 'high', 'normal'), r.needed_by ASC
              LIMIT " . (int) $limit,
            self::PUBLIC_STATUSES
        );

        return array_map([$this, 'present'], $rows);
    }

    /** One request, joined for display. Null when it does not exist. */
    public function findDetail(int $id): ?array
    {
        $row = Database::one(
            "SELECT r.*, ct.name AS city, u.full_name AS requester_name, u.role AS requester_role
               FROM critical_blood_requests r
               JOIN cities ct ON ct.id = r.city_id
               JOIN users  u  ON u.id  = r.requester_id
              WHERE r.id = ? AND r.deleted_at IS NULL
              LIMIT 1",
            [$id]
        );

        return $row ? $this->present($row) : null;
    }

    /** Everything this account has raised, newest first. */
    public function forRequester(int $requesterId): array
    {
        $rows = Database::all(
            "SELECT r.*, ct.name AS city, u.full_name AS requester_name
               FROM critical_blood_requests r
               JOIN cities ct ON ct.id = r.city_id
               JOIN users  u  ON u.id  = r.requester_id
              WHERE r.requester_id = ? AND r.deleted_at IS NULL
           ORDER BY r.created_at DESC",
            [$requesterId]
        );

        return array_map([$this, 'present'], $rows);
    }

    /** The administrator queue for one status. */
    public function queue(string $status): array
    {
        $rows = Database::all(
            "SELECT r.*, ct.name AS city,
                    u.full_name AS requester_name, u.email AS requester_email, u.role AS requester_role
               FROM critical_blood_requests r
               JOIN cities ct ON ct.id = r.city_id
               JOIN users  u  ON u.id  = r.requester_id
              WHERE r.status = ? AND r.deleted_at IS NULL
           ORDER BY FIELD(r.priority, 'urgent', 'high', 'normal'), r.created_at ASC",
            [$status]
        );

        return array_map([$this, 'present'], $rows);
    }

    /** Badge count for the admin sidebar. */
    public function pendingCount(): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM critical_blood_requests WHERE status = ? AND deleted_at IS NULL',
            [MOD_PENDING]
        );
    }

    // ---------- presentation ----------

    /**
     * Map a stored row onto the keys the hub cards and the detail screen read.
     *
     * Those screens were written against `BloodSampleData`, whose arrays were
     * already display-shaped - "3 h 42 m left" rather than a DATETIME. Keeping
     * the same keys means the templates did not have to be rewritten when the
     * sample rows gave way to real ones, and the formatting stays in one place
     * instead of being repeated in three views.
     */
    private function present(array $r): array
    {
        $due       = strtotime((string) $r['needed_by']);
        $priority  = (string) $r['priority'];
        $hospital  = (string) $r['hospital'];
        $address   = (string) ($r['address'] ?? '');

        return $r + [
            'group'         => $r['blood_group'],
            'units'         => (int) $r['units_needed'],
            // `critical_blood_requests` has no column counting units already
            // given, and `blood_donation_records` links to a campaign, not to a
            // request. Shown as 0 rather than invented.
            'fulfilled'     => 0,
            'hospital'      => $address !== '' ? $hospital . ', ' . $address : $hospital,
            'hospitalShort' => $hospital . ', ' . $r['city'],
            'ward'          => $r['ward'] ?: '-',
            'neededBy'      => date('j M Y, g:i A', $due),
            'remaining'     => self::countdown($due),
            'priority'      => self::PRIORITIES[$priority] ?? 'Normal',
            'tone'          => self::TONES_BY_PRIORITY[$priority] ?? 'blue',
            'requester'     => $r['requester_name'],
            'posted'        => time_ago($r['created_at']),
            'reason'        => str_limit((string) $r['notes'], 90),
            'note'          => (string) $r['notes'],
        ];
    }

    /** "3 h 42 m left", or "Deadline passed" once it has. */
    private static function countdown(int $due): string
    {
        $seconds = $due - time();
        if ($seconds <= 0) {
            return 'Deadline passed';
        }

        $days  = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $mins  = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return "{$days} d {$hours} h left";
        }
        return "{$hours} h {$mins} m left";
    }
}
