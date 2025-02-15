<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiService extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_url', 'company_id', 'service_name', 'api_endpoint'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
