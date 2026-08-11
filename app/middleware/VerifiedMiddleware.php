<?php
namespace Middleware;

use Core\Auth, Core\HttpException;

/** Organizations, GN officers and blood banks must be admin-verified. */
class VerifiedMiddleware
{
    public function handle(array $args = []): void
    {
        $user = Auth::user();
        if (!$user || ($user['status'] ?? '') !== STATUS_ACTIVE) {
            throw new HttpException(403, 'Your account is still awaiting administrator verification.');
        }
    }
}
