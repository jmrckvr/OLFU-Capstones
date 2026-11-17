<script setup>
/*
 * Admin → Projects (template page 13). Every project in every status.
 * Search, status pill switch, program filter, sortable/paged table, quick view,
 * Edit / Delete (in-page confirm), Add project, Export CSV.
 */
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Eye, FileDown, FilterX, FolderKanban, Lock, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import Modal from '../../Components/Modal.vue';
import SearchField from '../../Components/SearchField.vue';
import { EmptyState, PillSwitch, StatusPill } from '../../Components/Bits';
import { confirmDialog, formatDate } from '../../lib/ui';

const props = defineProps({ projects: Array, statuses: Object });

const search = ref('');
const status = ref('');
const program = ref('');
const hasFilters = computed(() => search.value || status.value || program.value);
const rows = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.projects.filter((p) =>
        (!status.value || p.status === status.value)
        && (!program.value || p.program === program.value)
        && (!q || `${p.title} ${p.proponents} ${p.technology ?? ''} ${p.owner?.name ?? ''}`.toLowerCase().includes(q)));
});
const pills = computed(() => [
    { value: '', label: 'All', count: props.projects.length },
    { value: 'approved', label: 'Approved', count: props.projects.filter((p) => p.status === 'approved').length },
    { value: 'pending', label: 'Pending', count: props.projects.filter((p) => p.status === 'pending').length },
    { value: 'returned', label: 'Returned', count: props.projects.filter((p) => p.status === 'returned').length },
    { value: 'draft', label: 'Draft', count: props.projects.filter((p) => p.status === 'draft').length },
]);
function clearFilters() { search.value = ''; status.value = ''; program.value = ''; }

const columns = [
    { key: 'title', label: 'Project', sortable: true },
    { key: 'academic_year', label: 'Year', sortable: true, width: '110px' },
    { key: 'status', label: 'Status', sortable: true, width: '190px' },
    { key: 'downloads', label: 'Downloads', sortable: true, width: '110px', align: 'right' },
    { key: 'actions', label: '', width: '130px', align: 'right' },
];

const quick = ref(null);
async function remove(p) {
    const ok = await confirmDialog({
        title: 'Delete this project?', message: `“${p.title}” and all its files will be removed for good.`,
        confirmText: 'Yes, delete', danger: true,
    });
    if (ok) router.delete(`/admin/projects/${p.id}`, { preserveScroll: true, onSuccess: () => { quick.value = null; } });
}
const exportUrl = computed(() => '/admin/projects/export' + (status.value ? `?status=${status.value}` : ''));
</script>

<template>
    <AppLayout title="Projects" :crumbs="[['Administration', '/admin/projects']]">
        <template #actions>
            <a :href="exportUrl" class="btn btn-outline"><FileDown /><span class="btn-label">Export CSV</span></a>
            <Link href="/admin/projects/create" class="btn btn-primary"><Plus /><span class="btn-label">Add project</span></Link>
        </template>

        <div class="toolbar">
            <SearchField v-model="search" placeholder="Search title, proponent, technology" />
            <PillSwitch v-model="status" :options="pills" />
            <select v-model="program" class="select" style="min-width: 130px" aria-label="Program">
                <option value="">All programs</option><option>BSIT</option><option>BSCS</option>
            </select>
            <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm" @click="clearFilters"><FilterX />Clear filters</button>
        </div>

        <section class="panel fill">
            <DataTable :columns="columns" :rows="rows" clickable noun="projects" :default-sort="{ key: 'title', dir: 'asc' }" @open="quick = $event">
                <template #cell-title="{ row }">
                    <div class="flex items-center gap-3" style="min-width: 0">
                        <img v-if="row.cover_path" :src="`/projects/${row.id}/cover`" class="thumb" style="width: 40px; height: 30px" alt="" loading="lazy" />
                        <div style="min-width: 0">
                            <div class="cell-title" :title="row.title">{{ row.title }}<Lock v-if="row.is_restricted" style="width: 12px; height: 12px; display: inline; margin-left: 6px; color: var(--info)" /></div>
                            <div class="cell-sub" :title="row.proponents">{{ row.proponents }}</div>
                        </div>
                    </div>
                </template>
                <template #cell-academic_year="{ row }"><span class="small nowrap">{{ row.academic_year }}</span></template>
                <template #cell-status="{ row }"><StatusPill :status="row.status" /></template>
                <template #cell-downloads="{ row }"><span class="tabular">{{ row.downloads }}</span></template>
                <template #cell-actions="{ row }">
                    <div class="actions">
                        <Link :href="`/projects/${row.id}`" class="btn btn-ghost btn-sm" title="View" aria-label="View"><Eye /></Link>
                        <Link :href="`/admin/projects/${row.id}/edit`" class="btn btn-ghost btn-sm" title="Edit" aria-label="Edit"><Pencil /></Link>
                        <button type="button" class="btn btn-ghost btn-sm" title="Delete" aria-label="Delete" style="color: var(--bad)" @click="remove(row)"><Trash2 /></button>
                    </div>
                </template>
                <template #empty>
                    <EmptyState :icon="FolderKanban" :title="hasFilters ? 'No projects match' : 'No projects yet'" :text="hasFilters ? 'Try a different search or filter.' : 'Add a project or wait for student submissions.'">
                        <button v-if="hasFilters" type="button" class="btn btn-outline btn-sm" style="margin-top: 6px" @click="clearFilters"><FilterX />Clear filters</button>
                    </EmptyState>
                </template>
            </DataTable>
        </section>

        <Modal :show="!!quick" :title="quick?.title" size="wide" @close="quick = null">
            <template v-if="quick">
                <div class="flex items-center gap-2 flex-wrap">
                    <StatusPill :status="quick.status" />
                    <span v-if="quick.is_restricted" class="status restricted">Restricted files</span>
                    <span class="small faint">by {{ quick.owner?.name ?? 'Unknown' }}</span>
                </div>
                <div><div class="eyebrow">Proponents</div><div style="margin-top: 4px">{{ quick.proponents }}</div></div>
                <div class="facts">
                    <div class="fact"><b>{{ quick.academic_year }}</b><span>Academic year</span></div>
                    <div class="fact"><b :title="quick.category">{{ quick.category }}</b><span>Category</span></div>
                    <div class="fact"><b>{{ quick.program }}</b><span>Program</span></div>
                    <div class="fact"><b>{{ quick.downloads }}</b><span>Downloads</span></div>
                </div>
                <div class="facts">
                    <div class="fact"><b>{{ formatDate(quick.submitted_at) }}</b><span>Submitted</span></div>
                    <div class="fact"><b>{{ formatDate(quick.approved_at) }}</b><span>Approved</span></div>
                    <div class="fact"><b :title="quick.technology">{{ quick.technology || '—' }}</b><span>Technologies</span></div>
                </div>
            </template>
            <template #footer>
                <button type="button" class="btn btn-danger-outline" @click="remove(quick)"><Trash2 />Delete</button>
                <span style="flex: 1" />
                <Link :href="`/admin/projects/${quick?.id}/edit`" class="btn btn-outline"><Pencil />Edit</Link>
                <Link :href="`/projects/${quick?.id}`" class="btn btn-primary"><Eye />Open</Link>
            </template>
        </Modal>
    </AppLayout>
</template>
