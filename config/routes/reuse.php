<?php
/**
 * MEMBER 4 - Reuse Items.  CRUD entity: Reuse Item Listing.
 * Only Member 4 edits this file.
 *
 * The shared screens this file used to carry - the profile photo, the settings
 * write, Report a Problem, the dengue map, the sandbox checkout and disaster
 * reporting - moved to `config/routes/shared.php` on 2026-09-19. They were
 * never reuse work; they were parked here because web.php is frozen and a
 * member could only add a route inside their own file. `Core\App` globs
 * `config/routes/*.php`, so a second shared file costs nothing.
 */
use Core\Router;

return function (Router $r) {

    /*
     * `block_role:blood_bank,organization` - reuse is closed to both roles.
     * These pages are public, so `role:` (an allowlist) would have shut guests
     * out; the rule has to be a denial. It is keyed on the role, not on an
     * account, so every blood bank and organization registered from here on
     * inherits it. Neither sidebar draws a link to any of it either.
     *
     * Organizations were added on 2026-08-18 by group decision: reuse is a
     * person-to-person module - a household passes on something it no longer
     * needs - and an organization coordinates relief rather than taking part in
     * that exchange. Blocking the role rather than hiding the link is what makes
     * it stick; a hidden link is still a reachable URL.
     */
    $r->get('/reuse',      'Reuse\ItemListingController@index', ['block_role:blood_bank,organization']);
    // Item photos. {id} is the item_images row, not a path - the router
    // compiles {param} to [^/]+, so a stored "items/ab12.jpg" cannot match.
    $r->get('/reuse/image/{id}', 'Reuse\ItemListingController@image', ['block_role:blood_bank,organization']);
    $r->get('/reuse/{id}', 'Reuse\ItemListingController@show', ['block_role:blood_bank,organization']);

    // Public donor profile - the "View Profile" button on the detail page.
    $r->get('/reuse/donor/{id}', 'Reuse\ItemListingController@donor', ['block_role:blood_bank,organization']);

    // donor: full CRUD
    $r->group('/my/reuse', ['auth', 'block_role:blood_bank,organization'], function (Router $r) {
        $r->get('',           'Reuse\ItemListingController@manage');  // READ
        $r->get('/create',    'Reuse\ItemListingController@create');  // CREATE
        $r->post('',          'Reuse\ItemListingController@store', ['csrf']);
        $r->get('/{id}/edit', 'Reuse\ItemListingController@edit');    // UPDATE
        $r->put('/{id}',      'Reuse\ItemListingController@update', ['csrf']);
        $r->delete('/{id}',   'Reuse\ItemListingController@destroy', ['csrf']); // DELETE
        $r->post('/{id}/relist', 'Reuse\ItemListingController@relist', ['csrf']); // undo a withdrawal
        $r->get('/{id}/requests', 'Reuse\ItemRequestController@index');

        // Deciding on a request. The listing is derived from the request id,
        // so these sit under /requests/ rather than under an item.
        $r->get('/requests/{id}/letter',    'Reuse\ItemRequestController@letter');
        $r->post('/requests/{id}/select',   'Reuse\ItemRequestController@select',   ['csrf']);
        $r->post('/requests/{id}/reject',   'Reuse\ItemRequestController@reject',   ['csrf']);
        $r->post('/requests/{id}/handover', 'Reuse\ItemRequestController@handover', ['csrf']);
    });

    /*
     * The two halves of one handover, and the thread that arranges it.
     *
     * These sit outside /my/reuse because both parties use them, not only the
     * donor: /my/reuse is the donor's workspace and every route in it goes
     * through ownedOrFail(). Each controller derives the listing from the
     * request id and checks the caller is one of the two people on it.
     *
     * `received` is the requester's side of `handover`. Whichever of the two is
     * holding the item when it moves is the one able to say so, so either may
     * close the donation and the service refuses the second attempt.
     */
    $r->group('/reuse/requests', ['auth', 'block_role:blood_bank,organization'], function (Router $r) {
        $r->post('/{id}/received', 'Reuse\ItemRequestController@received', ['csrf']);
        $r->get('/{id}/messages',  'Reuse\ItemMessageController@show');
        $r->post('/{id}/messages', 'Reuse\ItemMessageController@send', ['csrf']);
    });

    $r->post('/reuse/{id}/request', 'Reuse\ItemRequestController@store',
             ['auth', 'csrf', 'block_role:blood_bank,organization']);

    /*
     * "My Activities" - the requester's side of the flow, and where a recipient
     * reviews a donor. Both sidebar-community.php and sidebar-org.php have
     * linked here since week 1; until this route existed the link answered 404.
     *
     * The page is not reuse-only. As of 2026-09-04 it carries four sections -
     * reuse requests, cleanup registrations, blood camp registrations and
     * donations given - which is the condition this comment used to set for
     * dropping the role block, so the block is gone.
     *
     * It was blocked for blood banks and organizations while it read back reuse
     * requests and nothing else: neither role may make one, so the page rendered
     * empty with every link on it leading to a 403. Both roles can volunteer for
     * a cleanup campaign and donate to a request, so both now have something to
     * read here. An account with nothing simply sees the sections it has, which
     * may be none.
     */
    $r->group('/my/activities', ['auth'], function (Router $r) {
        $r->get('', 'Reuse\MyActivityController@index');
        $r->post('/{id}/feedback', 'Reuse\ItemRequestController@feedback', ['csrf']);
    });

    /*
     * Administrator review of GS letters. An admin screen, but reuse-module
     * work - routes/admin.php belongs to Member 3 and reviews *accounts*, while
     * this reviews evidence attached to a reuse request. Declared here so no
     * other member's route file has to change.
     */
    $r->group('/admin/reuse/letters', ['auth', 'role:admin'], function (Router $r) {
        $r->get('',                'Reuse\GsLetterController@index');
        $r->get('/{id}/file',      'Reuse\GsLetterController@file');
        $r->post('/{id}/approve',  'Reuse\GsLetterController@approve', ['csrf']);
        $r->post('/{id}/reject',   'Reuse\GsLetterController@reject',  ['csrf']);
    });
};
