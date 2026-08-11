<?php
namespace Models;

use Core\Model;

/** Lookup table — 25 rows, seeded. No deleted_at and no created_at column. */
class District extends Model
{
    protected string $table = 'districts';
    protected array  $fillable = ['name', 'province'];
    protected bool   $softDelete = false;

    /** Alphabetical, for <select> lists. */
    public function listAll(): array
    {
        return $this->all([], 'name ASC');
    }
}
