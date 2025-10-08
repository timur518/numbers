<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('scope')->default('direct');
            $table->string('job')->nullable();
            $table->enum('status', ['queued','running','success','failed'])->default('queued');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->unsignedInteger('api_units_used')->default(0);
            $table->unsignedInteger('campaigns_synced')->default(0);
            $table->unsignedInteger('ads_synced')->default(0);
            $table->unsignedInteger('affected_rows')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'provider']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
