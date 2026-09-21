<?php
namespace Models\Donation;

use Core\Database;
use Core\Model;

/**
 * An appeal for relief items or for money. Member 2's CRUD entity.
 *
 * `requester_id` points at `users(id)`, and `requester_type` records whether
 * that account is a Grama Niladhari or an organisation - it is set from the
 * signed-in role, never from the form.
 *
 * An item request owns rows in `requested_items`; a fund request owns rows in
 * `fund_donations`. Only the first is part of this entity's CRUD: the items are
 * written on the same form as the request and are meaningless without it.
 */
class DonationRequest extends Model
{
    protected string $table = 'donation_requests';
    protected array  $fillable = [
        'requester_id', 'requester_type', 'request_type', 'title', 'description',
        'disaster_details', 'affected_area', 'city_id', 'pickup_point',
        'target_amount', 'collected_amount', 'starts_on', 'ends_on',
        'cover_image', 'status',
        // Set by the admin review screen only; never present in requester input.
        'reviewed_by', 'reviewed_at', 'reject_reason',
    ];

    /**
     * `requester_type` ENUM, as every screen names the poster.
     *
     * `admin` joined the ENUM on 2026-09-13 - see
     * `database/migrations/2026_09_13_admin_can_request_donations.sql`.
     */
    public const REQUESTER_TYPES = [
        ROLE_GN           => 'Grama Niladhari',
        ROLE_ORGANIZATION => 'Organisation',
        ROLE_ADMIN        => 'Platform Admin',
    ];

    /** `request_type` ENUM, with the Figma's labels. */
    /**
     * The preset amounts on the fund checkout, and the payment methods the
     * sandbox gateway offers. Neither is a column - they are the choices the
     * form presents - but they live here so the checkout and the request page
     * cannot offer different ones.
     */
    public const AMOUNTS = [500, 1000, 2500, 5000, 10000];

    public const PAY_METHODS = ['Credit / Debit Card', 'Online Banking', 'eZ Cash / mCash'];

    /** The disaster kinds the create form offers, for `disaster_details`. */
    public const DISASTERS = ['Flood', 'Landslide', 'Drought', 'Storm', 'Fire', 'Epidemic'];

    public const TYPES = [
        'items' => 'Relief Items',
        'funds' => 'Monetary Funds',
    ];

    /** `status` ENUM, with the labels the Figma uses. */
    public const STATUSES = [
        REQUEST_PENDING   => 'Pending Approval',
        REQUEST_OPEN      => 'Open',
        REQUEST_FULFILLED => 'Fulfilled',
        REQUEST_EXPIRED   => 'Expired',
        MOD_REJECTED      => 'Rejected',
        REQUEST_CANCELLED => 'Cancelled',
    ];

    /** Badge tone per status, so views never hardcode a colour. */
    public const TONES = [
        REQUEST_PENDING   => 'amber',
        REQUEST_OPEN      => 'green',
        REQUEST_FULFILLED => 'blue',
        REQUEST_EXPIRED   => 'neutral',
        MOD_REJECTED      => 'red',
        REQUEST_CANCELLED => 'red',
    ];

    /** The statuses a donor is allowed to see on the public browse page. */
    public const PUBLIC_STATUSES = [REQUEST_OPEN, REQUEST_FULFILLED];

    // ---------- public browse ----------

    /**
     * The public list. A request appears only once an administrator has approved
     * it - the create form promises the requester exactly that.
     */
    public function browse(array $filters = [], int $limit = PER_PAGE, int $offset = 0): array
    {
        [$where, $params] = $this->browseWhere($filters);

        return Database::all(
            "SELECT {$this->cardColumns()}
               FROM donation_requests d
               JOIN cities ct   ON ct.id = d.city_id
               JOIN districts s ON s.id  = ct.district_id
               JOIN users u     ON u.id  = d.requester_id
          LEFT JOIN organizations o ON o.user_id = u.id
              WHERE {$where}
           ORDER BY d.ends_on ASC, d.id DESC
              LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    public function browseCount(array $filters = []): int
    {
        [$where, $params] = $this->browseWhere($filters);

        return (int) Database::value(
            "SELECT COUNT(*)
               FROM donation_requests d
               JOIN cities ct   ON ct.id = d.city_id
               JOIN districts s ON s.id  = ct.district_id
               JOIN users u     ON u.id  = d.requester_id
          LEFT JOIN organizations o ON o.user_id = u.id
              WHERE {$where}",
            $params
        );
    }

    // ---------- one request ----------

    /**
     * One request with everything the detail screens print.
     *
     * Cancelling soft deletes the row, so the requester's own screens must be
     * able to load a deleted one. Pass true and let the caller check ownership.
     */
    public function findDetail(int $id, bool $includeCancelled = false): ?array
    {
        $deletedClause = $includeCancelled ? '' : ' AND d.deleted_at IS NULL';

        return Database::one(
            "SELECT {$this->cardColumns()},
                    d.disaster_details, d.pickup_point, d.requester_id,
                    d.requester_type, d.reviewed_at, d.reject_reason, d.deleted_at,
                    u.phone AS requester_phone,
                    u.email AS requester_email,
                    u.status AS requester_status
               FROM donation_requests d
               JOIN cities ct   ON ct.id = d.city_id
               JOIN districts s ON s.id  = ct.district_id
               JOIN users u     ON u.id  = d.requester_id
          LEFT JOIN organizations o ON o.user_id = u.id
              WHERE d.id = ?{$deletedClause}
              LIMIT 1",
            [$id]
        );
    }

    /** Raw row including cancelled ones, for ownership checks before a write. */
    public function findAny(int $id): ?array
    {
        return Database::one('SELECT * FROM donation_requests WHERE id = ? LIMIT 1', [$id]);
    }

    // ---------- the requester's own list ----------

    /**
     * Every request this requester has posted, newest first. Cancelled ones are
     * included on purpose: the delete is soft and they stay in the history.
     */
    public function forRequester(int $requesterId, array $filters = []): array
    {
        $where  = 'd.requester_id = ?';
        $params = [$requesterId];

        if (!empty($filters['request_type'])) {
            $where   .= ' AND d.request_type = ?';
            $params[] = $filters['request_type'];
        }
        if (!empty($filters['status'])) {
            $where   .= ' AND d.status = ?';
            $params[] = $filters['status'];
        }

        return Database::all(
            "SELECT {$this->cardColumns()}, d.deleted_at
               FROM donation_requests d
               JOIN cities ct   ON ct.id = d.city_id
               JOIN districts s ON s.id  = ct.district_id
               JOIN users u     ON u.id  = d.requester_id
          LEFT JOIN organizations o ON o.user_id = u.id
              WHERE {$where}
           ORDER BY d.created_at DESC, d.id DESC",
            $params
        );
    }

    /** Counts per request type for the requester, for the manage screen's tabs. */
    public function typeCountsFor(int $requesterId): array
    {
        $rows = Database::all(
            'SELECT request_type, COUNT(*) AS n
               FROM donation_requests
              WHERE requester_id = ?
           GROUP BY request_type',
            [$requesterId]
        );

        $counts = ['all' => 0, 'items' => 0, 'funds' => 0];
        foreach ($rows as $row) {
            $counts[$row['request_type']] = (int) $row['n'];
            $counts['all']               += (int) $row['n'];
        }
        return $counts;
    }

    /** Counts per status for the requester, including cancelled ones. */
    public function statusCountsFor(int $requesterId): array
    {
        $rows = Database::all(
            'SELECT status, COUNT(*) AS n
               FROM donation_requests
              WHERE requester_id = ?
           GROUP BY status',
            [$requesterId]
        );

        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }
        return $counts;
    }

    // ---------- administrator review ----------

    public function queue(string $status): array
    {
        return Database::all(
            "SELECT {$this->cardColumns()}, d.reviewed_at, d.pickup_point,
                    u.email AS requester_email
               FROM donation_requests d
               JOIN cities ct   ON ct.id = d.city_id
               JOIN districts s ON s.id  = ct.district_id
               JOIN users u     ON u.id  = d.requester_id
          LEFT JOIN organizations o ON o.user_id = u.id
              WHERE d.status = ? AND d.deleted_at IS NULL
           ORDER BY d.created_at ASC",
            [$status]
        );
    }

    public function pendingCount(): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM donation_requests WHERE status = ? AND deleted_at IS NULL',
            [REQUEST_PENDING]
        );
    }

    // ---------- helpers ----------

    /**
     * The columns every card and detail screen shares, including the figures
     * they print. All of them are counted or summed from real rows.
     *
     * `donors` counts paid `fund_donations`; `progress` is money collected
     * against target for a fund request, and units received against units
     * required for an item one. `ref` is derived from the id and the year the
     * row was created - the Figma prints a reference like DR-2025-0001 and
     * there is no column for it, so it is computed rather than stored.
     */
    private function cardColumns(): string
    {
        return "d.id, d.request_type, d.title, d.description, d.affected_area,
                d.target_amount, d.collected_amount, d.starts_on, d.ends_on,
                d.status, d.city_id, d.created_at,
                ct.name AS city,
                s.name  AS district,
                COALESCE(o.org_name, u.full_name) AS requester,
                (o.verified_at IS NOT NULL OR u.status = 'active') AS verified,
                CONCAT('DR-', DATE_FORMAT(d.created_at, '%Y'), '-', LPAD(d.id, 4, '0')) AS ref,
                DATEDIFF(d.ends_on, CURDATE()) AS days_left,
                (SELECT COUNT(*) FROM fund_donations f
                  WHERE f.request_id = d.id AND f.status = 'paid'
                    AND f.deleted_at IS NULL) AS donors,
                CASE
                  WHEN d.request_type = 'funds' THEN
                    IF(COALESCE(d.target_amount, 0) > 0,
                       LEAST(100, ROUND(d.collected_amount / d.target_amount * 100)), 0)
                  ELSE COALESCE((
                    SELECT IF(SUM(ri.qty_required) > 0,
                              LEAST(100, ROUND(SUM(ri.qty_received) / SUM(ri.qty_required) * 100)), 0)
                      FROM requested_items ri
                     WHERE ri.request_id = d.id AND ri.deleted_at IS NULL), 0)
                END AS progress";
    }

    /** Shared WHERE for browse() and browseCount(), so paging cannot drift. */
    private function browseWhere(array $filters): array
    {
        $ph     = implode(',', array_fill(0, count(self::PUBLIC_STATUSES), '?'));
        $sql    = "d.deleted_at IS NULL AND d.status IN ({$ph})";
        $params = self::PUBLIC_STATUSES;

        if (!empty($filters['request_type'])) {
            $sql     .= ' AND d.request_type = ?';
            $params[] = $filters['request_type'];
        }
        if (!empty($filters['city_id'])) {
            $sql     .= ' AND d.city_id = ?';
            $params[] = (int) $filters['city_id'];
        }
        if (!empty($filters['status'])) {
            $sql     .= ' AND d.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['requester_id'])) {
            $sql     .= ' AND d.requester_id = ?';
            $params[] = (int) $filters['requester_id'];
        }
        if (!empty($filters['q'])) {
            $sql     .= ' AND (d.title LIKE ? OR d.affected_area LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        return [$sql, $params];
    }
}
