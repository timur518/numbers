<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('amocrm_credentials') && ! Schema::hasTable('amo_crm_credentials')) {
            Schema::rename('amocrm_credentials', 'amo_crm_credentials');
        }
    }
    public function down(): void {
        if (Schema::hasTable('amo_crm_credentials') && ! Schema::hasTable('amocrm_credentials')) {
            Schema::rename('amo_crm_credentials', 'amocrm_credentials');
        }
    }
};
