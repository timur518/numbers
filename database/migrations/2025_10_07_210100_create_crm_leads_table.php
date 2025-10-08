<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('crm_lead_id');
            $table->unsignedBigInteger('pipeline_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->boolean('is_won')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('created_at_crm')->nullable();
            $table->timestamp('updated_at_crm')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'crm_lead_id']);
            $table->index(['user_id', 'pipeline_id']);
            $table->index(['user_id', 'status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
