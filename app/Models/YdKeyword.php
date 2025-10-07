<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class YdKeyword extends Model
{
    protected $fillable = ['user_id','yd_keyword_id','yd_adgroup_id','yd_campaign_id','text','status','meta'];
    protected $casts = ['meta' => 'array'];
}
