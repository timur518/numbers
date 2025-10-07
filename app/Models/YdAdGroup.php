<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YdAdGroup extends Model
{
    protected $fillable = ['user_id','yd_adgroup_id','yd_campaign_id','name','status','meta'];
    protected $casts = ['meta' => 'array'];
}
