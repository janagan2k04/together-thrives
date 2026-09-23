<?php
namespace Services;

use Core\Database;
use Core\Logger;
use Models\Conversation;
use Models\Message;
use Models\Notification;

/**
 * Opening a thread and sending into it.
 *
 * The reuse module is the only caller today: a donor and the person who asked
 * for their item need to agree a pickup, and neither one's contact details are
 * shown to the other. `conversations.context_type` already has a 'reuse' value
 * in the frozen schema, so a thread is keyed on the listing rather than living
 * loose in an inbox.
 *
 * Adding a second module means calling openDirect() with its own context type -
 * nothing here is reuse-specific.
 */
class MessagingService
{
    private Conversation $conversations;
    private Message      $messages;
    private Notification $notifications;

    public function __construct()
    {
        $this->conversations = new Conversation();
        $this->messages      = new Message();
        $this->notifications = new Notification();
    }

    /**
     * The direct thread between two accounts for one context, created on first
     * use. Idempotent: opening it again returns the same row, which is why the
     * Message button can be pressed from either side any number of times.
     *
     * @return int the conversation id
     */
    public function openDirect(string $contextType, int $contextId, int $a, int $b, string $title): int
    {
        $existing = $this->conversations->findDirect($contextType, $contextId, $a, $b);
        if ($existing) {
            return (int) $existing['id'];
        }

        return Database::transaction(function () use ($contextType, $contextId, $a, $b, $title) {
            $id = $this->conversations->create([
                'type'         => 'direct',
                'title'        => mb_substr($title, 0, 160),
                'context_type' => $contextType,
                'context_id'   => $contextId,
            ]);

            $this->conversations->addMember($id, $a);
            $this->conversations->addMember($id, $b);

            // "user" before each id so Services\AuditLogService can name them on
            // the System Logs screen; a bare number has nothing to look up.
            Logger::audit("Conversation {$id} opened between user {$a} and user {$b} on {$contextType} {$contextId}");
            return $id;
        });
    }

    /**
     * Send one message and tell the other party.
     *
     * The notification is written in the same transaction as the message: a
     * message nobody is told about is the same as one that was never sent.
     */
    public function send(array $conversation, int $senderId, string $body, string $link): void
    {
        $other = $this->conversations->otherMember((int) $conversation['id'], $senderId);

        Database::transaction(function () use ($conversation, $senderId, $body, $link, $other) {
            $this->messages->create([
                'conversation_id' => (int) $conversation['id'],
                'sender_id'       => $senderId,
                'body'            => $body,
                'message_type'    => 'text',
            ]);

            if ($other) {
                $this->notifications->create([
                    'user_id'  => (int) $other['id'],
                    'title'    => 'New message',
                    'body'     => mb_substr($body, 0, 200),
                    'category' => 'system',
                    'link'     => $link,
                ]);
            }
        });
    }
}
