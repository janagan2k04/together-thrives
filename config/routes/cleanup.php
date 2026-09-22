<?php
/**
 * MEMBER 1 - Cleanup / Volunteering.  CRUD entity: Cleanup Event.
 * Only Member 1 edits this file.
 *
 * Order matters: the router returns the first pattern that matches, so every
 * literal path under /cleanup is registered before /cleanup/{id}, or "create"
 * and "certificate" would be read as campaign ids.
 */
use Core\Router;

return function (Router $r) {

    // Organization landing page. Auth::homeFor() sends organizations here after
    // login, so this route must exist. Real content lands in weeks 3-5.
    $r->get('/org/dashboard', 'HomeController@dashboard', ['auth', 'role:organization']);

    // ---------- community / guest browsing ----------

    /*
     * `block_role:grama_niladhari`, 2026-08-27, cross-module and agreed before
     * it was made - tell Dilsha before pulling.
     *
     * A Grama Niladhari officer HOSTS cleanup campaigns as of that date; the
     * officer does not volunteer for someone else's. "Cleanup Campaigns" is
     * gone from sidebar-gn.php and "My Cleanup Campaigns" (/org/cleanup) takes
     * its place. The block is what makes that stick: hiding a link leaves the
     * URL open, and /cleanup is public so an allowlist would have shut guests
     * out along with the officer. Same shape as the blood and reuse blocks.
     */
    $r->get('/cleanup', 'Cleanup\CleanupEventController@index', ['block_role:grama_niladhari']);

    // Community proposal form - registered before /cleanup/{id}.
    $r->group('/cleanup', ['auth', 'role:community'], function (Router $r) {
        $r->get('/create', 'Cleanup\CleanupEventController@create');
    });
    $r->post('/cleanup', 'Cleanup\CleanupEventController@store',
             ['auth', 'role:community', 'csrf']);

    // The appreciation certificate - a printable A4 sheet on the blank layout.
    $r->get('/cleanup/certificate/{id}', 'Cleanup\CertificateController@show', ['auth']);

    // Volunteer sign-up and feedback. The GET forms sit beside the POSTs that
    // handle them, and all four are above the /cleanup/{id} catch-all.
    $r->group('/cleanup', ['auth', 'role:community'], function (Router $r) {
        $r->get('/{id}/join',      'Cleanup\VolunteerController@form');
        $r->post('/{id}/join',     'Cleanup\VolunteerController@join', ['csrf']);
        // Withdrawing. A volunteer who signed up has to be able to say they can
        // no longer come, or their place is held against a campaign that is full.
        $r->post('/{id}/leave',    'Cleanup\VolunteerController@leave', ['csrf']);
        $r->get('/{id}/feedback',  'Cleanup\FeedbackController@form');
        $r->post('/{id}/feedback', 'Cleanup\FeedbackController@store', ['csrf']);
    });

    $r->get('/cleanup/{id}', 'Cleanup\CleanupEventController@show', ['block_role:grama_niladhari']);

    // ---------- organization and GS officer: full CRUD ----------

    /*
     * `grama_niladhari` added 2026-08-27, cross-module and agreed before it was
     * made - tell Dilsha before pulling.
     *
     * `cleanup_events.organization_id` is foreign-keyed to `organizations(id)`
     * and a GS account registers into `grama_niladharis`, so the controller
     * gives the officer a campaign-owner row in `organizations` the first time
     * they post here - see CleanupEventController::organisationOrFail(). Every
     * query in this module is untouched; the officer simply owns a row like any
     * other organiser.
     */
    $r->group('/org/cleanup', ['auth', 'role:organization,grama_niladhari', 'verified'], function (Router $r) {
        $r->get('',                 'Cleanup\CleanupEventController@manage');     // READ  (list)
        $r->get('/create',          'Cleanup\CleanupEventController@createOrg');  // CREATE form
        $r->post('',                'Cleanup\CleanupEventController@storeOrg', ['csrf']);
        $r->get('/{id}',            'Cleanup\CleanupEventController@detail');     // READ  (detail)
        $r->get('/{id}/edit',       'Cleanup\CleanupEventController@edit');       // UPDATE form
        $r->put('/{id}',            'Cleanup\CleanupEventController@update', ['csrf']);
        $r->delete('/{id}',         'Cleanup\CleanupEventController@destroy', ['csrf']); // DELETE (cancel)
        $r->post('/{id}/waste',     'Cleanup\CleanupEventController@storeWaste', ['csrf']);
        $r->get('/{id}/volunteers', 'Cleanup\AttendanceController@index');

        /*
         * The three writes the attendance screen owns, added 2026-09-04 with the
         * volunteer flow. Cross-module and agreed before it was made - tell
         * Dilsha before pulling.
         *
         * `event_registrations` is written by the volunteer at /cleanup/{id}/join;
         * these confirm one, check people in and place them in a work area. Every
         * one re-checks that the registration in the form belongs to the campaign
         * in the URL, so an organiser cannot reach another organiser's rows.
         */
        $r->post('/{id}/volunteers/{registration}/approve',
                 'Cleanup\AttendanceController@approve', ['csrf']);
        $r->post('/{id}/check-in',    'Cleanup\AttendanceController@checkIn',   ['csrf']);
        $r->post('/{id}/work-areas',  'Cleanup\AttendanceController@storeArea', ['csrf']);
        $r->post('/{id}/assign-area', 'Cleanup\AttendanceController@assignArea',['csrf']);
    });

    // ---------- administrator: the approval queue this module depends on ----------
    //
    // A campaign is created `draft` and only an administrator can publish it, so
    // without this screen /cleanup would stay empty for ever. It is declared here
    // rather than in routes/admin.php for the same reason M2, M3 and M4 declare
    // theirs in their own files - one file per member, no merge conflicts.
    $r->group('/admin/cleanup', ['auth', 'role:admin'], function (Router $r) {
        $r->get('',              'Cleanup\CampaignReviewController@index');
        $r->post('/{id}/approve', 'Cleanup\CampaignReviewController@approve', ['csrf']);
        $r->post('/{id}/reject',  'Cleanup\CampaignReviewController@reject',  ['csrf']);
    });
};
