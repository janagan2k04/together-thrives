<?php
namespace Models\Reuse;

use Core\Database;
use Core\Model;

/**
 * Photos of a reuse item. Child table: it has no `deleted_at`, so soft delete
 * is off, and no `updated_at` either - which means the inherited update() would
 * fail, because Model::update() always appends `updated_at = NOW()`. Anything
 * that changes a row here goes through Database::run() below instead.
 *
 * Rows are removed by the FK's ON DELETE CASCADE when an item is hard deleted;
 * a soft-deleted item keeps its images so it can be restored.
 */
class ItemImage extends Model
{
    protected string $table = 'item_images';
    protected array  $fillable = ['item_id', 'file_path', 'is_primary'];
    protected bool   $softDelete = false;

    /** Every photo of one item, cover first. */
    public function forItem(int $itemId): array
    {
        return Database::all(
            'SELECT * FROM item_images WHERE item_id = ? ORDER BY is_primary DESC, id ASC',
            [$itemId]
        );
    }

    /** The cover photo, or null when the donor uploaded none. */
    public function primaryFor(int $itemId): ?array
    {
        return Database::one(
            'SELECT * FROM item_images WHERE item_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1',
            [$itemId]
        );
    }

    /** One cover per item - clear the old flag before setting the new one. */
    public function makePrimary(int $imageId, int $itemId): bool
    {
        Database::run('UPDATE item_images SET is_primary = 0 WHERE item_id = ?', [$itemId]);
        return Database::run(
            'UPDATE item_images SET is_primary = 1 WHERE id = ? AND item_id = ?',
            [$imageId, $itemId]
        )->rowCount() > 0;
    }

    /**
     * An image plus its owning item, for the public image route. The route is
     * open, so the caller needs the item's state to decide what to serve.
     */
    public function withItem(int $imageId): ?array
    {
        return Database::one(
            'SELECT im.*, i.donor_id, i.status, i.deleted_at
               FROM item_images im
               JOIN reuse_items i ON i.id = im.item_id
              WHERE im.id = ? LIMIT 1',
            [$imageId]
        );
    }
}
