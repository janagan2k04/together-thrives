<?php
/**
 * MEMBER 4 — Reuse Items.  CRUD entity: Reuse Item Listing.
 * Only Member 4 edits this file.
 */
use Core\Router;

return function (Router $r) {

    // Grama Niladhari landing page. Auth::homeFor() sends GN officers here after
    // login, so this route must exist.
    $r->get('/gn/dashboard', 'HomeController@dashboard', ['auth', 'role:grama_niladhari']);

    /* ------------------------------------------------------------------
     * SHARED SCREENS — week 3, Member 4.
     *
     * `config/routes/web.php` is on the do-not-edit list in CLAUDE.md, and it
     * already declares /profile, /settings, /notifications, /messages,
     * /organizations and /support. The shared screens it does *not* declare are
     * routed from here — the same reasoning as the GS letter review block at
     * the bottom of this file, so no other member's route file has to change.
     *
     * If the group later agrees to open web.php, these belong there.
     * ------------------------------------------------------------------ */

    $r->post('/settings', 'ProfileController@saveSettings', ['auth', 'csrf']);
    $r->get('/search',    'SearchController@index',         ['auth']);

    // Report a Problem — the topbar's Report link, which used to point at /login.
    $r->get('/report',  'ReportController@problem',       ['auth']);
    $r->post('/report', 'ReportController@submitProblem', ['auth', 'csrf']);

    // Dengue Risk Map — linked from the community, organization and GN sidebars.
    $r->get('/dengue',  'DengueController@index', ['auth']);
    $r->post('/dengue', 'DengueController@store', ['auth', 'csrf']);

    // Supporting the platform, and the sandbox card form every module shares.
    $r->post('/support/donate', 'SupportController@donate', ['auth', 'csrf']);
    $r->get('/checkout',          'CheckoutController@show',    ['auth']);
    $r->post('/checkout',         'CheckoutController@pay',     ['auth', 'csrf']);
    $r->get('/checkout/receipt',  'CheckoutController@receipt', ['auth']);

    // Disaster reporting — sidebar-gn.php has linked at /gn/disasters since
    // week 1 and the link answered 404.
    $r->group('/gn/disasters', ['auth', 'role:grama_niladhari'], function (Router $r) {
        $r->get('',        'GnDisasterController@index');
        $r->get('/create', 'GnDisasterController@create');
        $r->post('',       'GnDisasterController@store', ['csrf']);
        $r->get('/{id}',   'GnDisasterController@show');
    });

    $r->get('/reuse',      'Reuse\ItemListingController@index');
    // Item photos. {id} is the item_images row, not a path — the router
    // compiles {param} to [^/]+, so a stored "items/ab12.jpg" cannot match.
    $r->get('/reuse/image/{id}', 'Reuse\ItemListingController@image');
    $r->get('/reuse/{id}', 'Reuse\ItemListingController@show');

    // Public donor profile — the "View Profile" button on the detail page.
    $r->get('/reuse/donor/{id}', 'Reuse\ItemListingController@donor');

    // donor: full CRUD
    $r->group('/my/reuse', ['auth'], function (Router $r) {
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

    $r->post('/reuse/{id}/request', 'Reuse\ItemRequestController@store', ['auth', 'csrf']);

    /*
     * "My Activities" — the requester's side of the flow, and where a recipient
     * reviews a donor. Both sidebar-community.php and sidebar-org.php have
     * linked here since week 1; until this route existed the link answered 404.
     *
     * The page is not reuse-only by nature — whoever builds cleanup sign-ups or
     * blood appointments should add a section to MyActivityController rather
     * than a second screen.
     */
    $r->group('/my/activities', ['auth'], function (Router $r) {
        $r->get('', 'Reuse\MyActivityController@index');
        $r->post('/{id}/feedback', 'Reuse\ItemRequestController@feedback', ['csrf']);
    });

    /*
     * Administrator review of GS letters. An admin screen, but reuse-module
     * work — routes/admin.php belongs to Member 3 and reviews *accounts*, while
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
