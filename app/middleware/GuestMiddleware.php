<?php
namespace Middleware;

use Core\Auth;

class GuestMiddleware
{
    public function handle(array $args = []): void
    {
        if (Auth::check()) {
            header('Location: ' . url(Auth::homeFor()));
            exit;
        }
    }
}
