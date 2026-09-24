<?php
namespace Services;

use Core\Auth;
use Core\Database;
use Core\Logger;
use Models\PasswordReset;
use Models\User;

/**
 * Forgotten-password links.
 *
 * There is no mail transport in this deliverable, so the link is written to
 * storage/logs/app.log with Logger::info() instead of being sent. Everything
 * either side of the delivery step is real: tokens are random, hashed at rest,
 * expire after an hour, and can be redeemed exactly once.
 *
 * issue() deliberately tells the caller almost nothing. A forgotten-password
 * form that answers "no such account" is a free membership oracle - anyone
 * could test whether an address is registered here - so the controller shows
 * the same screen either way.
 *
 * Status is not checked. Resetting a password grants no access on its own,
 * because Auth::attempt() still refuses anything that is not STATUS_ACTIVE,
 * and refusing suspended accounts here would leak their status through the
 * difference in behaviour.
 */
class PasswordResetService
{
    /** Links per address per hour. Generous for a real user, useless for a bomber. */
    private const MAX_PER_HOUR = 5;

    private User $users;
    private PasswordReset $resets;

    public function __construct()
    {
        $this->users  = new User();
        $this->resets = new PasswordReset();
    }

    /**
     * Create a link for the address, if it belongs to an account.
     *
     * Returns the link when one was issued and null otherwise. That return is
     * for the development screen only - callers must not vary what the visitor
     * sees based on it.
     */
    public function issue(string $email): ?string
    {
        $user = $this->users->findBy('email', $email);
        if (!$user) {
            // Logged, not shown. Useful when a real person swears they asked.
            Logger::info("Password reset requested for unknown address {$email}");
            return null;
        }

        if ($this->resets->recentCount($email) >= self::MAX_PER_HOUR) {
            Logger::audit("Password reset throttled for account {$user['id']} ({$email})");
            return null;
        }

        // Any earlier link stops working the moment a new one is asked for.
        $this->resets->invalidateFor($email);

        $token = Token::create();
        $this->resets->create([
            'email'      => $email,
            'token'      => Token::hash($token),
            'expires_at' => Token::expiryFor(Token::RESET_TTL),
        ]);

        $link = url('/reset-password/' . $token);
        Logger::info("Password reset link for {$email}: {$link}");
        Logger::audit("Password reset requested for account {$user['id']} ({$email})");

        return $link;
    }

    /** True while the link can still be exchanged for the new-password form. */
    public function isRedeemable(string $token): bool
    {
        return $this->resets->findUsable(Token::hash($token)) !== null;
    }

    /**
     * Redeem a link and set the new password. False for a token that is
     * unknown, expired, already spent, or whose account has since gone.
     */
    public function complete(string $token, string $password): bool
    {
        $row = $this->resets->findUsable(Token::hash($token));
        if (!$row) {
            return false;
        }

        $user = $this->users->findBy('email', $row['email']);
        if (!$user) {
            // The account was deleted between the request and the click. Spend
            // the token anyway so it cannot be tried again.
            $this->resets->markUsed((int) $row['id']);
            Logger::audit("Password reset token redeemed for missing account ({$row['email']})");
            return false;
        }

        Database::transaction(function () use ($row, $user, $password) {
            $this->users->update((int) $user['id'], ['password_hash' => Auth::hash($password)]);
            $this->resets->markUsed((int) $row['id']);
            // Belt and braces: every other outstanding link for the address dies
            // too, so a second copy of the mail is worthless.
            $this->resets->invalidateFor($row['email']);
        });

        Logger::audit("Password reset completed for account {$user['id']} ({$user['email']})");
        return true;
    }
}
