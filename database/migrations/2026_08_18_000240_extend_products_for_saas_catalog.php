<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->string('barcode', 80)->nullable()->after('sku');
            $table->text('description')->nullable()->after('category');
            $table->decimal('cost_price', 12, 2)->default(0)->after('price');
            $table->boolean('active')->default(true)->after('min_stock');

            $table->index(['workspace_id', 'active']);
            $table->index(['workspace_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['category_id', 'supplier_id', 'barcode', 'description', 'cost_price', 'active']);
        });
    }
};
