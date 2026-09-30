<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    FolderTree,
    Plus,
    Search,
    Edit2,
    Trash2,
    CheckCircle2,
    XCircle,
    Package,
    Layers,
    ChevronRight,
    X,
    Filter,
} from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    categories: Object,
    parentCategories: Array,
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
            route('categories.index'),
            { search: newSearch, status: newStatus },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});

// Modal State
const isModalOpen = ref(false);
const editingCategory = ref(null);

const colorPresets = [
    '#f59e0b', // Amber
    '#3b82f6', // Blue
    '#10b981', // Emerald
    '#8b5cf6', // Purple
    '#ec4899', // Pink
    '#06b6d4', // Cyan
    '#64748b', // Slate
];

const form = useForm({
    name: '',
    code: '',
    parent_id: '',
    description: '',
    color: '#f59e0b',
    is_active: true,
});

const openModal = (category = null) => {
    if (category) {
        editingCategory.value = category;
        form.name = category.name;
        form.code = category.code || '';
        form.parent_id = category.parent_id || '';
        form.description = category.description || '';
        form.color = category.color || '#f59e0b';
        form.is_active = Boolean(category.is_active);
    } else {
        editingCategory.value = null;
        form.reset();
        form.color = '#f59e0b';
        form.is_active = true;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingCategory.value) {
        form.put(route('categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('categories.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const toggleStatus = (category) => {
    router.post(route('categories.toggle-status', category.id), {}, {
        preserveScroll: true,
    });
};

const deleteCategory = (category) => {
    if (confirm(`Are you sure you want to delete category "${category.name}"?`)) {
        router.delete(route('categories.destroy', category.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Product Categories" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-2 text-slate-800">
                <FolderTree class="w-5 h-5 text-amber-500 shrink-0" />
                <span class="font-semibold text-lg sm:text-xl truncate">Product Categories</span>
            </div>
        </template>

        <div class="py-6 sm:py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Hero Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Category Hierarchy</span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                            {{ stats?.total ?? 0 }} {{ (stats?.total ?? 0) === 1 ? 'category' : 'categories' }}
                        </span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Organize your catalog into groups and subcategories for easy POS navigation and reporting.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="openModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-500 hover:bg-amber-600 shadow-sm shadow-amber-500/20 transition-all hover:shadow-md active:scale-95"
                    >
                        <Plus class="w-4 h-4 mr-1.5 stroke-[2.5]" />
                        <span>New Category</span>
                    </button>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Categories</span>
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 block">
                            {{ stats?.total ?? 0 }}
                        </span>
                        <span class="text-xs text-slate-400 mt-0.5 block">Catalog groups</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-amber-50 text-amber-600">
                        <FolderTree class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block">Active Categories</span>
                        <span class="text-2xl sm:text-3xl font-black text-emerald-700 mt-1 block">
                            {{ stats?.active ?? 0 }}
                        </span>
                        <span class="text-xs text-emerald-600/80 mt-0.5 block">Visible in POS & Store</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider block">Subcategories</span>
                        <span class="text-2xl sm:text-3xl font-black text-indigo-700 mt-1 block">
                            {{ stats?.subcategories ?? 0 }}
                        </span>
                        <span class="text-xs text-indigo-600/80 mt-0.5 block">Nested child tiers</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-indigo-50 text-indigo-600">
                        <Layers class="w-6 h-6" />
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
                        placeholder="Search category by name, code, description..."
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
            <div v-if="categories.data.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <FolderTree class="w-7 h-7" />
                </div>
                <h3 class="text-base font-bold text-slate-900">No categories found</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                    {{ search || status ? 'No categories matched your search criteria.' : 'Create your first product category to classify your inventory.' }}
                </p>
                <button
                    v-if="!search && !status"
                    @click="openModal()"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-xs"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    Create First Category
                </button>
            </div>

            <!-- Category List (Dual-Mode: Desktop Table & Mobile Cards) -->
            <template v-else>
                <!-- Desktop Table -->
                <div class="hidden md:block bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6">Category Name</th>
                                <th class="py-3.5 px-4">Code</th>
                                <th class="py-3.5 px-4">Parent Level</th>
                                <th class="py-3.5 px-4 text-center">Products</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <tr
                                v-for="category in categories.data"
                                :key="category.id"
                                class="hover:bg-slate-50/60 transition-colors group"
                            >
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-8 h-8 rounded-xl flex items-center justify-center text-white font-black text-xs shrink-0 shadow-xs"
                                            :style="{ backgroundColor: category.color || '#f59e0b' }"
                                        >
                                            {{ category.name.substring(0, 1).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 group-hover:text-amber-600 transition-colors truncate">
                                                {{ category.name }}
                                            </div>
                                            <div v-if="category.description" class="text-xs text-slate-400 truncate max-w-md">
                                                {{ category.description }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4 font-mono text-xs font-bold text-slate-600">
                                    <span v-if="category.code" class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200">
                                        {{ category.code }}
                                    </span>
                                    <span v-else class="text-slate-400 italic font-sans">—</span>
                                </td>

                                <td class="py-4 px-4 text-xs font-semibold text-slate-600">
                                    <span v-if="category.parent" class="inline-flex items-center gap-1 text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200">
                                        <ChevronRight class="w-3 h-3 text-slate-400" />
                                        {{ category.parent.name }}
                                    </span>
                                    <span v-else class="text-slate-400 font-medium">Top Level (Main)</span>
                                </td>

                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        <Package class="w-3.5 h-3.5 text-slate-400" />
                                        {{ category.products_count || 0 }} items
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-center">
                                    <button
                                        @click="toggleStatus(category)"
                                        type="button"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition-all',
                                            category.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                                                : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200'
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', category.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                        {{ category.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>

                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button
                                            @click="openModal(category)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                                            title="Edit Category"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteCategory(category)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                            title="Delete Category"
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
                <div class="block md:hidden space-y-3.5">
                    <div
                        v-for="category in categories.data"
                        :key="category.id"
                        class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div
                                    class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-black text-xs shrink-0 shadow-xs"
                                    :style="{ backgroundColor: category.color || '#f59e0b' }"
                                >
                                    {{ category.name.substring(0, 1).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-slate-900 text-sm truncate">
                                        {{ category.name }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mt-0.5">
                                        <span v-if="category.code" class="font-mono font-bold px-1 rounded bg-slate-100 text-slate-600 border border-slate-200 text-[10px]">
                                            {{ category.code }}
                                        </span>
                                        <span v-if="category.parent">under {{ category.parent.name }}</span>
                                        <span v-else>Top level</span>
                                    </div>
                                </div>
                            </div>

                            <button
                                @click="toggleStatus(category)"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold shrink-0',
                                    category.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500'
                                ]"
                            >
                                <span :class="['w-1.5 h-1.5 rounded-full', category.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                {{ category.is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                            <span class="text-slate-500 flex items-center gap-1">
                                <Package class="w-3.5 h-3.5 text-slate-400" />
                                {{ category.products_count || 0 }} products
                            </span>

                            <div class="flex items-center space-x-2">
                                <button
                                    @click="openModal(category)"
                                    class="px-2.5 py-1 text-xs font-bold text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200"
                                >
                                    Edit
                                </button>
                                <button
                                    @click="deleteCategory(category)"
                                    class="px-2.5 py-1 text-xs font-bold text-rose-600 bg-rose-50 rounded-lg hover:bg-rose-100"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="categories.links && categories.links.length > 3" class="flex justify-center pt-4">
                    <div class="flex flex-wrap gap-1">
                        <Link
                            v-for="link in categories.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 text-xs font-bold rounded-lg transition-colors',
                                link.active
                                    ? 'bg-amber-500 text-white'
                                    : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </template>

        </div>

        <!-- Create / Edit Category Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-5 sm:p-7">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                            <FolderTree class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">
                                {{ editingCategory ? 'Edit Category' : 'Create Category' }}
                            </h2>
                            <p class="text-xs text-slate-500">Classify items for streamlined sales and stock reporting</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="cat_name" value="Category Name *" />
                        <TextInput
                            id="cat_name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full rounded-xl"
                            placeholder="e.g. Electrical Cables & Conduit"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="cat_code" value="Category Code" />
                            <TextInput
                                id="cat_code"
                                v-model="form.code"
                                type="text"
                                class="mt-1 block w-full rounded-xl uppercase font-mono"
                                placeholder="e.g. ELEC"
                            />
                            <InputError :message="form.errors.code" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="cat_parent" value="Parent Category" />
                            <select
                                id="cat_parent"
                                v-model="form.parent_id"
                                class="mt-1 block w-full border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-xl shadow-xs text-sm"
                            >
                                <option value="">None (Top-Level Category)</option>
                                <option
                                    v-for="parent in parentCategories"
                                    :key="parent.id"
                                    :value="parent.id"
                                    :disabled="editingCategory && editingCategory.id === parent.id"
                                >
                                    {{ parent.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.parent_id" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Accent Color Tag" />
                        <div class="flex items-center gap-2 mt-1.5">
                            <button
                                v-for="c in colorPresets"
                                :key="c"
                                type="button"
                                @click="form.color = c"
                                class="w-7 h-7 rounded-full transition-transform active:scale-90 flex items-center justify-center shadow-xs"
                                :style="{ backgroundColor: c }"
                            >
                                <CheckCircle2 v-if="form.color === c" class="w-4 h-4 text-white stroke-[3]" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <InputLabel for="cat_desc" value="Description (Optional)" />
                        <textarea
                            id="cat_desc"
                            v-model="form.description"
                            rows="2"
                            class="mt-1 block w-full border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-xl shadow-xs text-sm"
                            placeholder="Briefly describe what belongs in this category..."
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <div class="flex items-center pt-1">
                        <label class="flex items-center text-xs font-semibold text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="rounded border-slate-300 text-amber-500 focus:ring-amber-500 mr-2"
                            />
                            Category is active and visible in products & POS
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 font-bold text-sm text-white shadow-xs transition-all active:scale-95 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>{{ editingCategory ? 'Update Category' : 'Save Category' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </AppLayout>
</template>
