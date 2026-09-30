<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { BarChart3, TrendingUp, ShoppingCart, Package, DollarSign, Wallet, Users, Factory, FileDown } from '@lucide/vue';

const props = defineProps({
    metrics: Object
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value || 0);
};

const reports = [
    { name: 'Sales & Revenue', description: 'Analyze your income, best selling items, and sales trends.', icon: TrendingUp, color: 'text-indigo-600', bg: 'bg-indigo-50', route: 'reports.sales' },
    { name: 'Purchases', description: 'Track your procurement, supplier costs, and purchase history.', icon: ShoppingCart, color: 'text-emerald-600', bg: 'bg-emerald-50', route: 'reports.purchases' },
    { name: 'Inventory & Stock', description: 'View stock valuation, low stock alerts, and movement.', icon: Package, color: 'text-amber-600', bg: 'bg-amber-50', route: 'reports.inventory' },
    { name: 'Profit & Loss', description: 'Comprehensive income statement detailing your net margins.', icon: DollarSign, color: 'text-rose-600', bg: 'bg-rose-50', route: 'reports.profit-loss' },
    { name: 'Expenses', description: 'Breakdown of your operating costs and overheads.', icon: Wallet, color: 'text-fuchsia-600', bg: 'bg-fuchsia-50', route: 'reports.expenses' },
    { name: 'Customers', description: 'Customer acquisition, retention, and debt analysis.', icon: Users, color: 'text-blue-600', bg: 'bg-blue-50', route: 'reports.customers' },
    { name: 'Manufacturing', description: 'Production efficiency, material usage, and yields.', icon: Factory, color: 'text-orange-600', bg: 'bg-orange-50', route: 'reports.manufacturing' },
];
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <BarChart3 class="w-6 h-6 mr-3 text-indigo-500" />
                Reports & Analytics
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Monthly Overview -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-6 flex items-center">
                    Overview: {{ metrics.month_name }}
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100 relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-xs font-bold text-indigo-800 uppercase tracking-wider mb-1">Total Sales</p>
                            <p class="text-2xl font-black text-indigo-900">{{ formatCurrency(metrics.monthly_sales) }}</p>
                        </div>
                        <TrendingUp class="absolute -right-4 -bottom-4 w-24 h-24 text-indigo-500 opacity-10" />
                    </div>
                    
                    <div class="p-4 bg-emerald-50 rounded-lg border border-emerald-100 relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-xs font-bold text-emerald-800 uppercase tracking-wider mb-1">Total Purchases</p>
                            <p class="text-2xl font-black text-emerald-900">{{ formatCurrency(metrics.monthly_purchases) }}</p>
                        </div>
                        <ShoppingCart class="absolute -right-4 -bottom-4 w-24 h-24 text-emerald-500 opacity-10" />
                    </div>
                    
                    <div class="p-4 bg-rose-50 rounded-lg border border-rose-100 relative overflow-hidden">
                        <div class="relative z-10">
                            <p class="text-xs font-bold text-rose-800 uppercase tracking-wider mb-1">Operating Expenses</p>
                            <p class="text-2xl font-black text-rose-900">{{ formatCurrency(metrics.monthly_expenses) }}</p>
                        </div>
                        <Wallet class="absolute -right-4 -bottom-4 w-24 h-24 text-rose-500 opacity-10" />
                    </div>
                </div>
            </div>

            <!-- Report Modules -->
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider pt-4">Available Reports</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link v-for="report in reports" :key="report.name" :href="route(report.route)" class="group bg-white rounded-xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="flex items-start gap-4">
                        <div :class="['p-3 rounded-xl flex-shrink-0', report.bg]">
                            <component :is="report.icon" :class="['w-6 h-6', report.color]" />
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ report.name }}</h4>
                            <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ report.description }}</p>
                        </div>
                    </div>
                    
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-slate-100">
                        <div :class="['h-full w-0 group-hover:w-full transition-all duration-300', report.bg.replace('50', '500')]"></div>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
