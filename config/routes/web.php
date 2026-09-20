<?php
/**
 * SHARED ROUTES - owned by the whole group.
 * Coordinate before editing this file. Module routes go in your own file.
 */
use Core\Router;

return function (Router $r) {

    // ---- public ----
    $r->get('/',                 'HomeController@landing');
    $r->get('/about',            'HomeController@about');
    $r->get('/support',          'SupportController@index');
    $r->get('/language/{code}',  'LanguageController@switch');

    // ---- authentication (guest only) ----
    $r->group('', ['guest'], function (Router $r) {
        $r->get('/login',                 'AuthController@showLogin');
        $r->post('/login',                'AuthController@login', ['csrf']);

        $r->get('/register',              'AuthController@chooseType');
        $r->get('/register/community',    'AuthController@showCommunityForm');
        $r->post('/register/community',   'AuthController@registerCommunity', ['csrf']);
        $r->get('/register/organization', 'AuthController@showOrgForm');
        $r->post('/register/organization','AuthController@registerOrganization', ['csrf']);
        $r->get('/register/gn',           'AuthController@showGnForm');
        $r->post('/register/gn',          'AuthController@registerGn', ['csrf']);
        $r->get('/register/blood-bank',   'AuthController@showBloodBankForm');
        $r->post('/register/blood-bank',  'AuthController@registerBloodBank', ['csrf']);

        $r->get('/forgot-password',       'PasswordController@showForgot');
        $r->post('/forgot-password',      'PasswordController@sendLink', ['csrf']);
        $r->get('/reset-password/{token}','PasswordController@showReset');
        $r->post('/reset-password',       'PasswordController@reset', ['csrf']);
    });

    $r->get('/registration-submitted', 'AuthController@submitted');
    $r->get('/verify-email/{token}',   'AuthController@verifyEmail');
    $r->post('/logout',                'AuthController@logout', ['auth', 'csrf']);

    // ---- signed-in shared pages ----
    $r->group('', ['auth'], function (Router $r) {
        $r->get('/dashboard',      'HomeController@dashboard');
        $r->get('/profile',        'ProfileController@show');
        $r->post('/profile',       'ProfileController@update', ['csrf']);
        $r->get('/settings',       'ProfileController@settings');
        $r->get('/notifications',  'NotificationController@index');
        $r->post('/notifications/read', 'NotificationController@markAllRead', ['csrf']);
        $r->get('/messages',       'MessageController@index');
        $r->get('/organizations',  'OrganizationController@index');
        $r->get('/organizations/{id}', 'OrganizationController@show');

        // Protected file serving - checks permission before streaming.
        // Two segments, because a stored path is always "folder/name.ext" and a
        // single {param} compiles to ([^/]+), which can never match the slash.
        $r->get('/file/{folder}/{name}', 'FileController@serve');
    });
};
