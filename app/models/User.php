<?php
namespace Models;

use Core\Database;
use Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'full_name', 'email', 'phone', 'nic', 'nic_document_path', 'password_hash',
        'role', 'status', 'city_id', 'blood_group', 'avatar_path',
        'locale', 'email_verified_at',
    ];

    public function emailTaken(string $email, ?int $exceptId = null): bool
    {
        return $this->exists('email', $email, $exceptId);
    }

    /**
     * Every account still waiting on an administrator, newest first.
     *
     * One query across the three detail tables so the queue can show what was
     * actually submitted — registration number, GN division, licence number —
     * without a second lookup per row. LEFT JOIN because a community member has
     * no detail row at all.
     */
    public function pendingVerifications(array $filters = []): array
    {
        $sql = "SELECT u.id, u.full_name, u.email, u.phone, u.role, u.status,
                       u.nic, u.nic_document_path, u.created_at,
                       o.org_name, o.reg_number, o.org_type, o.website, o.description,
                       o.document_path  AS org_document,
                       g.gn_division, g.ds_division, g.document_path AS gn_document,
                       b.bank_name, b.license_no, b.hospital_name, b.hotline,
                       b.document_path  AS bank_document,
                       d.name AS district, c.name AS city
                FROM users u
                LEFT JOIN organizations    o ON o.user_id = u.id AND o.deleted_at IS NULL
                LEFT JOIN grama_niladharis g ON g.user_id = u.id AND g.deleted_at IS NULL
                LEFT JOIN blood_banks      b ON b.user_id = u.id AND b.deleted_at IS NULL
                LEFT JOIN districts        d ON d.id = g.district_id
                LEFT JOIN cities           c ON c.id = u.city_id
                WHERE u.status = ? AND u.deleted_at IS NULL";
        $params = [STATUS_PENDING];

        if (!empty($filters['role'])) {
            $sql .= " AND u.role = ?";
            $params[] = $filters['role'];
        }

        $sql .= " ORDER BY u.created_at DESC";
        return Database::all($sql, $params);
    }

    /** One pending account with the same joined detail as the queue. */
    public function pendingVerification(int $id): ?array
    {
        $rows = $this->pendingVerifications();
        foreach ($rows as $row) {
            if ((int) $row['id'] === $id) return $row;
        }
        return null;
    }

    /** Admin user list — every role and status, with the district for context. */
    public function browseForAdmin(array $filters = []): array
    {
        $sql = "SELECT u.id, u.full_name, u.email, u.role, u.status, u.created_at,
                       COALESCE(c.name, d.name) AS district
                FROM users u
                LEFT JOIN cities           c ON c.id = u.city_id
                LEFT JOIN grama_niladharis g ON g.user_id = u.id AND g.deleted_at IS NULL
                LEFT JOIN districts        d ON d.id = g.district_id
                WHERE u.deleted_at IS NULL AND u.role <> ?";
        $params = [ROLE_ADMIN];

        if (!empty($filters['role'])) {
            $sql .= " AND u.role = ?";
            $params[] = $filters['role'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY u.created_at DESC";
        return Database::all($sql, $params);
    }

    /** How many accounts are waiting — drives the sidebar badge. */
    public function pendingCount(): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM users WHERE status = ? AND deleted_at IS NULL",
            [STATUS_PENDING]
        );
    }
}
