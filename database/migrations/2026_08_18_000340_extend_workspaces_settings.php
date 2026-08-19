<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('currency', 3)->default('BRL')->after('plan');
            $table->string('locale', 10)->default('pt-BR')->after('currency');
            $table->string('timezone', 60)->default('America/Sao_Paulo')->after('locale');
            $table->string('business_type', 80)->nullable()->after('timezone');
            $table->boolean('onboarding_completed')->default(false)->after('business_type');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['currency', 'locale', 'timezone', 'business_type', 'onboarding_completed']);
        });
    }
};
