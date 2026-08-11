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

        // still to be built — see app/controllers/Admin/PlaceholderController.php
        $r->get('/suspended',     'Admin\PlaceholderController@suspended');
        $r->get('/complaints',    'Admin\PlaceholderController@complaints');
        $r->get('/announcements', 'Admin\PlaceholderController@announcements');
        $r->get('/dengue',        'Admin\PlaceholderController@dengue');
        $r->get('/admins',        'Admin\PlaceholderController@admins');
        $r->get('/logs',          'Admin\PlaceholderController@logs');
        $r->get('/analytics',     'Admin\PlaceholderController@analytics');
        $r->get('/settings',      'Admin\PlaceholderController@settings');
    });
};
