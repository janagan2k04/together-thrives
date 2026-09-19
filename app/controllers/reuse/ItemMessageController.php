<?php
namespace Controllers\Reuse;

use Core\Controller;
use Core\Session;
use Models\Conversation;
use Models\Message;
use Models\Reuse\ItemRequest;
use Models\Reuse\ReuseItem;
use Services\MessagingService;

/**
 * The conversation between a donor and one person who asked for their item.
 *
 * A donor cannot see a requester's phone number and a requester cannot see the
 * donor's, so until this screen existed the only way to agree a pickup was the
 * one line typed into the request form.
 *
 * The thread is keyed on the request, not on the listing:
 * `conversations.context_type = 'reuse'` and `context_id` = the `item_requests`
 * row, with both accounts as members. `uq_item_requester` makes that unique per
 * pair anyway, and it is what lets the inbox link a thread straight back to the
 * screen it belongs to.
 *
 * Both sides reach the same URL and the same guard - the caller must be either
 * the donor of the listing or the requester on that row, and nobody else,
 * administrators included. A private arrangement between two households is not
 * an admin screen.
 */
class ItemMessageController extends Controller
{
    private ReuseItem        $items;
    private ItemRequest      $requests;
    private Conversation     $conversations;
    private Message          $messages;
    private MessagingService $messaging;

    public function __construct()
    {
        $this->items         = new ReuseItem();
        $this->requests      = new ItemRequest();
        $this->conversations = new Conversation();
        $this->messages      = new Message();
        $this->messaging     = new MessagingService();
    }

    /** The thread. Opening it for the first time is what creates it. */
    public function show(string $requestId): void
    {
        [$item, $request, $otherId] = $this->partyOrFail((int) $requestId);

        $conversationId = $this->messaging->openDirect(
            'reuse',
            (int) $request['id'],
            (int) auth()['id'],
            $otherId,
            $item['title']
        );

        $this->view('reuse.messages', [
            'title'    => 'Messages about ' . $item['title'],
            'item'     => $item,
            'request'  => $request,
            'other'    => $this->conversations->otherMember($conversationId, (int) auth()['id']),
            'messages' => $this->messages->forConversation($conversationId),
            'meId'     => (int) auth()['id'],
        ]);
    }

    /** Send one message into it. */
    public function send(string $requestId): void
    {
        [$item, $request, $otherId] = $this->partyOrFail((int) $requestId);

        $data = $this->validate(['body' => 'required|max:2000']);

        $conversationId = $this->messaging->openDirect(
            'reuse',
            (int) $request['id'],
            (int) auth()['id'],
            $otherId,
            $item['title']
        );

        $this->messaging->send(
            ['id' => $conversationId],
            (int) auth()['id'],
            trim($data['body']),
            '/reuse/requests/' . (int) $requestId . '/messages'
        );

        $this->redirect('/reuse/requests/' . (int) $requestId . '/messages');
    }

    /**
     * Resolve a request id to [listing, request, the other party's user id],
     * having proved the signed-in account is one of the two.
     *
     * findAny() rather than find(): a withdrawn listing is soft deleted, and the
     * two people who were already arranging a handover still need to talk.
     *
     * @return array{0: array, 1: array, 2: int}
     */
    private function partyOrFail(int $requestId): array
    {
        $request = $this->requests->find($requestId);
        if (!$request) {
            $this->abort(404, 'Request not found.');
        }

        $item = $this->items->findAny((int) $request['item_id']);
        if (!$item) {
            $this->abort(404, 'Listing not found.');
        }

        $me        = (int) auth()['id'];
        $donorId   = (int) $item['donor_id'];
        $requester = (int) $request['requester_id'];

        if ($me === $donorId) {
            return [$item, $request, $requester];
        }
        if ($me === $requester) {
            return [$item, $request, $donorId];
        }

        $this->abort(403, 'This conversation is between the donor and the requester.');
    }
}
