<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Services\SampleData;

/**
 * Messaging.
 *
 * `conversations`, `conversation_members` and `messages` are all in the frozen
 * schema, but no module writes to them yet and chat was not assigned to anyone
 * for the interim. This renders the two-pane layout from the Figma with sample
 * threads so the topbar's envelope and every "Message donor" style button has
 * somewhere to land.
 */
class MessageController extends Controller
{
    public function index(): void
    {
        $role = Auth::role();

        $this->view('messages.index', [
            'title'    => 'Messages',
            'threads'  => SampleData::threads($role),
            'bubbles'  => SampleData::conversation($role),
            'isAdmin'  => $role === ROLE_ADMIN,
        ]);
    }
}
