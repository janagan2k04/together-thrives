<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * A problem reported by a user, and the administrator's handling of it.
 *
 * The table has no `deleted_at` and no `updated_at`, so soft delete is off and
 * the inherited update() cannot be used - a report is a record of something
 * somebody said, and is closed rather than edited or removed.
 *
 * `content_type` / `content_id` were designed for reporting a specific post. The
 * "Report a Problem" form is broader than that - most reports are about the
 * platform rather than about one listing - so a report with nothing specific to
 * point at is filed against the reporter's own account: `content_type = 'user'`,
 * `content_id` = the reporter. The ENUM has no "platform" value and the schema is
 * frozen, and filing it against the person who raised it is at least true.
 * `reason` then carries the subject and `details` the description.
 */
class Report extends Model
{
    protected string $table = 'reports';
    protected array  $fillable = [
        'reporter_id', 'content_type', 'content_id', 'reason', 'details', 'status', 'handled_by',
    ];
    protected bool $softDelete = false;

    /** `status` ENUM, worded as the admin screen shows it. */
    public const STATUSES = [
        'open'      => 'Open',
        'reviewing' => 'Reviewing',
        'resolved'  => 'Resolved',
        'dismissed' => 'Dismissed',
    ];

    public const TONES = [
        'open'      => 'amber',
        'reviewing' => 'blue',
        'resolved'  => 'green',
        'dismissed' => 'neutral',
    ];

    /** The admin queue, open ones first then newest. */
    public function forAdmin(string $status = ''): array
    {
        $where  = '1=1';
        $params = [];

        if ($status !== '' && isset(self::STATUSES[$status])) {
            $where   .= ' AND r.status = ?';
            $params[] = $status;
        }

        return Database::all(
            "SELECT r.*, u.full_name AS reporter, u.email AS reporter_email, u.role AS reporter_role,
                    h.full_name AS handler
               FROM reports r
               JOIN users u      ON u.id = r.reporter_id
          LEFT JOIN users h      ON h.id = r.handled_by
              WHERE {$where}
           ORDER BY (r.status = 'open') DESC, r.created_at DESC",
            $params
        );
    }

    public function findDetail(int $id): ?array
    {
        return Database::one(
            'SELECT r.*, u.full_name AS reporter, u.email AS reporter_email, u.role AS reporter_role,
                    h.full_name AS handler
               FROM reports r
               JOIN users u ON u.id = r.reporter_id
          LEFT JOIN users h ON h.id = r.handled_by
              WHERE r.id = ?
              LIMIT 1',
            [$id]
        );
    }

    /**
     * Close a report.
     *
     * The table has no `updated_at`, so Model::update() would fail with Unknown
     * column. Written out here instead.
     */
    public function close(int $id, string $status, int $handledBy): void
    {
        Database::run(
            'UPDATE reports SET status = :status, handled_by = :handled_by WHERE id = :id',
            ['status' => $status, 'handled_by' => $handledBy, 'id' => $id]
        );
    }

    public function openCount(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM reports WHERE status = 'open'");
    }

    /** This member's own reports, so they can see what happened to them. */
    public function forReporter(int $reporterId, int $limit = 10): array
    {
        return Database::all(
            'SELECT * FROM reports WHERE reporter_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit,
            [$reporterId]
        );
    }
}
