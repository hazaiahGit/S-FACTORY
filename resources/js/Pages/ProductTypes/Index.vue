<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Boxes,
    Plus,
    Search,
    Edit2,
    Trash2,
    CheckCircle2,
    XCircle,
    Package,
    ChevronRight,
    X,
    Filter,
    Factory,
    ShoppingCart,
    Truck,
    Layers,
    ShieldAlert,
    Check,
} from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    productTypes: Object,
    filters: Object,
    stats: Object,
});

// Search & Filter State
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

let searchDebounceTimeout = null;
watch([search, status], ([newSearch, newStatus]) => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        router.get(
            route('product-types.index'),
            { search: newSearch, status: newStatus },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});

// Modal State
const isModalOpen = ref(false);
const editingType = ref(null);

const form = useForm({
    name: '',
    code: '',
    description: '',
    is_sold: true,
    is_purchased: true,
    is_manufactured: false,
    track_stock: true,
    is_active: true,
});

const openModal = (productType = null) => {
    if (productType) {
        editingType.value = productType;
        form.name = productType.name;
        form.code = productType.code || '';
        form.description = productType.description || '';
        form.is_sold = Boolean(productType.is_sold);
        form.is_purchased = Boolean(productType.is_purchased);
        form.is_manufactured = Boolean(productType.is_manufactured);
        form.track_stock = Boolean(productType.track_stock);
        form.is_active = Boolean(productType.is_active);
    } else {
        editingType.value = null;
        form.reset();
        form.is_sold = true;
        form.is_purchased = true;
        form.is_manufactured = false;
        form.track_stock = true;
        form.is_active = true;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingType.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingType.value) {
        form.put(route('product-types.update', editingType.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('product-types.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const toggleStatus = (typeItem) => {
    router.patch(route('product-types.toggle-status', typeItem.id), {}, {
        preserveScroll: true,
    });
};

const deleteType = (typeItem) => {
    if (typeItem.products_count > 0) {
        alert(`Cannot delete "${typeItem.name}" because it is currently assigned to ${typeItem.products_count} product(s).`);
        return;
    }

    if (typeItem.is_default) {
        alert(`"${typeItem.name}" is a protected system default product type and cannot be deleted.`);
        return;
    }

    if (confirm(`Are you sure you want to delete product type "${typeItem.name}"? This action cannot be undone.`)) {
        router.delete(route('product-types.destroy', typeItem.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Product Types" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-2 text-slate-800">
                <Boxes class="w-5 h-5 text-amber-500 shrink-0" />
                <span class="font-semibold text-lg sm:text-xl truncate">Product Types</span>
            </div>
        </template>

        <div class="py-6 sm:py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Hero Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Product Types</span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                            {{ stats?.total ?? 0 }} {{ (stats?.total ?? 0) === 1 ? 'type' : 'types' }}
                        </span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Define catalog classification rules, manufacturing eligibility, and stock tracking behavior.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="openModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-500 hover:bg-amber-600 shadow-sm shadow-amber-500/20 transition-all hover:shadow-md active:scale-95"
                    >
                        <Plus class="w-4 h-4 mr-1.5 stroke-[2.5]" />
                        <span>Add Product Type</span>
                    </button>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Types</span>
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 block">
                            {{ stats?.total ?? 0 }}
                        </span>
                        <span class="text-xs text-slate-400 mt-0.5 block">Configured models</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-amber-50 text-amber-600">
                        <Boxes class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block">Active</span>
                        <span class="text-2xl sm:text-3xl font-black text-emerald-700 mt-1 block">
                            {{ stats?.active ?? 0 }}
                        </span>
                        <span class="text-xs text-emerald-600/80 mt-0.5 block">Ready for items</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-purple-600 uppercase tracking-wider block">Manufacturable</span>
                        <span class="text-2xl sm:text-3xl font-black text-purple-700 mt-1 block">
                            {{ stats?.manufacturable ?? 0 }}
                        </span>
                        <span class="text-xs text-purple-600/80 mt-0.5 block">Bill of Materials</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-purple-50 text-purple-600">
                        <Factory class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-blue-600 uppercase tracking-wider block">Salable</span>
                        <span class="text-2xl sm:text-3xl font-black text-blue-700 mt-1 block">
                            {{ stats?.salable ?? 0 }}
                        </span>
                        <span class="text-xs text-blue-600/80 mt-0.5 block">Available at POS</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-blue-50 text-blue-600">
                        <ShoppingCart class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Toolbar: Search & Filter -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Search class="w-4 h-4" />
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search product types by name, code, description..."
                        class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all placeholder:text-slate-400"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="status"
                        class="py-2 px-3 text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="productTypes.data.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <Boxes class="w-7 h-7" />
                </div>
                <h3 class="text-base font-bold text-slate-900">No product types found</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                    {{ search || status ? 'No product types matched your criteria.' : 'Create your first product type to define inventory behaviors.' }}
                </p>
                <button
                    v-if="!search && !status"
                    @click="openModal()"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-xs"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    Create First Product Type
                </button>
            </div>

            <!-- Product Types List (Dual-Mode: Desktop Table & Mobile Cards) -->
            <template v-else>
                <!-- Desktop Table -->
                <div class="hidden lg:block bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6">Product Type</th>
                                <th class="py-3.5 px-4">Code</th>
                                <th class="py-3.5 px-4">Operational Capabilities</th>
                                <th class="py-3.5 px-4 text-center">Products</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <tr
                                v-for="item in productTypes.data"
                                :key="item.id"
                                class="hover:bg-slate-50/60 transition-colors group"
                            >
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/60 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                            <Boxes class="w-4 h-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-slate-900 group-hover:text-amber-600 transition-colors truncate">
                                                    {{ item.name }}
                                                </span>
                                                <span
                                                    v-if="item.is_default"
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 border border-slate-200 text-slate-600"
                                                >
                                                    Default
                                                </span>
                                            </div>
                                            <div v-if="item.description" class="text-xs text-slate-400 truncate max-w-md">
                                                {{ item.description }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4 font-mono text-xs font-bold text-slate-600">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200">
                                        {{ item.code }}
                                    </span>
                                </td>

                                <td class="py-4 px-4">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span
                                            v-if="item.is_sold"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"
                                            title="Available in Sales Orders and POS"
                                        >
                                            <ShoppingCart class="w-3 h-3" />
                                            Sell
                                        </span>
                                        <span
                                            v-if="item.is_purchased"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200"
                                            title="Available in Purchase Orders"
                                        >
                                            <Truck class="w-3 h-3" />
                                            Purchase
                                        </span>
                                        <span
                                            v-if="item.is_manufactured"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200"
                                            title="Can be produced in manufacturing"
                                        >
                                            <Factory class="w-3 h-3" />
                                            Manufacture
                                        </span>
                                        <span
                                            v-if="item.track_stock"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200"
                                            title="Maintains physical stock balance"
                                        >
                                            <Package class="w-3 h-3" />
                                            Track Stock
                                        </span>
                                    </div>
                                </td>

                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        <Package class="w-3.5 h-3.5 text-slate-400" />
                                        {{ item.products_count || 0 }} items
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-center">
                                    <button
                                        @click="toggleStatus(item)"
                                        type="button"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition-all',
                                            item.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                                                : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200'
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', item.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>

                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button
                                            @click="openModal(item)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                                            title="Edit Type"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteType(item)"
                                            :disabled="item.products_count > 0 || item.is_default"
                                            :class="(item.products_count > 0 || item.is_default) ? 'text-slate-300 cursor-not-allowed' : 'text-slate-400 hover:text-rose-600 hover:bg-rose-50'"
                                            class="p-1.5 rounded-lg transition-colors"
                                            :title="item.is_default ? 'Protected system default' : (item.products_count > 0 ? 'Assigned to products' : 'Delete Type')"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card Deck -->
                <div class="block lg:hidden space-y-3.5">
                    <div
                        v-for="item in productTypes.data"
                        :key="item.id"
                        class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/60 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                    <Boxes class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <h3 class="font-bold text-slate-900 text-sm truncate">{{ item.name }}</h3>
                                        <span
                                            v-if="item.is_default"
                                            class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 border border-slate-200 text-slate-600"
                                        >
                                            Default
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 font-mono">{{ item.code }}</p>
                                </div>
                            </div>

                            <button
                                @click="toggleStatus(item)"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold shrink-0',
                                    item.is_active
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : 'bg-slate-100 text-slate-500 border border-slate-200'
                                ]"
                            >
                                <span :class="['w-1.5 h-1.5 rounded-full', item.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                {{ item.is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </div>

                        <p v-if="item.description" class="text-xs text-slate-500 line-clamp-2">
                            {{ item.description }}
                        </p>

                        <!-- Capabilities Chips -->
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <span
                                v-if="item.is_sold"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"
                            >
                                <ShoppingCart class="w-3 h-3" />
                                Salable
                            </span>
                            <span
                                v-if="item.is_purchased"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200"
                            >
                                <Truck class="w-3 h-3" />
                                Purchasable
                            </span>
                            <span
                                v-if="item.is_manufactured"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-200"
                            >
                                <Factory class="w-3 h-3" />
                                Manufacturable
                            </span>
                            <span
                                v-if="item.track_stock"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200"
                            >
                                <Package class="w-3 h-3" />
                                Stock Tracked
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2.5 border-t border-slate-100 text-xs">
                            <span class="text-slate-500 font-medium flex items-center gap-1">
                                <Package class="w-3.5 h-3.5 text-slate-400" />
                                {{ item.products_count || 0 }} items
                            </span>

                            <div class="flex items-center gap-2">
                                <button
                                    @click="openModal(item)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 transition-colors"
                                >
                                    <Edit2 class="w-3.5 h-3.5 text-amber-600" />
                                    Edit
                                </button>
                                <button
                                    @click="deleteType(item)"
                                    :disabled="item.products_count > 0 || item.is_default"
                                    :class="(item.products_count > 0 || item.is_default) ? 'opacity-40 cursor-not-allowed bg-slate-50 text-slate-400' : 'bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200'"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold transition-colors"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="productTypes.links && productTypes.links.length > 3"
                    class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4"
                >
                    <p class="text-xs text-slate-500 font-medium">
                        Showing <span class="font-bold text-slate-800">{{ productTypes.from || 0 }}</span> to
                        <span class="font-bold text-slate-800">{{ productTypes.to || 0 }}</span> of
                        <span class="font-bold text-slate-800">{{ productTypes.total }}</span> product types
                    </p>
                    <div class="flex items-center space-x-1">
                        <template v-for="(link, key) in productTypes.links" :key="key">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="link.active ? 'bg-amber-500 text-white font-bold border-amber-500 shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-200'"
                                class="px-3 py-1.5 text-xs rounded-xl border transition-colors"
                                preserve-scroll
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3 py-1.5 text-xs rounded-xl border border-slate-100 text-slate-300 cursor-not-allowed"
                            />
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Create / Edit Product Type Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-5 sm:p-7 bg-white rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                            <Boxes class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">
                                {{ editingType ? 'Edit Product Type' : 'Create Product Type' }}
                            </h2>
                            <p class="text-xs text-slate-500">Define operational capabilities and catalog handling</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Type Name -->
                        <div>
                            <InputLabel for="type_name" value="Type Name *" />
                            <TextInput
                                id="type_name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-xl"
                                placeholder="e.g. Raw Material, Finished Good, Service"
                                required
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <!-- Type Code -->
                        <div>
                            <InputLabel for="type_code" value="Code / Slug" />
                            <TextInput
                                id="type_code"
                                v-model="form.code"
                                type="text"
                                class="mt-1 block w-full rounded-xl font-mono uppercase"
                                placeholder="e.g. raw_material (auto if blank)"
                            />
                            <InputError :message="form.errors.code" class="mt-1" />
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel for="type_description" value="Description (Optional)" />
                        <textarea
                            id="type_description"
                            v-model="form.description"
                            rows="2"
                            class="mt-1 block w-full border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-xl shadow-xs text-sm placeholder:text-slate-400"
                            placeholder="Describe how items of this type operate in the supply chain..."
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Operational Capabilities Grid -->
                    <div class="space-y-2 pt-1">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-400 block">
                            Operational Capabilities
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-100/70 transition-colors">
                                <input
                                    type="checkbox"
                                    v-model="form.is_sold"
                                    class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4"
                                />
                                <div class="ml-3">
                                    <span class="text-sm font-bold text-slate-800 block">Salable</span>
                                    <span class="text-xs text-slate-500 block">Available in Sales Orders & POS</span>
                                </div>
                            </label>

                            <label class="flex items-start p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-100/70 transition-colors">
                                <input
                                    type="checkbox"
                                    v-model="form.is_purchased"
                                    class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4"
                                />
                                <div class="ml-3">
                                    <span class="text-sm font-bold text-slate-800 block">Purchasable</span>
                                    <span class="text-xs text-slate-500 block">Available in Purchase Orders</span>
                                </div>
                            </label>

                            <label class="flex items-start p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-100/70 transition-colors">
                                <input
                                    type="checkbox"
                                    v-model="form.is_manufactured"
                                    class="mt-0.5 rounded border-slate-300 text-purple-600 focus:ring-purple-500 w-4 h-4"
                                />
                                <div class="ml-3">
                                    <span class="text-sm font-bold text-slate-800 block">Manufacturable</span>
                                    <span class="text-xs text-slate-500 block">Output of Bill of Materials / Production</span>
                                </div>
                            </label>

                            <label class="flex items-start p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-100/70 transition-colors">
                                <input
                                    type="checkbox"
                                    v-model="form.track_stock"
                                    class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-500 w-4 h-4"
                                />
                                <div class="ml-3">
                                    <span class="text-sm font-bold text-slate-800 block">Track Inventory</span>
                                    <span class="text-xs text-slate-500 block">Maintains warehouse on-hand counts</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Status Toggle -->
                    <div class="flex items-center space-x-2 pt-1">
                        <input
                            type="checkbox"
                            id="type_status"
                            v-model="form.is_active"
                            class="rounded border-slate-300 text-amber-500 focus:ring-amber-500 w-4 h-4"
                        />
                        <label for="type_status" class="text-sm font-semibold text-slate-700 cursor-pointer">
                            Active in catalog selection
                        </label>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 mt-6">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 rounded-xl shadow-xs transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ editingType ? 'Save Changes' : 'Create Product Type' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
