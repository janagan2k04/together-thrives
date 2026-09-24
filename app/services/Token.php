<?php
namespace Services;

/**
 * Shared rules for the single-use link tokens used by password reset and email
 * verification. Both features need exactly the same primitives, and letting the
 * two drift apart is how one of them ends up with weaker tokens than the other.
 *
 * The raw token travels in the link; only its SHA-256 hash is stored. A leaked
 * `password_resets` table therefore cannot be used to take over accounts, and
 * the hash is a fixed 64 characters so it fits the VARCHAR(255) token columns.
 * Storing the hash also makes lookup safe - we search by hash rather than
 * comparing the secret in PHP, so there is no string comparison to time.
 */
final class Token
{
    /** A live reset link is a password in disguise. Keep the window short. */
    public const RESET_TTL = '+60 minutes';

    /** Confirming an address is not urgent, and people read mail late. */
    public const VERIFY_TTL = '+48 hours';

    /** 32 random bytes - 64 hex characters, safe to put in a URL as-is. */
    public static function create(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /** @param string $ttl one of the TTL constants above */
    public static function expiryFor(string $ttl): string
    {
        return date('Y-m-d H:i:s', strtotime($ttl));
    }
}
