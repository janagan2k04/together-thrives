<?php
/**
 * SHARED SCREENS - owned by the whole group, like web.php.
 *
 * `config/routes/web.php` is on the do-not-edit list in CLAUDE.md, and it
 * already declares /profile, /settings, /notifications, /messages,
 * /organizations and /support. The shared screens it does *not* declare live
 * here.
 *
 * These routes sat in `config/routes/reuse.php` from week 3 until 2026-09-19,
 * because a member's own route file was the only place a new route could go
 * without touching the frozen one. `Core\App` loads every file matching
 * `config/routes/*.php`, so a second shared file needs no change to web.php and
 * no change to any member's file - which is what finally let them move.
 *
 * None of this is reuse-module work: it is the profile photo, the settings
 * write, Report a Problem, the dengue risk map, the sandbox checkout and
 * disaster reporting. Coordinate before editing, the same as web.php.
 */
use Core\Router;

return function (Router $r) {

    // Grama Niladhari landing page. Auth::homeFor() sends GN officers here after
    // login, so this route must exist.
    $r->get('/gn/dashboard', 'HomeController@dashboard', ['auth', 'role:grama_niladhari']);

    $r->post('/settings', 'ProfileController@saveSettings', ['auth', 'csrf']);

    // Profile photo. `users.avatar_path` has been in the frozen schema since
    // week 1 with nothing writing it, so every avatar on the platform was an
    // initial in a circle. /profile itself is declared in web.php.
    $r->post('/profile/avatar', 'ProfileController@updateAvatar', ['auth', 'csrf']);
    $r->post('/profile/avatar/remove', 'ProfileController@removeAvatar', ['auth', 'csrf']);
    // Public, by user id - an avatar is shown to whoever can see the account, so
    // it must not go through upload_url(), which serves the owner and admins
    // only. Same shape as /reuse/image/{id}.
    $r->get('/avatar/{id}', 'ProfileController@avatar');

    // /search is gone with the topbar search box that was its only entry point.
    // Per-page filters (reuse, cleanup volunteers, messages) are unaffected -
    // they post to their own screens, not here.

    // Report a Problem - reached from the sidebar; the topbar no longer repeats it.
    $r->get('/report',  'ReportController@problem',       ['auth']);
    $r->post('/report', 'ReportController@submitProblem', ['auth', 'csrf']);

    // Dengue Risk Map - linked from the community, organization and GN sidebars.
    $r->get('/dengue',  'DengueController@index', ['auth']);
    $r->post('/dengue', 'DengueController@store', ['auth', 'csrf']);
    // Alert photos, served by row id. Everyone signed in reads the same list,
    // so the photos on it are readable by everyone who can see the list -
    // upload_url() would have shown each one only to whoever uploaded it.
    $r->get('/dengue/image/{id}', 'DengueController@image', ['auth']);

    // Supporting the platform, and the sandbox card form every module shares.
    $r->post('/support/donate', 'SupportController@donate', ['auth', 'csrf']);
    $r->get('/checkout',          'CheckoutController@show',    ['auth']);
    $r->post('/checkout',         'CheckoutController@pay',     ['auth', 'csrf']);
    $r->get('/checkout/receipt',  'CheckoutController@receipt', ['auth']);

    /*
     * Disaster reporting - sidebar-gn.php has linked at /gn/disasters since
     * week 1 and the link answered 404.
     *
     * `admin` added 2026-09-13 by group decision: an administrator files a
     * disaster the same way an officer does, and reads every officer's report
     * rather than only their own. GnDisasterController branches on the role for
     * the listing; the create form and the write are identical for both.
     */
    $r->group('/gn/disasters', ['auth', 'role:grama_niladhari,admin'], function (Router $r) {
        $r->get('',        'GnDisasterController@index');
        $r->get('/create', 'GnDisasterController@create');
        $r->post('',       'GnDisasterController@store', ['csrf']);
        $r->get('/{id}',   'GnDisasterController@show');
    });
};
