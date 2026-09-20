<?php
namespace Core;

use Models\User;

class Auth
{
    private static ?array $cached = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = (new User())->findBy('email', $email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Logger::audit("Failed login for {$email} from " . Request::ip());
            return false;
        }

        // Only an active account may sign in. Written as an allow-list on
        // purpose: a denied status that nobody remembered to list here would
        // otherwise be let straight through, which is how STATUS_REJECTED was
        // able to sign in before the admin verification queue was tested.
        if ($user['status'] !== STATUS_ACTIVE) {
            Session::flash('error', match ($user['status']) {
                STATUS_SUSPENDED => 'This account has been suspended. Contact support.',
                STATUS_PENDING   => 'Your account is awaiting administrator verification.',
                STATUS_REJECTED  => 'Your registration was not approved. Contact support.',
                default          => 'This account cannot sign in. Contact support.',
            });
            Logger::audit("Blocked login for {$email} — status {$user['status']}");
            return false;
        }

        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('role', $user['role']);
        self::$cached = $user;

        Database::run('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        Logger::audit("User {$user['id']} ({$user['role']}) logged in");
    }

    public static function logout(): void
    {
        if ($id = Session::get('user_id')) {
            Logger::audit("User {$id} logged out");
        }
        self::$cached = null;
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function user(): ?array
    {
        if (!self::check()) return null;
        if (self::$cached === null) {
            self::$cached = (new User())->find(self::id());
        }
        return self::$cached;
    }

    public static function role(): ?string
    {
        return Session::get('role');
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /** Dashboard each role lands on after login. */
    public static function homeFor(?string $role = null): string
    {
        return match ($role ?? self::role()) {
            ROLE_ADMIN        => '/admin/dashboard',
            ROLE_ORGANIZATION => '/org/dashboard',
            ROLE_GN           => '/gn/dashboard',
            ROLE_BLOOD_BANK   => '/blood-bank/dashboard',
            default           => '/dashboard',
        };
    }
}
