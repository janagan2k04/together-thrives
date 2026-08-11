<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * In-app notifications. The table has no `deleted_at` and no `updated_at`,
 * so soft delete is off — a notification is either read or removed outright.
 */
class Notification extends Model
{
    protected string $table = 'notifications';
    protected bool $softDelete = false;

    protected array $fillable = ['user_id', 'title', 'body', 'category', 'link', 'read_at'];

    /** Categories allowed by the ENUM — anything else is rejected by MySQL. */
    public const CATEGORY_VERIFICATION = 'verification';
    public const CATEGORY_SYSTEM       = 'system';

    /** Newest first for the bell dropdown and the notifications page. */
    public function forUser(int $userId, int $limit = 20): array
    {
        return Database::all(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}",
            [$userId]
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
