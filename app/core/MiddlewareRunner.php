<?php
namespace Core;

class MiddlewareRunner
{
    private const MAP = [
        'auth'     => \Middleware\AuthMiddleware::class,
        'guest'    => \Middleware\GuestMiddleware::class,
        'role'     => \Middleware\RoleMiddleware::class,
        'csrf'     => \Middleware\CsrfMiddleware::class,
        'verified' => \Middleware\VerifiedMiddleware::class,
    ];

    /** Accepts "auth" or "role:admin,organization" */
    public static function run(string $definition): void
    {
        [$name, $argString] = array_pad(explode(':', $definition, 2), 2, null);
        $args = $argString ? explode(',', $argString) : [];

        if (!isset(self::MAP[$name])) {
            throw new \RuntimeException("Unknown middleware: {$name}");
        }
        (new (self::MAP[$name])())->handle($args);
    }
}
