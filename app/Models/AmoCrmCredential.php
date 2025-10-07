<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmoCrmCredential extends Model
{
    protected $fillable = ['user_id','base_domain','client_id','client_secret'];
    protected $casts = [
        'client_id'     => 'encrypted',
        'client_secret' => 'encrypted',
    ];
    public function user() { return $this->belongsTo(User::class); }
}
