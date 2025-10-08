<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YmSessionDaily extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'visits',
        'goals',
        'meta',
    ];

    protected $casts = [
        'date' => 'date',
        'goals' => 'array',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
