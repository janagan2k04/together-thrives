<?php
namespace Models\Reuse;

use Core\Database;
use Core\Model;

/**
 * Lookup table - 11 rows, seeded. Like `cities` it has neither `deleted_at`
 * nor `created_at`, so soft delete is off and every read must pass an explicit
 * $orderBy; the inherited default of `created_at DESC` is a SQL error here.
 */
class ItemCategory extends Model
{
    protected string $table = 'item_categories';
    protected array  $fillable = ['name'];
    protected bool   $softDelete = false;

    /** Alphabetical, for <select> lists. */
    public function listAll(): array
    {
        return $this->all([], 'name ASC');
    }

    /**
     * Categories with a count of the listings a browser can actually see,
     * for the "Furniture (42)" filter panel in the design.
     */
    public function listWithCounts(): array
    {
        return Database::all(
            "SELECT c.id, c.name, COUNT(i.id) AS item_count
               FROM item_categories c
          LEFT JOIN reuse_items i
                 ON i.category_id = c.id
                AND i.deleted_at IS NULL
                AND i.status <> ?
           GROUP BY c.id, c.name
           ORDER BY c.name ASC",
            [ITEM_REMOVED]
        );
    }
}
