<?php

namespace App\Models;

use App\Enums\Provider;
use Illuminate\Database\Eloquent\Model;

class OauthToken extends Model
{
    protected $fillable = ['user_id','provider','access_token','refresh_token','expires_at','account_id','scope'];
    protected $casts = [
        'provider' => Provider::class,
        'expires_at' => 'datetime',
        'scope' => 'array',
        // шифрование токенов
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];
    public function user(){ return $this->belongsTo(User::class); }
}
