<?php
namespace Services;

use Core\Database;
use Core\Logger;
use Models\EmailVerification;
use Models\User;

/**
 * Confirming that an address belongs to the person who typed it.
 *
 * This is a different thing from administrator verification, and the two are
 * easy to confuse because the schema uses the word for both:
 *
 *   users.email_verified_at  - the address was confirmed by clicking a link
 *   users.status             - an administrator decided about the account
 *
 * Only the second one gates access. VerifiedMiddleware reads `status`, and
 * nothing yet reads `email_verified_at`. Making an unconfirmed address block
 * sign-in would lock out every account registered before this phase, so that
 * is left as a group decision rather than a silent change.
 *
 * As with password resets there is no mail transport, so the link is logged.
 */
class EmailVerificationService
{
    private User $users;
    private EmailVerification $tokens;

    public function __construct()
    {
        $this->users  = new User();
        $this->tokens = new EmailVerification();
    }

    /**
     * Issue a confirmation link. Returns the link, or null if the address is
     * already confirmed and there is nothing to do.
     */
    public function issue(int $userId, string $email): ?string
    {
        $user = $this->users->find($userId);
        if (!$user || $user['email_verified_at'] !== null) {
            return null;
        }

        $this->tokens->clearFor($userId);

        $token = Token::create();
        $this->tokens->create([
            'user_id'    => $userId,
            'token'      => Token::hash($token),
            'expires_at' => Token::expiryFor(Token::VERIFY_TTL),
        ]);

        $link = url('/verify-email/' . $token);
        Logger::info("Email verification link for {$email}: {$link}");

        return $link;
    }

    /**
     * Redeem a link. Returns the user row on success, null for a token that is
     * unknown, expired, or whose account has since gone.
     */
    public function confirm(string $token): ?array
    {
        $row = $this->tokens->findUsable(Token::hash($token));
        if (!$row) {
            return null;
        }

        $userId = (int) $row['user_id'];
        $user   = $this->users->find($userId);

        if (!$user) {
            $this->tokens->clearFor($userId);
            return null;
        }

        Database::transaction(function () use ($userId) {
            $this->users->update($userId, ['email_verified_at' => date('Y-m-d H:i:s')]);
            // No used_at column on this table, so spent tokens are removed.
            $this->tokens->clearFor($userId);
        });

        Logger::audit("Email confirmed for account {$userId} ({$user['email']})");
        return $user;
    }
}
