<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@inventoryflow.com'],
            ['name' => 'Demo User', 'password' => Hash::make('inventory123')]
        );

        $workspace = Workspace::firstOrCreate(
            ['slug' => 'demo-store'],
            ['name' => 'Demo Store', 'owner_id' => $user->id, 'plan' => 'pro', 'currency' => 'BRL', 'locale' => 'pt-BR', 'timezone' => 'America/Sao_Paulo', 'business_type' => 'E-commerce', 'onboarding_completed' => true]
        );
        $workspace->users()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);
        if (! $user->current_workspace_id) $user->update(['current_workspace_id' => $workspace->id]);

        Subscription::firstOrCreate(['workspace_id' => $workspace->id, 'status' => 'active'], ['plan' => 'pro', 'provider' => 'local', 'current_period_ends_at' => now()->addMonth()]);
        $warehouse = Warehouse::firstOrCreate(['workspace_id' => $workspace->id, 'code' => 'MAIN'], ['name' => 'Main Warehouse', 'is_default' => true]);
        $peripherals = Category::firstOrCreate(['workspace_id' => $workspace->id, 'slug' => 'peripherals'], ['name' => 'Peripherals']);
        $accessories = Category::firstOrCreate(['workspace_id' => $workspace->id, 'slug' => 'accessories'], ['name' => 'Accessories']);
        $supplier = Supplier::firstOrCreate(['workspace_id' => $workspace->id, 'name' => 'Tech Supplier'], ['email' => 'supplier@example.com', 'contact_name' => 'Alex']);
        Customer::firstOrCreate(['workspace_id' => $workspace->id, 'email' => 'customer@example.com'], ['name' => 'Sample Customer', 'phone' => '+55 11 99999-0000']);

        $items = [
            ['name' => 'Mechanical Keyboard', 'sku' => 'KB-001', 'category_id' => $peripherals->id, 'price' => 299.90, 'cost_price' => 180, 'qty' => 12, 'min' => 5],
            ['name' => 'Wireless Mouse', 'sku' => 'MS-002', 'category_id' => $peripherals->id, 'price' => 149.90, 'cost_price' => 80, 'qty' => 4, 'min' => 5],
            ['name' => 'USB-C Hub', 'sku' => 'HB-003', 'category_id' => $accessories->id, 'price' => 189.90, 'cost_price' => 110, 'qty' => 7, 'min' => 3],
        ];

        foreach ($items as $data) {
            $product = Product::firstOrCreate(
                ['workspace_id' => $workspace->id, 'sku' => $data['sku']],
                ['user_id' => $user->id, 'category_id' => $data['category_id'], 'supplier_id' => $supplier->id, 'name' => $data['name'], 'category' => Category::find($data['category_id'])->name, 'price' => $data['price'], 'cost_price' => $data['cost_price'], 'stock' => $data['qty'], 'min_stock' => $data['min'], 'active' => true]
            );
            ProductWarehouseStock::firstOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['workspace_id' => $workspace->id, 'quantity' => $data['qty'], 'min_stock' => $data['min']]
            );
        }
    }
}
