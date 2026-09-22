<?php
/**
 * MEMBER 2 - Item & Fund Donation.  CRUD entity: Donation Request.
 * Only Member 2 edits this file.
 *
 * Ownership note: this is Janudi's module. It was built out for week 3 at the
 * project owner's request, the same way M3's blood and M1's cleanup route files
 * were. The six CRUD routes the file already declared are unchanged in shape -
 * `/my/donations` still lists, creates, edits, updates and deletes - and the
 * rest of the Figma's screens have been added around them. Whoever owns it from
 * here is free to change any of it.
 *
 * ROUTE ORDER MATTERS. `Router` compiles `{id}` to `([^/]+)` and matches in
 * declaration order, so every literal path has to be declared before the
 * parameter that would otherwise swallow it - `/my/donations/create` before
 * `/my/donations/{id}`. Getting this wrong is silent: the literal simply starts
 * 404ing as an unknown id.
 */
use Core\Router;

return function (Router $r) {

    // ---- public browse (guests included) --------------------------
    $r->get('/donations', 'Donation\DonationRequestController@index');

    // ---- donor actions on a request (signed in) -------------------
    // Declared before /donations/{id} only for readability; the router would
    // not confuse them anyway, because they carry a second path segment.
    $r->group('/donations', ['auth'], function (Router $r) {
        $r->post('/{id}/reserve',  'Donation\ItemReservationController@store', ['csrf']);
        /*
         * Taking a promise back, 2026-09-13. A donor who reserved 20 packs and
         * then cannot bring them had no way to say so, and the quantity sat
         * against the request until the collection period ran out.
         *
         * Two shapes, because they answer two different sentences: one
         * reservation ("not that one after all") and the whole request ("I
         * cannot help with this at all"). Both refuse anything the requester has
         * already confirmed received.
         */
        $r->post('/{id}/reservations/{reservation}/cancel',
                 'Donation\ItemReservationController@cancel', ['csrf']);
        $r->post('/{id}/leave', 'Donation\ItemReservationController@leave', ['csrf']);
        $r->get('/{id}/checkout',  'Donation\PaymentController@checkout');
        $r->post('/{id}/checkout', 'Donation\PaymentController@pay', ['csrf']);
        /*
         * The receipt is addressed by the DONATION, not the request: a donor who
         * gives to the same appeal twice has two receipts and the second must
         * not overwrite the first. Registered inside this group so it sits above
         * /donations/{id}; the two shapes cannot collide because `receipt` is a
         * literal in different positions.
         */
        $r->get('/receipt/{id}',   'Donation\PaymentController@receipt');
    });

    $r->get('/donations/{id}', 'Donation\DonationRequestController@show');

    // ---- the requester's own workspace ----------------------------
    /*
     * `donation_requests.requester_type` is the ENUM these three roles are
     * written into, so exactly these three roles reach this group. All three
     * sidebars link here.
     *
     * `admin` added 2026-09-13 by group decision, with
     * `database/migrations/2026_09_13_admin_can_request_donations.sql` widening
     * the ENUM to match. An administrator's own request is created `pending` and
     * still has to be approved at /admin/donations like anyone else's.
     */
    $r->group('/my/donations', ['auth', 'role:grama_niladhari,organization,admin', 'verified'], function (Router $r) {
        $r->get('',           'Donation\DonationRequestController@manage');   // READ (list)
        $r->get('/create',    'Donation\DonationRequestController@create');   // CREATE form
        $r->post('',          'Donation\DonationRequestController@store', ['csrf']);
        $r->get('/{id}',      'Donation\DonationRequestController@detail');   // READ (detail)
        $r->get('/{id}/edit', 'Donation\DonationRequestController@edit');     // UPDATE form
        $r->put('/{id}',      'Donation\DonationRequestController@update', ['csrf']);
        $r->delete('/{id}',   'Donation\DonationRequestController@destroy', ['csrf']); // DELETE

        // The requester confirms that a reserved item arrived. Moves
        // `requested_items.qty_received` with the reservation, in one
        // transaction - see Models\Donation\ItemReservation::confirm().
        $r->post('/{id}/reservations/{reservation}/confirm',
                 'Donation\ItemReservationController@confirm', ['csrf']);
    });

    // ---- administrator review ---------------------------------------
    /*
     * A request is created `pending` and stays invisible to donors until it is
     * approved here - the create form and the public list both promise that.
     * Without this screen `open` would be unreachable.
     *
     * An admin screen, but donation-module work, so it is declared here rather
     * than in routes/admin.php. Same call Member 4 made for the GS letter queue
     * and Member 3 for blood campaigns; no other member's route file changes.
     */
    $r->group('/admin/donations', ['auth', 'role:admin'], function (Router $r) {
        $r->get('',               'Donation\RequestReviewController@index');
        $r->post('/{id}/approve', 'Donation\RequestReviewController@approve', ['csrf']);
        $r->post('/{id}/reject',  'Donation\RequestReviewController@reject',  ['csrf']);
    });
};
