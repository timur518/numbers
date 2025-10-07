<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YdAd extends Model
{
    protected $fillable = ['user_id','yd_ad_id','yd_adgroup_id','yd_campaign_id','status','meta'];
    protected $casts = ['meta' => 'array'];
}
