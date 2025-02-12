<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiService extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'base_url'];

    public function tokens()
    {
        return $this->hasMany(ApiToken::class);
    }
}
