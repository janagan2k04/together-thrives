<?php
namespace Controllers\Donation;

use Core\Controller;
use Core\Session;
use Models\Donation\DonationRequest;
use Models\Donation\ItemReservation;
use Models\Donation\RequestedItem;
use Models\Notification;

/**
 * Reserving items against a donation request - the Reserve button on each row of
 * the Items Needed panel (frame 2 of Donation-User.svg) - and the requester
 * confirming that what was promised arrived.
 *
 * The reservation and `requested_items.qty_reserved` move together inside one
 * transaction, and the guard against over-reserving is a condition on the UPDATE
 * rather than a check in PHP: two donors reserving the last of something at the
 * same moment is a race, and only the database can settle it. See
 * Models\Donation\ItemReservation::reserve().
 *
 * The item id posted by the form is checked back to the request in the URL. A
 * donor could otherwise reserve against one request's item while looking at
 * another's page.
 */
class ItemReservationController extends Controller
{
    private ItemReservation $reservations;

    public function __construct()
    {
        $this->reservations = new ItemReservation();
    }

    /** CREATE - the donor reserves a quantity of one requested item. */
    public function store(string $id): void
    {
        // Only an approved request can be reserved against - one still waiting
        // on an administrator is not public.
        $request = (new DonationRequest())->findDetail((int) $id);
        if (!$request || !in_array($request['status'], DonationRequest::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Donation request not found.');
        }

        if ($request['request_type'] !== 'items') {
            $this->abort(404, 'This request does not take item donations.');
        }

        $data = $this->validate([
            'requested_item_id' => 'required|integer',
            'quantity'          => 'required|integer|min_value:1',
        ]);

        $item = (new RequestedItem())->find((int) $data['requested_item_id']);
        if (!$item || (int) $item['request_id'] !== (int) $request['id']) {
            $this->abort(404, 'That item is not on this request.');
        }

        $reservationId = $this->reservations->reserve(
            (int) $item['id'],
            (int) auth()['id'],
            (int) $data['quantity']
        );

        if ($reservationId === 0) {
            $remaining = max(0, (int) $item['qty_required'] - (int) $item['qty_reserved']);
            Session::flash('error', $remaining > 0
                ? 'Only ' . $remaining . ' ' . $item['unit'] . ' of ' . $item['item_name'] . ' are still needed.'
                : $item['item_name'] . ' is fully reserved already.');
            $this->redirect('/donations/' . $id);
        }

        (new Notification())->create([
            'user_id'  => (int) $request['requester_id'],
            'title'    => 'An item was reserved',
            'body'     => (auth()['full_name'] ?? 'A donor') . ' reserved '
                          . $data['quantity'] . ' ' . $item['unit'] . ' of ' . $item['item_name']
                          . ' for ' . $request['title'] . '.',
            'category' => 'donation',
            'link'     => '/my/donations/' . $request['id'],
        ]);

        Session::flash('success', 'Reserved. The requester can see your promise and will confirm it '
            . 'when the items arrive.');
        $this->redirect('/donations/' . $id);
    }

    /**
     * DELETE, donor side - one reservation is withdrawn.
     *
     * Ownership is the donor's, not the requester's: this is their own promise.
     * `ItemReservation::cancel()` re-checks both the owner and the state inside
     * the UPDATE, so a replayed form gives back the quantity once and no more.
     */
    public function cancel(string $id, string $reservationId): void
    {
        $row = $this->reservations->findWithRequest((int) $reservationId);

        if (!$row || (int) $row['request_id'] !== (int) $id) {
            $this->abort(404, 'Reservation not found.');
        }

        if ((int) $row['donor_id'] !== (int) auth()['id']) {
            $this->abort(403, 'You can only withdraw your own reservations.');
        }

        if (!$this->reservations->cancel((int) $row['id'], (int) auth()['id'])) {
            Session::flash('info', $row['status'] === 'reserved'
                ? 'That reservation has already been withdrawn.'
                : 'That donation has already been received, so it cannot be withdrawn.');
            $this->redirect('/donations/' . $id);
        }

        (new Notification())->create([
            'user_id'  => (int) $row['requester_id'],
            'title'    => 'A reservation was withdrawn',
            'body'     => (auth()['full_name'] ?? 'A donor') . ' withdrew ' . $row['quantity'] . ' '
                          . $row['unit'] . ' of ' . $row['item_name'] . ' from ' . $row['title']
                          . '. The quantity is needed again.',
            'category' => 'donation',
            'link'     => '/my/donations/' . $row['request_id'],
        ]);

        Session::flash('success', 'Reservation withdrawn. The quantity is back on the request.');
        $this->redirect('/donations/' . $id);
    }

    /**
     * The donor leaves a request altogether - every promise of theirs on it that
     * has not been received yet goes back.
     *
     * One button rather than pressing Undo down a list, because "I cannot help
     * with this after all" is one decision. Anything the requester has already
     * confirmed stays: those goods arrived.
     */
    public function leave(string $id): void
    {
        $request = (new DonationRequest())->findDetail((int) $id);
        if (!$request || !in_array($request['status'], DonationRequest::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Donation request not found.');
        }

        $donorId = (int) auth()['id'];
        $mine    = $this->reservations->forDonorAndRequest($donorId, (int) $request['id']);
        $given   = 0;

        foreach ($mine as $reservation) {
            if ($this->reservations->cancel((int) $reservation['id'], $donorId)) {
                $given++;
            }
        }

        if ($given === 0) {
            Session::flash('info', 'You have nothing left to withdraw from this request.');
            $this->redirect('/donations/' . $id);
        }

        (new Notification())->create([
            'user_id'  => (int) $request['requester_id'],
            'title'    => 'A donor withdrew from your request',
            'body'     => (auth()['full_name'] ?? 'A donor') . ' withdrew ' . $given
                          . ($given === 1 ? ' reservation' : ' reservations') . ' from '
                          . $request['title'] . '. Those quantities are needed again.',
            'category' => 'donation',
            'link'     => '/my/donations/' . $request['id'],
        ]);

        Session::flash('success', $given === 1
            ? 'You have left this request. Your reservation has gone back.'
            : 'You have left this request. All ' . $given . ' of your reservations have gone back.');
        $this->redirect('/donations/' . $id);
    }

    /**
     * The requester marks a reservation as received.
     *
     * Ownership is checked against the request the item belongs to, not against
     * the reservation - the donor owns the promise, the requester owns the
     * confirmation.
     */
    public function confirm(string $id, string $reservationId): void
    {
        $row = $this->reservations->findWithRequest((int) $reservationId);

        if (!$row || (int) $row['request_id'] !== (int) $id) {
            $this->abort(404, 'Reservation not found.');
        }

        if ((int) $row['requester_id'] !== (int) auth()['id'] && !role_is(ROLE_ADMIN)) {
            $this->abort(403, 'You can only confirm deliveries to your own requests.');
        }

        if ($row['status'] === 'confirmed') {
            Session::flash('info', 'That delivery is already confirmed.');
            $this->redirect('/my/donations/' . $id);
        }

        $this->reservations->confirm((int) $row['id'], (int) auth()['id']);

        (new Notification())->create([
            'user_id'  => (int) $row['donor_id'],
            'title'    => 'Your donation was received',
            'body'     => $row['title'] . ' confirmed your ' . $row['quantity'] . ' '
                          . $row['unit'] . ' of ' . $row['item_name'] . '. Thank you.',
            'category' => 'donation',
            'link'     => '/donations/' . $id,
        ]);

        Session::flash('success', 'Delivery confirmed.');
        $this->redirect('/my/donations/' . $id);
    }
}
