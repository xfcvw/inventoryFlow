<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\CreatesWorkspace;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use CreatesWorkspace, RefreshDatabase;

    public function test_manager_can_create_product_and_initial_stock(): void
    {
        $user = User::factory()->create();
        $workspace = $this->createWorkspaceFor($user, 'owner');
        $category = Category::create(['workspace_id' => $workspace->id, 'name' => 'Peripherals', 'slug' => 'peripherals']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/products', [
            'name' => 'Mechanical Keyboard', 'sku' => 'KB-100', 'category_id' => $category->id,
            'price' => 299.90, 'cost_price' => 180, 'min_stock' => 3, 'initial_stock' => 10, 'active' => true,
        ])->assertCreated();

        $productId = $response->json('id');
        $this->assertDatabaseHas('products', ['id' => $productId, 'workspace_id' => $workspace->id, 'stock' => 10]);
        $this->assertDatabaseHas('product_warehouse_stocks', ['product_id' => $productId, 'quantity' => 10]);
    }

    public function test_other_workspace_products_are_not_listed(): void
    {
        $ownerA = User::factory()->create();
        $workspaceA = $this->createWorkspaceFor($ownerA);
        $ownerB = User::factory()->create();
        $this->createWorkspaceFor($ownerB);

        $workspaceA->products()->create([
            'user_id' => $ownerA->id, 'name' => 'Private Product', 'sku' => 'PRIVATE-1',
            'category' => 'Test', 'price' => 10, 'cost_price' => 5, 'stock' => 1, 'min_stock' => 0, 'active' => true,
        ]);

        Sanctum::actingAs($ownerB);
        $this->getJson('/api/products')->assertOk()->assertJsonCount(0);
    }

    public function test_viewer_cannot_create_products(): void
    {
        $owner = User::factory()->create();
        $workspace = $this->createWorkspaceFor($owner);
        $viewer = User::factory()->create();
        $workspace->users()->attach($viewer->id, ['role' => 'viewer']);
        $viewer->update(['current_workspace_id' => $workspace->id]);
        Sanctum::actingAs($viewer);

        $this->postJson('/api/products', [
            'name' => 'Blocked', 'sku' => 'BLOCK-1', 'price' => 10, 'min_stock' => 0, 'active' => true,
        ])->assertForbidden();
    }
}
