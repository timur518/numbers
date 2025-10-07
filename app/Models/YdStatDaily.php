<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YdStatDaily extends Model
{
    protected $fillable = [
        'user_id','date','yd_campaign_id','yd_ad_id','yd_keyword_id',
        'impressions','clicks','cost_micros','currency','meta'
    ];
    protected $casts = ['date' => 'date','meta' => 'array'];
}
