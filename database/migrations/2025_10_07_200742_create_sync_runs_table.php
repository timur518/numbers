<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sync_runs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider'); // direct|metrika|amocrm
            $t->string('job');      // campaigns|ads|keywords|report
            $t->string('status');   // ok|error
            $t->text('message')->nullable();
            $t->unsignedInteger('affected_rows')->default(0);
            $t->timestamps();
            $t->index(['user_id','provider','job','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('sync_runs'); }
};
