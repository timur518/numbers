<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('direct_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['idle','running','success','failed'])->default('idle');
            $table->string('scope')->default('direct');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('api_units_used')->default(0);
            $table->unsignedInteger('campaigns_synced')->default(0);
            $table->unsignedInteger('ads_synced')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('direct_sync_runs');
    }
};
