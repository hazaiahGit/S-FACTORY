<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function createBusiness(): Business
    {
        return Business::create([
            'name' => 'Demo Hardware',
            'slug' => 'demo-hardware-'.uniqid(),
            'is_active' => true,
        ]);
    }

    public function test_expense_category_crud_operations(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'job_title' => 'Tenant Administrator',
        ]);

        $this->actingAs($user);

        // 1. Create
        $response = $this->post(route('expense-categories.store'), [
            'name' => 'Electricity & Water',
            'color' => '#f59e0b',
            'is_active' => true,
        ]);
        $response->assertSessionHas('success');

        $category = ExpenseCategory::where('business_id', $business->id)->where('name', 'Electricity & Water')->first();
        $this->assertNotNull($category);
        $this->assertEquals('#f59e0b', $category->color);
        $this->assertTrue($category->is_active);

        // 2. Read (Index)
        $indexResponse = $this->get(route('expense-categories.index'));
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn ($page) => $page
            ->component('ExpenseCategories/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Electricity & Water')
        );

        // 3. Update
        $updateResponse = $this->put(route('expense-categories.update', $category->id), [
            'name' => 'Utilities & Energy',
            'color' => '#10b981',
            'is_active' => false,
        ]);
        $updateResponse->assertSessionHas('success');

        $category->refresh();
        $this->assertEquals('Utilities & Energy', $category->name);
        $this->assertEquals('#10b981', $category->color);
        $this->assertFalse($category->is_active);

        // 4. Delete
        $deleteResponse = $this->delete(route('expense-categories.destroy', $category->id));
        $deleteResponse->assertSessionHas('success');
        $this->assertNull(ExpenseCategory::find($category->id));
    }

    public function test_expense_category_cannot_be_deleted_if_it_has_expenses(): void
    {
        $business = $this->createBusiness();
        $branch = Branch::create(['business_id' => $business->id, 'name' => 'Main Branch', 'code' => 'MAIN', 'is_active' => true]);
        $user = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'job_title' => 'Tenant Administrator',
        ]);

        $this->actingAs($user);

        $category = ExpenseCategory::create([
            'business_id' => $business->id,
            'name' => 'Office Rent',
            'color' => '#8b5cf6',
            'is_active' => true,
        ]);

        // Create an expense under this category
        Expense::create([
            'business_id' => $business->id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'user_id' => $user->id,
            'expense_number' => 'EXP-2026-0001',
            'title' => 'January Rent Payment',
            'amount' => 1500000,
            'expense_date' => '2026-01-05',
            'payment_method' => 'bank_transfer',
            'status' => 'approved',
        ]);

        // Attempting to delete must fail with error in session
        $deleteResponse = $this->delete(route('expense-categories.destroy', $category->id));
        $deleteResponse->assertSessionHas('error');
        $this->assertNotNull(ExpenseCategory::find($category->id));
    }

    public function test_expense_category_business_isolation(): void
    {
        $businessA = $this->createBusiness();
        $businessB = $this->createBusiness();

        $userA = User::factory()->create(['business_id' => $businessA->id]);

        $categoryB = ExpenseCategory::create([
            'business_id' => $businessB->id,
            'name' => 'Competitor Raw Materials',
            'is_active' => true,
        ]);

        $this->actingAs($userA);

        // User A trying to update or delete Business B's category must be 403 Forbidden
        $this->put(route('expense-categories.update', $categoryB->id), ['name' => 'Hacked'])->assertForbidden();
        $this->delete(route('expense-categories.destroy', $categoryB->id))->assertForbidden();
    }
}
