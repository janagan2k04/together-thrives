<?php
namespace Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();

        // rotate id periodically to limit fixation
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } elseif (time() - $_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    public static function get(string $k, mixed $d = null): mixed { return $_SESSION[$k] ?? $d; }
    public static function set(string $k, mixed $v): void         { $_SESSION[$k] = $v; }
    public static function has(string $k): bool                   { return isset($_SESSION[$k]); }
    public static function forget(string $k): void                { unset($_SESSION[$k]); }

    public static function regenerate(): void { session_regenerate_id(true); }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ---- flash messages ----
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    public static function pullFlash(): array
    {
        $f = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $f;
    }

    // ---- old input + errors (repopulate forms after a failed submit) ----
    public static function flashInput(array $input, array $errors = []): void
    {
        unset($input['password'], $input['password_confirm']);
        $_SESSION['_old']    = $input;
        $_SESSION['_errors'] = $errors;
    }

    public static function pullOld(): array
    {
        $o = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        return $o;
    }

    public static function pullErrors(): array
    {
        $e = $_SESSION['_errors'] ?? [];
        unset($_SESSION['_errors']);
        return $e;
    }
}
