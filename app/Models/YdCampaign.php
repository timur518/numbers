<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YdCampaign extends Model
{
    protected $fillable = ['user_id','yd_campaign_id','name','status','currency','meta'];
    protected $casts = ['meta' => 'array'];
}
