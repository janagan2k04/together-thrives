<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * A message thread between two accounts.
 *
 * `conversations`, `conversation_members` and `messages` are all in the frozen
 * schema and, until 2026-09-05, nothing wrote to any of them - /messages listed
 * whatever `conversation_members` held for the signed-in user, which was always
 * nothing. The reuse module is the first writer: a donor and the person asking
 * for their item get a thread, keyed on the listing.
 *
 * `context_type` / `context_id` are what make a thread findable again: there is
 * one conversation per listing per requester, so opening the thread twice does
 * not create two of them.
 *
 * **`conversation_members` has no `created_at`** - it stamps `joined_at` - so
 * Model::create() cannot write it and addMember() below does its own INSERT.
 * `conversations` has no `updated_at`, so Model::update() would fail here too;
 * a thread is never edited, only added to.
 */
class Conversation extends Model
{
    protected string $table = 'conversations';
    protected array  $fillable = ['type', 'title', 'context_type', 'context_id'];

    /**
     * The direct thread for one context, or null. Both members are required to
     * match, so two different requesters on the same listing get one thread
     * each rather than sharing the donor's.
     */
    public function findDirect(string $contextType, int $contextId, int $a, int $b): ?array
    {
        return Database::one(
            'SELECT c.*
               FROM conversations c
               JOIN conversation_members ma ON ma.conversation_id = c.id AND ma.user_id = ?
               JOIN conversation_members mb ON mb.conversation_id = c.id AND mb.user_id = ?
              WHERE c.type = \'direct\'
                AND c.context_type = ? AND c.context_id = ?
                AND c.deleted_at IS NULL
              LIMIT 1',
            [$a, $b, $contextType, $contextId]
        );
    }

    /** True when this account is a member of the thread - the whole read guard. */
    public function hasMember(int $conversationId, int $userId): bool
    {
        return (bool) Database::value(
            'SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ? LIMIT 1',
            [$conversationId, $userId]
        );
    }

    /**
     * `conversation_members` stamps `joined_at`, not `created_at`, so this is a
     * hand-written INSERT rather than a model create(). INSERT IGNORE leans on
     * `uq_conv_user` so adding the same member twice is a no-op rather than a
     * duplicate-key error.
     */
    public function addMember(int $conversationId, int $userId): void
    {
        Database::run(
            'INSERT IGNORE INTO conversation_members (conversation_id, user_id) VALUES (?, ?)',
            [$conversationId, $userId]
        );
    }

    /** The other party on a direct thread, for the header and the thread list. */
    public function otherMember(int $conversationId, int $userId): ?array
    {
        return Database::one(
            'SELECT u.id, u.full_name, u.role
               FROM conversation_members m
               JOIN users u ON u.id = m.user_id
              WHERE m.conversation_id = ? AND m.user_id <> ?
              LIMIT 1',
            [$conversationId, $userId]
        );
    }

    /**
     * The signed-in account's threads, newest activity first, each with who it
     * is with and how many messages it holds.
     */
    public function forUser(int $userId): array
    {
        return Database::all(
            "SELECT c.id, c.type, c.title, c.context_type, c.context_id, c.created_at,
                    o.full_name AS other_name,
                    (SELECT COUNT(*) FROM messages ms
                      WHERE ms.conversation_id = c.id AND ms.deleted_at IS NULL) AS message_count,
                    (SELECT ms.body FROM messages ms
                      WHERE ms.conversation_id = c.id AND ms.deleted_at IS NULL
                   ORDER BY ms.id DESC LIMIT 1) AS last_body,
                    (SELECT ms.created_at FROM messages ms
                      WHERE ms.conversation_id = c.id AND ms.deleted_at IS NULL
                   ORDER BY ms.id DESC LIMIT 1) AS last_at
               FROM conversations c
               JOIN conversation_members m ON m.conversation_id = c.id AND m.user_id = ?
          LEFT JOIN conversation_members mo ON mo.conversation_id = c.id AND mo.user_id <> ?
          LEFT JOIN users o ON o.id = mo.user_id
              WHERE c.deleted_at IS NULL
           ORDER BY COALESCE((SELECT MAX(ms.created_at) FROM messages ms
                               WHERE ms.conversation_id = c.id), c.created_at) DESC,
                    c.id DESC",
            [$userId, $userId]
        );
    }
}
