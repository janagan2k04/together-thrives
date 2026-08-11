<?php
namespace Models\Reuse;

use Core\Database;
use Core\Model;

/**
 * A reusable item a member is giving away.
 *
 * Note the column is `item_condition`, not `condition` — CONDITION is a
 * reserved word in MySQL 8, which is why schema.sql spells it that way.
 */
class ReuseItem extends Model
{
    protected string $table = 'reuse_items';
    protected array  $fillable = [
        'donor_id', 'category_id', 'title', 'description', 'item_condition',
        'quantity', 'city_id', 'pickup_note', 'status',
    ];

    /** Values allowed by the item_condition ENUM in schema.sql. */
    public const CONDITIONS = [
        'new'    => 'New',
        'good'   => 'Good',
        'fair'   => 'Fair',
        'usable' => 'Usable',
    ];

    /** Labels for the status ENUM, as the cards in the design show them. */
    public const STATUSES = [
        ITEM_AVAILABLE => 'Available',
        ITEM_RESERVED  => 'Pending',
        ITEM_DONATED   => 'Donated',
        ITEM_REMOVED   => 'Removed',
    ];

    /**
     * Public browse list. Withdrawn listings never appear, whoever is asking.
     *
     * "Nearest to me" is in the Figma sort menu but not offered here — the
     * schema stores a city, not coordinates, so there is nothing to sort by.
     */
    public function browse(array $filters = [], int $limit = PER_PAGE, int $offset = 0): array
    {
        [$where, $params] = $this->browseWhere($filters);

        $sql = "SELECT i.id, i.title, i.item_condition, i.quantity, i.status, i.created_at,
                       c.name  AS category,
                       ct.name AS city,
                       d.name  AS district,
                       u.full_name AS donor_name,
                       (SELECT im.id FROM item_images im
                         WHERE im.item_id = i.id
                      ORDER BY im.is_primary DESC, im.id ASC LIMIT 1) AS image_id
                  FROM reuse_items i
                  JOIN item_categories c ON c.id = i.category_id
                  JOIN cities ct        ON ct.id = i.city_id
                  JOIN districts d      ON d.id = ct.district_id
                  JOIN users u          ON u.id = i.donor_id
                 WHERE {$where}
              ORDER BY " . $this->orderFor($filters['sort'] ?? '') . "
                 LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

        return Database::all($sql, $params);
    }

    /** Total rows the same filters match, for paging. */
    public function browseCount(array $filters = []): int
    {
        [$where, $params] = $this->browseWhere($filters);

        return (int) Database::value(
            "SELECT COUNT(*)
               FROM reuse_items i
               JOIN item_categories c ON c.id = i.category_id
               JOIN cities ct        ON ct.id = i.city_id
               JOIN users u          ON u.id = i.donor_id
              WHERE {$where}",
            $params
        );
    }

    /**
     * One listing with everything the detail page prints.
     *
     * Withdrawing soft deletes the row, so the detail page has to be able to
     * load a deleted one — it is still the donor's own listing, and only the
     * caller knows whether the viewer is that donor. Pass true and check.
     */
    public function findDetail(int $id, bool $includeWithdrawn = false): ?array
    {
        $deletedClause = $includeWithdrawn ? '' : ' AND i.deleted_at IS NULL';

        return Database::one(
            "SELECT i.*, c.name AS category, ct.name AS city, d.name AS district,
                    u.full_name AS donor_name, u.email AS donor_email,
                    u.role AS donor_role, u.status AS donor_status,
                    u.created_at AS donor_joined,
                    (SELECT COUNT(*) FROM reuse_items x
                      WHERE x.donor_id = i.donor_id
                        AND x.status = ?
                        AND x.deleted_at IS NULL) AS donor_donated_count
               FROM reuse_items i
               JOIN item_categories c ON c.id = i.category_id
               JOIN cities ct         ON ct.id = i.city_id
               JOIN districts d       ON d.id = ct.district_id
               JOIN users u           ON u.id = i.donor_id
              WHERE i.id = ?{$deletedClause}
              LIMIT 1",
            [ITEM_DONATED, $id]
        );
    }

    /** Including withdrawn ones, for the donor's own management screens. */
    public function findAny(int $id): ?array
    {
        return Database::one('SELECT * FROM reuse_items WHERE id = ? LIMIT 1', [$id]);
    }

    /**
     * The donor's own listings, with the number of requests waiting on each.
     *
     * Withdrawn listings are included on purpose: soft delete keeps the row,
     * and the donor needs to see it to relist it. Everyone else's views filter
     * them out.
     */
    public function forDonor(int $donorId): array
    {
        return Database::all(
            "SELECT i.id, i.title, i.status, i.quantity, i.item_condition, i.created_at,
                    c.name AS category, ct.name AS city,
                    (SELECT COUNT(*) FROM item_requests r
                      WHERE r.item_id = i.id
                        AND r.status = 'pending'
                        AND r.deleted_at IS NULL) AS pending_requests,
                    (SELECT COUNT(*) FROM item_requests r
                      WHERE r.item_id = i.id
                        AND r.deleted_at IS NULL) AS total_requests,
                    (SELECT im.id FROM item_images im
                      WHERE im.item_id = i.id
                   ORDER BY im.is_primary DESC, im.id ASC LIMIT 1) AS image_id,
                    i.deleted_at
               FROM reuse_items i
               JOIN item_categories c ON c.id = i.category_id
               JOIN cities ct         ON ct.id = i.city_id
              WHERE i.donor_id = ?
           ORDER BY i.created_at DESC",
            [$donorId]
        );
    }

    /** "Similar items" strip on the detail page — same category, not this one. */
    public function similar(int $categoryId, int $excludeId, int $limit = 4): array
    {
        return Database::all(
            "SELECT i.id, i.title, i.status, c.name AS category, ct.name AS city, i.created_at,
                    (SELECT im.id FROM item_images im
                      WHERE im.item_id = i.id
                   ORDER BY im.is_primary DESC, im.id ASC LIMIT 1) AS image_id
               FROM reuse_items i
               JOIN item_categories c ON c.id = i.category_id
               JOIN cities ct         ON ct.id = i.city_id
              WHERE i.category_id = ? AND i.id <> ?
                AND i.status <> ? AND i.deleted_at IS NULL
           ORDER BY i.created_at DESC
              LIMIT " . (int) $limit,
            [$categoryId, $excludeId, ITEM_REMOVED]
        );
    }

    /** Counts for the donor's own listing tabs. */
    public function statusCountsFor(int $donorId): array
    {
        // Withdrawn listings counted too, so the Removed tile is not always 0.
        $rows = Database::all(
            "SELECT status, COUNT(*) AS n
               FROM reuse_items
              WHERE donor_id = ?
           GROUP BY status",
            [$donorId]
        );

        $counts = array_fill_keys(array_keys(self::STATUSES), 0);
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }
        return $counts;
    }

    /**
     * The figures across the top of the public donor profile. Everything here
     * is counted from real rows — there are no placeholder numbers.
     */
    public function donorStats(int $donorId): array
    {
        $items = Database::one(
            "SELECT COUNT(*) AS total,
                    SUM(status = ?) AS donated,
                    SUM(status = ?) AS listed
               FROM reuse_items
              WHERE donor_id = ? AND deleted_at IS NULL",
            [ITEM_DONATED, ITEM_AVAILABLE, $donorId]
        ) ?: [];

        $requests = Database::one(
            "SELECT COUNT(*) AS received,
                    SUM(r.is_disaster_affected = 1 AND r.status = 'selected') AS disaster_helped
               FROM item_requests r
               JOIN reuse_items i ON i.id = r.item_id
              WHERE i.donor_id = ? AND r.deleted_at IS NULL",
            [$donorId]
        ) ?: [];

        return [
            'total'           => (int) ($items['total'] ?? 0),
            'donated'         => (int) ($items['donated'] ?? 0),
            'listed'          => (int) ($items['listed'] ?? 0),
            'received'        => (int) ($requests['received'] ?? 0),
            'disaster_helped' => (int) ($requests['disaster_helped'] ?? 0),
        ];
    }

    // ---------- filter helpers ----------

    /** Shared WHERE for browse() and browseCount(), so paging cannot drift. */
    private function browseWhere(array $filters): array
    {
        $sql    = 'i.deleted_at IS NULL AND i.status <> ?';
        $params = [ITEM_REMOVED];

        if (!empty($filters['category_id'])) {
            $sql .= ' AND i.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['item_condition'])) {
            $sql .= ' AND i.item_condition = ?';
            $params[] = $filters['item_condition'];
        }
        if (!empty($filters['city_id'])) {
            $sql .= ' AND i.city_id = ?';
            $params[] = (int) $filters['city_id'];
        }
        if (!empty($filters['donor_id'])) {
            $sql .= ' AND i.donor_id = ?';
            $params[] = (int) $filters['donor_id'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (i.title LIKE ? OR i.description LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        return [$sql, $params];
    }

    /** Sort keys come from a fixed list — never interpolate user input here. */
    private function orderFor(string $sort): string
    {
        return match ($sort) {
            'oldest'   => 'i.created_at ASC',
            'category' => 'c.name ASC, i.created_at DESC',
            default    => 'i.created_at DESC',
        };
    }
}
