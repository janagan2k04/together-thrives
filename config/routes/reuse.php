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
    $r->get('/reuse/{id}', 'Reuse\ItemListingController@show');

    // donor: full CRUD
    $r->group('/my/reuse', ['auth'], function (Router $r) {
        $r->get('',           'Reuse\ItemListingController@manage');  // READ
        $r->get('/create',    'Reuse\ItemListingController@create');  // CREATE
        $r->post('',          'Reuse\ItemListingController@store', ['csrf']);
        $r->get('/{id}/edit', 'Reuse\ItemListingController@edit');    // UPDATE
        $r->put('/{id}',      'Reuse\ItemListingController@update', ['csrf']);
        $r->delete('/{id}',   'Reuse\ItemListingController@destroy', ['csrf']); // DELETE
        $r->get('/{id}/requests', 'Reuse\ItemRequestController@index');
    });

    $r->post('/reuse/{id}/request', 'Reuse\ItemRequestController@store', ['auth', 'csrf']);
};
