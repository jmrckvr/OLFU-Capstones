<script setup>
/*
 * Add / edit project (template page 8). Used by:
 *   students → /submissions/create, /submissions/{id}/edit   ("Save as draft" / "Submit for review")
 *   admin    → /admin/projects/create, /admin/projects/{id}/edit ("Save as draft" / "Publish", plus "Restricted")
 * The form scrolls inside its panel; the buttons stay visible at the bottom.
 */
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { FileText, ImagePlus, Lock, Save, Send, Trash2, Undo2, UploadCloud, X } from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Field, StatusPill } from '../../Components/Bits';
import { fileSize, toast } from '../../lib/ui';

const props = defineProps({ project: Object, options: Object, mode: String });
const isAdmin = props.mode === 'admin';
const editing = !!props.project;
const p = props.project ?? {};

const form = useForm({
    intent: 'draft',
    title: p.title ?? '',
    proponents: p.proponents ?? '',
    program: p.program ?? props.options.programs[0],
    academic_year: p.academic_year ?? props.options.academic_years[0],
    category: p.category ?? props.options.categories[0],
    adviser: p.adviser ?? '',
    technology: p.technology ?? '',
    description: p.description ?? '',
    abstract: p.abstract ?? '',
    source_url: p.source_url ?? '',
    is_restricted: p.is_restricted ?? false,
    documents: [],
    cover: null,
    remove_files: [],
});

const existing = computed(() => (p.files ?? []).filter((f) => !form.remove_files.includes(f.id)));
const removed = computed(() => (p.files ?? []).filter((f) => form.remove_files.includes(f.id)));
const docError = computed(() => Object.entries(form.errors).find(([k]) => k.startsWith('documents'))?.[1]);

// Picking files: skip duplicates (same name and size) and anything over 50 MB.
const dragging = ref(false);
const picker = ref(null);
function addFiles(list) {
    for (const file of list) {
        const dupe = form.documents.some((f) => f.name === file.name && f.size === file.size)
            || existing.value.some((f) => f.original_name === file.name && f.size === file.size);
        if (dupe) { toast(`“${file.name}” is already attached.`, 'info'); continue; }
        if (file.size > 50 * 1024 * 1024) { toast(`“${file.name}” is larger than 50 MB.`, 'error'); continue; }
        form.documents.push(file);
    }
    form.clearErrors('documents');
}
function onDrop(e) { dragging.value = false; addFiles(e.dataTransfer.files); }

const coverPreview = ref(editing && p.cover_path ? `/projects/${p.id}/cover` : null);
function pickCover(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.cover = file;
    coverPreview.value = URL.createObjectURL(file);
}

const submitUrl = isAdmin
    ? (editing ? `/admin/projects/${p.id}` : '/admin/projects')
    : (editing ? `/submissions/${p.id}` : '/submissions');

function save(intent) {
    form.intent = intent;
    form.transform((d) => (editing ? { ...d, _method: 'put' } : d)).post(submitUrl, {
        forceFormData: true,
        preserveScroll: true,
        onError: () => toast('Please fix the highlighted fields.', 'error'),
    });
}

const title = editing ? `Edit: ${p.title}` : 'Add project';
const crumbs = isAdmin ? [['Projects', '/admin/projects']] : [['My Submissions', '/submissions']];
const cancelUrl = isAdmin ? '/admin/projects' : '/submissions';
// Admin editing a non-draft project: one "Save changes" button that checks all required fields.
const adminSaveOnly = isAdmin && editing && p.status !== 'draft';
</script>

<template>
    <AppLayout :title="title" :crumbs="crumbs">
        <template #actions>
            <Link :href="cancelUrl" class="btn btn-ghost hide-phone">Cancel</Link>
        </template>

        <form class="panel fill rise" novalidate @submit.prevent>
            <div class="panel-head">
                <span class="h-section">{{ editing ? 'Project details' : 'New project' }}</span>
                <StatusPill v-if="editing" :status="p.status" />
            </div>
            <div class="scroll panel-body">
                <div v-if="editing && p.status === 'returned' && p.feedback" class="callout bad" style="margin-bottom: 16px">
                    <Undo2 /><div><b>Coordinator feedback:</b> {{ p.feedback }}</div>
                </div>
                <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr))">
                    <Field label="Project title" :error="form.errors.title" required>
                        <input v-model="form.title" class="input" maxlength="255" placeholder="Library Book Reservation System" />
                    </Field>
                    <Field label="Proponents" :error="form.errors.proponents" hint="Separate names with commas" required>
                        <input v-model="form.proponents" class="input" placeholder="Juan Dela Cruz, Ana Reyes" />
                    </Field>
                    <div class="grid gap-4" style="grid-template-columns: 1fr 1fr">
                        <Field label="Academic year" :error="form.errors.academic_year" required>
                            <select v-model="form.academic_year" class="select"><option v-for="y in options.academic_years" :key="y">{{ y }}</option></select>
                        </Field>
                        <Field label="Program" :error="form.errors.program" required>
                            <select v-model="form.program" class="select"><option v-for="x in options.programs" :key="x">{{ x }}</option></select>
                        </Field>
                    </div>
                    <Field label="Category" :error="form.errors.category" required>
                        <select v-model="form.category" class="select"><option v-for="c in options.categories" :key="c">{{ c }}</option></select>
                    </Field>
                    <Field label="Adviser" :error="form.errors.adviser" hint="Needed to submit">
                        <input v-model="form.adviser" class="input" placeholder="Prof. D. Navarro" />
                    </Field>
                    <Field label="Technologies" :error="form.errors.technology" hint="Comma separated">
                        <input v-model="form.technology" class="input" placeholder="PHP, MySQL, Bootstrap" />
                    </Field>
                    <Field label="Short description" :error="form.errors.description" :hint="`${form.description.length}/500`" style="grid-column: 1 / -1">
                        <input v-model="form.description" class="input" maxlength="500" placeholder="One sentence: what does the system do?" />
                    </Field>
                    <Field label="Abstract" :error="form.errors.abstract" hint="Needed to submit" style="grid-column: 1 / -1">
                        <textarea v-model="form.abstract" class="textarea" rows="5" placeholder="This study presents…" />
                    </Field>
                    <Field label="Source code link" :error="form.errors.source_url" hint="GitHub or Drive link, when applicable" style="grid-column: 1 / -1">
                        <input v-model="form.source_url" class="input" type="url" placeholder="https://github.com/…" />
                    </Field>

                    <!-- Documents -->
                    <div class="field" :class="{ 'has-error': docError }" style="grid-column: 1 / -1">
                        <span class="field-label">Documents and presentation</span>
                        <div class="dropzone" :class="{ over: dragging }" role="button" tabindex="0"
                            @click="picker.click()" @keydown.enter.prevent="picker.click()"
                            @dragover.prevent="dragging = true" @dragleave="dragging = false" @drop.prevent="onDrop">
                            <span class="file-icon" style="width: 40px; height: 40px"><UploadCloud /></span>
                            <div style="min-width: 0">
                                <div style="font-weight: 600" class="one-line">Drop files here or click to choose</div>
                                <div class="field-hint">PDF, Word, PowerPoint or ZIP · up to 50 MB each</div>
                            </div>
                            <input ref="picker" type="file" multiple hidden accept=".pdf,.doc,.docx,.ppt,.pptx,.zip" @change="addFiles($event.target.files); $event.target.value = ''" />
                        </div>
                        <span v-if="docError" class="error-text">{{ docError }}</span>
                        <TransitionGroup tag="div" name="list" class="flex flex-col gap-2" style="margin-top: 4px">
                            <div v-for="f in existing" :key="'e' + f.id" class="file-row">
                                <span class="file-icon"><FileText /></span>
                                <div class="grow" style="min-width: 0"><div class="one-line small" style="font-weight: 600">{{ f.original_name }}</div><div class="tiny faint">{{ fileSize(f.size) }} · uploaded</div></div>
                                <button type="button" class="icon-btn" title="Remove" aria-label="Remove file" @click="form.remove_files.push(f.id)"><Trash2 /></button>
                            </div>
                            <div v-for="(f, i) in form.documents" :key="'n' + f.name + f.size" class="file-row">
                                <span class="file-icon"><FileText /></span>
                                <div class="grow" style="min-width: 0"><div class="one-line small" style="font-weight: 600">{{ f.name }}</div><div class="tiny faint">{{ fileSize(f.size) }} · new</div></div>
                                <button type="button" class="icon-btn" title="Remove" aria-label="Remove file" @click="form.documents.splice(i, 1)"><X /></button>
                            </div>
                            <div v-for="f in removed" :key="'r' + f.id" class="file-row" style="opacity: .6">
                                <span class="file-icon"><Trash2 /></span>
                                <div class="grow one-line small"><s>{{ f.original_name }}</s> · will be removed</div>
                                <button type="button" class="btn btn-ghost btn-sm" @click="form.remove_files = form.remove_files.filter((id) => id !== f.id)">Undo</button>
                            </div>
                        </TransitionGroup>
                    </div>

                    <!-- Cover -->
                    <div class="field" :class="{ 'has-error': form.errors.cover }">
                        <span class="field-label">Cover image (optional)</span>
                        <label class="dropzone" style="padding: 10px">
                            <img v-if="coverPreview" :src="coverPreview" alt="" style="width: 72px; height: 48px; object-fit: cover; border-radius: 6px" />
                            <span v-else class="file-icon" style="width: 40px; height: 40px"><ImagePlus /></span>
                            <span class="small one-line" style="font-weight: 600">{{ coverPreview ? 'Change image' : 'Choose an image' }}</span>
                            <input type="file" accept="image/png,image/jpeg,image/webp" hidden @change="pickCover" />
                        </label>
                        <span v-if="form.errors.cover" class="error-text">{{ form.errors.cover }}</span>
                    </div>

                    <div v-if="isAdmin" class="field">
                        <span class="field-label">Access</span>
                        <div class="file-row" style="height: 68px">
                            <span class="file-icon"><Lock /></span>
                            <div class="grow" style="min-width: 0"><div class="small" style="font-weight: 600">Restricted files</div><div class="tiny faint one-line">Only coordinator and admin can open files</div></div>
                            <button type="button" class="switch" role="switch" :aria-checked="form.is_restricted" aria-label="Restricted files" @click="form.is_restricted = !form.is_restricted" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-foot" style="justify-content: flex-end">
                <span v-if="form.progress" class="small muted grow">Uploading… {{ form.progress.percentage }}%</span>
                <template v-if="adminSaveOnly">
                    <button type="button" class="btn btn-primary" :disabled="form.processing" @click="save('submit')"><Save />Save changes</button>
                </template>
                <template v-else>
                    <button type="button" class="btn btn-outline" :disabled="form.processing" @click="save('draft')"><Save />Save as draft</button>
                    <button type="button" class="btn btn-primary" :disabled="form.processing" @click="save('submit')">
                        <Send />{{ isAdmin ? 'Publish to repository' : (p.status === 'returned' ? 'Resubmit for review' : 'Submit for review') }}
                    </button>
                </template>
            </div>
        </form>
    </AppLayout>
</template>
