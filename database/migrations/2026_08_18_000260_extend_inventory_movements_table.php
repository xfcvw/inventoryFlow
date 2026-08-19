<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('reason', 160)->nullable()->after('quantity');
            $table->string('reference_type', 80)->nullable()->after('reason');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->integer('balance_after')->nullable()->after('reference_id');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->dropColumn(['warehouse_id', 'reason', 'reference_type', 'reference_id', 'balance_after']);
        });
    }
};
