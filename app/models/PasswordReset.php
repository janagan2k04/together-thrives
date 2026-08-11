<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * Password reset tokens.
 *
 * The table has no `deleted_at` and no `updated_at`, so soft delete is off and
 * `Model::update()` must never be used here — it appends `updated_at = NOW()`
 * and MySQL would reject the statement. The writes below go through Database
 * directly for that reason.
 *
 * Rows are keyed by email rather than user_id, which is how the frozen schema
 * declares the table.
 */
class PasswordReset extends Model
{
    protected string $table = 'password_resets';
    protected bool $softDelete = false;

    protected array $fillable = ['email', 'token', 'expires_at', 'used_at'];

    /**
     * The only row that may be redeemed: hash matches, not already spent, not
     * expired. Anything failing one of those three is indistinguishable to the
     * caller, which is what we want — an expired token and a forged one should
     * produce the same answer.
     */
    public function findUsable(string $tokenHash): ?array
    {
        return Database::one(
            "SELECT * FROM password_resets
              WHERE token = ? AND used_at IS NULL AND expires_at > NOW()
              LIMIT 1",
            [$tokenHash]
        );
    }

    public function markUsed(int $id): void
    {
        Database::run("UPDATE password_resets SET used_at = NOW() WHERE id = ?", [$id]);
    }

    /**
     * Burn every outstanding link for an address. Called when a new one is
     * issued and again after a successful reset, so an older link still sitting
     * in a mailbox cannot be replayed.
     */
    public function invalidateFor(string $email): void
    {
        Database::run(
            "UPDATE password_resets SET used_at = NOW() WHERE email = ? AND used_at IS NULL",
            [$email]
        );
    }

    /**
     * Requests from this address in the recent past — used to throttle the form.
     * The window is inlined as an integer rather than bound, because MySQL will
     * not accept a placeholder for the quantity in an INTERVAL expression.
     */
    public function recentCount(string $email, int $minutes = 60): int
    {
        $minutes = max(1, $minutes);
        return (int) Database::value(
            "SELECT COUNT(*) FROM password_resets
              WHERE email = ? AND created_at > (NOW() - INTERVAL {$minutes} MINUTE)",
            [$email]
        );
    }
}
