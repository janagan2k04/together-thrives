<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * One message in a conversation.
 *
 * `messages` has `created_at` but no `updated_at`, so create() works and
 * update() would fail with `Unknown column`. A sent message is never edited,
 * which is the behaviour the missing column already implies.
 */
class Message extends Model
{
    protected string $table = 'messages';
    protected array  $fillable = ['conversation_id', 'sender_id', 'body', 'message_type'];

    /** Every message in a thread, oldest first, with who sent it. */
    public function forConversation(int $conversationId): array
    {
        return Database::all(
            'SELECT m.id, m.sender_id, m.body, m.created_at, u.full_name AS sender_name
               FROM messages m
               JOIN users u ON u.id = m.sender_id
              WHERE m.conversation_id = ? AND m.deleted_at IS NULL
           ORDER BY m.created_at ASC, m.id ASC',
            [$conversationId]
        );
    }
}
