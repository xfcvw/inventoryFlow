<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0)->after('customer');
            $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
            $table->decimal('tax', 12, 2)->default(0)->after('discount');
            $table->text('notes')->nullable()->after('status');
            $table->boolean('stock_applied')->default(false)->after('notes');
            $table->timestamp('processed_at')->nullable()->after('stock_applied');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['customer_id', 'warehouse_id', 'subtotal', 'discount', 'tax', 'notes', 'stock_applied', 'processed_at']);
        });
    }
};
