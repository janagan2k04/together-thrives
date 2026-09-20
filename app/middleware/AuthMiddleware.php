<?php
namespace Middleware;

use Core\Auth, Core\Session, Core\Request;

class AuthMiddleware
{
    public function handle(array $args = []): void
    {
        if (!Auth::check()) {
            Session::set('intended', Request::uri());
            Session::flash('error', 'Please sign in to continue.');
            header('Location: ' . url('/login'));
            exit;
        }
    }
}
