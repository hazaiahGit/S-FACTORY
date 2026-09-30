<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRightLeft, Plus, MapPin, Truck, CheckCircle, Package, ThumbsUp, Zap } from '@lucide/vue';

const props = defineProps({
    transfers: Object,
    isTenantAdmin: Boolean,
    currentBranchId: [Number, String],
});

const page = usePage();
const userRoles = page.props.auth.roles ?? [];
const isSuperAdmin = userRoles.includes('Super Admin') || userRoles.includes('Admin') || props.isTenantAdmin;
const isManager = userRoles.includes('Manager');
const canManage = isSuperAdmin || isManager;

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const approveTransfer = (transferId) => {
    if (confirm('Approve this transfer request? This authorises dispatching the stock.')) {
        router.post(route('stock-transfers.approve', transferId));
    }
};

const dispatchTransfer = (transferId) => {
    if (confirm('Confirm dispatch? This will deduct stock from the source branch.')) {
        router.post(route('stock-transfers.dispatch', transferId));
    }
};

const receiveTransfer = (transferId) => {
    if (confirm('Confirm receipt? This will add stock to the destination branch.')) {
        router.post(route('stock-transfers.receive', transferId));
    }
};

const completeTransfer = (transferId) => {
    if (confirm('Instantly complete this transfer? This will deduct from source and add stock directly to the destination branch.')) {
        router.post(route('stock-transfers.complete', transferId));
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <ArrowRightLeft class="w-6 h-6 mr-3 text-amber-500" />
                Stock Transfers
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex justify-between items-center mb-4">
                <p class="text-sm text-slate-500">Track and transfer inventory across branches.</p>
                <Link :href="route('stock-transfers.create')" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 transition-colors">
                    <Plus class="w-4 h-4 mr-2" />
                    New Transfer Request
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="divide-y divide-slate-100">
                    <div v-for="transfer in transfers.data" :key="transfer.id" class="p-5 hover:bg-slate-50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                            <div>
                                <div class="flex items-center gap-3">
                                    <Link :href="route('stock-transfers.show', transfer.id)" class="text-base sm:text-lg font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                        {{ transfer.transfer_number }}
                                    </Link>
                                    <span :class="[
                                        'px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider rounded-full whitespace-nowrap',
                                        transfer.status === 'received' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' :
                                        transfer.status === 'dispatched' ? 'bg-blue-100 text-blue-800 border border-blue-200' :
                                        transfer.status === 'approved' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 'bg-amber-100 text-amber-800 border border-amber-200'
                                    ]">
                                        {{ transfer.status }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 mt-1">
                                    Requested: {{ formatDate(transfer.requested_date) }}
                                    <span v-if="transfer.requested_by" class="ml-2">by {{ transfer.requested_by.name }}</span>
                                </div>
                            </div>

                            <!-- Action buttons right on the card -->
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-if="transfer.status === 'requested' && canManage"
                                    @click="approveTransfer(transfer.id)"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors"
                                >
                                    <ThumbsUp class="w-3.5 h-3.5 mr-1" />
                                    Approve
                                </button>

                                <button
                                    v-if="transfer.status === 'approved' && (canManage || transfer.from_branch_id == currentBranchId)"
                                    @click="dispatchTransfer(transfer.id)"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-colors"
                                >
                                    <Truck class="w-3.5 h-3.5 mr-1" />
                                    Dispatch
                                </button>

                                <button
                                    v-if="transfer.status === 'dispatched' && (canManage || transfer.to_branch_id == currentBranchId)"
                                    @click="receiveTransfer(transfer.id)"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 shadow-sm transition-colors"
                                >
                                    <CheckCircle class="w-3.5 h-3.5 mr-1" />
                                    Receive Stock
                                </button>

                                <button
                                    v-if="transfer.status !== 'received' && canManage"
                                    @click="completeTransfer(transfer.id)"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 border border-emerald-300 shadow-sm transition-colors"
                                    title="Complete transfer and move stock immediately"
                                >
                                    <Zap class="w-3.5 h-3.5 mr-1 text-emerald-600" />
                                    Complete Transfer
                                </button>

                                <span v-if="transfer.status === 'received'" class="inline-flex items-center text-xs font-bold text-emerald-600">
                                    <CheckCircle class="w-4 h-4 mr-1 text-emerald-500" />
                                    Stock Added to Branch
                                </span>

                                <Link
                                    :href="route('stock-transfers.show', transfer.id)"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors"
                                >
                                    Details →
                                </Link>
                            </div>
                        </div>

                        <!-- Route info & Item summary -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-slate-50 p-3.5 rounded-lg border border-slate-100 text-sm">
                            <div class="flex items-center gap-3">
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase">From</span>
                                    <span class="font-bold text-slate-800 flex items-center">
                                        <MapPin class="w-3.5 h-3.5 text-slate-400 mr-1" />
                                        {{ transfer.from_branch?.name || 'N/A' }}
                                    </span>
                                </div>
                                <div class="text-slate-300 font-bold">→</div>
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase">To Branch</span>
                                    <span class="font-bold text-emerald-700 flex items-center">
                                        <MapPin class="w-3.5 h-3.5 text-emerald-500 mr-1" />
                                        {{ transfer.to_branch?.name || 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Transferred Items -->
                            <div class="flex items-center md:justify-end gap-2 text-xs">
                                <Package class="w-4 h-4 text-indigo-500 flex-shrink-0" />
                                <span class="text-slate-500">Items:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="item in transfer.items"
                                        :key="item.id"
                                        class="inline-flex items-center px-2 py-0.5 rounded bg-white border border-slate-200 font-medium text-slate-800"
                                    >
                                        {{ item.product?.name || 'Product' }}: <strong class="ml-1 text-indigo-600">{{ item.requested_quantity }}</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="transfers.data.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                        <ArrowRightLeft class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No stock transfers found.</p>
                        <p class="text-sm mt-1">Start by requesting a transfer from another branch.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
