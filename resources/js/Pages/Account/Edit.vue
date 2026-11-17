<script setup>
/*
 * My Account (template page 16): change your name and password, see your role,
 * and set the look (dark mode, sidebar). Role and status are read-only here on purpose.
 */
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { KeyRound, Moon, PanelLeftClose, Save } from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import { Avatar, Field, StatusPill } from '../../Components/Bits';
import { prefs, setCollapsed, setDark } from '../../lib/ui';

const props = defineProps({ roleLabel: String, memberSince: String, projectCount: Number });
const user = computed(() => usePage().props.auth.user);

const profile = useForm({ name: user.value.name });
function saveProfile() { profile.put('/account', { preserveScroll: true }); }

const pw = useForm({ current_password: '', password: '', password_confirmation: '' });
function savePassword() { pw.put('/account/password', { preserveScroll: true, onSuccess: () => pw.reset(), onError: () => pw.reset('current_password') }); }
</script>

<template>
    <AppLayout title="My Account">
        <div class="row-fill" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); align-content: start">
            <div class="flex flex-col gap-4" style="min-width: 0">
                <section class="panel rise">
                    <div class="panel-body flex items-center gap-4">
                        <Avatar :name="user.name" size="lg" />
                        <div style="min-width: 0">
                            <div class="h-title one-line">{{ user.name }}</div>
                            <div class="small muted one-line">{{ user.email }}</div>
                            <div class="flex items-center gap-2" style="margin-top: 6px">
                                <span class="chip">{{ roleLabel }}</span><StatusPill status="active" label="Active" />
                            </div>
                        </div>
                    </div>
                    <div class="panel-foot small muted">
                        <span class="one-line">Member since {{ memberSince }}</span><span class="grow" />
                        <span v-if="user.role === 'student'" class="nowrap">{{ projectCount }} submissions</span>
                    </div>
                </section>

                <form class="panel rise" style="animation-delay: 60ms" @submit.prevent="saveProfile">
                    <div class="panel-head"><span class="h-section">Profile</span></div>
                    <div class="panel-body grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))">
                        <Field label="Name" :error="profile.errors.name" required><input v-model="profile.name" class="input" autocomplete="name" /></Field>
                        <Field label="Role" hint="Only an administrator can change roles"><input class="input" :value="roleLabel" readonly /></Field>
                    </div>
                    <div class="panel-foot" style="justify-content: flex-end">
                        <button class="btn btn-primary" :disabled="profile.processing || !profile.isDirty"><Save />Save changes</button>
                    </div>
                </form>
            </div>

            <div class="flex flex-col gap-4" style="min-width: 0">
                <form class="panel rise" style="animation-delay: 90ms" @submit.prevent="savePassword">
                    <div class="panel-head"><span class="h-section">Change password</span></div>
                    <div class="panel-body grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr))">
                        <Field label="Current password" :error="pw.errors.current_password" style="grid-column: 1 / -1">
                            <input v-model="pw.current_password" class="input" type="password" autocomplete="current-password" />
                        </Field>
                        <Field label="New password" :error="pw.errors.password" hint="At least 8 characters">
                            <input v-model="pw.password" class="input" type="password" autocomplete="new-password" />
                        </Field>
                        <Field label="Confirm new password"><input v-model="pw.password_confirmation" class="input" type="password" autocomplete="new-password" /></Field>
                    </div>
                    <div class="panel-foot" style="justify-content: flex-end">
                        <button class="btn btn-primary" :disabled="pw.processing || !pw.current_password || !pw.password"><KeyRound />Change password</button>
                    </div>
                </form>

                <section class="panel rise" style="animation-delay: 120ms">
                    <div class="panel-head"><span class="h-section">Appearance</span><span class="tiny faint">Saved on this device</span></div>
                    <div class="list-row">
                        <span class="file-icon"><Moon /></span>
                        <div class="grow"><div class="title">Dark mode</div><div class="sub">Easier on the eyes at night</div></div>
                        <button type="button" class="switch" role="switch" :aria-checked="prefs.dark" aria-label="Dark mode" @click="setDark(!prefs.dark)" />
                    </div>
                    <div class="list-row hide-phone">
                        <span class="file-icon"><PanelLeftClose /></span>
                        <div class="grow"><div class="title">Compact sidebar</div><div class="sub">Show icons only</div></div>
                        <button type="button" class="switch" role="switch" :aria-checked="prefs.collapsed" aria-label="Compact sidebar" @click="setCollapsed(!prefs.collapsed)" />
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
