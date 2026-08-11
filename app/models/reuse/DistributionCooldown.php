<?php
namespace Models\Reuse;

use Core\Database;
use Core\Model;

/**
 * The fair-distribution rule the design states on the donate form: after
 * receiving an item, that member cannot request the same category again until
 * the cooldown expires, so more people get helped.
 *
 * This table has no `created_at`, `updated_at` or `deleted_at` at all, which
 * means none of the inherited writes work — create() appends created_at,
 * update() appends updated_at, and delete() would soft delete. Everything here
 * goes through Database::run().
 */
class DistributionCooldown extends Model
{
    protected string $table = 'distribution_cooldowns';
    protected array  $fillable = ['user_id', 'category_id', 'received_at', 'cooldown_until'];
    protected bool   $softDelete = false;

    /** Weeks a recipient waits before asking for the same category again. */
    public const WEEKS = 4;

    /** Start the clock for one recipient and category. */
    public function start(int $userId, int $categoryId): void
    {
        Database::run(
            'INSERT INTO distribution_cooldowns (user_id, category_id, received_at, cooldown_until)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? WEEK))',
            [$userId, $categoryId, self::WEEKS]
        );
    }

    /** The live cooldown for this member and category, if there is one. */
    public function active(int $userId, int $categoryId): ?array
    {
        return Database::one(
            'SELECT * FROM distribution_cooldowns
              WHERE user_id = ? AND category_id = ? AND cooldown_until > NOW()
           ORDER BY cooldown_until DESC LIMIT 1',
            [$userId, $categoryId]
        );
    }
}
