<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * The location lookup every module writes into its `city_id` column.
 *
 * Since 2026-08-27 it holds exactly ONE ROW PER DISTRICT, named after the
 * district: 25 rows, mirroring `districts` one for one. The group could not
 * list every city in Sri Lanka and a dropdown where 12 of the 25 districts had
 * no entry at all was worse than none, so every form asks for a district.
 *
 * The table and the column keep their names because schema.sql is frozen and
 * six tables carry a NOT NULL `city_id` foreign key to `cities`. What a row
 * MEANS changed; where it is stored did not. Anything joining `cities` for a
 * display name is therefore already printing a district name - which is why
 * "Colombo, Colombo" turned up on a few screens and had to be collapsed.
 *
 * No `deleted_at` and no `created_at` column.
 */
class City extends Model
{
    protected string $table = 'cities';
    protected array  $fillable = ['district_id', 'name'];
    protected bool   $softDelete = false;

    /** Alphabetical, for the District <select> on every form. */
    public function listAll(): array
    {
        return $this->all([], 'name ASC');
    }

    /**
     * The row that stands for a district.
     *
     * Two forms ask for a district by `districts.id` - disaster reporting and
     * the dengue map - but every module column is a `cities` foreign key, so
     * the id has to be translated before it is stored. One row per district
     * since 2026-08-27, so the answer is unambiguous.
     */
    public function forDistrict(int $districtId): ?int
    {
        $id = Database::value(
            'SELECT id FROM cities WHERE district_id = ? ORDER BY id LIMIT 1',
            [$districtId]
        );
        return $id === null ? null : (int) $id;
    }

    /**
     * Same 25 rows with the district name alongside, for callers that render a
     * `district` key. One row per district now, so the two names are equal -
     * kept so the views that read `$c['district']` do not have to change.
     */
    public function listWithDistrict(): array
    {
        return Database::all(
            'SELECT c.id, c.name, d.name AS district
               FROM cities c
               JOIN districts d ON d.id = c.district_id
           ORDER BY d.name ASC'
        );
    }
}
