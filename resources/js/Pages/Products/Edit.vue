<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Package, DollarSign, BarChart2, Upload, X, RefreshCw, Hash } from '@lucide/vue';
import { ref, computed } from 'vue';

const props = defineProps({
    product: Object,
    categories: Array,
    brands: Array,
    units: Array,
});

const form = useForm({
    name: props.product.name ?? '',
    sku: props.product.sku ?? '',
    barcode: props.product.barcode ?? '',
    description: props.product.description ?? '',
    category_id: props.product.category_id ?? '',
    brand_name: props.product.brand?.name ?? '',
    unit_id: props.product.unit_id ?? '',
    product_type: props.product.product_type ?? 'product',
    purchase_price: props.product.purchase_price ?? '',
    selling_price: props.product.selling_price ?? '',
    wholesale_price: props.product.wholesale_price ?? '',
    min_selling_price: props.product.min_selling_price ?? '',
    min_stock: props.product.min_stock ?? 5,
    reorder_level: props.product.reorder_level ?? 5,
    track_stock: props.product.track_stock ?? true,
    tax_applicable: props.product.tax_applicable ?? false,
    tax_rate: props.product.tax_rate ?? 18,
    is_active: props.product.is_active ?? true,
    is_featured: props.product.is_featured ?? false,
    notes: props.product.notes ?? '',
    image: null,
    _method: 'PUT',
});

const imagePreview = ref(null);
const existingImage = computed(() => {
    const path = props.product.image;
    if (!path) return null;
    return path.startsWith('uploads/') ? '/' + path : '/storage/' + path;
});

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    const reader = new FileReader();
    reader.onload = (ev) => { imagePreview.value = ev.target.result; };
    reader.readAsDataURL(file);
};
const removeImage = () => { form.image = null; imagePreview.value = null; };

const submit = () => {
    form.post(route('products.update', props.product.id), { forceFormData: true });
};
</script>

<template>
    <AppLayout :title="`Edit: ${product.name}`">
        <div class="max-w-3xl mx-auto px-4 py-8">

            <!-- Header -->
            <div class="flex items-center mb-7">
                <Link :href="route('products.index')" class="mr-4 p-2 rounded-full hover:bg-slate-200 transition-colors text-slate-500">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Edit Product</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ product.name }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- BASIC INFO -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider flex items-center mb-5">
                        <Package class="h-4 w-4 mr-1.5 text-indigo-400" /> Basic Information
                    </h2>

                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Product Name <span class="text-rose-500">*</span></label>
                        <input v-model="form.name" type="text" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-base" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">SKU / Code</label>
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                                <Hash class="h-3.5 w-3.5 text-slate-400 flex-shrink-0" />
                                <input v-model="form.sku" class="flex-1 bg-transparent text-sm font-mono font-bold text-indigo-700 focus:outline-none" />
                            </div>
                            <p v-if="form.errors.sku" class="mt-1 text-xs text-rose-600">{{ form.errors.sku }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Barcode</label>
                            <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                                <Hash class="h-3.5 w-3.5 text-slate-400 flex-shrink-0" />
                                <input v-model="form.barcode" class="flex-1 bg-transparent text-sm font-mono font-bold text-emerald-700 focus:outline-none" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Category <span class="text-rose-500">*</span></label>
                            <select v-model="form.category_id" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select category…</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="form.errors.category_id" class="mt-1 text-xs text-rose-600">{{ form.errors.category_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Brand</label>
                            <input v-model="form.brand_name" type="text" placeholder="e.g. Twiga, Simba…" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Unit of Measure <span class="text-rose-500">*</span></label>
                            <select v-model="form.unit_id" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select unit…</option>
                                <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }} ({{ unit.abbreviation }})</option>
                            </select>
                            <p v-if="form.errors.unit_id" class="mt-1 text-xs text-rose-600">{{ form.errors.unit_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Product Type</label>
                            <select v-model="form.product_type" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="product">Finished Product</option>
                                <option value="raw_material">Raw Material</option>
                                <option value="service">Service</option>
                                <option value="manufactured">Manufactured</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- PRICING -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider flex items-center mb-5">
                        <DollarSign class="h-4 w-4 mr-1.5 text-emerald-400" /> Pricing
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Cost Price (per unit)</label>
                            <FormattedNumberInput v-model="form.purchase_price"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Retail Selling Price <span class="text-rose-500">*</span></label>
                            <FormattedNumberInput v-model="form.selling_price"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                            <p v-if="form.errors.selling_price" class="mt-1 text-xs text-rose-600">{{ form.errors.selling_price }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Wholesale Price</label>
                            <FormattedNumberInput v-model="form.wholesale_price" placeholder="Optional"
                                class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div class="flex items-center gap-4 pt-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.tax_applicable" type="checkbox" class="h-4 w-4 text-indigo-600 border-slate-300 rounded" />
                                <span class="text-sm font-semibold text-slate-700">Tax Applicable</span>
                            </label>
                            <div v-if="form.tax_applicable" class="flex items-center gap-2">
                                <FormattedNumberInput v-model="form.tax_rate" class="w-20 border-slate-300 rounded-lg text-sm" />
                                <span class="text-sm text-slate-500">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STOCK ALERTS -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider flex items-center mb-5">
                        <BarChart2 class="h-4 w-4 mr-1.5 text-rose-400" /> Stock Alerts
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Low Stock Alert</label>
                            <FormattedNumberInput v-model="form.min_stock" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Reorder Level</label>
                            <FormattedNumberInput v-model="form.reorder_level" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div class="col-span-2 flex flex-wrap gap-5">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.track_stock" type="checkbox" class="h-4 w-4 text-indigo-600 border-slate-300 rounded" />
                                <span class="text-sm font-semibold text-slate-700">Track Stock</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-indigo-600 border-slate-300 rounded" />
                                <span class="text-sm font-semibold text-slate-700">Active (visible in POS)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.is_featured" type="checkbox" class="h-4 w-4 text-indigo-600 border-slate-300 rounded" />
                                <span class="text-sm font-semibold text-slate-700">Featured</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- IMAGE -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider flex items-center mb-4">
                        <Upload class="h-4 w-4 mr-1.5 text-slate-400" /> Product Image
                    </h2>
                    <!-- Current or preview -->
                    <div v-if="imagePreview || existingImage" class="relative inline-block mb-4">
                        <img :src="imagePreview || existingImage" class="h-28 w-28 object-cover rounded-xl border border-slate-200" />
                        <button type="button" @click="removeImage" class="absolute -top-2 -right-2 bg-rose-500 text-white rounded-full p-0.5">
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <label class="block cursor-pointer">
                        <div class="border-2 border-dashed border-slate-300 rounded-xl p-5 text-center hover:border-indigo-400 transition-colors">
                            <Upload class="mx-auto h-7 w-7 text-slate-300 mb-2" />
                            <p class="text-sm text-slate-400">Click to replace image</p>
                        </div>
                        <input type="file" accept="image/*" @change="handleImageChange" class="hidden" />
                    </label>
                </div>

                <!-- NOTES -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-4 pb-8">
                    <Link :href="route('products.index')" class="px-6 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-colors">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="px-8 py-2.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        {{ form.processing ? 'Saving…' : 'Update Product' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
