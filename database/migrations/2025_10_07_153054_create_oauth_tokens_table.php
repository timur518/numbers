<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');              // metrika|direct|amocrm
            $table->text('access_token');            // encrypted
            $table->text('refresh_token')->nullable();// encrypted
            $table->timestamp('expires_at')->nullable();
            $table->string('account_id')->nullable();// id аккаунта/кабинета
            $table->json('scope')->nullable();
            $table->timestamps();

            $table->unique(['user_id','provider']);
        });
    }
    public function down(): void { Schema::dropIfExists('oauth_tokens'); }
};

