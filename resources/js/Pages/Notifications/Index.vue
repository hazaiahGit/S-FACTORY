<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Bell,
    CheckCheck,
    AlertTriangle,
    CheckCircle,
    Info,
    Building2,
    Filter,
    ExternalLink,
    ChevronRight,
} from '@lucide/vue';

const props = defineProps({
    notifications: Object,
    branches: Array,
    unreadCount: Number,
    filters: Object,
});

const statusFilter = ref(props.filters?.status || 'all');
const typeFilter = ref(props.filters?.type || '');
const branchFilter = ref(props.filters?.branch_id || '');

const applyFilters = () => {
    router.get(route('notifications.index'), {
        status: statusFilter.value,
        type: typeFilter.value || undefined,
        branch_id: branchFilter.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([statusFilter, typeFilter, branchFilter], () => {
    applyFilters();
});

const markAsRead = (notification) => {
    if (!notification.is_read) {
        router.post(route('notifications.read', notification.id), {}, { preserveScroll: true });
    }
};

const markAllRead = () => {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Notifications" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-800">Notifications</h2>
                    <span v-if="unreadCount > 0" class="px-2.5 py-0.5 text-xs font-black rounded-full bg-amber-500 text-white shadow-xs">
                        {{ unreadCount }} Unread
                    </span>
                </div>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    @click="markAllRead"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors shadow-2xs"
                >
                    <CheckCheck class="w-4 h-4 text-emerald-600" />
                    <span>Mark all read</span>
                </button>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Filter Bar -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Status Tabs -->
                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl self-start md:self-auto">
                    <button
                        type="button"
                        @click="statusFilter = 'all'"
                        :class="[
                            'px-4 py-1.5 text-xs font-bold rounded-lg transition-all',
                            statusFilter === 'all' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'unread'"
                        :class="[
                            'px-4 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5',
                            statusFilter === 'unread' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        <span>Unread</span>
                        <span v-if="unreadCount > 0" class="w-2 h-2 rounded-full bg-amber-500"></span>
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'read'"
                        :class="[
                            'px-4 py-1.5 text-xs font-bold rounded-lg transition-all',
                            statusFilter === 'read' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        Read
                    </button>
                </div>

                <!-- Type & Branch Filters -->
                <div class="flex items-center gap-3 flex-wrap">
                    <!-- Branch Filter (Tenant Admin Only) -->
                    <div v-if="branches && branches.length > 0" class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5">
                        <Building2 class="w-3.5 h-3.5 text-slate-400 mr-2" />
                        <select
                            v-model="branchFilter"
                            class="text-xs bg-transparent border-none focus:ring-0 text-slate-700 font-bold p-0 cursor-pointer w-36 truncate"
                        >
                            <option value="">All Branches</option>
                            <option v-for="b in branches" :key="b.id" :value="b.id">
                                {{ b.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <select
                        v-model="typeFilter"
                        class="text-xs bg-slate-50 border border-slate-200 rounded-xl py-1.5 px-3 text-slate-700 font-bold focus:ring-amber-500 focus:border-amber-500"
                    >
                        <option value="">All Types</option>
                        <option value="alert">Alerts (Low Stock)</option>
                        <option value="warning">Warnings (Credit Sales)</option>
                        <option value="success">Success (Payments)</option>
                        <option value="info">System Info</option>
                    </select>
                </div>
            </div>

            <!-- Notifications Feed -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
                <div
                    v-for="item in notifications.data"
                    :key="item.id"
                    :class="[
                        'p-5 transition-colors flex items-start justify-between gap-4',
                        !item.is_read ? 'bg-amber-50/25 hover:bg-amber-50/40' : 'hover:bg-slate-50/70'
                    ]"
                >
                    <div class="flex items-start gap-4 min-w-0 flex-1">
                        <!-- Icon -->
                        <div class="shrink-0 mt-0.5">
                            <div v-if="item.type === 'alert'" class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                                <AlertTriangle class="w-5 h-5" />
                            </div>
                            <div v-else-if="item.type === 'warning'" class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                                <AlertTriangle class="w-5 h-5" />
                            </div>
                            <div v-else-if="item.type === 'success'" class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                <CheckCircle class="w-5 h-5" />
                            </div>
                            <div v-else class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                <Info class="w-5 h-5" />
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span v-if="item.branch_name" class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                    📍 {{ item.branch_name }}
                                </span>
                                <span v-else class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    🏢 Enterprise
                                </span>
                                <h4 :class="['text-sm font-bold', !item.is_read ? 'text-slate-900' : 'text-slate-700']">
                                    {{ item.title }}
                                </h4>
                                <span v-if="!item.is_read" class="w-2 h-2 rounded-full bg-amber-500"></span>
                            </div>
                            <p class="text-sm text-slate-600 leading-relaxed">
                                {{ item.message }}
                            </p>
                            <div class="flex items-center gap-4 mt-2">
                                <span class="text-xs text-slate-400 font-medium">{{ item.time_ago }}</span>
                                <a
                                    v-if="item.action_url"
                                    :href="item.action_url"
                                    @click="markAsRead(item)"
                                    class="text-xs font-bold text-amber-600 hover:text-amber-700 inline-flex items-center gap-1 transition-colors"
                                >
                                    <span>View Details</span>
                                    <ExternalLink class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Right Action Button -->
                    <div class="shrink-0 flex items-center gap-2">
                        <button
                            v-if="!item.is_read"
                            type="button"
                            @click="markAsRead(item)"
                            class="px-2.5 py-1 text-xs font-bold rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                            title="Mark as read"
                        >
                            Mark Read
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="notifications.data.length === 0" class="p-16 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-50 text-slate-300 flex items-center justify-center mx-auto mb-4 border border-slate-100">
                        <Bell class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-bold text-slate-800">No notifications found</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        There are no notifications matching your selected filters.
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.links && notifications.links.length > 3" class="flex justify-center pt-2">
                <nav class="relative z-0 inline-flex rounded-xl shadow-2xs -space-x-px bg-white border border-slate-200 p-1">
                    <template v-for="(link, i) in notifications.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 text-xs font-bold rounded-lg transition-colors',
                                link.active ? 'bg-amber-500 text-white' : 'text-slate-600 hover:bg-slate-100'
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3 py-1.5 text-xs font-medium text-slate-300 pointer-events-none"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
