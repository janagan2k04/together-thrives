<?php
namespace Middleware;

use Core\Auth, Core\HttpException;

/**
 * The inverse of RoleMiddleware: deny the roles named, let everyone else past.
 *
 * `role:` is an allowlist, which cannot express "everybody except a blood bank"
 * on a page that guests may also open. The reuse browser and the blood donor hub
 * are both public - listing every permitted role would have shut guests out of
 * them, so the rule has to be written as a denial.
 *
 *   $r->get('/reuse', '…', ['block_role:blood_bank']);
 *
 * Signed-out visitors have no role and always pass; that is the point.
 *
 * @param array $args list of denied roles
 */
class BlockRoleMiddleware
{
    public function handle(array $args = []): void
    {
        if (Auth::check() && Auth::is(...$args)) {
            throw new HttpException(403, 'This section is not available for your account type.');
        }
    }
}
