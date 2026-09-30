<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    CheckCircle, 
    XCircle, 
    Clock, 
    Filter,
    FileText,
    AlertCircle
} from '@lucide/vue';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
    approvals: Object,
    counts: Object,
    filters: Object,
});

const currentStatus = ref(props.filters.status || 'pending');

const filterByStatus = (status) => {
    currentStatus.value = status;
    router.get(route('approvals.index'), { status }, { preserveState: true, replace: true });
};

// Formatting helpers
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const formatType = (type) => {
    if (!type) return 'Unknown';
    // e.g. App\Models\Expense -> Expense
    const parts = type.split('\\');
    return parts[parts.length - 1];
};

// Approve Form
const approveForm = useForm({});
const confirmApprove = (id) => {
    if(confirm('Are you sure you want to approve this request?')) {
        approveForm.post(route('approvals.approve', id), {
            preserveScroll: true
        });
    }
};

// Reject Modal State
const isRejectModalOpen = ref(false);
const selectedApproval = ref(null);
const rejectForm = useForm({
    rejection_reason: '',
});

const openRejectModal = (approval) => {
    selectedApproval.value = approval;
    rejectForm.rejection_reason = '';
    isRejectModalOpen.value = true;
};

const closeRejectModal = () => {
    isRejectModalOpen.value = false;
    selectedApproval.value = null;
    rejectForm.reset();
};

const submitReject = () => {
    rejectForm.post(route('approvals.reject', selectedApproval.value.id), {
        preserveScroll: true,
        onSuccess: () => closeRejectModal(),
    });
};
</script>

<template>
    <Head title="Approvals" />

    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 md:flex md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Approval Workflow</h1>
                    <p class="mt-1 text-sm text-slate-500">Review and manage requests requiring your authorization.</p>
                </div>
            </div>

            <!-- Stats/Tabs -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <button 
                    @click="filterByStatus('pending')"
                    :class="['flex items-center p-4 rounded-xl border transition-all text-left', currentStatus === 'pending' ? 'bg-amber-50 border-amber-200 shadow-sm' : 'bg-white border-slate-200 hover:bg-slate-50']"
                >
                    <div class="p-3 rounded-lg bg-amber-100 text-amber-600 mr-4">
                        <Clock class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Pending</p>
                        <p class="text-2xl font-bold text-slate-900">{{ counts.pending }}</p>
                    </div>
                </button>

                <button 
                    @click="filterByStatus('approved')"
                    :class="['flex items-center p-4 rounded-xl border transition-all text-left', currentStatus === 'approved' ? 'bg-emerald-50 border-emerald-200 shadow-sm' : 'bg-white border-slate-200 hover:bg-slate-50']"
                >
                    <div class="p-3 rounded-lg bg-emerald-100 text-emerald-600 mr-4">
                        <CheckCircle class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Approved</p>
                        <p class="text-2xl font-bold text-slate-900">{{ counts.approved }}</p>
                    </div>
                </button>

                <button 
                    @click="filterByStatus('rejected')"
                    :class="['flex items-center p-4 rounded-xl border transition-all text-left', currentStatus === 'rejected' ? 'bg-rose-50 border-rose-200 shadow-sm' : 'bg-white border-slate-200 hover:bg-slate-50']"
                >
                    <div class="p-3 rounded-lg bg-rose-100 text-rose-600 mr-4">
                        <XCircle class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">Rejected</p>
                        <p class="text-2xl font-bold text-slate-900">{{ counts.rejected }}</p>
                    </div>
                </button>

                <button 
                    @click="filterByStatus('all')"
                    :class="['flex items-center p-4 rounded-xl border transition-all text-left', currentStatus === 'all' ? 'bg-slate-100 border-slate-300 shadow-sm' : 'bg-white border-slate-200 hover:bg-slate-50']"
                >
                    <div class="p-3 rounded-lg bg-slate-100 text-slate-600 mr-4">
                        <Filter class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500">All Requests</p>
                        <p class="text-2xl font-bold text-slate-900">{{ counts.pending + counts.approved + counts.rejected }}</p>
                    </div>
                </button>
            </div>

            <!-- List -->
            <div class="bg-transparent md:bg-white md:rounded-xl md:shadow-sm md:border md:border-slate-200 md:overflow-hidden">
                <!-- Mobile View -->
                <div class="block md:hidden space-y-4 mb-4">
                    <template v-if="approvals.data.length > 0">
                        <div v-for="approval in approvals.data" :key="approval.id" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-lg bg-indigo-50 flex items-center justify-center mr-3 border border-indigo-100 shrink-0">
                                        <FileText class="h-5 w-5 text-indigo-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">{{ formatType(approval.requestable_type) }}</p>
                                        <p class="text-xs text-slate-500 capitalize">{{ approval.action }}</p>
                                    </div>
                                </div>
                                <div v-if="approval.status !== 'pending'">
                                    <span :class="[
                                        'px-2.5 py-1 text-[10px] font-bold rounded-full uppercase tracking-wider whitespace-nowrap',
                                        approval.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                    ]">
                                        {{ approval.status }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500">Requester</span>
                                    <span class="font-medium text-slate-900">{{ approval.requester?.name || 'System' }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-slate-500">Date</span>
                                    <span class="text-slate-900">{{ formatDate(approval.created_at) }}</span>
                                </div>
                                <div class="text-sm mt-3">
                                    <span class="block text-slate-500 mb-1">Details</span>
                                    <p class="text-slate-700 bg-slate-50 p-2.5 rounded-lg text-xs border border-slate-100">{{ approval.reason || 'No specific reason provided' }}</p>
                                    <p v-if="approval.status === 'rejected'" class="text-xs text-rose-600 mt-2 font-medium flex items-start">
                                        <AlertCircle class="h-4 w-4 mr-1 shrink-0"/> {{ approval.rejection_reason }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="approval.status === 'pending'" class="flex space-x-3 border-t border-slate-100 pt-4 mt-2">
                                <button @click="confirmApprove(approval.id)" class="flex-1 px-3 py-2.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-sm font-bold transition-colors text-center">
                                    Approve
                                </button>
                                <button @click="openRejectModal(approval)" class="flex-1 px-3 py-2.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-sm font-bold transition-colors text-center">
                                    Reject
                                </button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 text-center py-12 px-4">
                        <div class="h-12 w-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3 border border-slate-100">
                            <CheckCircle class="h-6 w-6 text-slate-300" />
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">You're all caught up!</h3>
                        <p class="text-sm text-slate-500">There are no {{ currentStatus !== 'all' ? currentStatus : '' }} approval requests waiting for you.</p>
                    </div>
                </div>

                <!-- Desktop View -->
                <div class="hidden md:block">
                    <div v-if="approvals.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Request Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Requester</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Details</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr v-for="approval in approvals.data" :key="approval.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 rounded-lg bg-indigo-50 flex items-center justify-center mr-3 border border-indigo-100">
                                                <FileText class="h-4 w-4 text-indigo-600" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-900">{{ formatType(approval.requestable_type) }}</p>
                                                <p class="text-xs text-slate-500 capitalize">{{ approval.action }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-medium text-slate-900">{{ approval.requester?.name || 'System' }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm text-slate-600 truncate max-w-xs" :title="approval.reason">{{ approval.reason || 'No specific reason provided' }}</p>
                                        <p v-if="approval.status === 'rejected'" class="text-xs text-rose-600 mt-1 font-medium flex items-center">
                                            <AlertCircle class="h-3 w-3 mr-1"/> {{ approval.rejection_reason }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-slate-500">{{ formatDate(approval.created_at) }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div v-if="approval.status === 'pending'" class="flex justify-end space-x-2">
                                            <button @click="confirmApprove(approval.id)" class="px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold transition-colors">
                                                Approve
                                            </button>
                                            <button @click="openRejectModal(approval)" class="px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-xs font-bold transition-colors">
                                                Reject
                                            </button>
                                        </div>
                                        <div v-else>
                                            <span :class="[
                                                'px-3 py-1 text-xs font-bold rounded-full',
                                                approval.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                            ]">
                                                {{ approval.status.charAt(0).toUpperCase() + approval.status.slice(1) }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-center py-16 px-4">
                        <div class="h-16 w-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                            <CheckCircle class="h-8 w-8 text-slate-300" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-1">You're all caught up!</h3>
                        <p class="text-slate-500 max-w-md mx-auto">There are no {{ currentStatus !== 'all' ? currentStatus : '' }} approval requests waiting for you.</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="approvals.links && approvals.data.length > 0" class="px-4 py-4 md:px-6 md:py-4 border md:border-x-0 md:border-b-0 border-slate-200 md:border-t bg-white md:bg-slate-50 rounded-xl md:rounded-none">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <p class="text-sm text-slate-500 text-center sm:text-left">
                            Showing <span class="font-bold">{{ approvals.from }}</span> to <span class="font-bold">{{ approvals.to }}</span> of <span class="font-bold">{{ approvals.total }}</span> requests
                        </p>
                        <div class="flex flex-wrap justify-center sm:justify-end gap-1">
                            <Link 
                                v-for="(link, k) in approvals.links" 
                                :key="k" 
                                :href="link.url || '#'"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1.5 text-sm rounded-md transition-colors',
                                    link.active ? 'bg-indigo-600 text-white font-bold' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <TransitionRoot appear :show="isRejectModalOpen" as="template">
            <Dialog as="div" @close="closeRejectModal" class="relative z-50">
                <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-black/25 backdrop-blur-sm" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-bold leading-6 text-slate-900 flex items-center">
                                    <AlertCircle class="h-5 w-5 text-rose-500 mr-2" />
                                    Reject Request
                                </DialogTitle>
                                
                                <div class="mt-4">
                                    <p class="text-sm text-slate-500 mb-4">
                                        Please provide a reason for rejecting this request. This will be visible to the requester.
                                    </p>
                                    
                                    <form @submit.prevent="submitReject">
                                        <div>
                                            <label class="block text-sm font-bold text-slate-700 mb-1">Reason for Rejection</label>
                                            <textarea 
                                                v-model="rejectForm.rejection_reason" 
                                                rows="3"
                                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                placeholder="e.g., Exceeds budget limit, Need more information..."
                                                required
                                            ></textarea>
                                            <p v-if="rejectForm.errors.rejection_reason" class="mt-1 text-sm text-red-600">{{ rejectForm.errors.rejection_reason }}</p>
                                        </div>
                                        
                                        <div class="mt-6 flex justify-end space-x-3">
                                            <button 
                                                type="button" 
                                                @click="closeRejectModal"
                                                class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors"
                                            >
                                                Cancel
                                            </button>
                                            <button 
                                                type="submit" 
                                                :disabled="rejectForm.processing"
                                                class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm font-bold hover:bg-rose-700 transition-colors disabled:opacity-50"
                                            >
                                                Confirm Rejection
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>
