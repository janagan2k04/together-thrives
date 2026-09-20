<?php
namespace Models\Cleanup;

use Core\Database;
use Core\Model;

/**
 * A cleanup / volunteering campaign. Member 1's CRUD entity.
 *
 * `organization_id` points at `organizations(id)` - the profile row, not the
 * account - and is NOT NULL, so only a verified organisation can own a campaign.
 * That is why the community proposal screen validates but does not store: a
 * community member has no organisation row to hang a campaign on. The group
 * ruled on that rather than inventing an owner; see WEEK1-CHECKLIST.md.
 *
 * The lifecycle is the one the other three modules use, mapped onto this table's
 * narrower ENUM. `cleanup_events.status` has no `pending` and no `rejected`, and
 * there are no `reviewed_by` / `reviewed_at` columns:
 *
 *   draft      - created, waiting on an administrator. Invisible to the public.
 *   published  - approved. This is the only way a campaign becomes public.
 *   cancelled  - either the administrator rejected it (reason in `cancel_reason`,
 *                row still live) or the organiser withdrew it (soft deleted too).
 *                `deleted_at` is what tells the two apart.
 *
 * Editing an approved campaign returns it to `draft`, because the fields an
 * administrator checked - date, meeting point, volunteer limit, safety
 * guidelines - are exactly the ones an edit can change.
 */
class CleanupEvent extends Model
{
    protected string $table = 'cleanup_events';
    protected array  $fillable = [
        'organization_id', 'disaster_report_id', 'title', 'description', 'category',
        'event_date', 'start_time', 'end_time', 'meeting_point', 'city_id',
        'volunteer_limit', 'equipment_provided', 'safety_guidelines', 'cover_image',
        'status', 'cancel_reason',
    ];

    /** `category` ENUM, with the labels the Figma uses. */
    public const CATEGORIES = [
        'river'           => 'River Cleanup',
        'beach'           => 'Beach Cleanup',
        'urban'           => 'Urban Cleanup',
        'disaster_relief' => 'Disaster Relief',
        'dengue'          => 'Dengue Control',
        'other'           => 'Other',
    ];

    /** `status` ENUM, worded as the organiser screens show it. */
    public const STATUSES = [
        EVENT_DRAFT     => 'Pending Approval',
        EVENT_PUBLISHED => 'Upcoming',
        EVENT_ONGOING   => 'Ongoing',
        EVENT_COMPLETED => 'Completed',
        EVENT_CANCELLED => 'Cancelled',
    ];

    /** Badge tone per status, so views never hardcode a colour. */
    public const TONES = [
        EVENT_DRAFT     => 'amber',
        EVENT_PUBLISHED => 'blue',
        EVENT_ONGOING   => 'green',
        EVENT_COMPLETED => 'neutral',
        EVENT_CANCELLED => 'red',
    ];

    /**
     * The statuses a volunteer is allowed to see.
     *
     * `completed` is included because the browse card, the feedback form and the
     * certificate all hang off a finished campaign - a volunteer who attended
     * has to be able to open it again.
     */
    public const PUBLIC_STATUSES = [EVENT_PUBLISHED, EVENT_ONGOING, EVENT_COMPLETED];

    // ---------- public browse ----------

    /**
     * The public list. A campaign appears here only once an administrator has
     * approved it - the create form promises the organiser exactly that, so
     * `draft` rows stay private to the organisation that posted them.
     */
    public function browse(array $filters = [], int $limit = PER_PAGE, int $offset = 0): array
    {
        [$where, $params] = $this->browseWhere($filters);

        return Database::all(
            "SELECT {$this->cardColumns()}
               FROM cleanup_events e
               JOIN cities ct    ON ct.id = e.city_id
               JOIN districts ds ON ds.id = ct.district_id
               JOIN organizations o ON o.id = e.organization_id
          LEFT JOIN disaster_reports dr ON dr.id = e.disaster_report_id
              WHERE {$where}
           ORDER BY (e.event_date < CURDATE()) ASC, e.event_date ASC, e.start_time ASC
              LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    /** Total rows the same filters match, for paging. */
    public function browseCount(array $filters = []): int
    {
        [$where, $params] = $this->browseWhere($filters);

        return (int) Database::value(
            "SELECT COUNT(*)
               FROM cleanup_events e
               JOIN cities ct    ON ct.id = e.city_id
               JOIN districts ds ON ds.id = ct.district_id
               JOIN organizations o ON o.id = e.organization_id
              WHERE {$where}",
            $params
        );
    }

    // ---------- one campaign ----------

    /**
     * One campaign with everything the detail screens print.
     *
     * Cancelling soft deletes the row, so the organiser's own screens have to be
     * able to load a deleted one. Pass true and let the caller check ownership -
     * only it knows who is asking.
     */
    public function findDetail(int $id, bool $includeCancelled = false): ?array
    {
        $deletedClause = $includeCancelled ? '' : ' AND e.deleted_at IS NULL';

        return Database::one(
            "SELECT {$this->cardColumns()},
                    e.deleted_at, e.cancel_reason, e.disaster_report_id,
                    o.org_type, o.user_id AS organiser_user_id,
                    (o.verified_at IS NOT NULL) AS organiser_verified,
                    u.email AS organiser_email,
                    u.phone AS organiser_phone
               FROM cleanup_events e
               JOIN cities ct    ON ct.id = e.city_id
               JOIN districts ds ON ds.id = ct.district_id
               JOIN organizations o ON o.id = e.organization_id
               JOIN users u         ON u.id = o.user_id
          LEFT JOIN disaster_reports dr ON dr.id = e.disaster_report_id
              WHERE e.id = ?{$deletedClause}
              LIMIT 1",
            [$id]
        );
    }

    /** Raw row including cancelled ones, for ownership checks before a write. */
    public function findAny(int $id): ?array
    {
        return Database::one('SELECT * FROM cleanup_events WHERE id = ? LIMIT 1', [$id]);
    }

    // ---------- the organisation's own list ----------

    /**
     * Every campaign this organisation has posted, soonest first.
     *
     * Cancelled campaigns are included on purpose: the delete is soft, and the
     * organiser needs to still see what they cancelled or had rejected. The
     * public queries filter them out.
     */
    public function forOrganisation(int $organizationId, array $filters = []): array
    {
        $where  = 'e.organization_id = ?';
        $params = [$organizationId];

        if (!empty($filters['status'])) {
            $where   .= ' AND e.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $where   .= ' AND e.category = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['city'])) {
            $where   .= ' AND e.city_id = ?';
            $params[] = (int) $filters['city'];
        }

        return Database::all(
            "SELECT {$this->cardColumns()}, e.deleted_at, e.cancel_reason
               FROM cleanup_events e
               JOIN cities ct    ON ct.id = e.city_id
               JOIN districts ds ON ds.id = ct.district_id
               JOIN organizations o ON o.id = e.organization_id
          LEFT JOIN disaster_reports dr ON dr.id = e.disaster_report_id
              WHERE {$where}
           ORDER BY e.event_date DESC, e.id DESC",
            $params
        );
    }

    /** Counts per status for the organisation, including cancelled ones. */
    public function statusCountsFor(int $organizationId): array
    {
        $rows = Database::all(
            'SELECT status, COUNT(*) AS n
               FROM cleanup_events
              WHERE organization_id = ?
           GROUP BY status',
            [$organizationId]
        );

        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }
        return $counts;
    }

    // ---------- the volunteer's own campaigns ----------

    /**
     * The ids this member has registered for, for the Joined tab and the
     * "Already Joined" state on a card.
     *
     * Nothing writes `event_registrations` yet - joining is the volunteer flow,
     * not this CRUD entity - so today this honestly returns an empty list rather
     * than a made-up one.
     */
    public function joinedIds(int $userId): array
    {
        $rows = Database::all(
            "SELECT event_id FROM event_registrations
              WHERE user_id = ? AND status <> 'cancelled' AND deleted_at IS NULL",
            [$userId]
        );

        return array_map('intval', array_column($rows, 'event_id'));
    }

    // ---------- administrator review ----------

    /**
     * The admin queue. `draft` is this table's "waiting on a decision" - the
     * ENUM has no `pending`, and `Auth`'s constants already name it EVENT_DRAFT.
     *
     * Withdrawn campaigns never appear: the organiser soft deleted them, so
     * there is nothing left for an administrator to decide.
     */
    public function queue(string $status): array
    {
        return Database::all(
            "SELECT {$this->cardColumns()}, e.cancel_reason, e.safety_guidelines,
                    u.email AS organiser_email
               FROM cleanup_events e
               JOIN cities ct    ON ct.id = e.city_id
               JOIN districts ds ON ds.id = ct.district_id
               JOIN organizations o ON o.id = e.organization_id
               JOIN users u         ON u.id = o.user_id
          LEFT JOIN disaster_reports dr ON dr.id = e.disaster_report_id
              WHERE e.status = ? AND e.deleted_at IS NULL
           ORDER BY e.created_at ASC",
            [$status]
        );
    }

    /** Badge count for the admin sidebar. */
    public function pendingCount(): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM cleanup_events WHERE status = ? AND deleted_at IS NULL',
            [EVENT_DRAFT]
        );
    }

    // ---------- labels ----------

    public static function categoryLabel(?string $key): string
    {
        return self::CATEGORIES[$key] ?? (string) $key;
    }

    public static function statusLabel(?string $key): string
    {
        return self::STATUSES[$key] ?? (string) $key;
    }

    public static function statusTone(?string $key): string
    {
        return self::TONES[$key] ?? 'neutral';
    }

    /** Places taken against the limit, as a percentage. Never above 100. */
    public static function fillPercent(array $event): int
    {
        $limit = (int) ($event['volunteer_limit'] ?? 0);
        if ($limit < 1) {
            return 0;
        }
        return min(100, (int) round((int) ($event['registered'] ?? 0) / $limit * 100));
    }

    // ---------- helpers ----------

    /**
     * The columns every card and detail screen shares.
     *
     * Every figure here is counted from real rows. The Figma also draws a
     * "Viewed" counter, a minimum age, a visibility setting and an exact
     * location; none of them has a column, so they are collected on the create
     * form with a hint and left off the read screens entirely rather than
     * printed as a blank or an invented number.
     *
     * Requires the cities / districts / organizations joins, and the LEFT JOIN
     * onto disaster_reports.
     */
    private function cardColumns(): string
    {
        return "e.id, e.organization_id, e.title, e.description, e.category,
                e.event_date, e.start_time, e.end_time, e.meeting_point,
                e.city_id, e.volunteer_limit, e.equipment_provided,
                e.safety_guidelines, e.status, e.created_at,
                ct.name AS city,
                ds.name AS district,
                o.org_name AS organiser,
                dr.title  AS disaster_report,
                ROUND(TIME_TO_SEC(TIMEDIFF(e.end_time, e.start_time)) / 3600, 1) AS duration_hours,
                (SELECT COUNT(*) FROM event_registrations r
                  WHERE r.event_id = e.id AND r.deleted_at IS NULL
                    AND r.status <> 'cancelled') AS registered,
                (SELECT COUNT(*) FROM event_registrations r
                  WHERE r.event_id = e.id AND r.deleted_at IS NULL
                    AND r.status = 'pending') AS pending,
                (SELECT COUNT(*) FROM event_registrations r
                  JOIN attendance a ON a.registration_id = r.id
                 WHERE r.event_id = e.id AND a.checked_in_at IS NOT NULL) AS checked_in,
                (SELECT COUNT(*) FROM event_feedback f
                  WHERE f.event_id = e.id AND f.deleted_at IS NULL) AS feedback";
    }

    /**
     * Shared WHERE for browse() and browseCount(), so paging cannot drift.
     */
    private function browseWhere(array $filters): array
    {
        $ph     = implode(',', array_fill(0, count(self::PUBLIC_STATUSES), '?'));
        $sql    = "e.deleted_at IS NULL AND e.status IN ({$ph})";
        $params = self::PUBLIC_STATUSES;

        if (!empty($filters['city'])) {
            $sql     .= ' AND e.city_id = ?';
            $params[] = (int) $filters['city'];
        }
        if (!empty($filters['category'])) {
            $sql     .= ' AND e.category = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['status'])) {
            $sql     .= ' AND e.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['ids'])) {
            $ids  = array_map('intval', $filters['ids']);
            $sql .= ' AND e.id IN (' . implode(',', $ids) . ')';
        }
        if (!empty($filters['q'])) {
            $sql     .= ' AND (e.title LIKE ? OR e.meeting_point LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        return [$sql, $params];
    }
}
