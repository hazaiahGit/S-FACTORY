<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    Search, Plus, Package, Eye, Edit, Trash2, X, Filter
} from '@lucide/vue';
import { ref, watch } from 'vue';

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const props = defineProps({
    products: Object,
    categories: Array,
    brands: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.category_id || '');

watch([search, categoryId], debounce(([newSearch, newCategory]) => {
    router.get(
        route('products.index'),
        { search: newSearch, category_id: newCategory },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value || 0);
};

const getStockStatus = (product) => {
    const qty = parseFloat(product.stock_sum_quantity || 0);
    const min = parseFloat(product.min_stock || 0);
    
    if (qty <= 0) return { label: 'Out of Stock', class: 'bg-rose-100 text-rose-800' };
    if (qty <= min) return { label: 'Low Stock', class: 'bg-amber-100 text-amber-800' };
    return { label: 'In Stock', class: 'bg-emerald-100 text-emerald-800' };
};

const deleteProduct = (id) => {
    if (confirm("Are you sure you want to delete this product?")) {
        router.delete(route('products.destroy', id));
    }
};

// Image URL helper
const getImageUrl = (path) => {
    if (!path) return null;
    return path.startsWith('uploads/') ? '/' + path : '/storage/' + path;
};

// Lightbox state
const lightboxOpen = ref(false);
const lightboxImage = ref('');
const openLightbox = (url) => {
    if (!url) return;
    lightboxImage.value = url;
    lightboxOpen.value = true;
};
</script>

<template>
    <AppLayout title="Products Catalogue">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Products Catalogue</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage your inventory and pricing</p>
                </div>
                <div class="flex gap-3 w-full sm:w-auto">
                    <Link :href="route('products.create')" class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                        <Plus class="h-4 w-4 mr-2" />
                        Add Product
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-4 w-4 text-slate-400" />
                    </div>
                    <input v-model="search" type="text" placeholder="Search by name, SKU or barcode..." class="pl-10 w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>
                <div class="w-full sm:w-64">
                    <select v-model="categoryId" class="w-full border-slate-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid (Mobile & Desktop) -->
            <div v-if="products.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="product in products.data" :key="product.id" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col hover:shadow-md transition-shadow">
                    
                    <!-- Image Area -->
                    <div class="relative h-48 bg-slate-50 border-b border-slate-100 flex items-center justify-center group cursor-pointer" @click="openLightbox(getImageUrl(product.image))">
                        <img v-if="product.image" :src="getImageUrl(product.image)" class="w-full h-full object-cover" />
                        <Package v-else class="h-12 w-12 text-slate-300" />
                        
                        <!-- Hover Overlay -->
                        <div v-if="product.image" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-white text-sm font-medium bg-black/50 px-3 py-1.5 rounded-lg flex items-center"><Eye class="h-4 w-4 mr-1.5"/> View Image</span>
                        </div>

                        <!-- Status Badge -->
                        <div class="absolute top-3 left-3">
                            <span :class="['px-2.5 py-1 text-xs font-bold rounded-lg shadow-sm', getStockStatus(product).class]">
                                {{ getStockStatus(product).label }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4 flex-1 flex flex-col">
                        <div class="mb-1">
                            <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider">{{ product.category?.name || 'Uncategorized' }}</p>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 leading-tight mb-1">{{ product.name }}</h3>
                        <p class="text-xs text-slate-500 mb-4 flex items-center gap-2">
                            <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">{{ product.sku }}</span>
                            <span v-if="product.brand" class="truncate">&bull; {{ product.brand.name }}</span>
                        </p>

                        <!-- Pricing & Stock -->
                        <div class="mt-auto grid grid-cols-2 gap-3 mb-4">
                            <div class="bg-slate-50 rounded-lg p-2.5 border border-slate-100">
                                <p class="text-xs text-slate-500 mb-0.5">Retail Price</p>
                                <p class="text-sm font-bold text-slate-900">{{ formatCurrency(product.selling_price) }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-2.5 border border-slate-100">
                                <p class="text-xs text-slate-500 mb-0.5">Stock Level</p>
                                <p class="text-sm font-bold text-slate-900">
                                    {{ Number(product.stock_sum_quantity || 0).toLocaleString() }} 
                                    <span class="text-xs font-normal">{{ product.unit?.abbreviation }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-2 gap-2 pt-4 border-t border-slate-100 mt-auto">
                            <Link v-if="$can('edit products')" :href="route('products.edit', product.id)" class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                <Edit class="h-4 w-4 text-slate-500" /> Edit
                            </Link>
                            <button v-if="$can('delete products')" @click="deleteProduct(product.id)" class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-rose-600 bg-white border border-rose-200 rounded-lg hover:bg-rose-50 transition-colors">
                                <Trash2 class="h-4 w-4" /> Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                <Package class="mx-auto h-12 w-12 text-slate-300 mb-4" />
                <h3 class="text-lg font-bold text-slate-900">No products found</h3>
                <p class="text-slate-500 mt-1 mb-6">Get started by creating a new product.</p>
                <Link :href="route('products.create')" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <Plus class="h-4 w-4 mr-2" /> Add Product
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="products.links && products.links.length > 3" class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <template v-for="(link, i) in products.links" :key="i">
                        <Link 
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                link.active ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-slate-300 text-slate-500 hover:bg-slate-50',
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                i === 0 ? 'rounded-l-md' : '',
                                i === products.links.length - 1 ? 'rounded-r-md' : ''
                            ]"
                        />
                        <span v-else v-html="link.label" class="relative inline-flex items-center px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-300 cursor-not-allowed" :class="[i === 0 ? 'rounded-l-md' : '', i === products.links.length - 1 ? 'rounded-r-md' : '']"></span>
                    </template>
                </nav>
            </div>
            
        </div>

        <!-- Lightbox Modal -->
        <div v-if="lightboxOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4" @click="lightboxOpen = false">
            <button class="absolute top-4 right-4 p-2 text-white/70 hover:text-white bg-white/10 hover:bg-white/20 rounded-full transition-colors" @click.stop="lightboxOpen = false">
                <X class="h-6 w-6" />
            </button>
            <img :src="lightboxImage" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl" @click.stop />
        </div>
    </AppLayout>
</template>

