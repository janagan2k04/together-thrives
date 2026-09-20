<?php
namespace Middleware;

use Core\Csrf, Core\Request, Core\HttpException;

class CsrfMiddleware
{
    public function handle(array $args = []): void
    {
        if (in_array(Request::method(), ['POST', 'PUT', 'DELETE'], true)) {
            if (!Csrf::check($_POST['_csrf'] ?? null)) {
                throw new HttpException(419, 'Your session expired. Please try again.');
            }
        }
    }
}
