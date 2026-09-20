<?php
namespace Controllers\Donation;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Donation\DonationRequest;
use Models\Donation\RequestedItem;
use Services\ContentVerificationService;

/**
 * MEMBER 2 - administrator review of donation requests.
 *
 * A new request is created `pending` and is invisible to donors: the create
 * form, the requester's detail screens and the public list all promise that an
 * administrator fact-checks an appeal before anyone is asked to give to it.
 * This is the screen that keeps the promise - without it `open` would be
 * unreachable and the public list would stay empty forever.
 *
 * Rejecting takes a reason, because `donation_requests.reject_reason` exists and
 * the requester's detail screen prints it. Approving does not: nothing shows an
 * approval note.
 *
 * It is an admin screen, but donation-module work, so it lives under
 * Controllers\Donation and routes from routes/donation.php - the same call
 * Member 4 made for the GS letter queue and Member 3 for blood campaigns, and it
 * means no other member's file has to change.
 */
class RequestReviewController extends Controller
{
    private DonationRequest $requests;
    private RequestedItem   $items;

    public function __construct()
    {
        $this->requests = new DonationRequest();
        $this->items    = new RequestedItem();
    }

    public function index(): void
    {
        $status = (string) Request::get('status', REQUEST_PENDING);
        if (!in_array($status, [REQUEST_PENDING, REQUEST_OPEN, MOD_REJECTED], true)) {
            $status = REQUEST_PENDING;
        }

        $rows = $this->requests->queue($status);

        $this->view('donation.admin-requests', [
            'title'    => 'Donation Request Review',
            'requests' => $rows,
            'itemsBy'  => $this->items->forRequests(array_column($rows, 'id')),
            'status'   => $status,
            'pending'  => $this->requests->pendingCount(),
            'statuses' => DonationRequest::STATUSES,
        ]);
    }

    /*
     * Both decisions go through ContentVerificationService, not through the
     * model directly. Added 2026-08-18: writing a status here and stopping meant
     * the author was never told their post had been decided on, and the unified
     * queue at /admin/verification would have been a second implementation of
     * the same rule. One service, two doors into it.
     */
    public function approve(string $id): void
    {
        $request = $this->pendingOrFail($id);

        (new ContentVerificationService())->approve('donation', (int) $request['id'], (int) auth()['id']);

        Session::flash('success', 'Request approved. Donors can now see it, and the requester has been notified.');
        $this->redirect('/admin/donations');
    }

    public function reject(string $id): void
    {
        $request = $this->pendingOrFail($id);
        $data    = $this->validate(['reject_reason' => 'required|max:255']);

        (new ContentVerificationService())
            ->reject('donation', (int) $request['id'], (int) auth()['id'], $data['reject_reason']);

        Session::flash('success', 'Request rejected. The requester has been notified and can edit it and resubmit.');
        $this->redirect('/admin/donations');
    }

    /**
     * Both decisions must refuse a request that has already been decided - a
     * second decision would silently overwrite the first and contradict whatever
     * the requester was already told.
     */
    private function pendingOrFail(string $id): array
    {
        $request = $this->requests->find((int) $id);
        if (!$request) {
            $this->abort(404, 'Donation request not found.');
        }

        if ($request['status'] !== REQUEST_PENDING) {
            Session::flash('error', 'That request has already been reviewed.');
            $this->redirect('/admin/donations');
        }

        return $request;
    }
}
