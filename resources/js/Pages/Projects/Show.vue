<script setup>
/*
 * One project (template pages 5, 6 and 11).
 *   left:  title, proponents, facts, abstract, feedback for the owner
 *   right: files (download / preview PDF), source link, citation, review history,
 *          and the Review box for the coordinator when the project is pending.
 * Restricted projects show "Access denied" instead of files to students.
 */
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowLeft, Check, Copy, Download, ExternalLink, Eye, FileText, Github, Lock, Pencil, RotateCcw, Send, Trash2, Undo2,
} from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import Modal from '../../Components/Modal.vue';
import { Avatar, EmptyState, Field, StatusPill } from '../../Components/Bits';
import { citation, confirmDialog, copyText, fileSize, formatDate } from '../../lib/ui';

const props = defineProps({ project: Object, canSeeFiles: Boolean, can: Object, isOwner: Boolean });
const page = usePage();
const role = computed(() => page.props.auth.user.role);
const p = computed(() => props.project);

const editUrl = computed(() => (role.value === 'administrator' ? `/admin/projects/${p.value.id}/edit` : `/submissions/${p.value.id}/edit`));
const backUrl = computed(() => {
    if (p.value.status === 'approved') return ['Repository', '/repository'];
    if (role.value === 'student') return ['My Submissions', '/submissions'];
    return ['Review Queue', '/reviews'];
});
const proponents = computed(() => p.value.proponents.split(',').map((s) => s.trim()).filter(Boolean));

// Review (coordinator / admin)
const review = useForm({ decision: '', feedback: '', is_restricted: p.value.is_restricted });
function decide(decision) {
    review.decision = decision;
    review.post(`/reviews/${p.value.id}`, { preserveScroll: true });
}

async function remove() {
    const ok = await confirmDialog({
        title: 'Delete this project?',
        message: `“${p.value.title}” and its files will be removed for good.`,
        confirmText: 'Delete project', danger: true,
    });
    if (ok) router.delete(role.value === 'administrator' ? `/admin/projects/${p.value.id}` : `/submissions/${p.value.id}`);
}
function resubmit() { router.post(`/submissions/${p.value.id}/submit`); }

const preview = ref(null); // the file shown in the PDF preview dialog
</script>

<template>
    <AppLayout :title="p.title" :crumbs="[backUrl]" :active="backUrl[1]">
        <template #actions>
            <Link :href="backUrl[1]" class="btn btn-ghost hide-phone"><ArrowLeft /><span class="btn-label">Back</span></Link>
            <button v-if="can.delete" type="button" class="btn btn-danger-outline" @click="remove"><Trash2 /><span class="btn-label">Delete</span></button>
            <Link v-if="can.update" :href="editUrl" class="btn btn-outline"><Pencil /><span class="btn-label">Edit</span></Link>
            <button v-if="isOwner && ['draft', 'returned'].includes(p.status)" type="button" class="btn btn-primary" @click="resubmit">
                <Send /><span class="btn-label">{{ p.status === 'returned' ? 'Resubmit' : 'Submit' }}</span>
            </button>
        </template>

        <div class="row-fill" style="grid-template-columns: minmax(0, 1.6fr) minmax(300px, 1fr)">
            <!-- Left: the write-up -->
            <section class="panel fill rise">
                <div class="panel-head">
                    <StatusPill :status="p.status" />
                    <span v-if="p.is_restricted" class="status restricted"><Lock style="width: 11px; height: 11px" />Restricted files</span>
                    <span class="grow" />
                    <span class="small faint nowrap hide-phone">Submitted by {{ p.owner?.name ?? 'Unknown' }}</span>
                </div>
                <div class="scroll panel-body flex flex-col gap-4">
                    <div v-if="isOwner && p.status === 'returned' && p.feedback" class="callout bad">
                        <RotateCcw />
                        <div><b>Resubmission required.</b> {{ p.feedback }}<br /><Link :href="editUrl" class="link" style="color: inherit; text-decoration: underline">Edit and resubmit</Link></div>
                    </div>
                    <div v-else-if="isOwner && p.status === 'pending'" class="callout warn"><Send /><div>Waiting for the Research Coordinator to review.</div></div>

                    <div>
                        <div class="eyebrow">Proponents</div>
                        <div class="flex flex-wrap gap-2" style="margin-top: 6px">
                            <span v-for="name in proponents" :key="name" class="chip" style="height: 28px; gap: 6px; padding-left: 3px">
                                <Avatar :name="name" size="sm" style="width: 22px; height: 22px; font-size: 9.5px" />{{ name }}
                            </span>
                        </div>
                    </div>

                    <div class="facts">
                        <div class="fact"><b>{{ p.academic_year }}</b><span>Academic year</span></div>
                        <div class="fact"><b :title="p.category">{{ p.category }}</b><span>Category</span></div>
                        <div class="fact"><b>{{ p.program }}</b><span>Program</span></div>
                        <div class="fact"><b :title="p.adviser">{{ p.adviser || '—' }}</b><span>Adviser</span></div>
                    </div>
                    <div v-if="p.technology">
                        <div class="eyebrow">Technologies</div>
                        <div class="flex flex-wrap gap-2" style="margin-top: 6px">
                            <span v-for="t in p.technology.split(',')" :key="t" class="chip">{{ t.trim() }}</span>
                        </div>
                    </div>
                    <p v-if="p.description" class="muted" style="font-size: 14px">{{ p.description }}</p>
                    <div>
                        <div class="h-section" style="margin-bottom: 8px">Abstract</div>
                        <div v-if="p.abstract" class="abstract">{{ p.abstract }}</div>
                        <p v-else class="faint small">No abstract yet.</p>
                    </div>
                </div>
            </section>

            <!-- Right: files, citation, review -->
            <div class="fill" style="gap: 16px; display: flex; flex-direction: column">
                <section class="panel fill rise" style="animation-delay: 60ms">
                    <div class="panel-head"><span class="h-section">Files</span><span class="small faint nowrap">{{ p.downloads }} downloads</span></div>
                    <div class="scroll panel-body flex flex-col gap-3">
                        <img v-if="p.cover_path" :src="`/projects/${p.id}/cover`" class="project-cover" alt="Project cover illustration" />

                        <template v-if="canSeeFiles">
                            <div v-for="f in p.files" :key="f.id" class="file-row">
                                <span class="file-icon"><FileText /></span>
                                <div class="grow" style="min-width: 0">
                                    <div class="one-line" style="font-weight: 600; font-size: 13px" :title="f.original_name">{{ f.original_name }}</div>
                                    <div class="tiny faint">{{ fileSize(f.size) }}</div>
                                </div>
                                <button v-if="f.mime_type === 'application/pdf'" type="button" class="icon-btn" title="Preview" aria-label="Preview PDF" @click="preview = f"><Eye /></button>
                                <a :href="`/projects/${p.id}/files/${f.id}`" class="icon-btn" title="Download" aria-label="Download"><Download /></a>
                            </div>
                            <EmptyState v-if="!p.files?.length" :icon="FileText" title="No files uploaded" style="min-height: 110px" />
                        </template>
                        <div v-else class="callout bad">
                            <Lock />
                            <div><b>Access denied.</b> This project has restricted data. Files are available to the Research Coordinator and Administrator only.</div>
                        </div>

                        <a v-if="p.source_url" :href="p.source_url" target="_blank" rel="noopener" class="file-row" style="cursor: pointer">
                            <span class="file-icon"><Github /></span>
                            <div class="grow one-line" style="font-weight: 600; font-size: 13px">Source code repository</div>
                            <ExternalLink style="width: 16px; height: 16px; color: var(--text-faint)" />
                        </a>

                        <div v-if="p.status === 'approved'">
                            <div class="flex items-center justify-between" style="margin-bottom: 6px">
                                <span class="eyebrow">Cite this project (APA)</span>
                                <button type="button" class="btn btn-ghost btn-sm" @click="copyText(citation(p))"><Copy />Copy</button>
                            </div>
                            <div class="citation">{{ citation(p) }}</div>
                        </div>

                        <div v-if="p.reviews?.length">
                            <div class="eyebrow" style="margin-bottom: 4px">Review history</div>
                            <div class="timeline">
                                <div v-for="r in p.reviews" :key="r.id" class="tl-item">
                                    <span class="tl-dot" :class="r.decision"><Check v-if="r.decision === 'approved'" /><Undo2 v-else /></span>
                                    <div style="min-width: 0">
                                        <div class="small"><b>{{ r.decision === 'approved' ? 'Approved' : 'Returned' }}</b> by {{ r.reviewer?.name ?? 'a reviewer' }}</div>
                                        <div class="tiny faint">{{ formatDate(r.created_at, true) }}</div>
                                        <div v-if="r.feedback" class="small muted" style="margin-top: 2px">“{{ r.feedback }}”</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-if="can.review" class="panel rise" style="animation-delay: 120ms; flex: none">
                    <div class="panel-head"><span class="h-section">Review</span></div>
                    <div class="panel-body flex flex-col gap-3">
                        <Field label="Feedback" hint="Required when requesting resubmission" :error="review.errors.feedback">
                            <textarea v-model="review.feedback" class="textarea" rows="2" placeholder="What should the students fix or improve?" />
                        </Field>
                        <label class="checkbox"><input v-model="review.is_restricted" type="checkbox" />Restrict files to coordinator and admin</label>
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-outline" style="flex: 1" :disabled="review.processing" @click="decide('returned')"><Undo2 />Request resubmission</button>
                            <button type="button" class="btn btn-primary" style="flex: 1" :disabled="review.processing" @click="decide('approved')"><Check />Approve</button>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="!!preview" :title="preview?.original_name" size="xwide" @close="preview = null">
            <template #head-actions>
                <a v-if="preview" :href="`/projects/${p.id}/files/${preview.id}`" class="btn btn-outline btn-sm"><Download />Download</a>
            </template>
            <iframe v-if="preview" :src="`/projects/${p.id}/files/${preview.id}/preview`" class="pdf-frame" style="margin: 0 -18px -16px; width: calc(100% + 36px); flex: 1" title="PDF preview" />
        </Modal>
    </AppLayout>
</template>
