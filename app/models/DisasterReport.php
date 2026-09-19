<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * A disaster a Grama Niladhari officer has filed.
 *
 * The table has been in the frozen schema since week 1 and nothing wrote it -
 * /gn/disasters drew Services\SampleData and the create form ended with "nothing
 * was saved". The GN dashboard counts these reports, and a counter over a table
 * nobody writes can only ever read 0, so the officer's screens read and write
 * the real rows now.
 *
 * `city_id` is the district: `cities` has held exactly one row per district
 * since 2026-08-27. The form asks for a district, so writes go through
 * City::forDistrict().
 */
class DisasterReport extends Model
{
    protected string $table = 'disaster_reports';
    protected array  $fillable = [
        'reporter_id', 'title', 'disaster_type', 'description', 'location',
        'city_id', 'severity', 'status',
    ];

    /** Labels for the status ENUM, as the officer's screens show them. */
    public const STATUSES = [
        MOD_PENDING  => 'Pending Verification',
        MOD_APPROVED => 'Verified',
        MOD_REJECTED => 'Rejected',
    ];

    /** Everything this officer has filed, newest first. */
    public function forReporter(int $reporterId, array $filters = []): array
    {
        $where  = 'd.reporter_id = ? AND d.deleted_at IS NULL';
        $params = [$reporterId];

        if (!empty($filters['status'])) {
            $where   .= ' AND d.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['severity'])) {
            $where   .= ' AND d.severity = ?';
            $params[] = $filters['severity'];
        }

        return Database::all(
            "SELECT d.*, ct.name AS district
               FROM disaster_reports d
               JOIN cities ct ON ct.id = d.city_id
              WHERE {$where}
           ORDER BY d.created_at DESC, d.id DESC",
            $params
        );
    }

    /**
     * Every report on the platform, whoever filed it - the administrator's view
     * of the same screen. Carries the filing officer's name, which the officer's
     * own list has no use for.
     */
    public function browseAll(array $filters = []): array
    {
        $where  = 'd.deleted_at IS NULL';
        $params = [];

        if (!empty($filters['status'])) {
            $where   .= ' AND d.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['severity'])) {
            $where   .= ' AND d.severity = ?';
            $params[] = $filters['severity'];
        }

        return Database::all(
            "SELECT d.*, ct.name AS district, u.full_name AS reporter
               FROM disaster_reports d
               JOIN cities ct ON ct.id = d.city_id
               JOIN users u   ON u.id  = d.reporter_id
              WHERE {$where}
           ORDER BY d.created_at DESC, d.id DESC",
            $params
        );
    }

    /** One report with its district name, for the detail screen. */
    public function findDetail(int $id): ?array
    {
        return Database::one(
            "SELECT d.*, ct.name AS district, u.full_name AS reporter
               FROM disaster_reports d
               JOIN cities ct ON ct.id = d.city_id
               JOIN users u   ON u.id  = d.reporter_id
              WHERE d.id = ? AND d.deleted_at IS NULL
              LIMIT 1",
            [$id]
        );
    }

    /**
     * Verified reports, for the "linked disaster" picker on a cleanup campaign.
     * Only an approved report may justify one, which is why this filters.
     */
    public function verified(): array
    {
        return Database::all(
            "SELECT d.id, d.title, ct.name AS city
               FROM disaster_reports d
               JOIN cities ct ON ct.id = d.city_id
              WHERE d.status = ? AND d.deleted_at IS NULL
           ORDER BY d.created_at DESC",
            [MOD_APPROVED]
        );
    }
}
