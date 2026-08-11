<?php
/**
 * MEMBER 2 — Item & Fund Donation.  CRUD entity: Donation Request.
 * Only Member 2 edits this file.
 */
use Core\Router;

return function (Router $r) {

    $r->get('/donations',      'Donation\DonationRequestController@index');
    $r->get('/donations/{id}', 'Donation\DonationRequestController@show');

    // GN + organization: full CRUD
    $r->group('/my/donations', ['auth', 'role:grama_niladhari,organization', 'verified'], function (Router $r) {
        $r->get('',           'Donation\DonationRequestController@manage');  // READ
        $r->get('/create',    'Donation\DonationRequestController@create');  // CREATE
        $r->post('',          'Donation\DonationRequestController@store', ['csrf']);
        $r->get('/{id}/edit', 'Donation\DonationRequestController@edit');    // UPDATE
        $r->put('/{id}',      'Donation\DonationRequestController@update', ['csrf']);
        $r->delete('/{id}',   'Donation\DonationRequestController@destroy', ['csrf']); // DELETE
    });

    // donors
    $r->group('/donations', ['auth'], function (Router $r) {
        $r->post('/{id}/reserve', 'Donation\ItemReservationController@store', ['csrf']);
        $r->get('/{id}/checkout', 'Donation\PaymentController@checkout');
    });
};
