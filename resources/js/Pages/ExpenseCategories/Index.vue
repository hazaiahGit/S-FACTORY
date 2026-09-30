<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Tags,
    Plus,
    Search,
    Edit2,
    Trash2,
    CheckCircle2,
    ReceiptText,
    Wallet,
    X,
    ArrowLeft,
    Check,
    AlertCircle
} from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    categories: Object,
    filters: Object,
    stats: Object,
});

// Search & Filter State
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

let searchDebounceTimeout = null;
watch([search, status], ([newSearch, newStatus]) => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        router.get(
            route('expense-categories.index'),
            { search: newSearch, status: newStatus },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});

// Modal State
const isModalOpen = ref(false);
const editingCategory = ref(null);

const colorPresets = [
    '#f43f5e', // Rose
    '#f59e0b', // Amber
    '#10b981', // Emerald
    '#3b82f6', // Blue
    '#8b5cf6', // Purple
    '#06b6d4', // Cyan
    '#64748b', // Slate
    '#ec4899', // Pink
];

const form = useForm({
    name: '',
    color: '#f43f5e',
    is_active: true,
});

const openCreateModal = () => {
    editingCategory.value = null;
    form.reset();
    form.clearErrors();
    form.name = '';
    form.color = '#f43f5e';
    form.is_active = true;
    isModalOpen.value = true;
};

const openEditModal = (cat) => {
    editingCategory.value = cat;
    form.clearErrors();
    form.name = cat.name;
    form.color = cat.color || '#f43f5e';
    form.is_active = Boolean(cat.is_active);
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingCategory.value = null;
    form.reset();
};

const submit = () => {
    if (editingCategory.value) {
        form.put(route('expense-categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('expense-categories.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCategory = (cat) => {
    if (cat.expenses_count > 0) {
        alert(`Cannot delete "${cat.name}" because it has ${cat.expenses_count} associated expenses. Deactivate it instead.`);
        return;
    }
    if (confirm(`Are you sure you want to delete category "${cat.name}"?`)) {
        router.delete(route('expense-categories.destroy', cat.id), {
            preserveScroll: true,
        });
    }
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(val || 0);
};
</script>

<template>
    <Head title="Expense Categories" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center space-x-3">
                    <Link :href="route('expenses.index')" class="text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-md">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div class="flex items-center space-x-2">
                        <Tags class="w-6 h-6 text-rose-500" />
                        <span class="text-lg sm:text-xl font-bold text-slate-900">Expense Categories</span>
                    </div>
                </div>

                <button
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 focus:outline-none transition-colors"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    New Category
                </button>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Stats KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Categories</p>
                        <p class="text-2xl font-black text-slate-900 mt-1">{{ stats?.total ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <Tags class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Categories</p>
                        <p class="text-2xl font-black text-emerald-600 mt-1">{{ stats?.active ?? 0 }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Categorized Expenses</p>
                        <p class="text-2xl font-black text-rose-600 mt-1">{{ formatCurrency(stats?.total_amount) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <Wallet class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Filter and Action Bar -->
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="relative w-full sm:w-80">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search category name..."
                        class="w-full pl-9 pr-4 py-2 border-slate-200 rounded-lg text-sm focus:ring-rose-500 focus:border-rose-500"
                    />
                </div>

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <select
                        v-model="status"
                        class="w-full sm:w-44 border-slate-200 rounded-lg text-sm focus:ring-rose-500 focus:border-rose-500 font-medium"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>

                    <Link
                        :href="route('expenses.create')"
                        class="hidden sm:inline-flex items-center text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-2 rounded-lg transition-colors whitespace-nowrap"
                    >
                        <ReceiptText class="w-3.5 h-3.5 mr-1.5" />
                        Record Expense
                    </Link>
                </div>
            </div>

            <!-- Categories Listing -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div v-if="categories.data.length === 0" class="p-12 text-center">
                    <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 mx-auto flex items-center justify-center mb-4">
                        <Tags class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-bold text-slate-900">No expense categories found</h3>
                    <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                        Categorize your business expenses (e.g., Electricity, Raw Materials, Salaries) for clear financial reporting.
                    </p>
                    <button
                        @click="openCreateModal"
                        class="mt-5 inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-sm shadow-xs transition-colors"
                    >
                        <Plus class="w-4 h-4 mr-1.5" />
                        Add First Category
                    </button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3.5 px-4 sm:px-6">Category</th>
                                <th class="py-3.5 px-4">Color Tag</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center">Associated Expenses</th>
                                <th class="py-3.5 px-4 text-right">Total Incurred</th>
                                <th class="py-3.5 px-4 sm:px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="cat in categories.data"
                                :key="cat.id"
                                class="hover:bg-slate-50/80 transition-colors"
                            >
                                <td class="py-4 px-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-white text-xs shadow-xs shrink-0"
                                            :style="{ backgroundColor: cat.color || '#f43f5e' }"
                                        >
                                            {{ cat.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 text-sm block">{{ cat.name }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-3.5 h-3.5 rounded-full inline-block shrink-0 shadow-xs"
                                            :style="{ backgroundColor: cat.color || '#f43f5e' }"
                                        ></span>
                                        <span class="text-xs font-mono text-slate-500">{{ cat.color || '#f43f5e' }}</span>
                                    </div>
                                </td>

                                <td class="py-4 px-4 text-center">
                                    <span
                                        :class="[
                                            cat.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200',
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border'
                                        ]"
                                    >
                                        {{ cat.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-center">
                                    <span class="font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md text-xs">
                                        {{ cat.expenses_count }} {{ cat.expenses_count === 1 ? 'expense' : 'expenses' }}
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-right">
                                    <span class="font-extrabold text-slate-900">
                                        {{ formatCurrency(cat.expenses_sum_amount) }}
                                    </span>
                                </td>

                                <td class="py-4 px-4 sm:px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEditModal(cat)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                                            title="Edit Category"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteCategory(cat)"
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

                <!-- Pagination if multi-page -->
                <div v-if="categories.links && categories.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ categories.from }} to {{ categories.to }} of {{ categories.total }} categories
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in categories.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                link.active ? 'bg-rose-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed' : '',
                                'px-3 py-1 text-xs rounded-md'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <Modal :show="isModalOpen" @close="closeModal" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-5">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold">
                            <Tags class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                {{ editingCategory ? 'Edit Expense Category' : 'New Expense Category' }}
                            </h3>
                            <p class="text-xs text-slate-500">Group your expenses for organized tracking</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="category_name" value="Category Name *" />
                        <TextInput
                            id="category_name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full rounded-lg font-bold"
                            placeholder="e.g. Electricity & Water, Logistics, Salaries"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Accent Color Tag" />
                        <div class="flex items-center gap-2.5 mt-2">
                            <button
                                v-for="c in colorPresets"
                                :key="c"
                                type="button"
                                @click="form.color = c"
                                class="w-7 h-7 rounded-full transition-transform active:scale-90 flex items-center justify-center shadow-xs"
                                :style="{ backgroundColor: c }"
                            >
                                <Check v-if="form.color === c" class="w-4 h-4 text-white stroke-[3]" />
                            </button>
                        </div>
                        <InputError :message="form.errors.color" class="mt-1" />
                    </div>

                    <div class="pt-2">
                        <label class="flex items-center cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-slate-300 text-rose-600 shadow-sm focus:ring-rose-500"
                            />
                            <span class="ml-2 text-xs font-bold text-slate-700">Active (Visible when recording expenses)</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white rounded-lg text-xs font-bold shadow-xs transition-colors flex items-center"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>{{ editingCategory ? 'Save Changes' : 'Create Category' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
