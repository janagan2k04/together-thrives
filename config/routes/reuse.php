<?php
/**
 * MEMBER 4 — Reuse Items.  CRUD entity: Reuse Item Listing.
 * Only Member 4 edits this file.
 */
use Core\Router;

return function (Router $r) {

    // Grama Niladhari landing page. Auth::homeFor() sends GN officers here after
    // login, so this route must exist. Real content lands in weeks 3–5.
    $r->get('/gn/dashboard', 'HomeController@dashboard', ['auth', 'role:grama_niladhari']);

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
};
