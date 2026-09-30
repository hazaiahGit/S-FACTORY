<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Truck, CheckCircle, Package, MapPin, ThumbsUp } from '@lucide/vue';

const props = defineProps({
    transfer: Object,
    currentBranchId: Number,
});

const page = usePage();
const userRoles = page.props.auth.roles ?? [];
const isSuperAdmin = userRoles.includes('Super Admin');
const isManager = userRoles.includes('Manager');
const canApprove = isSuperAdmin || isManager;

const isDestination = props.transfer.to_branch_id === props.currentBranchId;
const isSource = props.transfer.from_branch_id === props.currentBranchId;

const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const approveTransfer = () => {
    if (confirm('Approve this transfer request? This authorises the source branch to dispatch the stock.')) {
        router.post(route('stock-transfers.approve', props.transfer.id));
    }
};

const dispatchTransfer = () => {
    if (confirm('Confirm dispatch? This will immediately reduce stock from your branch.')) {
        router.post(route('stock-transfers.dispatch', props.transfer.id));
    }
};

const receiveTransfer = () => {
    if (confirm('Confirm receipt? This will immediately add stock to your branch.')) {
        router.post(route('stock-transfers.receive', props.transfer.id));
    }
};

const statusColor = {
    requested: 'bg-amber-100 text-amber-800 border border-amber-200',
    approved: 'bg-indigo-100 text-indigo-800 border border-indigo-200',
    dispatched: 'bg-blue-100 text-blue-800 border border-blue-200',
    received: 'bg-emerald-100 text-emerald-800 border border-emerald-200',
    cancelled: 'bg-rose-100 text-rose-800 border border-rose-200',
};

const steps = [
    { key: 'requested', label: 'Requested' },
    { key: 'approved', label: 'Approved' },
    { key: 'dispatched', label: 'Dispatched' },
    { key: 'received', label: 'Received' },
];

const stepIndex = (status) => steps.findIndex(s => s.key === status);
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link :href="route('stock-transfers.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-lg">{{ transfer.transfer_number }}</span>
                            <span :class="['px-2.5 py-1 text-xs font-bold uppercase tracking-wider rounded-full', statusColor[transfer.status] || 'bg-slate-100 text-slate-700']">
                                {{ transfer.status }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mt-0.5">
                            {{ transfer.from_branch?.name }} → {{ transfer.to_branch?.name }}
                        </p>
                    </div>
                </div>

                <!-- Action Buttons based on workflow step -->
                <div class="flex flex-wrap gap-2">
                    <!-- Step 1: Manager/Admin approves a requested transfer -->
                    <button
                        v-if="transfer.status === 'requested' && canApprove"
                        @click="approveTransfer"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors"
                    >
                        <ThumbsUp class="w-4 h-4 mr-2" />
                        Approve Transfer
                    </button>

                    <!-- Step 2: Source branch dispatches after approval -->
                    <button
                        v-if="transfer.status === 'approved' && isSource"
                        @click="dispatchTransfer"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors"
                    >
                        <Truck class="w-4 h-4 mr-2" />
                        Dispatch Stock
                    </button>

                    <!-- Step 3: Destination branch confirms receipt -->
                    <button
                        v-if="transfer.status === 'dispatched' && isDestination"
                        @click="receiveTransfer"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 transition-colors"
                    >
                        <CheckCircle class="w-4 h-4 mr-2" />
                        Confirm Receipt
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">

            <!-- Workflow Progress Steps -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-center justify-between relative">
                    <div class="absolute left-0 right-0 top-4 h-0.5 bg-slate-200 z-0 mx-8"></div>
                    <div
                        v-for="(step, i) in steps"
                        :key="step.key"
                        class="flex flex-col items-center z-10"
                    >
                        <div
                            :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2',
                                stepIndex(transfer.status) > i
                                    ? 'bg-emerald-500 border-emerald-500 text-white'
                                    : stepIndex(transfer.status) === i
                                        ? 'bg-amber-400 border-amber-400 text-slate-900'
                                        : 'bg-white border-slate-300 text-slate-400'
                            ]"
                        >
                            <CheckCircle v-if="stepIndex(transfer.status) > i" class="w-4 h-4" />
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <span :class="['mt-2 text-xs font-semibold', stepIndex(transfer.status) >= i ? 'text-slate-900' : 'text-slate-400']">
                            {{ step.label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Details -->
                <div class="space-y-6">
                    <!-- Routing -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center">
                                <MapPin class="w-3.5 h-3.5 mr-2 text-indigo-500" />
                                Route
                            </h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase">From</span>
                                <p class="font-bold text-slate-900">{{ transfer.from_branch?.name }}</p>
                            </div>
                            <div class="pl-3 border-l-2 border-indigo-100">
                                <Truck class="w-4 h-4 text-slate-300" />
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase">To</span>
                                <p class="font-bold text-emerald-700">{{ transfer.to_branch?.name }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Timeline</h3>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div>
                                <span class="text-xs font-bold text-slate-400 uppercase">Requested</span>
                                <p class="font-semibold text-slate-700">{{ formatDate(transfer.requested_date) }}</p>
                                <p class="text-slate-500 text-xs">by {{ transfer.requested_by?.name || 'System' }}</p>
                            </div>
                            <div v-if="transfer.approved_by">
                                <span class="text-xs font-bold text-indigo-400 uppercase">Approved</span>
                                <p class="font-semibold text-slate-700">by {{ transfer.approved_by?.name }}</p>
                            </div>
                            <div v-if="transfer.dispatched_date">
                                <span class="text-xs font-bold text-blue-400 uppercase">Dispatched</span>
                                <p class="font-semibold text-slate-700">{{ formatDate(transfer.dispatched_date) }}</p>
                                <p class="text-slate-500 text-xs">by {{ transfer.dispatched_by?.name || 'System' }}</p>
                            </div>
                            <div v-if="transfer.received_date">
                                <span class="text-xs font-bold text-emerald-400 uppercase">Received</span>
                                <p class="font-semibold text-slate-700">{{ formatDate(transfer.received_date) }}</p>
                                <p class="text-slate-500 text-xs">by {{ transfer.received_by?.name || 'System' }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="transfer.notes" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <span class="text-xs font-bold text-slate-400 uppercase">Notes</span>
                        <p class="mt-1 text-sm text-slate-700">{{ transfer.notes }}</p>
                    </div>
                </div>

                <!-- Right: Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 bg-slate-50 flex items-center">
                            <Package class="w-4 h-4 mr-2 text-emerald-500" />
                            <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">Transfer Items</h3>
                        </div>
                        <div>
                            <div
                                v-for="item in transfer.items"
                                :key="item.id"
                                class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors"
                            >
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">{{ item.product?.name }}</div>
                                        <div class="text-xs text-slate-500">{{ item.product?.sku }}</div>
                                    </div>
                                    <div class="flex gap-6">
                                        <div class="text-center">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Requested</span>
                                            <span class="text-lg font-black text-slate-900">{{ formatNumber(item.requested_quantity) }}</span>
                                        </div>
                                        <div v-if="['dispatched','received'].includes(transfer.status)" class="text-center">
                                            <span class="block text-[10px] font-bold text-blue-400 uppercase mb-1">Dispatched</span>
                                            <span class="text-lg font-black text-blue-700">{{ formatNumber(item.dispatched_quantity) }}</span>
                                        </div>
                                        <div v-if="transfer.status === 'received'" class="text-center">
                                            <span class="block text-[10px] font-bold text-emerald-400 uppercase mb-1">Received</span>
                                            <span class="text-lg font-black text-emerald-700">{{ formatNumber(item.received_quantity) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
