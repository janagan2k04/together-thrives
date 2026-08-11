<?php
namespace Models;

use Core\Database;
use Core\Model;

/** Lookup table — 37 rows, seeded. No deleted_at and no created_at column. */
class City extends Model
{
    protected string $table = 'cities';
    protected array  $fillable = ['district_id', 'name'];
    protected bool   $softDelete = false;

    /** Alphabetical, for <select> lists. */
    public function listAll(): array
    {
        return $this->all([], 'name ASC');
    }

    /** Cities grouped under their district, for optgroup selects. */
    public function listWithDistrict(): array
    {
        return Database::all(
            'SELECT c.id, c.name, d.name AS district
               FROM cities c
               JOIN districts d ON d.id = c.district_id
           ORDER BY d.name ASC, c.name ASC'
        );
    }
}
