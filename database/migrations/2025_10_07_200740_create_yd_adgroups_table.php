<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('yd_adgroups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('yd_adgroup_id')->index();
            $t->unsignedBigInteger('yd_campaign_id')->index();
            $t->string('name')->nullable();
            $t->string('status')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->unique(['user_id','yd_adgroup_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('yd_adgroups'); }
};
