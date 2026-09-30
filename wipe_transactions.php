<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Wiping transaction records to start fresh...\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

$tablesToWipe = [
    'sales', 'sale_items', 'sale_payments', 'sale_returns',
    'purchases', 'purchase_items', 'purchase_payments', 'purchase_returns',
    'stock_transfers', 'stock_transfer_items',
    'production_orders', 'production_materials',
    'stocks', 'stock_movements',
    'expenses', 'approval_requests', 'customer_payments',
    'product_batches', 'cashier_shifts',
    // We intentionally KEEP: users, roles, permissions, business, branches, categories, units, products, customers, suppliers
];

foreach($tablesToWipe as $table) {
    if (Schema::hasTable($table)) {
        DB::table($table)->truncate();
        echo "Truncated $table\n";
    }
}

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "\nDone! All business transactions wiped. Master data (Products, Users, Customers, etc.) kept intact.\n";
