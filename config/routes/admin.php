<?php
/**
 * ADMIN — verification queue built in week 2 by Member 3.
 *
 * The sections under "still to be built" are real screens in admin.svg that
 * belong to later weeks. They route to a placeholder so the sidebar has no dead
 * links; whoever builds one swaps the target and deletes its line here.
 */
use Core\Router;

return function (Router $r) {
    $r->group('/admin', ['auth', 'role:admin'], function (Router $r) {
        $r->get('/dashboard', 'Admin\AdminDashboardController@index');

        // user & organization management
        $r->get('/users',                  'Admin\UserManagementController@index');
        $r->post('/users/{id}/suspend',    'Admin\UserManagementController@suspend',   ['csrf']);
        $r->post('/users/{id}/reinstate',  'Admin\UserManagementController@reinstate', ['csrf']);
        $r->get('/organizations',          'Admin\OrganizationController@index');

        $r->get('/verification',               'Admin\OrgVerificationController@index');
        $r->get('/verification/{id}',          'Admin\OrgVerificationController@show');
        // Router compiles {param} to ([^/]+), so the shared /file/{path} route
        // cannot match "folder/name.pdf". Serve review documents by account id.
        $r->get('/verification/{id}/document', 'Admin\OrgVerificationController@document');
        $r->post('/verification/{id}/approve', 'Admin\OrgVerificationController@approve', ['csrf']);
        $r->post('/verification/{id}/reject',  'Admin\OrgVerificationController@reject',  ['csrf']);

        /*
         * The remaining eight sidebar screens, built in week 3 by Member 4.
         * They used to point at Admin\PlaceholderController; that controller and
         * its view are gone, as the comment at the top of this file asked.
         * Nothing above this block changed.
         */

        // content moderation
        $r->get('/suspended',          'Admin\ModerationController@suspended');
        $r->get('/complaints',         'Admin\ModerationController@complaints');
        $r->get('/complaints/{id}',    'Admin\ModerationController@complaint');
        $r->post('/complaints/{id}/resolve', 'Admin\ModerationController@resolve', ['csrf']);

        // announcements
        $r->get('/announcements',           'Admin\AnnouncementController@index');
        $r->get('/announcements/create',    'Admin\AnnouncementController@create');
        $r->post('/announcements',          'Admin\AnnouncementController@store', ['csrf']);
        $r->get('/announcements/{id}/edit', 'Admin\AnnouncementController@edit');
        $r->put('/announcements/{id}',      'Admin\AnnouncementController@update', ['csrf']);

        // dengue
        $r->get('/dengue',  'Admin\DengueAlertController@index');
        $r->post('/dengue', 'Admin\DengueAlertController@store', ['csrf']);

        // system management
        $r->get('/admins',    'Admin\SystemController@admins');
        $r->post('/admins',   'Admin\SystemController@storeAdmin', ['csrf']);
        $r->get('/logs',      'Admin\SystemController@logs');
        $r->get('/analytics', 'Admin\SystemController@analytics');
        $r->get('/settings',  'Admin\SystemController@settings');
        $r->post('/settings', 'Admin\SystemController@saveSettings', ['csrf']);
    });
};
