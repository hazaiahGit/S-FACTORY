<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAndSupplierCrudTest extends TestCase
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

    public function test_customer_crud_operations(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'job_title' => 'Tenant Administrator',
        ]);

        $this->actingAs($user);

        // 1. Create
        $response = $this->post(route('customers.store'), [
            'name' => 'John Doe Builders',
            'phone' => '+255712345678',
            'email' => 'john@builders.com',
            'address' => 'Plot 45, Mwenge, Dar es Salaam',
            'customer_type' => 'wholesale',
            'credit_allowed' => true,
            'credit_limit' => 5000000,
        ]);
        $response->assertRedirect(route('customers.index'));

        $customer = Customer::where('business_id', $business->id)->where('name', 'John Doe Builders')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('+255712345678', $customer->phone);

        // 2. Read (Index & Show)
        $this->get(route('customers.index'))->assertOk();
        $this->get(route('customers.show', $customer->id))->assertOk();
        $this->get(route('customers.edit', $customer->id))->assertOk();

        // 3. Update
        $updateResponse = $this->put(route('customers.update', $customer->id), [
            'name' => 'John Doe Enterprises',
            'phone' => '+255788888888',
            'email' => 'john@enterprises.com',
            'customer_type' => 'vip',
            'credit_allowed' => true,
            'credit_limit' => 10000000,
        ]);
        $updateResponse->assertRedirect(route('customers.index'));
        $this->assertEquals('John Doe Enterprises', $customer->fresh()->name);
        $this->assertEquals('+255788888888', $customer->fresh()->phone);

        // 4. Delete
        $deleteResponse = $this->delete(route('customers.destroy', $customer->id));
        $deleteResponse->assertRedirect(route('customers.index'));
        $this->assertNull(Customer::find($customer->id));
    }

    public function test_supplier_crud_operations(): void
    {
        $business = $this->createBusiness();
        $user = User::factory()->create([
            'business_id' => $business->id,
            'job_title' => 'Tenant Administrator',
        ]);

        $this->actingAs($user);

        // 1. Create
        $response = $this->post(route('suppliers.store'), [
            'name' => 'Simba Cement Suppliers Ltd',
            'contact_person' => 'Juma Simba',
            'phone' => '+255755123456',
            'email' => 'orders@simbacement.tz',
            'address' => 'Industrial Area, Tanga',
            'tax_number' => 'TIN-987654321',
            'payment_terms' => 'credit_30',
            'credit_days' => 30,
            'credit_limit' => 20000000,
        ]);
        $response->assertRedirect(route('suppliers.index'));

        $supplier = Supplier::where('business_id', $business->id)->where('name', 'Simba Cement Suppliers Ltd')->first();
        $this->assertNotNull($supplier);
        $this->assertEquals('Juma Simba', $supplier->contact_person);

        // 2. Read (Index & Show)
        $this->get(route('suppliers.index'))->assertOk();
        $this->get(route('suppliers.show', $supplier->id))->assertOk();
        $this->get(route('suppliers.edit', $supplier->id))->assertOk();

        // 3. Update
        $updateResponse = $this->put(route('suppliers.update', $supplier->id), [
            'name' => 'Simba Cement Industries Ltd',
            'contact_person' => 'Bakari Simba',
            'phone' => '+255755999999',
            'email' => 'sales@simbacement.tz',
            'tax_number' => 'TIN-987654321',
            'payment_terms' => 'credit_60',
            'credit_days' => 60,
            'credit_limit' => 25000000,
        ]);
        $updateResponse->assertRedirect(route('suppliers.index'));
        $this->assertEquals('Simba Cement Industries Ltd', $supplier->fresh()->name);
        $this->assertEquals('Bakari Simba', $supplier->fresh()->contact_person);

        // 4. Delete
        $deleteResponse = $this->delete(route('suppliers.destroy', $supplier->id));
        $deleteResponse->assertRedirect(route('suppliers.index'));
        $this->assertNull(Supplier::find($supplier->id));
    }

    public function test_non_admin_only_sees_their_branch_and_cannot_access_other_branches(): void
    {
        $business = $this->createBusiness();
        $branchA = Branch::create(['business_id' => $business->id, 'name' => 'Branch A', 'code' => 'BRA', 'is_active' => true]);
        $branchB = Branch::create(['business_id' => $business->id, 'name' => 'Branch B', 'code' => 'BRB', 'is_active' => true]);

        // Regular user assigned to Branch A
        $userA = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branchA->id,
        ]);

        $this->actingAs($userA);

        // 1. Tagging: Non-admin creating customer is tagged with Branch A even if requesting Branch B
        $response = $this->post(route('customers.store'), [
            'name' => 'Branch A Customer',
            'branch_id' => $branchB->id, // Attempt to assign to Branch B
        ]);
        $response->assertRedirect(route('customers.index'));
        $customerA = Customer::where('name', 'Branch A Customer')->first();
        $this->assertEquals($branchA->id, $customerA->branch_id);

        // Create a customer for Branch B directly
        $customerB = Customer::create([
            'business_id' => $business->id,
            'branch_id' => $branchB->id,
            'name' => 'Branch B Customer',
            'is_active' => true,
        ]);

        // 2. Non-admin A should only see Customer A in index, not Customer B
        $indexResponse = $this->get(route('customers.index'));
        $indexResponse->assertOk();
        $indexResponse->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.id', $customerA->id)
        );

        // 3. Non-admin A attempting to view/edit/delete Customer B must get 403 Forbidden
        $this->get(route('customers.show', $customerB->id))->assertForbidden();
        $this->get(route('customers.edit', $customerB->id))->assertForbidden();
        $this->put(route('customers.update', $customerB->id), ['name' => 'Hacked'])->assertForbidden();
        $this->delete(route('customers.destroy', $customerB->id))->assertForbidden();

        // 4. Non-admin A cannot change active branch via endpoint
        $this->post(route('active-branch.update'), ['branch_id' => $branchB->id])->assertForbidden();
    }
}
