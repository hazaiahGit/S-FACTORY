<?php

use App\Http\Controllers\ActiveBranchController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\Manufacturing\BillOfMaterialController;
use App\Http\Controllers\Manufacturing\ProductionOrderController;
// use App\Http\Controllers\PurchasePaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalePaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockTakeController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\System\PackageController;
use App\Http\Controllers\System\TenantController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ─────────────────────────────────────────────────────────

Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->is_system_admin && ! auth()->user()->business_id) {
            return redirect()->route('system.tenants.index');
        }

        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

// ─── Authenticated Routes ─────────────────────────────────────────────────

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ─── Products & Catalogue ──────────────────────────────────────────

    Route::resource('products', ProductController::class);
    Route::get('products/{product}/stock-history', [ProductController::class, 'stockHistory'])->name('products.stock-history');
    Route::get('products/{product}/price-history', [ProductController::class, 'priceHistory'])->name('products.price-history');
    Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
    Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
    Route::post('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');

    Route::resource('categories', CategoryController::class);
    Route::match(['post', 'patch'], 'categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

    Route::resource('units', UnitController::class);
    Route::match(['post', 'patch'], 'units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('units.toggle-status');

    Route::resource('product-types', ProductTypeController::class);
    Route::match(['post', 'patch'], 'product-types/{productType}/toggle-status', [ProductTypeController::class, 'toggleStatus'])->name('product-types.toggle-status');

    Route::resource('brands', BrandController::class);

    // ─── Suppliers ────────────────────────────────────────────────────

    Route::resource('suppliers', SupplierController::class);
    Route::get('suppliers/{supplier}/statement', [SupplierController::class, 'statement'])->name('suppliers.statement');

    // ─── Customers ────────────────────────────────────────────────────

    Route::resource('customers', CustomerController::class);
    Route::get('customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
    Route::post('customers/{customer}/payment', [CustomerController::class, 'recordPayment'])->name('customers.payment');

    // ─── Purchases ────────────────────────────────────────────────────

    Route::resource('purchases', PurchaseController::class);
    // Route::post('purchases/{purchase}/payment', [PurchasePaymentController::class, 'store'])->name('purchases.payment');
    Route::get('purchases/{purchase}/print', [PurchaseController::class, 'print'])->name('purchases.print');

    // ─── Sales / POS ──────────────────────────────────────────────────

    Route::resource('sales', SaleController::class);
    Route::get('pos', [SaleController::class, 'pos'])->name('pos');
    // Route::post('sales/{sale}/payment', [SalePaymentController::class, 'store'])->name('sales.payment');
    Route::post('sales/{sale}/return', [SaleController::class, 'createReturn'])->name('sales.return');
    Route::get('sales/{sale}/print', [SaleController::class, 'print'])->name('sales.print');
    Route::post('sales/{sale}/convert-to-sale', [SaleController::class, 'convertToSale'])->name('sales.convert');
    Route::patch('sales/{sale}/status', [SaleController::class, 'updateStatus'])->name('sales.status');

    // ─── Expenses ─────────────────────────────────────────────────────

    Route::resource('expenses', ExpenseController::class);

    // ─── Inventory / Stock ────────────────────────────────────────────

    Route::get('inventory', [StockController::class, 'index'])->name('inventory.index');
    Route::get('inventory/movements', [StockController::class, 'movements'])->name('inventory.movements');
    Route::delete('inventory/movements/{movement}', [StockController::class, 'destroyMovement'])->name('inventory.movements.destroy');
    Route::get('inventory/low-stock', [StockController::class, 'lowStock'])->name('inventory.low-stock');
    Route::get('inventory/valuation', [StockController::class, 'valuation'])->name('inventory.valuation');

    // Stock Transfers
    Route::resource('stock-transfers', StockTransferController::class);
    Route::post('stock-transfers/{stockTransfer}/approve', [StockTransferController::class, 'approve'])->name('stock-transfers.approve');
    Route::post('stock-transfers/{stockTransfer}/dispatch', [StockTransferController::class, 'dispatch'])->name('stock-transfers.dispatch');
    Route::post('stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive'])->name('stock-transfers.receive');

    // Stock Takes
    Route::resource('stock-takes', StockTakeController::class);
    Route::post('stock-takes/{stockTake}/approve', [StockTakeController::class, 'approve'])->name('stock-takes.approve');
    Route::post('stock-takes/{stockTake}/complete', [StockTakeController::class, 'complete'])->name('stock-takes.complete');

    // Stock Adjustments
    Route::resource('stock-adjustments', StockAdjustmentController::class);
    Route::post('stock-adjustments/{stockAdjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('stock-adjustments.approve');

    // ─── Manufacturing ────────────────────────────────────────────────

    Route::resource('manufacturing/bom', BillOfMaterialController::class)->names([
        'index' => 'bom.index',
        'create' => 'bom.create',
        'store' => 'bom.store',
        'show' => 'bom.show',
        'edit' => 'bom.edit',
        'update' => 'bom.update',
        'destroy' => 'bom.destroy',
    ]);

    // Manufacturing / Production Orders — removed from UI per user request
    // Route::resource('manufacturing/production', ProductionOrderController::class)->parameters([
    //     'production' => 'productionOrder'
    // ])->names([
    //     'index' => 'production.index',
    //     'create' => 'production.create',
    //     'store' => 'production.store',
    //     'show' => 'production.show',
    //     'edit' => 'production.edit',
    //     'update' => 'production.update',
    //     'destroy' => 'production.destroy',
    // ]);
    // Route::post('manufacturing/production/{productionOrder}/start', [ProductionOrderController::class, 'start'])->name('production.start');
    // Route::post('manufacturing/production/{productionOrder}/complete', [ProductionOrderController::class, 'complete'])->name('production.complete');
    // Route::post('manufacturing/production/{productionOrder}/approve', [ProductionOrderController::class, 'approve'])->name('production.approve');

    // ─── Branches ─────────────────────────────────────────────────────

    Route::resource('branches', BranchController::class);

    // ─── Users & Roles ────────────────────────────────────────────────

    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::resource('roles', RoleController::class);
    Route::post('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('roles.sync-permissions');

    // ─── Targets & Performance ────────────────────────────────────────

    Route::post('/set-active-branch', [ActiveBranchController::class, 'update'])->name('active-branch.update');
    // ─── System Administrator Area ─────────────────────────────────────
    Route::middleware(['system.admin'])->prefix('system')->name('system.')->group(function () {
        Route::resource('tenants', TenantController::class)->except(['create', 'show', 'edit']);
        Route::put('tenants/{tenant}/subscription', [TenantController::class, 'updateSubscription'])->name('tenants.subscription');
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::resource('packages', PackageController::class)->except(['create', 'show', 'edit']);
    });

    Route::resource('targets', TargetController::class);

    // ─── Reports ──────────────────────────────────────────────────────

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('expenses', [ReportController::class, 'expenses'])->name('expenses');
        Route::get('customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('suppliers', [ReportController::class, 'suppliers'])->name('suppliers');
        Route::get('manufacturing', [ReportController::class, 'manufacturing'])->name('manufacturing');
        Route::get('branches', [ReportController::class, 'branches'])->name('branches');

        // Exports
        Route::get('export/{type}', [ReportController::class, 'export'])->name('export');
        Route::get('pdf/{type}', [ReportController::class, 'pdf'])->name('pdf');
    });

    // ─── Audit Logs ───────────────────────────────────────────────────

    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // ─── Approvals ────────────────────────────────────────────────────

    Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('approvals/{approvalRequest}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('approvals/{approvalRequest}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');

    // ─── Settings ─────────────────────────────────────────────────────

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/business', [SettingsController::class, 'updateBusiness'])->name('settings.business');
    Route::post('settings/logo', [SettingsController::class, 'uploadLogo'])->name('settings.logo');

    // ─── Notifications ────────────────────────────────────────────────

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // ─── Language Switch ──────────────────────────────────────────────

    Route::post('language/{locale}', function (string $locale) {
        abort_unless(in_array($locale, ['en', 'sw']), 404);
        auth()->user()->update(['preferred_language' => $locale]);
        session(['locale' => $locale]);

        return back();
    })->name('language.switch');
});

require __DIR__.'/auth.php';
