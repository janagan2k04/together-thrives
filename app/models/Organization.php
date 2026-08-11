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
     * Admin list. Status lives on the users row, not here, so the account state
     * shown in the table comes from the join.
     */
    public function browseForAdmin(array $filters = []): array
    {
        $sql = "SELECT o.id, o.org_name, o.reg_number, o.org_type, o.website,
                       o.verified_at, u.id AS user_id, u.email, u.status, u.created_at,
                       c.name AS city
                FROM organizations o
                JOIN users  u ON u.id = o.user_id
                LEFT JOIN cities c ON c.id = o.city_id
                WHERE o.deleted_at IS NULL AND u.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['org_type'])) {
            $sql .= " AND o.org_type = ?";
            $params[] = $filters['org_type'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY o.created_at DESC";
        return Database::all($sql, $params);
    }
}
