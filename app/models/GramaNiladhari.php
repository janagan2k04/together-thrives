<?php
namespace Models;

use Core\Model;

class GramaNiladhari extends Model
{
    protected string $table = 'grama_niladharis';
    protected array  $fillable = [
        'user_id', 'gn_division', 'division_code', 'ds_division',
        'district_id', 'office_phone', 'document_path',
        'verified_at', 'verified_by',
    ];
}
