<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Package, DollarSign, BarChart2, Upload, X, RefreshCw, Hash, Calculator } from '@lucide/vue';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    categories: Array,
    units: Array,
    productTypes: {
        type: Array,
        default: () => [],
    },
});

// ── Auto-generate SKU ──────────────────────────────────────────
const generateSku = (name) => {
    const prefix = (name || 'PROD').trim().toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 4) || 'PROD';
    const rand = Math.floor(1000 + Math.random() * 9000);
    return `${prefix}-${rand}`;
};

// ── Auto-generate Barcode (13-digit EAN style) ─────────────────
const generateBarcode = () => {
    const digits = Array.from({ length: 12 }, () => Math.floor(Math.random() * 10)).join('');
    // Compute EAN-13 check digit
    let sum = 0;
    for (let i = 0; i < 12; i++) sum += parseInt(digits[i]) * (i % 2 === 0 ? 1 : 3);
    const check = (10 - (sum % 10)) % 10;
    return digits + check;
};

const form = useForm({
    name: '',
    sku: generateSku(''),
    barcode: generateBarcode(),
    description: '',
    category_id: '',
    brand_name: '',
    unit_id: '',
    product_type_id: '',
    product_type: 'product',
    total_cost: '',         // user enters: total purchase cost
    opening_stock: '',      // user enters: how many units
    purchase_price: '',     // computed: total_cost / opening_stock
    selling_price: '',
    wholesale_price: '',
    min_selling_price: '',
    min_stock: 5,
    reorder_level: 5,
    track_stock: true,
    tax_applicable: false,
    tax_rate: 18,
    is_active: true,
    is_featured: false,
    notes: '',
    image: null,
});

// Auto-update SKU when name changes
watch(() => form.name, (name) => {
    form.sku = generateSku(name);
});

// Initialize and sync product type
if (props.productTypes && props.productTypes.length > 0) {
    const defaultType = props.productTypes.find(t => t.code === 'product') || props.productTypes[0];
    if (defaultType) {
        form.product_type_id = defaultType.id;
        form.product_type = defaultType.code;
        form.track_stock = Boolean(defaultType.track_stock);
    }
}

watch(() => form.product_type_id, (newTypeId) => {
    const matched = props.productTypes?.find(t => t.id === newTypeId);
    if (matched) {
        form.product_type = matched.code;
        form.track_stock = Boolean(matched.track_stock);
    }
});

// Compute cost per unit when total_cost or opening_stock changes
const costPerUnit = computed(() => {
    const total = parseFloat(form.total_cost);
    const qty   = parseFloat(form.opening_stock);
    if (total > 0 && qty > 0) return (total / qty).toFixed(2);
    return null;
});

// Keep purchase_price in sync with computed cost per unit
watch(costPerUnit, (val) => {
    if (val !== null) form.purchase_price = val;
});

const refreshSku      = () => { form.sku      = generateSku(form.name); };
const refreshBarcode  = () => { form.barcode  = generateBarcode(); };

// ── Image ────────────────────────────────────────────────────────
const imagePreview = ref(null);
const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    const reader = new FileReader();
    reader.onload = (ev) => { imagePreview.value = ev.target.result; };
    reader.readAsDataURL(file);
};
const removeImage = () => { form.image = null; imagePreview.value = null; };

// ── Submit ───────────────────────────────────────────────────────
const submit = () => {
    form.post(route('products.store'), { forceFormData: true });
};
</script>

<template>
    <AppLayout title="New Product">
        <div class="max-w-3xl mx-auto px-4 py-8">

            <!-- Header -->
            <div class="flex items-center mb-7">
                <Link :href="route('products.index')" class="mr-4 p-2 rounded-full hover:bg-slate-200 transition-colors text-slate-500">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">New Product</h1>
                    <p class="text-sm text-slate-500 mt-0.5">Add a new product to your catalogue</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- ── BASIC INFO ───────────────────────── -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider flex items-center mb-5">
                        <Package class="h-4 w-4 mr-1.5 text-indigo-400" /> Basic Information
                    </h2>

                    <!-- Product Name (full width) -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Product Name <span class="text-rose-500">*</span></label>
                        <input v-model="form.name" type="text" autofocus
                            placeholder="e.g. Roofing Nails 3-inch"
                            class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-base" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                    </div>

                    <!-- Auto-generated chips row -->
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <!-- SKU -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">SKU / Code</label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                                    <Hash class="h-3.5 w-3.5 text-slate-400 flex-shrink-0" />
                                    <span class="text-sm font-mono font-bold text-indigo-700 truncate">{{ form.sku }}</span>
                                </div>
                                <button type="button" @click="refreshSku" title="Regenerate"
                                    class="p-2 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-400 transition-colors">
                                    <RefreshCw class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Barcode -->
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Barcode (EAN-13)</label>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                                    <Hash class="h-3.5 w-3.5 text-slate-400 flex-shrink-0" />
                                    <span class="text-sm font-mono font-bold text-emerald-700 truncate">{{ form.barcode }}</span>
                                </div>
                                <button type="button" @click="refreshBarcode" title="Regenerate"
                                    class="p-2 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-400 transition-colors">
                                    <RefreshCw class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Category + Brand -->
                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-sm font-semibold text-slate-700">Category <span class="text-rose-500">*</span></label>
                                <a :href="route('categories.index')" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Manage</a>
                            </div>
                            <select v-model="form.category_id" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select category…</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="form.errors.category_id" class="mt-1 text-xs text-rose-600">{{ form.errors.category_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Brand</label>
                            <input v-model="form.brand_name" type="text" placeholder="e.g. Twiga, Simba…"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                    </div>

                    <!-- Unit + Product Type -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-sm font-semibold text-slate-700">Unit of Measure <span class="text-rose-500">*</span></label>
                                <a :href="route('units.index')" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Manage</a>
                            </div>
                            <select v-model="form.unit_id" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select unit…</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }} ({{ unit.abbreviation }})</option>
                            </select>
                            <p v-if="form.errors.unit_id" class="mt-1 text-xs text-rose-600">{{ form.errors.unit_id }}</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-sm font-semibold text-slate-700">Product Type</label>
                                <a :href="route('product-types.index')" target="_blank" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+ Manage</a>
                            </div>
                            <select v-model="form.product_type_id" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select product type…</option>
                                <option v-for="pt in productTypes" :key="pt.id" :value="pt.id">
                                    {{ pt.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.product_type_id" class="mt-1 text-xs text-rose-600">{{ form.errors.product_type_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- ── COST & STOCK ─────────────────────── -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider flex items-center mb-5">
                        <Calculator class="h-4 w-4 mr-1.5 text-amber-400" /> Purchase Cost & Opening Stock
                    </h2>

                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <!-- Total Cost Paid -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Total Cost Paid (TZS) <span class="text-rose-500">*</span></label>
                            <FormattedNumberInput v-model="form.total_cost" placeholder="e.g. 50,000"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="mt-1 text-xs text-slate-400">Total amount paid for the entire batch</p>
                        </div>

                        <!-- Opening Stock Quantity -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Opening Stock Qty <span class="text-rose-500">*</span></label>
                            <FormattedNumberInput v-model="form.opening_stock" placeholder="e.g. 100"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="mt-1 text-xs text-slate-400">How many units you have now</p>
                        </div>
                    </div>

                    <!-- Computed Cost Per Unit (read-only display) -->
                    <div v-if="costPerUnit" class="mb-5 p-4 bg-indigo-50 border border-indigo-200 rounded-xl flex items-center gap-3">
                        <Calculator class="h-5 w-5 text-indigo-500 flex-shrink-0" />
                        <div>
                            <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Computed Cost Per Unit</p>
                            <p class="text-2xl font-black text-indigo-800">{{ Number(costPerUnit).toLocaleString() }} TZS</p>
                            <p class="text-xs text-indigo-500">{{ form.total_cost }} ÷ {{ form.opening_stock }} units</p>
                        </div>
                    </div>
                    <div v-else class="mb-5 p-4 bg-slate-50 border border-dashed border-slate-300 rounded-xl text-center text-sm text-slate-400">
                        Enter total cost and opening stock above to auto-calculate cost per unit
                    </div>
                </div>

                <!-- ── SELLING PRICE ────────────────────── -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider flex items-center mb-5">
                        <DollarSign class="h-4 w-4 mr-1.5 text-emerald-400" /> Selling Price
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Retail Selling Price (TZS) <span class="text-rose-500">*</span></label>
                            <FormattedNumberInput v-model="form.selling_price" placeholder="0"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            <p v-if="form.errors.selling_price" class="mt-1 text-xs text-rose-600">{{ form.errors.selling_price }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Wholesale Price (TZS)</label>
                            <FormattedNumberInput v-model="form.wholesale_price" placeholder="Optional"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>

                        <!-- Tax -->
                        <div class="col-span-2 flex items-center gap-5 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.tax_applicable" type="checkbox" class="h-4 w-4 text-indigo-600 border-slate-300 rounded" />
                                <span class="text-sm font-semibold text-slate-700">Tax Applicable</span>
                            </label>
                            <div v-if="form.tax_applicable" class="flex items-center gap-2">
                                <FormattedNumberInput v-model="form.tax_rate" max="100"
                                    class="w-24 border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                                <span class="text-sm text-slate-500">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── STOCK ALERTS ─────────────────────── -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider flex items-center mb-5">
                        <BarChart2 class="h-4 w-4 mr-1.5 text-rose-400" /> Stock Alerts
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Low Stock Alert</label>
                            <FormattedNumberInput v-model="form.min_stock"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="mt-1 text-xs text-slate-400">Warn when stock drops below this</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Reorder Level</label>
                            <FormattedNumberInput v-model="form.reorder_level"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="mt-1 text-xs text-slate-400">Trigger reorder at this quantity</p>
                        </div>
                        <div class="col-span-2 flex flex-wrap gap-5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.track_stock" type="checkbox" class="h-4 w-4 text-indigo-600 border-slate-300 rounded" />
                                <span class="text-sm font-semibold text-slate-700">Track Stock Levels</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-indigo-600 border-slate-300 rounded" />
                                <span class="text-sm font-semibold text-slate-700">Active (visible in POS)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ── IMAGE (optional, collapsed) ────────── -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider flex items-center mb-4">
                        <Upload class="h-4 w-4 mr-1.5 text-slate-400" /> Product Image <span class="ml-2 text-xs font-normal text-slate-400 normal-case tracking-normal">(optional)</span>
                    </h2>
                    <div v-if="imagePreview" class="relative inline-block mb-4">
                        <img :src="imagePreview" class="h-28 w-28 object-cover rounded-xl border border-slate-200" />
                        <button type="button" @click="removeImage" class="absolute -top-2 -right-2 bg-rose-500 text-white rounded-full p-0.5">
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <label class="block cursor-pointer">
                        <div class="border-2 border-dashed border-slate-300 rounded-xl p-5 text-center hover:border-indigo-400 transition-colors">
                            <Upload class="mx-auto h-7 w-7 text-slate-300 mb-2" />
                            <p class="text-sm text-slate-400">Click to upload</p>
                        </div>
                        <input type="file" accept="image/*" @change="handleImageChange" class="hidden" />
                    </label>
                </div>

                <!-- Description (optional, at bottom) -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Notes / Description <span class="text-xs font-normal text-slate-400">(optional)</span></label>
                    <textarea v-model="form.notes" rows="2" placeholder="Any notes about this product…"
                        class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-4 pb-8">
                    <Link :href="route('products.index')" class="px-6 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-8 py-2.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Saving…' : 'Save Product' }}
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
