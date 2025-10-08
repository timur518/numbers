<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmLeadUtm extends Model
{
    protected $fillable = [
        'user_id',
        'crm_lead_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'referer',
        'landing',
        'first_touch_at',
        'last_touch_at',
        'attribution_model',
        'meta',
    ];

    protected $casts = [
        'first_touch_at' => 'datetime',
        'last_touch_at' => 'datetime',
        'meta' => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }
}
