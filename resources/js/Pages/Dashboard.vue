<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    TrendingUp,
    TrendingDown,
    Package,
    ShoppingCart,
    Wallet,
    AlertTriangle,
    ArrowUpRight,
    ArrowDownRight,
    DollarSign,
    Factory
} from '@lucide/vue';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Filler
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, PointElement, LineElement, Filler);

const props = defineProps({
    period: String,
    dateFrom: String,
    dateTo: String,
    branchId: [Number, String, null],
    kpis: Object,
    charts: Object,
    permissions: { type: Object, default: () => ({ canViewProfit: true, canViewStockValue: true, canViewReport: true }) },
    recent_sales: Array,
    branches: Array,
});

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '***';
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const handlePeriodChange = (e) => {
    router.get(route('dashboard'), { period: e.target.value, branch_id: props.branchId }, { preserveState: true });
};

const handleBranchChange = (e) => {
    router.get(route('dashboard'), { period: props.period, branch_id: e.target.value }, { preserveState: true });
};

// Chart Data setup
const salesChartData = computed(() => {
    const datasets = [
        {
            label: 'Revenue',
            backgroundColor: 'rgba(59, 130, 246, 0.2)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(59, 130, 246, 1)',
            fill: true,
            data: props.charts.sales_trend.map(item => item.revenue)
        },
    ];

    if (props.permissions.canViewProfit) {
        datasets.push({
            label: 'Profit',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderColor: 'rgba(16, 185, 129, 1)',
            borderWidth: 2,
            borderDash: [5, 5],
            fill: false,
            data: props.charts.sales_trend.map(item => item.profit)
        });
    }

    return {
        labels: props.charts.sales_trend.map(item => item.date),
        datasets,
    };
});

const salesChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { position: 'top' },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) { label += ': '; }
                    if (context.parsed.y !== null) {
                        label += new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(context.parsed.y);
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        y: { beginAtZero: true, ticks: { callback: (value) => value >= 1000 ? (value/1000) + 'k' : value } }
    }
};

const topProductsChartData = computed(() => {
    return {
        labels: props.charts.top_products.map(p => p.name.length > 15 ? p.name.substring(0, 15) + '...' : p.name),
        datasets: [{
            label: 'Quantity Sold',
            backgroundColor: 'rgba(245, 158, 11, 0.8)', // Amber
            borderRadius: 4,
            data: props.charts.top_products.map(p => p.total_qty)
        }]
    };
});

const topProductsOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y', // Horizontal bar chart
    plugins: { legend: { display: false } },
    scales: { x: { beginAtZero: true } }
};
</script>

<template>
    <AppLayout>
        <template #header>Dashboard Overview</template>

        <div class="space-y-6">
            <!-- Controls -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0 bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <div class="flex space-x-2">
                    <select :value="period" @change="handlePeriodChange" class="rounded-md border-slate-300 py-2 pl-3 pr-10 text-sm focus:border-amber-500 focus:outline-none focus:ring-amber-500">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                    
                    <select v-if="branches.length > 1" :value="branchId || ''" @change="handleBranchChange" class="rounded-md border-slate-300 py-2 pl-3 pr-10 text-sm focus:border-amber-500 focus:outline-none focus:ring-amber-500">
                        <option value="">All Branches</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                </div>
                
                <div class="text-sm text-slate-500 font-medium bg-slate-100 px-3 py-1.5 rounded-md">
                    Showing data from <span class="text-slate-800">{{ formatDate(dateFrom) }}</span> to <span class="text-slate-800">{{ formatDate(dateTo) }}</span>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Revenue -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-blue-50 rounded-full opacity-50"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Total Revenue</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ formatCurrency(kpis.revenue) }}</h3>
                        </div>
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-lg">
                            <ShoppingCart class="w-6 h-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-slate-500">{{ kpis.total_sales_count }} sales recorded</span>
                    </div>
                </div>

                <!-- Gross Profit -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-emerald-50 rounded-full opacity-50"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Gross Profit</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ formatCurrency(kpis.gross_profit) }}</h3>
                        </div>
                        <div class="p-3 bg-emerald-100 text-emerald-600 rounded-lg">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span v-if="kpis.revenue > 0" class="text-emerald-600 font-medium">
                            {{ ((kpis.gross_profit / kpis.revenue) * 100).toFixed(1) }}% Margin
                        </span>
                        <span v-else class="text-slate-500">0% Margin</span>
                    </div>
                </div>

                <!-- Expenses -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-rose-50 rounded-full opacity-50"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Expenses</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ formatCurrency(kpis.total_expenses) }}</h3>
                        </div>
                        <div class="p-3 bg-rose-100 text-rose-600 rounded-lg">
                            <TrendingDown class="w-6 h-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-slate-500">
                        Net Profit: <span :class="['ml-1 font-semibold', kpis.net_profit >= 0 ? 'text-emerald-600' : 'text-rose-600']">{{ formatCurrency(kpis.net_profit) }}</span>
                    </div>
                </div>

                <!-- Inventory Value -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200 hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-amber-50 rounded-full opacity-50"></div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Stock Value</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ formatCurrency(kpis.stock_value) }}</h3>
                        </div>
                        <div class="p-3 bg-amber-100 text-amber-600 rounded-lg">
                            <Package class="w-6 h-6" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm space-x-4">
                        <div v-if="kpis.low_stock_count > 0" class="flex items-center text-amber-600">
                            <AlertTriangle class="w-4 h-4 mr-1" />
                            <span>{{ kpis.low_stock_count }} Low</span>
                        </div>
                        <div v-if="kpis.out_of_stock_count > 0" class="flex items-center text-rose-600">
                            <AlertTriangle class="w-4 h-4 mr-1" />
                            <span>{{ kpis.out_of_stock_count }} Empty</span>
                        </div>
                        <span v-if="kpis.low_stock_count === 0 && kpis.out_of_stock_count === 0" class="text-emerald-600">Healthy Stock</span>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Sales Trend -->
                <div class="lg:col-span-2 bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">
                        {{ permissions.canViewProfit ? 'Revenue & Profit Trend' : 'Revenue Trend' }}
                    </h3>
                    <div class="h-80">
                        <Line v-if="charts.sales_trend.length > 0" :data="salesChartData" :options="salesChartOptions" />
                        <div v-else class="h-full flex items-center justify-center text-slate-400">No data available for this period.</div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">Top Selling Products</h3>
                    <div class="h-80">
                        <Bar v-if="charts.top_products.length > 0" :data="topProductsChartData" :options="topProductsOptions" />
                        <div v-else class="h-full flex items-center justify-center text-slate-400">No data available for this period.</div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Recent Sales & Debts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Sales Table -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50/50">
                        <h3 class="text-lg font-semibold text-slate-800">Recent Sales</h3>
                        <Link :href="route('sales.index')" class="text-sm text-amber-600 hover:text-amber-700 font-medium">View All &rarr;</Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Invoice</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Customer</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <tr v-for="sale in recent_sales" :key="sale.id" class="hover:bg-slate-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                        <Link :href="route('sales.show', sale.id)" class="text-amber-600 hover:underline">{{ sale.sale_number }}</Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        {{ sale.customer?.name || 'Walk-in Customer' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="[
                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                            sale.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 
                                            sale.payment_status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800'
                                        ]">
                                            {{ sale.payment_status.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800 text-right">
                                        {{ formatCurrency(sale.total_amount) }}
                                    </td>
                                </tr>
                                <tr v-if="recent_sales.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">No recent sales found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Debt & Pending Actions -->
                <div class="space-y-6">
                    <!-- Debt Summary -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Financial Obligations</h3>
                        
                        <div class="space-y-4">
                            <div v-if="kpis.customer_debt !== null" class="flex justify-between items-center p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                                <div>
                                    <p class="text-xs text-emerald-600 font-semibold uppercase tracking-wider">Customers Owe You</p>
                                    <p class="text-lg font-bold text-emerald-700">{{ formatCurrency(kpis.customer_debt) }}</p>
                                </div>
                                <ArrowDownRight class="w-8 h-8 text-emerald-400 opacity-50" />
                            </div>

                            <div v-if="kpis.supplier_debt !== null" class="flex justify-between items-center p-3 bg-rose-50 rounded-lg border border-rose-100">
                                <div>
                                    <p class="text-xs text-rose-600 font-semibold uppercase tracking-wider">You Owe Suppliers</p>
                                    <p class="text-lg font-bold text-rose-700">{{ formatCurrency(kpis.supplier_debt) }}</p>
                                </div>
                                <ArrowUpRight class="w-8 h-8 text-rose-400 opacity-50" />
                            </div>
                            
                            <div v-if="kpis.customer_debt === null && kpis.supplier_debt === null" class="text-sm text-slate-500 italic">
                                You do not have permission to view financial debts.
                            </div>
                        </div>
                    </div>

                    <!-- Pending Approvals -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-slate-200">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Action Required</h3>
                        
                        <Link :href="route('approvals.index')" class="flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors group">
                            <div class="flex items-center">
                                <div class="bg-amber-100 p-2 rounded-md mr-3 group-hover:bg-amber-200 transition-colors">
                                    <ClipboardList class="w-5 h-5 text-amber-700" />
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800">Pending Approvals</p>
                                </div>
                            </div>
                            <span v-if="kpis.pending_approvals > 0" class="inline-flex items-center justify-center w-6 h-6 bg-rose-100 text-rose-700 text-xs font-bold rounded-full">
                                {{ kpis.pending_approvals }}
                            </span>
                            <span v-else class="text-sm text-slate-400">None</span>
                        </Link>
                        
                        <div class="mt-3 flex items-center justify-between p-3 rounded-lg border border-slate-200 bg-slate-50">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-md mr-3">
                                    <Factory class="w-5 h-5 text-blue-700" />
                                </div>
                                <div>
                                    <p class="font-medium text-slate-800">Production Today</p>
                                </div>
                            </div>
                            <span class="font-bold text-slate-700">{{ kpis.production_today }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
