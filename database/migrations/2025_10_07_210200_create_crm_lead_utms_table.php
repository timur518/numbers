<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_lead_utms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crm_lead_id')->constrained('crm_leads')->cascadeOnDelete();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('referer')->nullable();
            $table->string('landing')->nullable();
            $table->timestamp('first_touch_at')->nullable();
            $table->timestamp('last_touch_at')->nullable();
            $table->string('attribution_model')->default('last');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'crm_lead_id']);
            $table->index(['user_id', 'utm_campaign']);
            $table->index(['user_id', 'utm_term']);
            $table->index(['user_id', 'utm_source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_utms');
    }
};
