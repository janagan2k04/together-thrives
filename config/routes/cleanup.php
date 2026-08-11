<?php
/**
 * MEMBER 1 — Cleanup / Volunteering.  CRUD entity: Cleanup Event.
 * Only Member 1 edits this file.
 */
use Core\Router;

return function (Router $r) {

    // Organization landing page. Auth::homeFor() sends organizations here after
    // login, so this route must exist. Real content lands in weeks 3–5.
    $r->get('/org/dashboard', 'HomeController@dashboard', ['auth', 'role:organization']);

    // public / guest browsing
    $r->get('/cleanup',            'Cleanup\CleanupEventController@index');
    $r->get('/cleanup/{id}',       'Cleanup\CleanupEventController@show');

    // organization: full CRUD
    $r->group('/org/cleanup', ['auth', 'role:organization', 'verified'], function (Router $r) {
        $r->get('',                'Cleanup\CleanupEventController@manage');   // READ  (list)
        $r->get('/create',         'Cleanup\CleanupEventController@create');   // CREATE form
        $r->post('',               'Cleanup\CleanupEventController@store', ['csrf']);
        $r->get('/{id}/edit',      'Cleanup\CleanupEventController@edit');     // UPDATE form
        $r->put('/{id}',           'Cleanup\CleanupEventController@update', ['csrf']);
        $r->delete('/{id}',        'Cleanup\CleanupEventController@destroy', ['csrf']); // DELETE (cancel)
        $r->get('/{id}/volunteers','Cleanup\AttendanceController@index');
    });

    // community volunteer
    $r->group('/cleanup', ['auth', 'role:community'], function (Router $r) {
        $r->post('/{id}/join',     'Cleanup\VolunteerController@join', ['csrf']);
        $r->post('/{id}/feedback', 'Cleanup\FeedbackController@store', ['csrf']);
    });
};
