<?php
namespace Core;

class Csrf
{
    public static function token(): string
    {
        if (!Session::has('_csrf')) {
            Session::set('_csrf', bin2hex(random_bytes(32)));
        }
        return Session::get('_csrf');
    }

    public static function check(?string $token): bool
    {
        return is_string($token)
            && Session::has('_csrf')
            && hash_equals(Session::get('_csrf'), $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }
}
