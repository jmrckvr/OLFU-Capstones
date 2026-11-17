<script setup>
/*
 * Admin → Users (template page 14).
 * Add user (dialog) · change role (dialog) · activate/deactivate (in-page confirm) · reset password.
 * You can't deactivate or demote yourself: the buttons are disabled here AND the server refuses it.
 */
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { KeyRound, Power, ShieldCheck, UserPlus, Users } from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import Modal from '../../Components/Modal.vue';
import SearchField from '../../Components/SearchField.vue';
import { Avatar, EmptyState, Field, PillSwitch, StatusPill } from '../../Components/Bits';
import { ROLE, confirmDialog, formatDate } from '../../lib/ui';

const props = defineProps({ users: Array, roles: Object });
const me = computed(() => usePage().props.auth.user);

const search = ref('');
const role = ref('');
const rows = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.users.filter((u) => (!role.value || u.role === role.value) && (!q || `${u.name} ${u.email}`.toLowerCase().includes(q)));
});
const pills = computed(() => [
    { value: '', label: 'All', count: props.users.length },
    ...Object.entries(props.roles).map(([value, label]) => ({
        value, label: value === 'coordinator' ? 'Coordinators' : label + 's', count: props.users.filter((u) => u.role === value).length,
    })),
]);
const columns = [
    { key: 'name', label: 'User', sortable: true },
    { key: 'role', label: 'Role', sortable: true, width: '190px', sortBy: (u) => ROLE[u.role] },
    { key: 'is_active', label: 'Status', sortable: true, width: '120px', sortBy: (u) => (u.is_active ? 0 : 1) },
    { key: 'projects_count', label: 'Projects', sortable: true, width: '96px', align: 'right' },
    { key: 'actions', label: '', width: '320px', align: 'right' },
];

// Add user
const adding = ref(false);
const addForm = useForm({ name: '', email: '', role: 'student' });
function addUser() {
    addForm.post('/admin/users', { preserveScroll: true, onSuccess: () => { adding.value = false; addForm.reset(); } });
}

// Change role
const editing = ref(null);
const roleForm = useForm({ role: '' });
function openRole(u) { editing.value = u; roleForm.role = u.role; roleForm.clearErrors(); }
function saveRole() {
    roleForm.patch(`/admin/users/${editing.value.id}`, { preserveScroll: true, onSuccess: () => { editing.value = null; } });
}

async function toggleActive(u) {
    const turningOff = u.is_active;
    const ok = await confirmDialog({
        title: turningOff ? `Deactivate ${u.name}?` : `Activate ${u.name}?`,
        message: turningOff ? 'They will be signed out and cannot log in until you activate them again.' : 'They will be able to log in again.',
        confirmText: turningOff ? 'Deactivate' : 'Activate', danger: turningOff,
    });
    if (ok) router.patch(`/admin/users/${u.id}`, { is_active: !u.is_active }, { preserveScroll: true });
}
async function resetPassword(u) {
    const ok = await confirmDialog({
        title: `Reset password for ${u.name}?`,
        message: 'A new temporary password will be shown once. Their current password stops working and they are signed out.',
        confirmText: 'Reset password',
    });
    if (ok) router.post(`/admin/users/${u.id}/reset-password`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Users" :crumbs="[['Administration', '/admin/projects']]">
        <template #actions>
            <button type="button" class="btn btn-primary" @click="adding = true"><UserPlus /><span class="btn-label">Add user</span></button>
        </template>

        <div class="toolbar">
            <SearchField v-model="search" placeholder="Search name or email" />
            <PillSwitch v-model="role" :options="pills" />
        </div>

        <section class="panel fill">
            <DataTable :columns="columns" :rows="rows" noun="users" :default-sort="{ key: 'name', dir: 'asc' }">
                <template #cell-name="{ row }">
                    <div class="flex items-center gap-3" style="min-width: 0">
                        <Avatar :name="row.name" />
                        <div style="min-width: 0">
                            <div class="cell-title">{{ row.name }}<span v-if="row.id === me.id" class="chip" style="margin-left: 6px; height: 18px">You</span></div>
                            <div class="cell-sub">{{ row.email }}</div>
                        </div>
                    </div>
                </template>
                <template #cell-role="{ row }"><span class="one-line" style="display: block">{{ roles[row.role] }}</span></template>
                <template #cell-is_active="{ row }"><StatusPill :status="row.is_active ? 'active' : 'inactive'" :label="row.is_active ? 'Active' : 'Inactive'" /></template>
                <template #cell-projects_count="{ row }"><span class="tabular">{{ row.projects_count }}</span></template>
                <template #cell-actions="{ row }">
                    <div class="actions">
                        <button type="button" class="btn btn-ghost btn-sm" :disabled="row.id === me.id" :title="row.id === me.id ? 'You cannot change your own role' : 'Change role'" @click="openRole(row)"><ShieldCheck />Role</button>
                        <button type="button" class="btn btn-ghost btn-sm" @click="resetPassword(row)"><KeyRound />Reset</button>
                        <button type="button" class="btn btn-sm" style="min-width: 112px" :class="row.is_active ? 'btn-danger-outline' : 'btn-outline'" :disabled="row.id === me.id"
                            :title="row.id === me.id ? 'You cannot deactivate yourself' : undefined" @click="toggleActive(row)">
                            <Power />{{ row.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </div>
                </template>
                <template #empty><EmptyState :icon="Users" title="No users match" text="Try a different search or role." /></template>
            </DataTable>
        </section>

        <Modal :show="adding" title="Add user" @close="adding = false">
            <Field label="Full name" :error="addForm.errors.name" required><input v-model="addForm.name" class="input" placeholder="Prof. Maria Santos" /></Field>
            <Field label="Email" :error="addForm.errors.email" required><input v-model="addForm.email" class="input" type="email" placeholder="name@olfu.edu.ph" /></Field>
            <Field label="Role" :error="addForm.errors.role" required>
                <select v-model="addForm.role" class="select"><option v-for="(label, value) in roles" :key="value" :value="value">{{ label }}</option></select>
            </Field>
            <p class="small faint">A temporary password is created and shown once after saving.</p>
            <template #footer>
                <button type="button" class="btn btn-ghost" @click="adding = false">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="addForm.processing" @click="addUser"><UserPlus />Create account</button>
            </template>
        </Modal>

        <Modal :show="!!editing" :title="`Role for ${editing?.name ?? ''}`" @close="editing = null">
            <div class="flex flex-col gap-2">
                <label v-for="(label, value) in roles" :key="value" class="file-row" style="cursor: pointer">
                    <input v-model="roleForm.role" type="radio" :value="value" style="accent-color: var(--brand)" />
                    <span class="grow" style="font-weight: 600">{{ label }}</span>
                </label>
            </div>
            <p v-if="roleForm.errors.role" class="error-text">{{ roleForm.errors.role }}</p>
            <p class="small faint">Member since {{ formatDate(editing?.created_at) }}.</p>
            <template #footer>
                <button type="button" class="btn btn-ghost" @click="editing = null">Cancel</button>
                <button type="button" class="btn btn-primary" :disabled="roleForm.processing || roleForm.role === editing?.role" @click="saveRole">Save role</button>
            </template>
        </Modal>
    </AppLayout>
</template>
