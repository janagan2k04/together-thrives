<?php
/**
 * MEMBER 3 — Blood Donation.  CRUD entity: Blood Campaign.
 * Only Member 3 edits this file.
 */
use Core\Router;

return function (Router $r) {

    // Blood bank landing page. Auth::homeFor() sends blood banks here after
    // login, so this route must exist. Real content lands in weeks 3–5.
    $r->get('/blood-bank/dashboard', 'HomeController@dashboard', ['auth', 'role:blood_bank']);

    $r->get('/blood',      'Blood\BloodCampaignController@index');
    $r->get('/blood/{id}', 'Blood\BloodCampaignController@show');

    // blood bank + organization: full CRUD
    $r->group('/my/blood', ['auth', 'role:blood_bank,organization', 'verified'], function (Router $r) {
        $r->get('',           'Blood\BloodCampaignController@manage');  // READ
        $r->get('/create',    'Blood\BloodCampaignController@create');  // CREATE
        $r->post('',          'Blood\BloodCampaignController@store', ['csrf']);
        $r->get('/{id}/edit', 'Blood\BloodCampaignController@edit');    // UPDATE
        $r->put('/{id}',      'Blood\BloodCampaignController@update', ['csrf']);
        $r->delete('/{id}',   'Blood\BloodCampaignController@destroy', ['csrf']); // DELETE
    });

    $r->group('/blood', ['auth'], function (Router $r) {
        $r->get('/{id}/screening',  'Blood\ScreeningController@show');
        $r->post('/{id}/screening', 'Blood\ScreeningController@submit', ['csrf']);
    });
};
