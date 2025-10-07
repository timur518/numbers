<?php

namespace App\Models;

use App\Enums\IntegrationStatus;
use App\Enums\Provider;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = ['user_id','provider','status','meta'];
    protected $casts = [
        'meta' => 'array',
        'provider' => Provider::class,
        'status' => IntegrationStatus::class,
    ];
    public function user(){ return $this->belongsTo(User::class); }
}
