<?php
/**
 * MEMBER 3 - Blood Donation.  CRUD entity: Blood Campaign.
 * Only Member 3 edits this file.
 *
 * Week 3 note: the routes below the original block were added to cover the
 * remaining frames in UI/Blood - critical requests, the medical screening form
 * and the appreciation certificate. Every controller they name exists. The
 * original six CRUD routes are unchanged.
 */
use Core\Router;

return function (Router $r) {

    // Blood bank landing page. Auth::homeFor() sends blood banks here after
    // login, so this route must exist.
    $r->get('/blood-bank/dashboard', 'HomeController@dashboard', ['auth', 'role:blood_bank']);

    /* ---------------- community: browse ---------------- */

    /*
     * Everything under /blood is the DONOR's side of the module - browse a camp,
     * join it, answer the screening form, collect the certificate. Only a person
     * donates blood, so `block_role:blood_bank,organization,grama_niladhari`
     * closes that subtree
     * to both institutional roles - every route except the two that CREATE a
     * critical request, noted below. What each of them organises lives under
     * /my/blood, and /blood-bank/dashboard above; both untouched.
     *
     * It has to be a denial rather than `role:`: these pages are public, and an
     * allowlist would have shut signed-out visitors out along with them. Keyed
     * on the role, so every such account registered from here on inherits it.
     *
     * Organizations were added on 2026-08-18 by group decision, the same call
     * that closed reuse to them. Grama Niladhari officers followed on
     * 2026-08-27: an officer runs camps for their division at /my/blood rather
     * than donating at someone else's, and "Blood Donation" is gone from
     * sidebar-gn.php in favour of "My Blood Campaigns".
     */
    // The hub. Five tabs, chosen with ?tab= so each one is a shareable URL.
    $r->get('/blood', 'Blood\BloodCampaignController@index', ['block_role:blood_bank,organization,grama_niladhari']);

    // Longer literal paths are declared before /blood/{id} so the placeholder
    // never swallows them.
    //
    // The two creation routes are the exception to the block above: posting an
    // urgent need is a REQUESTER action, not a donor one, and a blood bank or
    // an organization is the caller most likely to have one. /my/blood/create
    // links straight here for exactly that reason. Only the donor-facing half of
    // the critical request - reading one, answering "I can donate" - stays
    // closed to them.
    //
    // A Grama Niladhari officer is NOT part of that exception. The group opened
    // blood camps to the role on 2026-08-27 and stopped there: an officer hosts
    // a camp, an urgent patient-side appeal is somebody else's to raise. The
    // Campaign Type switch on /my/blood/create hides the option for the role,
    // and this is what makes hiding it mean something.
    $r->get('/blood/critical/create', 'Blood\CriticalRequestController@create', ['auth', 'block_role:grama_niladhari']);
    $r->post('/blood/critical',       'Blood\CriticalRequestController@store',  ['auth', 'csrf', 'block_role:grama_niladhari']);
    $r->get('/blood/critical/{id}',   'Blood\CriticalRequestController@show',   ['auth', 'block_role:blood_bank,organization,grama_niladhari']);
    $r->post('/blood/critical/{id}/respond', 'Blood\CriticalRequestController@respond', ['auth', 'csrf', 'block_role:blood_bank,organization,grama_niladhari']);

    $r->get('/blood/screening/submitted', 'Blood\ScreeningController@submitted', ['auth', 'block_role:blood_bank,organization,grama_niladhari']);
    $r->get('/blood/certificate/{id}',    'Blood\CertificateController@show',    ['auth', 'block_role:blood_bank,organization,grama_niladhari']);

    $r->get('/blood/{id}', 'Blood\BloodCampaignController@show', ['block_role:blood_bank,organization,grama_niladhari']);

    /* ---------------- community: join and screening ---------------- */

    $r->group('/blood', ['auth', 'block_role:blood_bank,organization,grama_niladhari'], function (Router $r) {
        $r->post('/{id}/join',      'Blood\BloodCampaignController@join', ['csrf']);
        $r->get('/{id}/screening',  'Blood\ScreeningController@show');
        $r->post('/{id}/screening', 'Blood\ScreeningController@submit', ['csrf']);

        // Same form, reached from a critical request instead of a camp.
        $r->get('/critical/{id}/screening',  'Blood\ScreeningController@showForRequest');
        $r->post('/critical/{id}/screening', 'Blood\ScreeningController@submitForRequest', ['csrf']);
    });

    /* ---------------- organiser: full CRUD ---------------- */

    /*
     * blood bank + organization + Grama Niladhari officer.
     *
     * `grama_niladhari` added 2026-08-27, cross-module and agreed before it was
     * made - tell Shenol before pulling. An officer runs camps in their division
     * and does not donate at someone else's, so /blood is blocked for the role
     * above and "My Blood Campaigns" replaces "Blood Donation" in sidebar-gn.php.
     *
     * `blood_campaigns.organizer_id` points at `users(id)`, so nothing in this
     * module needed changing; `organizer_type` records `organization` for an
     * officer, which is the only value the frozen ENUM has that is not
     * `blood_bank`.
     */
    $r->group('/my/blood', ['auth', 'role:blood_bank,organization,grama_niladhari', 'verified'], function (Router $r) {
        $r->get('',           'Blood\BloodCampaignController@manage');  // READ list
        $r->get('/create',    'Blood\BloodCampaignController@create');  // CREATE
        $r->post('',          'Blood\BloodCampaignController@store', ['csrf']);
        $r->get('/{id}',      'Blood\BloodCampaignController@detail');  // READ one
        $r->get('/{id}/edit', 'Blood\BloodCampaignController@edit');    // UPDATE
        $r->put('/{id}',      'Blood\BloodCampaignController@update', ['csrf']);
        $r->delete('/{id}',   'Blood\BloodCampaignController@destroy', ['csrf']); // DELETE

        /*
         * Recording who actually donated, from the organiser's donor table.
         * Added 2026-09-04 with the screening backend. `blood_donation_records`
         * is what My Donations and the appreciation certificate both read, so
         * this is the write that fills them. Cross-module - tell Shenol before
         * pulling.
         */
        $r->post('/{id}/donations', 'Blood\BloodCampaignController@storeDonation', ['csrf']);
    });

    /* ---------------- administrator: campaign review ---------------- */

    /*
     * A campaign is created `pending` and stays invisible to donors until it is
     * approved here - the create form and the public hub both promise that.
     * Without this screen `published` would be unreachable.
     *
     * An admin screen, but blood-module work, so it is declared here rather than
     * in routes/admin.php. Same call Member 4 made for the GS letter queue; no
     * other member's route file changes.
     */
    $r->group('/admin/blood/campaigns', ['auth', 'role:admin'], function (Router $r) {
        $r->get('',               'Blood\CampaignReviewController@index');
        $r->post('/{id}/approve', 'Blood\CampaignReviewController@approve', ['csrf']);
        $r->post('/{id}/reject',  'Blood\CampaignReviewController@reject',  ['csrf']);
    });
};
