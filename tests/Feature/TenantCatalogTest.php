<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\ProductType;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantCatalogSeederService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected User $tenant1Admin;

    protected User $tenant2Admin;

    protected Business $tenant1;

    protected Business $tenant2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant1 = Business::create([
            'name' => 'Mega Hardware & Steel Factory',
            'slug' => 'mega-hardware',
            'is_active' => true,
        ]);

        $this->tenant2 = Business::create([
            'name' => 'Demo Hardware',
            'slug' => 'demo-hardware',
            'is_active' => true,
        ]);

        $this->tenant1Admin = User::factory()->create([
            'business_id' => $this->tenant1->id,
            'email' => 'admin@sfactory.com',
            'is_system_admin' => false,
        ]);

        $this->tenant2Admin = User::factory()->create([
            'business_id' => $this->tenant2->id,
            'email' => 'demo@hardware.com',
            'is_system_admin' => false,
        ]);

        TenantCatalogSeederService::seedTenantDefaults($this->tenant1);
        TenantCatalogSeederService::seedTenantDefaults($this->tenant2);
    }

    public function test_tenant_can_view_only_their_own_categories(): void
    {
        $response = $this->actingAs($this->tenant1Admin)->get(route('categories.index'));
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->has('categories.data')
            ->where('categories.data.0.business_id', $this->tenant1->id)
        );
    }

    public function test_tenant_can_create_and_update_category(): void
    {
        $uniqueCode = 'TEST_CAT_'.time();

        $createResponse = $this->actingAs($this->tenant1Admin)->post(route('categories.store'), [
            'name' => 'Special Galvanized Wire',
            'code' => $uniqueCode,
            'description' => 'Test category for galvanization',
            'color' => '#10b981',
            'is_active' => true,
        ]);

        $createResponse->assertRedirect();

        $category = Category::where('business_id', $this->tenant1->id)
            ->where('code', $uniqueCode)
            ->first();

        $this->assertNotNull($category);
        $this->assertEquals('Special Galvanized Wire', $category->name);

        // Update category
        $updateResponse = $this->actingAs($this->tenant1Admin)->put(route('categories.update', $category->id), [
            'name' => 'Updated Galvanized Wire',
            'code' => $uniqueCode,
            'description' => 'Updated description',
            'color' => '#3b82f6',
            'is_active' => true,
        ]);

        $updateResponse->assertRedirect();
        $category->refresh();
        $this->assertEquals('Updated Galvanized Wire', $category->name);
        $this->assertEquals('#3b82f6', $category->color);
    }

    public function test_tenant_cannot_modify_another_tenants_category(): void
    {
        // Category created by Tenant 1
        $tenant1Category = Category::where('business_id', $this->tenant1->id)->first();
        $this->assertNotNull($tenant1Category);

        // Tenant 2 attempts to update Tenant 1's category
        $response = $this->actingAs($this->tenant2Admin)->put(route('categories.update', $tenant1Category->id), [
            'name' => 'Hacked Name',
            'code' => $tenant1Category->code,
            'is_active' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_tenant_can_crud_unit_of_measure(): void
    {
        $uniqueAbbr = 'pk_'.substr(uniqid(), 0, 5);

        // Create unit
        $createResponse = $this->actingAs($this->tenant1Admin)->post(route('units.store'), [
            'name' => 'Pack of 50',
            'abbreviation' => $uniqueAbbr,
            'type' => 'quantity',
            'allow_decimal' => false,
            'description' => 'Pack of 50 units',
            'is_active' => true,
        ]);

        $createResponse->assertRedirect();

        $unit = Unit::where('business_id', $this->tenant1->id)
            ->where('abbreviation', $uniqueAbbr)
            ->first();

        $this->assertNotNull($unit);
        $this->assertFalse((bool) $unit->allow_decimal);

        // Cross-tenant protection
        $foreignResponse = $this->actingAs($this->tenant2Admin)->put(route('units.update', $unit->id), [
            'name' => 'Attempted hijack',
            'abbreviation' => $uniqueAbbr,
            'type' => 'quantity',
        ]);
        $foreignResponse->assertStatus(403);
    }

    public function test_tenant_can_crud_product_type_with_capabilities(): void
    {
        $uniqueCode = 'fab_item_'.substr(uniqid(), 0, 5);

        $createResponse = $this->actingAs($this->tenant1Admin)->post(route('product-types.store'), [
            'name' => 'Fabricated Structure',
            'code' => $uniqueCode,
            'description' => 'Assembled factory structural components',
            'is_sold' => true,
            'is_purchased' => false,
            'is_manufactured' => true,
            'track_stock' => true,
            'is_active' => true,
        ]);

        $createResponse->assertRedirect();

        $type = ProductType::where('business_id', $this->tenant1->id)
            ->where('code', $uniqueCode)
            ->first();

        $this->assertNotNull($type);
        $this->assertTrue((bool) $type->is_manufactured);
        $this->assertFalse((bool) $type->is_purchased);

        // Toggle status
        $toggleResponse = $this->actingAs($this->tenant1Admin)->patch(route('product-types.toggle-status', $type->id));
        $toggleResponse->assertRedirect();
        $type->refresh();
        $this->assertFalse((bool) $type->is_active);
    }

    public function test_catalog_seeder_service_populates_starter_taxonomies_for_new_tenant(): void
    {
        $newBusiness = Business::create([
            'name' => 'New Tenant Test Corp '.time(),
            'slug' => 'test-corp-'.time(),
            'is_active' => true,
        ]);

        TenantCatalogSeederService::seedTenantDefaults($newBusiness);

        $this->assertGreaterThan(0, Category::where('business_id', $newBusiness->id)->count());
        $this->assertGreaterThan(0, Unit::where('business_id', $newBusiness->id)->count());
        $this->assertGreaterThan(0, ProductType::where('business_id', $newBusiness->id)->count());
    }
}
