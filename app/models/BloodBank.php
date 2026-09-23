<?php
namespace Models;

use Core\Model;

class BloodBank extends Model
{
    protected string $table = 'blood_banks';
    protected array  $fillable = [
        'user_id', 'bank_name', 'license_no', 'hospital_name',
        'address', 'city_id', 'hotline', 'document_path', 'verified_at',
    ];
}
