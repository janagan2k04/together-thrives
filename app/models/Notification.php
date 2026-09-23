<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * In-app notifications. The table has no `deleted_at` and no `updated_at`,
 * so soft delete is off - a notification is either read or removed outright.
 */
class Notification extends Model
{
    protected string $table = 'notifications';
    protected bool $softDelete = false;

    protected array $fillable = ['user_id', 'title', 'body', 'category', 'link', 'read_at'];

    /** Categories allowed by the ENUM - anything else is rejected by MySQL. */
    public const CATEGORY_VERIFICATION = 'verification';
    public const CATEGORY_SYSTEM       = 'system';

    /**
     * Newest first for the bell dropdown and the notifications page.
     *
     * `$category` is one of the ENUM values, or null for everything. The caller
     * checks it against the ENUM before passing it, so an unknown value never
     * reaches the query as a filter that silently matches nothing.
     */
    public function forUser(int $userId, int $limit = 20, ?string $category = null): array
    {
        $where  = 'user_id = ?';
        $params = [$userId];

        if ($category !== null) {
            $where   .= ' AND category = ?';
            $params[] = $category;
        }

        return Database::all(
            "SELECT * FROM notifications WHERE {$where} ORDER BY created_at DESC LIMIT " . (int) $limit,
            $params
        );
    }

    public function unreadCount(int $userId): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        );
    }

    public function markAllRead(int $userId): void
    {
        Database::run(
            "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL",
            [$userId]
        );
    }
}
