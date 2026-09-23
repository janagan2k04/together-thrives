<?php
namespace Models;

use Core\Database;
use Core\Model;

class Organization extends Model
{
    protected string $table = 'organizations';
    protected array  $fillable = [
        'user_id', 'org_name', 'reg_number', 'org_type', 'address', 'city_id',
        'website', 'description', 'logo_path', 'document_path',
        'verified_at', 'verified_by',
    ];

    /** Values allowed by the org_type ENUM in schema.sql. */
    public const TYPES = [
        'non_profit'      => 'Non-Profit Organization',
        'community_group' => 'Community Group',
        'blood_bank'      => 'Blood Bank',
    ];

    /**
     * Every institutional account, for /admin/organizations.
     *
     * A blood bank is an organization to the group and to the admin screen, but
     * not to the schema: it registers into `blood_banks`, a different table with
     * different column names and no `org_type`. Reading only `organizations` is
     * why the screen showed one row on a seed that has two institutions. The
     * UNION returns both, aliased onto one shape - `name` and `reference` rather
     * than org_name/reg_number, because the blood bank half calls them
     * bank_name and license_no.
     *
     * Status lives on the users row in both halves, so the account state in the
     * table comes from that join and not from `verified_at`.
     *
     * `u.role = 'organization'` on the first half, added 2026-08-27: a Grama
     * Niladhari officer is given an `organizations` profile row the first time
     * they host a cleanup campaign, because `cleanup_events.organization_id` is
     * foreign-keyed to this table. That row is a campaign owner, not an
     * institution - the officer's own account is decided on /admin/gs, and
     * listing the proxy here would ask an administrator to approve them twice.
     */
    public function browseForAdmin(array $filters = []): array
    {
        $wantType = $filters['org_type'] ?? '';
        $wantStat = $filters['status'] ?? '';

        $parts  = [];
        $params = [];

        // organizations: `org_type` is a real column, so the type filter applies
        // to it. A request for blood banks only skips this half entirely.
        if ($wantType !== 'blood_bank') {
            $sql = "SELECT o.id, o.org_name AS name, o.reg_number AS reference,
                           o.org_type, o.website, o.verified_at,
                           u.id AS user_id, u.email, u.status, u.created_at,
                           c.name AS city
                      FROM organizations o
                      JOIN users u ON u.id = o.user_id
                 LEFT JOIN cities c ON c.id = o.city_id
                     WHERE o.deleted_at IS NULL AND u.deleted_at IS NULL
                       AND u.role = '" . ROLE_ORGANIZATION . "'";
            if ($wantType !== '') {
                $sql .= " AND o.org_type = ?";
                $params[] = $wantType;
            }
            if ($wantStat !== '') {
                $sql .= " AND u.status = ?";
                $params[] = $wantStat;
            }
            $parts[] = $sql;
        }

        // blood_banks: every row is one, so the type filter either includes the
        // whole half or excludes it.
        if ($wantType === '' || $wantType === 'blood_bank') {
            $sql = "SELECT b.id, b.bank_name AS name, b.license_no AS reference,
                           'blood_bank' AS org_type, NULL AS website, b.verified_at,
                           u.id AS user_id, u.email, u.status, u.created_at,
                           c.name AS city
                      FROM blood_banks b
                      JOIN users u ON u.id = b.user_id
                 LEFT JOIN cities c ON c.id = b.city_id
                     WHERE b.deleted_at IS NULL AND u.deleted_at IS NULL";
            if ($wantStat !== '') {
                $sql .= " AND u.status = ?";
                $params[] = $wantStat;
            }
            $parts[] = $sql;
        }

        if (!$parts) {
            return [];
        }

        return Database::all(
            '(' . implode(') UNION ALL (', $parts) . ') ORDER BY created_at DESC',
            $params
        );
    }

    /** Grama Niladhari accounts, for /admin/gs. Same shape as the list above. */
    public function browseGramaNiladharis(array $filters = []): array
    {
        $sql = "SELECT g.id, u.full_name AS name, g.gn_division AS reference,
                       g.ds_division, g.division_code, g.office_phone, g.verified_at,
                       u.id AS user_id, u.email, u.status, u.created_at,
                       d.name AS district
                  FROM grama_niladharis g
                  JOIN users u ON u.id = g.user_id
             LEFT JOIN districts d ON d.id = g.district_id
                 WHERE g.deleted_at IS NULL AND u.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['district_id'])) {
            $sql .= " AND g.district_id = ?";
            $params[] = (int) $filters['district_id'];
        }

        return Database::all($sql . ' ORDER BY u.created_at DESC', $params);
    }
}
