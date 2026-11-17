<script setup>
/*
 * Review Queue (template page 10). Coordinator and admin.
 * Pill switch: "Pending" (projects waiting) | "History" (every decision, newest first).
 * Click a row for a quick view; "Review" opens the full project page with the review box.
 */
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ClipboardCheck, ExternalLink, History } from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import Modal from '../../Components/Modal.vue';
import SearchField from '../../Components/SearchField.vue';
import { Avatar, EmptyState, PillSwitch, StatusPill } from '../../Components/Bits';
import { formatDate, timeAgo } from '../../lib/ui';

const props = defineProps({ pending: Array, history: Array });

const view = ref('pending');
const search = ref('');
const decision = ref('');
const q = computed(() => search.value.trim().toLowerCase());

const pendingRows = computed(() => props.pending.filter((p) => !q.value || `${p.title} ${p.proponents}`.toLowerCase().includes(q.value)));
const historyRows = computed(() => props.history
    .filter((r) => !decision.value || r.decision === decision.value)
    .filter((r) => !q.value || `${r.project?.title} ${r.project?.proponents} ${r.reviewer?.name}`.toLowerCase().includes(q.value)));

const pendingCols = [
    { key: 'title', label: 'Project', sortable: true },
    { key: 'owner', label: 'Submitted by', sortable: true, width: '190px', sortBy: (r) => r.owner?.name },
    { key: 'submitted_at', label: 'Waiting', sortable: true, width: '120px' },
    { key: 'actions', label: '', width: '110px', align: 'right' },
];
const historyCols = [
    { key: 'title', label: 'Project', sortable: true, sortBy: (r) => r.project?.title },
    { key: 'decision', label: 'Decision', sortable: true, width: '200px' },
    { key: 'reviewer', label: 'Reviewer', sortable: true, width: '180px', sortBy: (r) => r.reviewer?.name },
    { key: 'created_at', label: 'Date', sortable: true, width: '130px' },
];

const quick = ref(null); // { project, review? }
</script>

<template>
    <AppLayout title="Review Queue">
        <div class="toolbar">
            <PillSwitch v-model="view" :options="[{ value: 'pending', label: 'Pending', count: pending.length }, { value: 'history', label: 'History', count: history.length }]" />
            <SearchField v-model="search" :placeholder="view === 'pending' ? 'Search pending projects' : 'Search history'" />
            <select v-if="view === 'history'" v-model="decision" class="select" aria-label="Decision">
                <option value="">All decisions</option>
                <option value="approved">Approved</option>
                <option value="returned">Returned</option>
            </select>
        </div>

        <section class="panel fill">
            <DataTable v-if="view === 'pending'" key="pending" :columns="pendingCols" :rows="pendingRows" clickable noun="pending"
                :default-sort="{ key: 'submitted_at', dir: 'asc' }" @open="quick = { project: $event }">
                <template #cell-title="{ row }">
                    <div class="cell-title" :title="row.title">{{ row.title }}</div>
                    <div class="cell-sub" :title="row.proponents">{{ row.proponents }}</div>
                </template>
                <template #cell-owner="{ row }">
                    <div class="flex items-center gap-2" style="min-width: 0"><Avatar :name="row.owner?.name" size="sm" /><span class="one-line">{{ row.owner?.name }}</span></div>
                </template>
                <template #cell-submitted_at="{ row }"><span class="small muted nowrap">{{ timeAgo(row.submitted_at) }}</span></template>
                <template #cell-actions="{ row }">
                    <div class="actions"><Link :href="`/projects/${row.id}`" class="btn btn-primary btn-sm"><ClipboardCheck />Review</Link></div>
                </template>
                <template #empty>
                    <EmptyState :icon="ClipboardCheck" :title="search ? 'No matches' : 'All caught up'" :text="search ? 'Try another search.' : 'No submissions are waiting for review.'" />
                </template>
            </DataTable>

            <DataTable v-else key="history" :columns="historyCols" :rows="historyRows" clickable noun="decisions"
                :default-sort="{ key: 'created_at', dir: 'desc' }" @open="quick = { project: $event.project, review: $event }">
                <template #cell-title="{ row }">
                    <div class="cell-title" :title="row.project?.title">{{ row.project?.title }}</div>
                    <div class="cell-sub">{{ row.project?.proponents }}</div>
                </template>
                <template #cell-decision="{ row }"><StatusPill :status="row.decision" :label="row.decision === 'approved' ? 'Approved' : 'Resubmission Required'" /></template>
                <template #cell-reviewer="{ row }"><span class="one-line" style="display: block">{{ row.reviewer?.name ?? '—' }}</span></template>
                <template #cell-created_at="{ row }"><span class="small muted nowrap">{{ formatDate(row.created_at) }}</span></template>
                <template #empty><EmptyState :icon="History" title="No decisions yet" text="Approvals and returns will be listed here." /></template>
            </DataTable>
        </section>

        <Modal :show="!!quick" :title="quick?.project?.title" size="wide" @close="quick = null">
            <template v-if="quick">
                <div class="flex items-center gap-2 flex-wrap">
                    <StatusPill :status="quick.project.status" />
                    <span v-if="quick.project.submitted_at" class="small faint">Submitted {{ formatDate(quick.project.submitted_at, true) }}</span>
                </div>
                <div><div class="eyebrow">Proponents</div><div style="margin-top: 4px">{{ quick.project.proponents }}</div></div>
                <div v-if="quick.project.academic_year" class="facts">
                    <div class="fact"><b>{{ quick.project.academic_year }}</b><span>Academic year</span></div>
                    <div class="fact"><b :title="quick.project.category">{{ quick.project.category }}</b><span>Category</span></div>
                    <div class="fact"><b>{{ quick.project.program }}</b><span>Program</span></div>
                </div>
                <div v-if="quick.review" class="callout" :class="quick.review.decision === 'approved' ? 'ok' : 'bad'">
                    <div><b>{{ quick.review.decision === 'approved' ? 'Approved' : 'Returned' }}</b> by {{ quick.review.reviewer?.name ?? 'a reviewer' }} on {{ formatDate(quick.review.created_at, true) }}<template v-if="quick.review.feedback">: “{{ quick.review.feedback }}”</template></div>
                </div>
            </template>
            <template #footer>
                <button type="button" class="btn btn-ghost" @click="quick = null">Close</button>
                <Link v-if="quick" :href="`/projects/${quick.project.id}`" class="btn btn-primary">
                    <template v-if="quick.project.status === 'pending'"><ClipboardCheck />Open review</template>
                    <template v-else><ExternalLink />Open project</template>
                </Link>
            </template>
        </Modal>
    </AppLayout>
</template>
