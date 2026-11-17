<script setup>
/*
 * My Submissions (template page 7). Student only.
 * Draft → Submit / Edit / Cancel · Pending → View / Cancel · Returned → Edit / Resubmit · Approved → View
 */
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Eye, FilePlus2, Pencil, Plus, Send, X } from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import Modal from '../../Components/Modal.vue';
import SearchField from '../../Components/SearchField.vue';
import { EmptyState, PillSwitch, StatusPill } from '../../Components/Bits';
import { STATUS, confirmDialog, formatDate, timeAgo } from '../../lib/ui';

const props = defineProps({ projects: Array, statuses: Object });

const status = ref('');
const search = ref('');
const rows = computed(() => props.projects.filter((p) =>
    (!status.value || p.status === status.value)
    && (!search.value || `${p.title} ${p.proponents}`.toLowerCase().includes(search.value.toLowerCase()))));
const pills = computed(() => [
    { value: '', label: 'All', count: props.projects.length },
    ...Object.entries(STATUS).map(([value, label]) => ({
        value, label: value === 'returned' ? 'Returned' : value === 'pending' ? 'Pending' : label,
        count: props.projects.filter((p) => p.status === value).length,
    })),
]);

const columns = [
    { key: 'title', label: 'Project', sortable: true },
    { key: 'status', label: 'Status', sortable: true, width: '190px' },
    { key: 'updated_at', label: 'Updated', sortable: true, width: '120px' },
    { key: 'actions', label: '', width: '310px', align: 'right' },
];

const quick = ref(null);
const busy = ref(false);

function submit(p) {
    busy.value = true;
    router.post(`/submissions/${p.id}/submit`, {}, { preserveScroll: true, onFinish: () => { busy.value = false; quick.value = null; } });
}
async function cancel(p) {
    const ok = await confirmDialog({
        title: p.status === 'draft' ? 'Delete this draft?' : 'Cancel this submission?',
        message: `“${p.title}” and its uploaded files will be removed.`,
        confirmText: p.status === 'draft' ? 'Delete draft' : 'Cancel submission', danger: true,
    });
    if (ok) router.delete(`/submissions/${p.id}`, { preserveScroll: true, onSuccess: () => { quick.value = null; } });
}
</script>

<template>
    <AppLayout title="My Submissions">
        <template #actions>
            <Link href="/submissions/create" class="btn btn-primary"><FilePlus2 /><span class="btn-label">New submission</span></Link>
        </template>

        <div class="toolbar">
            <SearchField v-model="search" placeholder="Search my submissions" />
            <PillSwitch v-model="status" :options="pills" />
        </div>

        <section class="panel fill">
            <DataTable :columns="columns" :rows="rows" clickable noun="submissions" :default-sort="{ key: 'updated_at', dir: 'desc' }" @open="quick = $event">
                <template #cell-title="{ row }">
                    <div class="cell-title" :title="row.title">{{ row.title }}</div>
                    <div class="cell-sub">{{ row.academic_year }} · {{ row.category }}</div>
                </template>
                <template #cell-status="{ row }"><StatusPill :status="row.status" /></template>
                <template #cell-updated_at="{ row }"><span class="small muted nowrap">{{ timeAgo(row.updated_at) }}</span></template>
                <template #cell-actions="{ row }">
                    <div class="actions">
                        <Link :href="`/projects/${row.id}`" class="btn btn-ghost btn-sm" title="View"><Eye /></Link>
                        <Link v-if="['draft', 'returned'].includes(row.status)" :href="`/submissions/${row.id}/edit`" class="btn btn-outline btn-sm"><Pencil />Edit</Link>
                        <button v-if="['draft', 'returned'].includes(row.status)" type="button" class="btn btn-primary btn-sm" :disabled="busy" @click="submit(row)">
                            <Send />{{ row.status === 'returned' ? 'Resubmit' : 'Submit' }}
                        </button>
                        <button v-if="['draft', 'pending'].includes(row.status)" type="button" class="btn btn-danger-outline btn-sm" @click="cancel(row)"><X />Cancel</button>
                    </div>
                </template>
                <template #empty>
                    <EmptyState v-if="projects.length" title="Nothing here" text="No submissions match this filter." />
                    <div v-else class="empty">
                        <Link href="/submissions/create" class="add-card tall" style="max-width: 360px"><Plus />Start your first submission</Link>
                        <p>Save a draft any time, then submit it for review when it's complete.</p>
                    </div>
                </template>
            </DataTable>
        </section>

        <Modal :show="!!quick" :title="quick?.title" size="wide" @close="quick = null">
            <template v-if="quick">
                <div class="flex items-center gap-2"><StatusPill :status="quick.status" /><span class="small faint">Updated {{ formatDate(quick.updated_at, true) }}</span></div>
                <div v-if="quick.status === 'returned' && quick.feedback" class="callout bad"><div><b>Coordinator feedback:</b> {{ quick.feedback }}</div></div>
                <div class="facts">
                    <div class="fact"><b>{{ quick.academic_year }}</b><span>Academic year</span></div>
                    <div class="fact"><b :title="quick.category">{{ quick.category }}</b><span>Category</span></div>
                    <div class="fact"><b>{{ quick.program }}</b><span>Program</span></div>
                </div>
                <div><div class="eyebrow">Proponents</div><div style="margin-top: 4px">{{ quick.proponents }}</div></div>
            </template>
            <template #footer>
                <button v-if="quick && ['draft', 'pending'].includes(quick.status)" type="button" class="btn btn-danger-outline" @click="cancel(quick)">Cancel submission</button>
                <span style="flex: 1" />
                <Link v-if="quick && ['draft', 'returned'].includes(quick.status)" :href="`/submissions/${quick.id}/edit`" class="btn btn-outline"><Pencil />Edit</Link>
                <Link v-if="quick" :href="`/projects/${quick.id}`" class="btn btn-primary"><Eye />Open project</Link>
            </template>
        </Modal>
    </AppLayout>
</template>
