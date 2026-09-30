<?php

namespace Tests\Feature;

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
}
