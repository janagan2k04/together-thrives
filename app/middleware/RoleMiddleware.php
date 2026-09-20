<?php
namespace Middleware;

use Core\Auth, Core\HttpException;

class RoleMiddleware
{
    /** @param array $args list of allowed roles */
    public function handle(array $args = []): void
    {
        if (!Auth::check() || !Auth::is(...$args)) {
            throw new HttpException(403, 'You do not have permission to view this page.');
        }
    }
}
