<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'scope',
        'job',
        'status',
        'progress',
        'started_at',
        'finished_at',
        'api_units_used',
        'campaigns_synced',
        'ads_synced',
        'affected_rows',
        'errors_count',
        'message',
        'meta',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'progress' => 'integer',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
