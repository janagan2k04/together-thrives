<?php
namespace Models\Blood;

use Core\Database;
use Core\Model;

/**
 * A scheduled blood donation camp. Member 3's CRUD entity.
 *
 * `organizer_id` points at `users(id)`, not at `organizations(id)` - a camp can
 * be posted by an organization or by a blood bank, and those are two different
 * profile tables hanging off the same account. `organizer_type` records which,
 * and is set from the signed-in role rather than from the form.
 *
 * Urgent needs are NOT rows here: they live in `critical_blood_requests`, which
 * is why the create form offers them as a link to another screen instead of a
 * radio button.
 */
class BloodCampaign extends Model
{
    protected string $table = 'blood_campaigns';
    protected array  $fillable = [
        'organizer_id', 'organizer_type', 'title', 'description', 'venue', 'address',
        'city_id', 'campaign_date', 'campaign_end_date', 'start_time', 'end_time', 'target_donors',
        'contact_phone', 'cover_image', 'auto_group_chat', 'status',
        // Set by the admin review screen only; never present in organiser input,
        // which is limited to the keys the validator returns.
        'reviewed_by', 'reviewed_at',
    ];

    /** Labels for the status ENUM, worded as the organiser screens show them. */
    /**
     * The eight blood groups, in the order every form on the platform lists
     * them. Matches the `users.blood_group` ENUM, so a value picked here is
     * always storable.
     */
    public const GROUPS = ['O-', 'O+', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

    public const STATUSES = [
        MOD_PENDING     => 'Pending Approval',
        EVENT_PUBLISHED => 'Published',
        EVENT_ONGOING   => 'Ongoing',
        EVENT_COMPLETED => 'Completed',
        MOD_REJECTED    => 'Rejected',
        EVENT_CANCELLED => 'Cancelled',
    ];

    /** Pill colour per status. Keys match STATUSES; classes live in admin.css. */
    public const TONES = [
        MOD_PENDING     => 'pending',
        EVENT_PUBLISHED => 'active',
        EVENT_ONGOING   => 'active',
        EVENT_COMPLETED => 'neutral',
        MOD_REJECTED    => 'rejected',
        EVENT_CANCELLED => 'rejected',
    ];

    /** The statuses a donor is allowed to see on the public hub. */
    public const PUBLIC_STATUSES = [EVENT_PUBLISHED, EVENT_ONGOING];

    // ---------- public browse ----------

    /**
     * Upcoming camps for the community hub, soonest first.
     *
     * A camp appears here only once an administrator has approved it - the
     * create form promises donors exactly that, so `pending` rows stay private
     * to their organiser.
     */
    public function browse(array $filters = [], int $limit = PER_PAGE, int $offset = 0): array
    {
        [$where, $params] = $this->browseWhere($filters);

        return Database::all(
            "SELECT c.id, c.title, c.venue, c.address, c.campaign_date, c.campaign_end_date,
                    c.start_time, c.end_time, c.target_donors, c.status,
                    ct.name AS city,
                    {$this->organiserNameSql()} AS organiser,
                    {$this->registeredSql()} AS registered
               FROM blood_campaigns c
               JOIN cities ct ON ct.id = c.city_id
               JOIN users  u  ON u.id  = c.organizer_id
          LEFT JOIN organizations o ON o.user_id = u.id
          LEFT JOIN blood_banks   b ON b.user_id = u.id
              WHERE {$where}
           ORDER BY c.campaign_date ASC, c.start_time ASC
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
               FROM blood_campaigns c
               JOIN cities ct ON ct.id = c.city_id
               JOIN users  u  ON u.id  = c.organizer_id
          LEFT JOIN organizations o ON o.user_id = u.id
          LEFT JOIN blood_banks   b ON b.user_id = u.id
              WHERE {$where}",
            $params
        );
    }

    // ---------- one campaign ----------

    /**
     * One camp with everything the public detail page prints.
     *
     * Cancelling soft deletes the row, so the organiser's own screens have to be
     * able to load a deleted one. Pass true and let the caller check ownership -
     * only it knows who is asking.
     */
    public function findDetail(int $id, bool $includeCancelled = false): ?array
    {
        $deletedClause = $includeCancelled ? '' : ' AND c.deleted_at IS NULL';

        return Database::one(
            "SELECT c.*,
                    ct.name AS city,
                    d.name  AS district,
                    u.full_name AS organiser_full_name,
                    u.email     AS organiser_email,
                    u.role      AS organiser_role,
                    {$this->organiserNameSql()} AS organiser,
                    {$this->registeredSql()} AS registered,
                    {$this->resultCountSql(BLOOD_ELIGIBLE)}   AS eligible,
                    {$this->resultCountSql(BLOOD_INELIGIBLE)} AS ineligible
               FROM blood_campaigns c
               JOIN cities ct ON ct.id = c.city_id
               JOIN districts d ON d.id = ct.district_id
               JOIN users  u  ON u.id  = c.organizer_id
          LEFT JOIN organizations o ON o.user_id = u.id
          LEFT JOIN blood_banks   b ON b.user_id = u.id
              WHERE c.id = ?{$deletedClause}
              LIMIT 1",
            [$id]
        );
    }

    /** Raw row including cancelled ones, for ownership checks before a write. */
    public function findAny(int $id): ?array
    {
        return Database::one('SELECT * FROM blood_campaigns WHERE id = ? LIMIT 1', [$id]);
    }

    // ---------- the organiser's own list ----------

    /**
     * Every campaign this organiser has posted, newest first.
     *
     * Cancelled campaigns are included on purpose: the delete is soft, and the
     * organiser needs to still see what they cancelled. Public views filter them.
     */
    public function forOrganiser(int $organizerId, array $filters = []): array
    {
        $where  = 'c.organizer_id = ?';
        $params = [$organizerId];

        if (!empty($filters['status'])) {
            $where   .= ' AND c.status = ?';
            $params[] = $filters['status'];
        }

        return Database::all(
            "SELECT c.id, c.title, c.description, c.venue, c.address, c.campaign_date,
                    c.campaign_end_date, c.start_time, c.end_time, c.target_donors, c.status, c.deleted_at,
                    ct.name AS city,
                    {$this->registeredSql()} AS registered,
                    {$this->resultCountSql(BLOOD_ELIGIBLE)}   AS eligible,
                    {$this->resultCountSql(BLOOD_INELIGIBLE)} AS ineligible
               FROM blood_campaigns c
               JOIN cities ct ON ct.id = c.city_id
              WHERE {$where}
           ORDER BY c.campaign_date DESC, c.id DESC",
            $params
        );
    }

    /** Counts per status for the organiser, including cancelled ones. */
    public function statusCountsFor(int $organizerId): array
    {
        $rows = Database::all(
            'SELECT status, COUNT(*) AS n
               FROM blood_campaigns
              WHERE organizer_id = ?
           GROUP BY status',
            [$organizerId]
        );

        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }
        return $counts;
    }

    /**
     * The two figures under the organiser card on a public camp page. Both are
     * counted from real rows - there are no placeholder numbers here.
     */
    public function organiserStats(int $organizerId): array
    {
        $row = Database::one(
            'SELECT COUNT(*) AS organised,
                    COALESCE(SUM(
                      (SELECT COUNT(*) FROM screening_responses s WHERE s.campaign_id = c.id)
                    ), 0) AS reached
               FROM blood_campaigns c
              WHERE c.organizer_id = ? AND c.deleted_at IS NULL',
            [$organizerId]
        ) ?: [];

        return [
            'organised' => (int) ($row['organised'] ?? 0),
            'reached'   => (int) ($row['reached'] ?? 0),
        ];
    }

    // ---------- administrator review ----------

    /**
     * The admin queue: campaigns waiting on a decision, or already decided.
     *
     * Cancelled campaigns never appear - the organiser withdrew them, so there
     * is nothing left for an administrator to decide.
     */
    public function queue(string $status): array
    {
        return Database::all(
            "SELECT c.id, c.title, c.description, c.venue, c.address, c.campaign_date,
                    c.campaign_end_date, c.start_time, c.end_time, c.target_donors, c.contact_phone,
                    c.status, c.created_at, c.reviewed_at,
                    ct.name AS city,
                    u.email AS organiser_email,
                    {$this->organiserNameSql()} AS organiser
               FROM blood_campaigns c
               JOIN cities ct ON ct.id = c.city_id
               JOIN users  u  ON u.id  = c.organizer_id
          LEFT JOIN organizations o ON o.user_id = u.id
          LEFT JOIN blood_banks   b ON b.user_id = u.id
              WHERE c.status = ? AND c.deleted_at IS NULL
           ORDER BY c.created_at ASC",
            [$status]
        );
    }

    /** Badge count for the admin sidebar. */
    public function pendingCount(): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM blood_campaigns WHERE status = ? AND deleted_at IS NULL',
            [MOD_PENDING]
        );
    }

    // ---------- helpers ----------

    /**
     * Shared WHERE for browse() and browseCount(), so paging cannot drift.
     *
     * Camps whose date has already passed drop off the hub - the section is
     * headed "Upcoming Blood Donation Camps" and a donor cannot join yesterday.
     * A multi-day camp is measured by its LAST day, so day two of a three-day
     * drive is still joinable; COALESCE covers rows written before
     * `campaign_end_date` existed.
     */
    private function browseWhere(array $filters): array
    {
        $ph     = implode(',', array_fill(0, count(self::PUBLIC_STATUSES), '?'));
        $sql    = "c.deleted_at IS NULL AND c.status IN ({$ph})
                   AND COALESCE(c.campaign_end_date, c.campaign_date) >= CURDATE()";
        $params = self::PUBLIC_STATUSES;

        if (!empty($filters['city_id'])) {
            $sql     .= ' AND c.city_id = ?';
            $params[] = (int) $filters['city_id'];
        }
        if (!empty($filters['q'])) {
            $sql     .= ' AND (c.title LIKE ? OR c.venue LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        return [$sql, $params];
    }

    /**
     * An organization posts under its org name and a blood bank under its bank
     * name; both fall back to the account name if the profile row is missing.
     * Requires the organizations/blood_banks LEFT JOINs to be in the query.
     */
    private function organiserNameSql(): string
    {
        return 'COALESCE(o.org_name, b.bank_name, u.full_name)';
    }

    /** Donors who have completed screening for this camp, whatever the verdict. */
    private function registeredSql(): string
    {
        return '(SELECT COUNT(*) FROM screening_responses s WHERE s.campaign_id = c.id)';
    }

    /**
     * Screened donors with one verdict. The result is a fixed constant chosen by
     * the caller, never user input, so it is safe to inline.
     */
    private function resultCountSql(string $result): string
    {
        return "(SELECT COUNT(*) FROM screening_responses s
                  WHERE s.campaign_id = c.id AND s.result = '" . $result . "')";
    }
}
