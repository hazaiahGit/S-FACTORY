<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Scale,
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
    Check,
    Hash,
    Box,
    Maximize2,
} from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    units: Object,
    filters: Object,
    stats: Object,
});

// Search & Filter State
const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const status = ref(props.filters.status || '');

let searchDebounceTimeout = null;
watch([search, type, status], ([newSearch, newType, newStatus]) => {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        router.get(
            route('units.index'),
            { search: newSearch, type: newType, status: newStatus },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 300);
});

// Modal State
const isModalOpen = ref(false);
const editingUnit = ref(null);

const unitTypes = [
    { value: 'quantity', label: 'Quantity / Count (e.g., pcs, dozen, box)', icon: Box, color: 'blue' },
    { value: 'weight', label: 'Weight / Mass (e.g., kg, gram, ton)', icon: Scale, color: 'amber' },
    { value: 'length', label: 'Length / Distance (e.g., meter, cm, feet)', icon: Layers, color: 'purple' },
    { value: 'volume', label: 'Volume / Liquid (e.g., liter, ml, gallon)', icon: Maximize2, color: 'cyan' },
    { value: 'area', label: 'Area / Surface (e.g., sq meter, sq ft)', icon: Layers, color: 'emerald' },
];

const form = useForm({
    name: '',
    abbreviation: '',
    type: 'quantity',
    allow_decimal: true,
    description: '',
    is_active: true,
});

const openModal = (unit = null) => {
    if (unit) {
        editingUnit.value = unit;
        form.name = unit.name;
        form.abbreviation = unit.abbreviation;
        form.type = unit.type || 'quantity';
        form.allow_decimal = Boolean(unit.allow_decimal);
        form.description = unit.description || '';
        form.is_active = Boolean(unit.is_active);
    } else {
        editingUnit.value = null;
        form.reset();
        form.type = 'quantity';
        form.allow_decimal = true;
        form.is_active = true;
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    editingUnit.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingUnit.value) {
        form.put(route('units.update', editingUnit.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('units.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const toggleStatus = (unit) => {
    router.patch(route('units.toggle-status', unit.id), {}, {
        preserveScroll: true,
    });
};

const deleteUnit = (unit) => {
    if (unit.products_count > 0) {
        alert(`Cannot delete "${unit.name}" because it is currently assigned to ${unit.products_count} product(s).`);
        return;
    }

    if (confirm(`Are you sure you want to delete unit "${unit.name}" (${unit.abbreviation})? This action cannot be undone.`)) {
        router.delete(route('units.destroy', unit.id), {
            preserveScroll: true,
        });
    }
};

const getTypeBadgeClass = (typeValue) => {
    switch (typeValue) {
        case 'weight':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'length':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'volume':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        case 'area':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'quantity':
        default:
            return 'bg-blue-50 text-blue-700 border-blue-200';
    }
};
</script>

<template>
    <Head title="Units of Measure" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-2 text-slate-800">
                <Scale class="w-5 h-5 text-amber-500 shrink-0" />
                <span class="font-semibold text-lg sm:text-xl truncate">Units of Measure</span>
            </div>
        </template>

        <div class="py-6 sm:py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Hero Action Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Units of Measure</span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                            {{ stats?.total ?? 0 }} {{ (stats?.total ?? 0) === 1 ? 'unit' : 'units' }}
                        </span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Configure standard measurement units, abbreviations, and precision rules for inventory and sales.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="openModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-500 hover:bg-amber-600 shadow-sm shadow-amber-500/20 transition-all hover:shadow-md active:scale-95"
                    >
                        <Plus class="w-4 h-4 mr-1.5 stroke-[2.5]" />
                        <span>Add Unit</span>
                    </button>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Units</span>
                        <span class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 block">
                            {{ stats?.total ?? 0 }}
                        </span>
                        <span class="text-xs text-slate-400 mt-0.5 block">Configured measures</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-amber-50 text-amber-600">
                        <Scale class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block">Active Units</span>
                        <span class="text-2xl sm:text-3xl font-black text-emerald-700 mt-1 block">
                            {{ stats?.active ?? 0 }}
                        </span>
                        <span class="text-xs text-emerald-600/80 mt-0.5 block">Available in catalog</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-emerald-50 text-emerald-600">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider block">Discrete (Integer)</span>
                        <span class="text-2xl sm:text-3xl font-black text-indigo-700 mt-1 block">
                            {{ stats?.discrete ?? 0 }}
                        </span>
                        <span class="text-xs text-indigo-600/80 mt-0.5 block">Whole numbers only</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-indigo-50 text-indigo-600">
                        <Hash class="w-6 h-6" />
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
                        placeholder="Search unit by name, abbreviation, description..."
                        class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all placeholder:text-slate-400"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <select
                        v-model="type"
                        class="py-2 px-3 text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"
                    >
                        <option value="">All Unit Types</option>
                        <option value="quantity">Quantity / Count</option>
                        <option value="weight">Weight / Mass</option>
                        <option value="length">Length</option>
                        <option value="volume">Volume</option>
                        <option value="area">Area</option>
                    </select>

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
            <div v-if="units.data.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <Scale class="w-7 h-7" />
                </div>
                <h3 class="text-base font-bold text-slate-900">No units of measure found</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                    {{ search || type || status ? 'No units matched your filter criteria.' : 'Create your first unit of measure to define quantity standards.' }}
                </p>
                <button
                    v-if="!search && !type && !status"
                    @click="openModal()"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-xs"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    Create First Unit
                </button>
            </div>

            <!-- Units List (Dual-Mode: Desktop Table & Mobile Cards) -->
            <template v-else>
                <!-- Desktop Table -->
                <div class="hidden md:block bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6">Unit Name</th>
                                <th class="py-3.5 px-4">Abbreviation</th>
                                <th class="py-3.5 px-4">Type</th>
                                <th class="py-3.5 px-4">Precision Rule</th>
                                <th class="py-3.5 px-4 text-center">Products</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <tr
                                v-for="unit in units.data"
                                :key="unit.id"
                                class="hover:bg-slate-50/60 transition-colors group"
                            >
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/60 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                            {{ unit.abbreviation }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 group-hover:text-amber-600 transition-colors truncate">
                                                {{ unit.name }}
                                            </div>
                                            <div v-if="unit.description" class="text-xs text-slate-400 truncate max-w-md">
                                                {{ unit.description }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4 font-mono text-xs font-bold text-slate-600">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 border border-slate-200">
                                        {{ unit.abbreviation }}
                                    </span>
                                </td>

                                <td class="py-4 px-4">
                                    <span
                                        :class="getTypeBadgeClass(unit.type)"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border capitalize"
                                    >
                                        {{ unit.type }}
                                    </span>
                                </td>

                                <td class="py-4 px-4">
                                    <span
                                        v-if="unit.allow_decimal"
                                        class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium"
                                    >
                                        <Check class="w-3.5 h-3.5" />
                                        Decimal Allowed
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-xs text-slate-500 font-medium"
                                    >
                                        <Hash class="w-3.5 h-3.5 text-indigo-500" />
                                        Whole Number (Integer)
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                        <Package class="w-3.5 h-3.5 text-slate-400" />
                                        {{ unit.products_count || 0 }} items
                                    </span>
                                </td>

                                <td class="py-4 px-4 text-center">
                                    <button
                                        @click="toggleStatus(unit)"
                                        type="button"
                                        :class="[
                                            'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold transition-all',
                                            unit.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'
                                                : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200'
                                        ]"
                                    >
                                        <span :class="['w-1.5 h-1.5 rounded-full', unit.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                        {{ unit.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>

                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button
                                            @click="openModal(unit)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                                            title="Edit Unit"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="deleteUnit(unit)"
                                            :disabled="unit.products_count > 0"
                                            :class="unit.products_count > 0 ? 'text-slate-300 cursor-not-allowed' : 'text-slate-400 hover:text-rose-600 hover:bg-rose-50'"
                                            class="p-1.5 rounded-lg transition-colors"
                                            :title="unit.products_count > 0 ? 'Cannot delete unit with attached products' : 'Delete Unit'"
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
                        v-for="unit in units.data"
                        :key="unit.id"
                        class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4 space-y-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/60 flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                                    {{ unit.abbreviation }}
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-slate-900 text-sm truncate">{{ unit.name }}</h3>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span :class="getTypeBadgeClass(unit.type)" class="text-[10px] font-bold px-2 py-0.2 rounded-full border capitalize">
                                            {{ unit.type }}
                                        </span>
                                        <span class="text-xs font-mono text-slate-500">({{ unit.abbreviation }})</span>
                                    </div>
                                </div>
                            </div>

                            <button
                                @click="toggleStatus(unit)"
                                type="button"
                                :class="[
                                    'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold shrink-0',
                                    unit.is_active
                                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                        : 'bg-slate-100 text-slate-500 border border-slate-200'
                                ]"
                            >
                                <span :class="['w-1.5 h-1.5 rounded-full', unit.is_active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                {{ unit.is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </div>

                        <p v-if="unit.description" class="text-xs text-slate-500 line-clamp-2">
                            {{ unit.description }}
                        </p>

                        <div class="grid grid-cols-2 gap-2 text-xs pt-2.5 border-t border-slate-100">
                            <div>
                                <span class="text-slate-400 text-[11px] font-medium block">Precision</span>
                                <span :class="unit.allow_decimal ? 'text-emerald-600' : 'text-slate-700'" class="font-bold">
                                    {{ unit.allow_decimal ? 'Decimal Allowed' : 'Whole Integer' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] font-medium block">Products</span>
                                <span class="text-slate-700 font-bold flex items-center gap-1">
                                    <Package class="w-3.5 h-3.5 text-slate-400" />
                                    {{ unit.products_count || 0 }} items
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                @click="openModal(unit)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200 transition-colors"
                            >
                                <Edit2 class="w-3.5 h-3.5 text-amber-600" />
                                Edit
                            </button>
                            <button
                                @click="deleteUnit(unit)"
                                :disabled="unit.products_count > 0"
                                :class="unit.products_count > 0 ? 'opacity-40 cursor-not-allowed bg-slate-50 text-slate-400' : 'bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200'"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-colors"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="units.links && units.links.length > 3"
                    class="p-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4"
                >
                    <p class="text-xs text-slate-500 font-medium">
                        Showing <span class="font-bold text-slate-800">{{ units.from || 0 }}</span> to
                        <span class="font-bold text-slate-800">{{ units.to || 0 }}</span> of
                        <span class="font-bold text-slate-800">{{ units.total }}</span> units
                    </p>
                    <div class="flex items-center space-x-1">
                        <template v-for="(link, key) in units.links" :key="key">
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

        <!-- Create / Edit Unit Modal -->
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-5 sm:p-7 bg-white rounded-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                            <Scale class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">
                                {{ editingUnit ? 'Edit Unit of Measure' : 'Create Unit of Measure' }}
                            </h2>
                            <p class="text-xs text-slate-500">Configure standard measurement units and counting rules</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Unit Name -->
                        <div>
                            <InputLabel for="unit_name" value="Unit Name *" />
                            <TextInput
                                id="unit_name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-xl"
                                placeholder="e.g. Kilogram, Piece, Meter"
                                required
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <!-- Abbreviation -->
                        <div>
                            <InputLabel for="unit_abbr" value="Abbreviation / Symbol *" />
                            <TextInput
                                id="unit_abbr"
                                v-model="form.abbreviation"
                                type="text"
                                class="mt-1 block w-full rounded-xl font-mono uppercase"
                                placeholder="e.g. kg, pcs, m, ltr"
                                required
                            />
                            <InputError :message="form.errors.abbreviation" class="mt-1" />
                        </div>
                    </div>

                    <!-- Unit Type -->
                    <div>
                        <InputLabel for="unit_type" value="Measurement Category / Type *" />
                        <select
                            id="unit_type"
                            v-model="form.type"
                            class="mt-1 block w-full border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-xl shadow-xs text-sm"
                            required
                        >
                            <option v-for="t in unitTypes" :key="t.value" :value="t.value">
                                {{ t.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.type" class="mt-1" />
                    </div>

                    <!-- Allow Decimal Toggle -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <label class="flex items-start space-x-3 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.allow_decimal"
                                class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-500 w-4 h-4"
                            />
                            <div>
                                <span class="text-sm font-bold text-slate-800 block">Allow Fractional / Decimal Quantities</span>
                                <span class="text-xs text-slate-500 block mt-0.5">
                                    Check this if items measured in this unit can have decimal quantities (e.g., 2.50 kg, 1.75 meters). Uncheck for discrete counts (e.g., pieces, packets).
                                </span>
                            </div>
                        </label>
                        <InputError :message="form.errors.allow_decimal" class="mt-1" />
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel for="unit_description" value="Description (Optional)" />
                        <textarea
                            id="unit_description"
                            v-model="form.description"
                            rows="2"
                            class="mt-1 block w-full border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-xl shadow-xs text-sm placeholder:text-slate-400"
                            placeholder="Brief notes about how this measurement unit is used..."
                        ></textarea>
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Status Toggle -->
                    <div class="flex items-center space-x-2 pt-1">
                        <input
                            type="checkbox"
                            id="unit_status"
                            v-model="form.is_active"
                            class="rounded border-slate-300 text-amber-500 focus:ring-amber-500 w-4 h-4"
                        />
                        <label for="unit_status" class="text-sm font-semibold text-slate-700 cursor-pointer">
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
                            {{ editingUnit ? 'Save Changes' : 'Create Unit' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
