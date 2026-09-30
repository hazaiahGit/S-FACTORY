<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockTransferWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function createBusiness(string $name = 'Hardware Business'): Business
    {
        return Business::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createBranch(int $businessId, string $name = 'Branch'): Branch
    {
        return Branch::create([
            'business_id' => $businessId,
            'name' => $name,
            'code' => strtoupper(Str::random(4)),
            'is_active' => true,
        ]);
    }

    protected function createProduct(int $businessId, string $name = 'Cement Bag', float $costPrice = 5000): Product
    {
        return Product::create([
            'business_id' => $businessId,
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'sku' => 'SKU-'.strtoupper(Str::random(6)),
            'track_stock' => true,
            'cost_price' => $costPrice,
            'selling_price' => $costPrice * 1.5,
            'is_active' => true,
        ]);
    }

    public function test_multi_step_stock_transfer_properly_moves_stock(): void
    {
        $business = $this->createBusiness();
        $sourceBranch = $this->createBranch($business->id, 'Source Branch');
        $destBranch = $this->createBranch($business->id, 'Destination Branch');

        $user = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $sourceBranch->id,
            'job_title' => 'Tenant Administrator',
        ]);

        $product = $this->createProduct($business->id, 'Cement Bag', 5000);

        // Add 50 stock to source branch
        $stockService = app(StockService::class);
        $stockService->increase($sourceBranch->id, $product->id, 50, 5000, 'initial_stock');

        $this->actingAs($user);

        // 1. Create transfer request
        $response = $this->post(route('stock-transfers.store'), [
            'from_branch_id' => $sourceBranch->id,
            'to_branch_id' => $destBranch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 20],
            ],
            'auto_complete' => false,
        ]);
        $response->assertRedirect(route('stock-transfers.index'));

        $transfer = StockTransfer::where('business_id', $business->id)->first();
        $this->assertNotNull($transfer);
        $this->assertEquals('requested', $transfer->status);

        // Source stock should still be 50 before dispatch
        $this->assertEquals(50, Stock::where('branch_id', $sourceBranch->id)->where('product_id', $product->id)->value('quantity'));

        // 2. Approve transfer
        $this->post(route('stock-transfers.approve', $transfer->id));
        $this->assertEquals('approved', $transfer->fresh()->status);

        // 3. Dispatch transfer (stock leaves source)
        $this->post(route('stock-transfers.dispatch', $transfer->id));
        $this->assertEquals('dispatched', $transfer->fresh()->status);
        $this->assertEquals(30, Stock::where('branch_id', $sourceBranch->id)->where('product_id', $product->id)->value('quantity'));

        // 4. Receive transfer (stock arrives at destination)
        $this->post(route('stock-transfers.receive', $transfer->id));
        $this->assertEquals('received', $transfer->fresh()->status);

        // Assert destination branch stock is now 20
        $destStock = Stock::where('branch_id', $destBranch->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($destStock);
        $this->assertEquals(20, $destStock->quantity);
    }

    public function test_instant_transfer_moves_stock_immediately(): void
    {
        $business = $this->createBusiness();
        $sourceBranch = $this->createBranch($business->id, 'Source Branch');
        $destBranch = $this->createBranch($business->id, 'Destination Branch');

        $user = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $sourceBranch->id,
            'job_title' => 'Tenant Administrator',
        ]);

        $product = $this->createProduct($business->id, 'Paint 20L', 12000);

        $stockService = app(StockService::class);
        $stockService->increase($sourceBranch->id, $product->id, 40, 12000, 'initial_stock');

        $this->actingAs($user);

        // Create with auto_complete = true
        $response = $this->post(route('stock-transfers.store'), [
            'from_branch_id' => $sourceBranch->id,
            'to_branch_id' => $destBranch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 15],
            ],
            'auto_complete' => true,
        ]);
        $response->assertRedirect(route('stock-transfers.index'));

        $transfer = StockTransfer::where('business_id', $business->id)->first();
        $this->assertEquals('received', $transfer->status);

        // Source decreased by 15 => 25
        $this->assertEquals(25, Stock::where('branch_id', $sourceBranch->id)->where('product_id', $product->id)->value('quantity'));

        // Destination increased by 15 => 15
        $destStock = Stock::where('branch_id', $destBranch->id)->where('product_id', $product->id)->first();
        $this->assertNotNull($destStock);
        $this->assertEquals(15, $destStock->quantity);
    }

    public function test_complete_transfer_endpoint_moves_stock_in_one_click(): void
    {
        $business = $this->createBusiness();
        $sourceBranch = $this->createBranch($business->id, 'Source Branch');
        $destBranch = $this->createBranch($business->id, 'Destination Branch');

        $user = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $sourceBranch->id,
            'job_title' => 'Tenant Administrator',
        ]);

        $product = $this->createProduct($business->id, 'Iron Sheet', 8000);

        $stockService = app(StockService::class);
        $stockService->increase($sourceBranch->id, $product->id, 30, 8000, 'initial_stock');

        $this->actingAs($user);

        // Create transfer in requested state
        $this->post(route('stock-transfers.store'), [
            'from_branch_id' => $sourceBranch->id,
            'to_branch_id' => $destBranch->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
            'auto_complete' => false,
        ]);

        $transfer = StockTransfer::where('business_id', $business->id)->first();

        // Admin clicks Complete Transfer
        $this->post(route('stock-transfers.complete', $transfer->id));

        $this->assertEquals('received', $transfer->fresh()->status);
        $this->assertEquals(20, Stock::where('branch_id', $sourceBranch->id)->where('product_id', $product->id)->value('quantity'));
        $this->assertEquals(10, Stock::where('branch_id', $destBranch->id)->where('product_id', $product->id)->value('quantity'));
    }
}
