<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenType extends Model
{
    use HasFactory;

    protected $fillable = ['type'];

    public function tokens()
    {
        return $this->hasMany(ApiToken::class);
    }
}
