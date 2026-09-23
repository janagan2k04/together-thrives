<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Models\Conversation;

/**
 * The inbox: every thread this account is part of.
 *
 * `conversations`, `conversation_members` and `messages` sat unwritten in the
 * frozen schema until 2026-09-05, when the reuse module started opening a
 * thread between a donor and the person asking for their item. So this screen
 * has content for the first time, and it lists real rows.
 *
 * It is a list, not a chat pane. A thread belongs to the context that created
 * it - a reuse listing, today - and Open goes to that module's own screen,
 * where the reply box is and where the guard that decides who may read it
 * lives. Reproducing the compose box here would mean a second copy of that
 * guard, and two copies of a permission check is one too many.
 */
class MessageController extends Controller
{
    /** Where a thread of each context type is read. */
    private const CONTEXT_LABELS = [
        'reuse'    => 'Reuse item',
        'cleanup'  => 'Cleanup campaign',
        'donation' => 'Donation request',
        'blood'    => 'Blood campaign',
    ];

    public function index(): void
    {
        $id = (int) Auth::user()['id'];

        $this->view('messages.index', [
            'title'   => 'Messages',
            'threads' => (new Conversation())->forUser($id),
            'labels'  => self::CONTEXT_LABELS,
        ]);
    }
}
