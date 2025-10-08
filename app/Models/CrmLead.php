<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmLead extends Model
{
    protected $fillable = [
        'user_id',
        'crm_lead_id',
        'pipeline_id',
        'status_id',
        'name',
        'price',
        'is_won',
        'closed_at',
        'created_at_crm',
        'updated_at_crm',
        'meta',
    ];

    protected $casts = [
        'is_won' => 'boolean',
        'closed_at' => 'datetime',
        'created_at_crm' => 'datetime',
        'updated_at_crm' => 'datetime',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function utm()
    {
        return $this->hasOne(CrmLeadUtm::class, 'crm_lead_id');
    }
}
