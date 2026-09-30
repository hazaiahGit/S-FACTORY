<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\ProductType;
use App\Models\Unit;
use Illuminate\Support\Str;

class TenantCatalogSeederService
{
    /**
     * Seed default categories, units of measure, and product types for a specific tenant.
     */
    public static function seedTenantDefaults(Business $business): void
    {
        // 1. Seed Default Product Types
        $defaultProductTypes = [
            [
                'name' => 'Standard Product',
                'code' => 'standard_product',
                'description' => 'Regular commercial items bought from suppliers and sold to customers.',
                'is_sold' => true,
                'is_purchased' => true,
                'is_manufactured' => false,
                'track_stock' => true,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Raw Material',
                'code' => 'raw_material',
                'description' => 'Ingredients and raw materials used in factory Bill of Materials (BOM) production.',
                'is_sold' => false,
                'is_purchased' => true,
                'is_manufactured' => false,
                'track_stock' => true,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Finished Goods',
                'code' => 'finished_goods',
                'description' => 'Manufactured items produced on-site and sold to retail or wholesale clients.',
                'is_sold' => true,
                'is_purchased' => false,
                'is_manufactured' => true,
                'track_stock' => true,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Consumables & Supplies',
                'code' => 'consumables',
                'description' => 'Operational items used internally (packaging, safety gear, fuel).',
                'is_sold' => false,
                'is_purchased' => true,
                'is_manufactured' => false,
                'track_stock' => true,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Service / Labor',
                'code' => 'service',
                'description' => 'Non-inventory services, installations, repairs, or delivery fees.',
                'is_sold' => true,
                'is_purchased' => false,
                'is_manufactured' => false,
                'track_stock' => false,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($defaultProductTypes as $type) {
            ProductType::firstOrCreate(
                ['business_id' => $business->id, 'code' => $type['code']],
                $type
            );
        }

        // 2. Seed Default Units of Measure (UOM)
        $defaultUnits = [
            [
                'name' => 'Pieces',
                'abbreviation' => 'pcs',
                'type' => 'quantity',
                'allow_decimal' => false,
                'description' => 'Countable individual items',
                'is_active' => true,
            ],
            [
                'name' => 'Kilograms',
                'abbreviation' => 'kg',
                'type' => 'weight',
                'allow_decimal' => true,
                'description' => 'Standard metric weight',
                'is_active' => true,
            ],
            [
                'name' => 'Meters',
                'abbreviation' => 'm',
                'type' => 'length',
                'allow_decimal' => true,
                'description' => 'Linear metric measurement (pipes, cables, rebar)',
                'is_active' => true,
            ],
            [
                'name' => 'Box / Carton',
                'abbreviation' => 'box',
                'type' => 'quantity',
                'allow_decimal' => false,
                'description' => 'Packaged bundle or box',
                'is_active' => true,
            ],
            [
                'name' => 'Liters',
                'abbreviation' => 'L',
                'type' => 'volume',
                'allow_decimal' => true,
                'description' => 'Liquid volume (paints, solvents, oils)',
                'is_active' => true,
            ],
            [
                'name' => 'Bags',
                'abbreviation' => 'bag',
                'type' => 'quantity',
                'allow_decimal' => false,
                'description' => 'Bagged items (cement, plaster, sand)',
                'is_active' => true,
            ],
        ];

        foreach ($defaultUnits as $unit) {
            Unit::firstOrCreate(
                ['business_id' => $business->id, 'abbreviation' => $unit['abbreviation']],
                $unit
            );
        }

        // 3. Seed Default Categories
        $defaultCategories = [
            [
                'name' => 'General Hardware',
                'code' => 'GEN',
                'color' => '#f59e0b',
                'description' => 'Fasteners, nails, screws, hinges, and locks',
                'is_active' => true,
            ],
            [
                'name' => 'Building & Construction',
                'code' => 'BLD',
                'color' => '#3b82f6',
                'description' => 'Cement, rebar, steel beams, roofing sheets, and timber',
                'is_active' => true,
            ],
            [
                'name' => 'Tools & Equipment',
                'code' => 'TLS',
                'color' => '#10b981',
                'description' => 'Power tools, hand tools, cutting discs, and welding equipment',
                'is_active' => true,
            ],
            [
                'name' => 'Paints & Finishes',
                'code' => 'PNT',
                'color' => '#ec4899',
                'description' => 'Wall paints, undercoats, thinners, rollers, and brushes',
                'is_active' => true,
            ],
            [
                'name' => 'Electrical & Plumbing',
                'code' => 'ELP',
                'color' => '#6366f1',
                'description' => 'Cables, conduit pipes, switches, PVC fittings, and valves',
                'is_active' => true,
            ],
        ];

        foreach ($defaultCategories as $category) {
            Category::firstOrCreate(
                ['business_id' => $business->id, 'slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'code' => $category['code'],
                    'color' => $category['color'],
                    'description' => $category['description'],
                    'is_active' => $category['is_active'],
                ]
            );
        }

        // 4. Seed Default Expense Categories
        $defaultExpenseCategories = [
            ['name' => 'Electricity & Water', 'color' => '#f59e0b'],
            ['name' => 'Rent & Rates', 'color' => '#8b5cf6'],
            ['name' => 'Salaries & Wages', 'color' => '#10b981'],
            ['name' => 'Logistics & Transport', 'color' => '#3b82f6'],
            ['name' => 'Repairs & Maintenance', 'color' => '#f43f5e'],
            ['name' => 'Office & Supplies', 'color' => '#64748b'],
        ];

        foreach ($defaultExpenseCategories as $expenseCat) {
            ExpenseCategory::firstOrCreate(
                ['business_id' => $business->id, 'name' => $expenseCat['name']],
                [
                    'color' => $expenseCat['color'],
                    'is_active' => true,
                ]
            );
        }
    }
}
