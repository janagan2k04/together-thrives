<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * Email confirmation tokens.
 *
 * No `deleted_at` and no `updated_at` on the table, so soft delete is off and
 * `Model::update()` must not be used — see the note on [[PasswordReset]]. The
 * schema has no `used_at` column either, so a redeemed token is deleted rather
 * than marked.
 */
class EmailVerification extends Model
{
    protected string $table = 'email_verifications';
    protected bool $softDelete = false;

    protected array $fillable = ['user_id', 'token', 'expires_at'];

    public function findUsable(string $tokenHash): ?array
    {
        return Database::one(
            "SELECT * FROM email_verifications
              WHERE token = ? AND expires_at > NOW()
              LIMIT 1",
            [$tokenHash]
        );
    }

    /** Drop every outstanding token for a user — reissue and redeem both do this. */
    public function clearFor(int $userId): void
    {
        Database::run("DELETE FROM email_verifications WHERE user_id = ?", [$userId]);
    }
}
