<?php
/**
 * MEMBER 3 — Blood Donation.  CRUD entity: Blood Campaign.
 * Only Member 3 edits this file.
 *
 * Week 3 note: the routes below the original block were added to cover the
 * remaining frames in UI/Blood — critical requests, the medical screening form
 * and the appreciation certificate. Every controller they name exists. The
 * original six CRUD routes are unchanged.
 */
use Core\Router;

return function (Router $r) {

    // Blood bank landing page. Auth::homeFor() sends blood banks here after
    // login, so this route must exist.
    $r->get('/blood-bank/dashboard', 'HomeController@dashboard', ['auth', 'role:blood_bank']);

    /* ---------------- community: browse ---------------- */

    // The hub. Five tabs, chosen with ?tab= so each one is a shareable URL.
    $r->get('/blood', 'Blood\BloodCampaignController@index');

    // Longer literal paths are declared before /blood/{id} so the placeholder
    // never swallows them.
    $r->get('/blood/critical/create', 'Blood\CriticalRequestController@create', ['auth']);
    $r->post('/blood/critical',       'Blood\CriticalRequestController@store',  ['auth', 'csrf']);
    $r->get('/blood/critical/{id}',   'Blood\CriticalRequestController@show',   ['auth']);
    $r->post('/blood/critical/{id}/respond', 'Blood\CriticalRequestController@respond', ['auth', 'csrf']);

    $r->get('/blood/screening/submitted', 'Blood\ScreeningController@submitted', ['auth']);
    $r->get('/blood/certificate/{id}',    'Blood\CertificateController@show',    ['auth']);

    $r->get('/blood/{id}', 'Blood\BloodCampaignController@show');

    /* ---------------- community: join and screening ---------------- */

    $r->group('/blood', ['auth'], function (Router $r) {
        $r->post('/{id}/join',      'Blood\BloodCampaignController@join', ['csrf']);
        $r->get('/{id}/screening',  'Blood\ScreeningController@show');
        $r->post('/{id}/screening', 'Blood\ScreeningController@submit', ['csrf']);

        // Same form, reached from a critical request instead of a camp.
        $r->get('/critical/{id}/screening',  'Blood\ScreeningController@showForRequest');
        $r->post('/critical/{id}/screening', 'Blood\ScreeningController@submitForRequest', ['csrf']);
    });

    /* ---------------- organiser: full CRUD ---------------- */

    // blood bank + organization
    $r->group('/my/blood', ['auth', 'role:blood_bank,organization', 'verified'], function (Router $r) {
        $r->get('',           'Blood\BloodCampaignController@manage');  // READ list
        $r->get('/create',    'Blood\BloodCampaignController@create');  // CREATE
        $r->post('',          'Blood\BloodCampaignController@store', ['csrf']);
        $r->get('/{id}',      'Blood\BloodCampaignController@detail');  // READ one
        $r->get('/{id}/edit', 'Blood\BloodCampaignController@edit');    // UPDATE
        $r->put('/{id}',      'Blood\BloodCampaignController@update', ['csrf']);
        $r->delete('/{id}',   'Blood\BloodCampaignController@destroy', ['csrf']); // DELETE
    });
};
