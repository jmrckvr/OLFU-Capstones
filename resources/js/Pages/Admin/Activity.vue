<script setup>
/*
 * Admin → Activity Log: who did what, and when (last 500 entries).
 * Filter by kind (projects / users / announcements), by person, and by date range.
 * Dates are compared as Manila calendar days, so "today" means today on your clock.
 */
import { computed, ref } from 'vue';
import { Activity, FilterX } from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import SearchField from '../../Components/SearchField.vue';
import { Avatar, EmptyState, PillSwitch } from '../../Components/Bits';
import { ROLE, formatDate } from '../../lib/ui';

const props = defineProps({ logs: Array });

const kind = ref('');
const search = ref('');
const range = ref('all');

// "YYYY-MM-DD" for a date, in Manila time.
const manilaDay = (d) => new Date(d).toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });
const today = manilaDay(new Date());
const daysAgo = (n) => manilaDay(Date.now() - n * 86400000);

const rows = computed(() => {
    const q = search.value.trim().toLowerCase();
    const min = { today, week: daysAgo(6), month: daysAgo(29) }[range.value];
    return props.logs.filter((l) =>
        (!kind.value || l.action.startsWith(kind.value + '.'))
        && (!min || manilaDay(l.created_at) >= min)
        && (!q || `${l.description} ${l.user?.name ?? ''}`.toLowerCase().includes(q)));
});
const pills = computed(() => ['', 'project', 'user', 'announcement'].map((k) => ({
    value: k, label: { '': 'All', project: 'Projects', user: 'Users', announcement: 'Announcements' }[k],
    count: props.logs.filter((l) => !k || l.action.startsWith(k + '.')).length,
})));
const hasFilters = computed(() => kind.value || search.value || range.value !== 'all');
function clearFilters() { kind.value = ''; search.value = ''; range.value = 'all'; }

const columns = [
    { key: 'created_at', label: 'When', sortable: true, width: '190px' },
    { key: 'user', label: 'Who', sortable: true, width: '220px', sortBy: (l) => l.user?.name },
    { key: 'description', label: 'What happened', sortable: true },
];
</script>

<template>
    <AppLayout title="Activity Log" :crumbs="[['Administration', '/admin/projects']]">
        <div class="toolbar">
            <SearchField v-model="search" placeholder="Search activity" />
            <PillSwitch v-model="kind" :options="pills" />
            <select v-model="range" class="select" aria-label="Date range">
                <option value="all">Any time</option>
                <option value="today">Today</option>
                <option value="week">Last 7 days</option>
                <option value="month">Last 30 days</option>
            </select>
            <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm" @click="clearFilters"><FilterX />Clear filters</button>
        </div>
        <section class="panel fill">
            <DataTable :columns="columns" :rows="rows" noun="entries" :page-size="15" :default-sort="{ key: 'created_at', dir: 'desc' }">
                <template #cell-created_at="{ row }"><span class="small muted nowrap">{{ formatDate(row.created_at, true) }}</span></template>
                <template #cell-user="{ row }">
                    <div class="flex items-center gap-2" style="min-width: 0">
                        <Avatar :name="row.user?.name ?? 'System'" size="sm" />
                        <div style="min-width: 0"><div class="cell-title" style="font-weight: 600">{{ row.user?.name ?? 'System' }}</div><div class="cell-sub">{{ ROLE[row.user?.role] ?? '' }}</div></div>
                    </div>
                </template>
                <template #cell-description="{ row }"><div class="one-line" :title="row.description">{{ row.description }}</div></template>
                <template #empty><EmptyState :icon="Activity" title="No activity" :text="hasFilters ? 'Nothing matches these filters.' : 'Actions will be recorded here.'" /></template>
            </DataTable>
        </section>
    </AppLayout>
</template>
