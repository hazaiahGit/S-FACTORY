<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchScopedDataAndNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']);
    }

    protected function createBusiness(string $name = 'Test Business'): Business
    {
        return Business::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'is_active' => true,
        ]);
    }

    protected function createBranch(int $businessId, string $name = 'Branch 1'): Branch
    {
        return Branch::create([
            'business_id' => $businessId,
            'name' => $name,
            'code' => strtoupper(Str::random(4)),
            'is_active' => true,
        ]);
    }

    public function test_tenant_admin_can_switch_active_branch_including_all_branches(): void
    {
        $business = $this->createBusiness();
        $branch1 = $this->createBranch($business->id, 'Main Branch');
        $branch2 = $this->createBranch($business->id, 'Branch 2');

        $admin = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch1->id,
        ]);
        $admin->assignRole('Super Admin');

        // 1. Switch to branch 2
        $response = $this->actingAs($admin)->post(route('active-branch.update'), [
            'branch_id' => $branch2->id,
        ]);
        $response->assertSessionHas('active_branch_id', $branch2->id);

        // 2. Switch to All Branches (null)
        $response = $this->actingAs($admin)->post(route('active-branch.update'), [
            'branch_id' => null,
        ]);
        $response->assertSessionHas('active_branch_id', 'all');
        $this->assertNull($admin->active_branch_id);
    }

    public function test_tenant_admin_has_access_to_all_notifications_across_business(): void
    {
        $business = $this->createBusiness();
        $branch1 = $this->createBranch($business->id, 'Main Branch');
        $branch2 = $this->createBranch($business->id, 'Branch 2');

        $admin = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch1->id,
        ]);
        $admin->assignRole('Super Admin');

        $service = app(NotificationService::class);
        $service->send($business->id, $branch1->id, 'alert', 'Branch 1 Alert', 'Low stock at branch 1');
        $service->send($business->id, $branch2->id, 'warning', 'Branch 2 Warning', 'Credit sale at branch 2');
        $service->send($business->id, null, 'info', 'Global Info', 'General update');

        $this->actingAs($admin);
        $notifications = AppNotification::forUser($admin)->get();

        $this->assertCount(3, $notifications);
    }

    public function test_branch_staff_only_sees_their_branch_and_global_notifications(): void
    {
        $business = $this->createBusiness();
        $branch1 = $this->createBranch($business->id, 'Main Branch');
        $branch2 = $this->createBranch($business->id, 'Branch 2');

        $cashier = User::factory()->create([
            'business_id' => $business->id,
            'branch_id' => $branch1->id,
        ]);
        $cashier->assignRole('Cashier');

        $service = app(NotificationService::class);
        $service->send($business->id, $branch1->id, 'alert', 'Branch 1 Alert', 'Low stock at branch 1');
        $service->send($business->id, $branch2->id, 'warning', 'Branch 2 Warning', 'Credit sale at branch 2');
        $service->send($business->id, null, 'info', 'Global Info', 'General update');

        $this->actingAs($cashier);
        $notifications = AppNotification::forUser($cashier)->get();

        $this->assertCount(2, $notifications);
        $this->assertTrue($notifications->contains('title', 'Branch 1 Alert'));
        $this->assertTrue($notifications->contains('title', 'Global Info'));
        $this->assertFalse($notifications->contains('title', 'Branch 2 Warning'));
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $business = $this->createBusiness();
        $admin = User::factory()->create(['business_id' => $business->id]);
        $admin->assignRole('Super Admin');

        $service = app(NotificationService::class);
        $notification = $service->send($business->id, null, 'info', 'Test Notif', 'Test Message');

        $this->actingAs($admin);
        $this->assertEquals(1, AppNotification::forUser($admin)->unreadFor($admin)->count());

        $this->post(route('notifications.read', $notification->id));

        $this->assertEquals(0, AppNotification::forUser($admin)->unreadFor($admin)->count());
        $this->assertTrue($notification->fresh()->isReadBy($admin));
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $business = $this->createBusiness();
        $admin = User::factory()->create(['business_id' => $business->id]);
        $admin->assignRole('Super Admin');

        $service = app(NotificationService::class);
        $service->send($business->id, null, 'info', 'Notif 1', 'Message 1');
        $service->send($business->id, null, 'info', 'Notif 2', 'Message 2');

        $this->actingAs($admin);
        $this->assertEquals(2, AppNotification::forUser($admin)->unreadFor($admin)->count());

        $this->post(route('notifications.read-all'));

        $this->assertEquals(0, AppNotification::forUser($admin)->unreadFor($admin)->count());
    }
}
