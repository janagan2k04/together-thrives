<?php
namespace Controllers\Reuse;

use Controllers\FileController;
use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Reuse\ItemImage;
use Models\Reuse\ItemRequest;
use Models\Reuse\ReuseItem;
use Services\ItemRequestService;

/**
 * Requests against a reuse listing: a member asks, the donor decides.
 *
 * Everything a donor can do here is scoped to their own listing, and
 * everything a requester can do is scoped to their own request.
 */
class ItemRequestController extends Controller
{
    private ReuseItem          $items;
    private ItemRequest        $requests;
    private ItemImage          $images;
    private ItemRequestService $service;

    public function __construct()
    {
        $this->items    = new ReuseItem();
        $this->requests = new ItemRequest();
        $this->images   = new ItemImage();
        $this->service  = new ItemRequestService();
    }

    // ---------- requester ----------

    public function store(string $itemId): void
    {
        $item = $this->items->findDetail((int) $itemId);
        if (!$item) {
            $this->abort(404, 'That listing is no longer available.');
        }

        $requesterId = (int) auth()['id'];

        // Checked again here, not only when drawing the button: the form can
        // be replayed, and the item may have been taken in the meantime.
        if ($reason = $this->service->blockedReason($item, $requesterId)) {
            Session::flash('error', $reason);
            $this->redirect('/reuse/' . $item['id']);
        }

        $data = $this->validate([
            'reason'               => 'max:1000',
            'is_disaster_affected' => 'in:0,1',
        ]);

        $letter = Request::file('gs_letter');

        try {
            $this->service->create($item, $requesterId, $data, $letter);
        } catch (\RuntimeException $e) {
            // A rejected GS letter — wrong type or too large.
            Session::flashInput($data, ['gs_letter' => [$e->getMessage()]]);
            Session::flash('error', $e->getMessage());
            $this->redirect('/reuse/' . $item['id']);
        }

        Session::flash('success', 'Request sent. The donor will be in touch.');
        $this->redirect('/reuse/' . $item['id']);
    }

    /**
     * The recipient rates the donor, from My Activities.
     *
     * Keyed on the request id rather than the item id: the request is what
     * proves this account was the one given the item, and it is derived here
     * rather than trusted from the form.
     */
    public function feedback(string $requestId): void
    {
        $request = $this->requests->find((int) $requestId);
        if (!$request) {
            $this->abort(404, 'Request not found.');
        }

        $item = $this->items->findAny((int) $request['item_id']);
        if (!$item) {
            $this->abort(404, 'Listing not found.');
        }

        $recipientId = (int) auth()['id'];

        // Checked here rather than only when drawing the form — the form can be
        // replayed, and a second review would inflate the donor's rating.
        if ($reason = $this->service->feedbackBlockedReason($item, $request, $recipientId)) {
            Session::flash('error', $reason);
            $this->redirect('/my/activities');
        }

        $data = $this->validate([
            'rating'  => 'required|in:1,2,3,4,5',
            'comment' => 'max:500',
        ]);

        $this->service->leaveFeedback(
            $item,
            $recipientId,
            (int) $data['rating'],
            $data['comment'] ?? null
        );

        Session::flash('success', 'Thank you — your review is now on the donor’s profile.');
        $this->redirect('/my/activities');
    }

    // ---------- donor ----------

    public function index(string $itemId): void
    {
        $item = $this->ownedOrFail((int) $itemId);

        $this->view('reuse.requests', [
            'title'    => 'Requests for ' . $item['title'],
            'item'     => $item,
            'requests' => $this->requests->forItem((int) $item['id']),
            'cover'    => $this->images->primaryFor((int) $item['id']),
        ]);
    }

    public function select(string $requestId): void
    {
        [$item, $request] = $this->pendingPairOrFail((int) $requestId);

        $this->service->select($item, $request);

        Session::flash('success', 'Recipient selected. The item is now reserved for them.');
        $this->redirect('/my/reuse/' . $item['id'] . '/requests');
    }

    public function reject(string $requestId): void
    {
        [$item, $request] = $this->pendingPairOrFail((int) $requestId);

        $this->service->reject($item, $request);

        Session::flash('success', 'Request turned down.');
        $this->redirect('/my/reuse/' . $item['id'] . '/requests');
    }

    /** The donor confirms the item actually changed hands. */
    public function handover(string $requestId): void
    {
        [$item, $request] = $this->pairOrFail((int) $requestId);

        if ($request['status'] !== 'selected') {
            Session::flash('error', 'Select a recipient before marking the item as handed over.');
            $this->redirect('/my/reuse/' . $item['id'] . '/requests');
        }

        $this->service->markDonated($item, $request);

        Session::flash('success', 'Marked as donated. Thank you for giving it a second life.');
        $this->redirect('/my/reuse/' . $item['id'] . '/requests');
    }

    // ---------- guards ----------

    /** A listing the signed-in account is allowed to manage. */
    private function ownedOrFail(int $itemId): array
    {
        $item = $this->items->find($itemId);
        if (!$item) {
            $this->abort(404, 'Listing not found.');
        }
        if ((int) $item['donor_id'] !== (int) auth()['id'] && !role_is(ROLE_ADMIN)) {
            $this->abort(403, 'You can only manage requests for your own listings.');
        }
        return $item;
    }

    /**
     * Resolve a request id to [item, request], having proved the caller owns
     * the listing it belongs to. The request id comes from a form, so the
     * listing is derived from it rather than trusted alongside it.
     *
     * @return array{0: array, 1: array}
     */
    private function pairOrFail(int $requestId): array
    {
        $request = $this->requests->find($requestId);
        if (!$request) {
            $this->abort(404, 'Request not found.');
        }
        return [$this->ownedOrFail((int) $request['item_id']), $request];
    }

    /** @return array{0: array, 1: array} */
    private function pendingPairOrFail(int $requestId): array
    {
        [$item, $request] = $this->pairOrFail($requestId);

        if ($request['status'] !== 'pending') {
            Session::flash('error', 'That request has already been answered.');
            $this->redirect('/my/reuse/' . $item['id'] . '/requests');
        }
        return [$item, $request];
    }

    /**
     * A GS letter is identity evidence: only the member who uploaded it, the
     * donor deciding on that request, and an administrator may open it.
     */
    public function letter(string $requestId): void
    {
        $request = $this->requests->find((int) $requestId);
        if (!$request || empty($request['gs_verification_id'])) {
            $this->abort(404);
        }

        $item = $this->items->find((int) $request['item_id']);
        $mayRead = role_is(ROLE_ADMIN)
            || (int) $request['requester_id'] === (int) auth()['id']
            || ($item && (int) $item['donor_id'] === (int) auth()['id']);

        if (!$mayRead) {
            $this->abort(403, 'You do not have permission to view this letter.');
        }

        $letter = (new \Models\Reuse\GsVerification())->find((int) $request['gs_verification_id']);
        if (!$letter) {
            $this->abort(404);
        }

        (new FileController())->stream($letter['letter_path']);
    }
}
