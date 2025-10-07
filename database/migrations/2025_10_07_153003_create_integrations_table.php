<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // metrika|direct|amocrm
            $table->string('status')->default('disconnected'); // connected|error|disconnected
            $table->json('meta')->nullable(); // аккаунт, ид кабинета и т.п.
            $table->timestamps();

            $table->unique(['user_id','provider']);
        });
    }

    public function down(): void { Schema::dropIfExists('integrations'); }
};

