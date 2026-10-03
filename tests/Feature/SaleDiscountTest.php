<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(['name' => 'view sales']);
        Permission::firstOrCreate(['name' => 'create sales']);
    }

    public function test_sale_creation_applies_discount_correctly(): void
    {
        $business = Business::create([
            'name' => 'Test Business',
            'slug' => 'test-biz-'.uniqid(),
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'code' => 'MB01',
            'is_main' => true,
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'job_title' => 'Tenant Administrator',
        ]);
        $user->assignRole($adminRole);

        $category = Category::create([
            'business_id' => $business->id,
            'name' => 'Cement',
            'slug' => 'cement',
        ]);

        $unit = Unit::create([
            'business_id' => $business->id,
            'name' => 'Bag',
            'abbreviation' => 'bag',
            'symbol' => 'bag',
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Portland Cement 50kg',
            'slug' => 'portland-cement-50kg',
            'sku' => 'PC-50',
            'selling_price' => 4000,
            'cost_price' => 3000,
            'track_stock' => false,
        ]);

        $this->actingAs($user);

        // Subtotal = 4000 (1 item @ 4000)
        // Discount = 500
        // Total Due = 3500
        // Payment = 3500
        $response = $this->post(route('sales.store'), [
            'branch_id' => $branch->id,
            'sale_type' => 'sale',
            'status' => 'confirmed',
            'transaction_date' => now()->toDateString(),
            'discount_amount' => 500,
            'discount_percent' => 12.5,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 4000,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                ],
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 3500,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.index'));

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals(4000.00, (float) $sale->subtotal);
        $this->assertEquals(500.00, (float) $sale->discount_amount);
        $this->assertEquals(3500.00, (float) $sale->total_amount);
        $this->assertEquals(3500.00, (float) $sale->paid_amount);
        $this->assertEquals(0.00, (float) $sale->balance_amount);
        $this->assertEquals('paid', $sale->payment_status);
    }

    public function test_sale_update_applies_discount_correctly(): void
    {
        $business = Business::create([
            'name' => 'Test Business 2',
            'slug' => 'test-biz-2-'.uniqid(),
            'is_active' => true,
        ]);

        $branch = Branch::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'code' => 'MB02',
            'is_main' => true,
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'job_title' => 'Tenant Administrator',
        ]);
        $user->assignRole($adminRole);

        $category = Category::create([
            'business_id' => $business->id,
            'name' => 'Paint',
            'slug' => 'paint',
        ]);

        $unit = Unit::create([
            'business_id' => $business->id,
            'name' => 'Litre',
            'abbreviation' => 'ltr',
            'symbol' => 'L',
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Emulsion White 20L',
            'slug' => 'emulsion-white-20l',
            'sku' => 'EW-20',
            'selling_price' => 10000,
            'cost_price' => 7000,
            'track_stock' => false,
        ]);

        $this->actingAs($user);

        // Initial sale in draft status with 0 discount
        $sale = Sale::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'sale_number' => 'SL-TEST-001',
            'sale_type' => 'sale',
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'fulfillment_status' => 'pending',
            'transaction_date' => now()->toDateString(),
            'subtotal' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
            'paid_amount' => 0,
            'balance_amount' => 10000,
        ]);

        // Update with 2000 discount
        $response = $this->put(route('sales.update', $sale->id), [
            'branch_id' => $branch->id,
            'sale_type' => 'sale',
            'status' => 'confirmed',
            'transaction_date' => now()->toDateString(),
            'discount_amount' => 2000,
            'discount_percent' => 20,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 10000,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                ],
            ],
            'payments' => [
                [
                    'payment_method' => 'cash',
                    'amount' => 8000,
                ],
            ],
        ]);

        $response->assertRedirect(route('sales.show', $sale->id));

        $sale->refresh();
        $this->assertEquals(10000.00, (float) $sale->subtotal);
        $this->assertEquals(2000.00, (float) $sale->discount_amount);
        $this->assertEquals(8000.00, (float) $sale->total_amount);
        $this->assertEquals(8000.00, (float) $sale->paid_amount);
        $this->assertEquals(0.00, (float) $sale->balance_amount);
        $this->assertEquals('paid', $sale->payment_status);
    }
}
