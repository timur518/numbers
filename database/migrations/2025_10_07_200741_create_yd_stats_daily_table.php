<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('yd_stats_daily', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->date('date')->index();
            $t->unsignedBigInteger('yd_campaign_id')->index();
            $t->unsignedBigInteger('yd_ad_id')->nullable()->index();
            $t->unsignedBigInteger('yd_keyword_id')->nullable()->index(); // CriteriaId
            $t->unsignedInteger('impressions')->default(0);
            $t->unsignedInteger('clicks')->default(0);
            $t->unsignedBigInteger('cost_micros')->default(0); // стоимость в микросах
            $t->string('currency', 10)->nullable();
            $t->timestamps();

            $t->unique(['user_id','date','yd_campaign_id','yd_ad_id','yd_keyword_id'], 'yd_stats_daily_uniq');
        });
    }
    public function down(): void { Schema::dropIfExists('yd_stats_daily'); }
};
