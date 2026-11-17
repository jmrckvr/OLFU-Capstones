<script setup>
/*
 * Public home page with the login card (template pages 1–2, combined into one screen).
 * The hero only states what the site is for; signing in happens in the card beside it.
 */
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Eye, EyeOff, LockKeyhole, Megaphone, Search, ShieldCheck, FileStack, BellRing } from 'lucide-vue-next';
import Modal from '../Components/Modal.vue';
import ToastHost from '../Components/ToastHost.vue';
import { Field } from '../Components/Bits';

const props = defineProps({ stats: Object, announcement: String, demoAccounts: Array });

const form = useForm({ email: '', password: '', remember: false });
const showPassword = ref(false);
const aboutOpen = ref(false);
const emailInput = ref(null);

function submit() {
    form.post('/login', { onFinish: () => form.reset('password') });
}
function useDemo(account) {
    form.email = account.email;
    form.password = 'password';
    form.clearErrors();
}

const steps = [
    ['Submit', 'Students upload documents, slides and a source link.'],
    ['Review', 'The Research Coordinator checks the files are complete.'],
    ['Approve or revise', 'Complete work joins the repository; others get feedback.'],
    ['Browse and access', 'Search, filter and open the files you may view.'],
];
const features = [
    [Search, 'Easy discovery', 'Search and filter by year, member, category and technology.'],
    [FileStack, 'Full documentation', 'Documents, presentations and source code in one record.'],
    [ShieldCheck, 'Controlled access', 'Role-based permissions, including restricted projects.'],
    [BellRing, 'Submission tracking', 'Status, history and notifications from draft to approval.'],
];
</script>

<template>
    <Head title="Welcome" />
    <div class="guest">
        <!-- Background hills, like the template art -->
        <svg class="guest-hills" viewBox="0 0 1440 400" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 140 C 240 40 420 60 640 150 S 1100 120 1440 60 L1440 400 L0 400 Z" fill="rgba(0,0,0,.14)" />
            <path d="M0 230 C 300 150 520 190 760 250 S 1200 200 1440 170 L1440 400 L0 400 Z" fill="rgba(0,0,0,.16)" />
        </svg>

        <nav class="guest-nav">
            <span class="brand">CCS Capstone Repository</span>
            <a href="#steps" class="navlink" @click.prevent="emailInput?.focus()">Repository</a>
            <a href="#steps" class="navlink">How it works</a>
            <a href="#about" class="navlink" @click.prevent="aboutOpen = true">About</a>
            <Link href="/register" class="btn btn-sm" style="background: #fff; color: #6b0808">Create account</Link>
        </nav>

        <div class="guest-main">
            <section class="hero rise">
                <h1>Explore CCS capstone projects</h1>
                <p class="lead">
                    One organized home for the approved capstone projects of the College of Computer Studies — documentation,
                    presentations and source code, kept in one place for students, advisers and future researchers.
                </p>
            </section>

            <form class="auth-card rise" style="animation-delay: 60ms" novalidate @submit.prevent="submit">
                <div>
                    <h2>Log in</h2>
                    <p class="muted small" style="margin-top: 2px">Use your OLFU account to continue.</p>
                </div>
                <Field label="Email" :error="form.errors.email">
                    <input ref="emailInput" v-model="form.email" class="input" type="email" autocomplete="username" placeholder="juan.delacruz@olfu.edu.ph" />
                </Field>
                <Field label="Password" :error="form.errors.password">
                    <div style="position: relative">
                        <input v-model="form.password" class="input" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" placeholder="••••••••" style="padding-right: 40px" />
                        <button type="button" class="icon-btn" style="position: absolute; right: 2px; top: 1px; width: 34px; height: 34px"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'" @click="showPassword = !showPassword">
                            <EyeOff v-if="showPassword" /><Eye v-else />
                        </button>
                    </div>
                </Field>
                <div class="flex items-center justify-between gap-2">
                    <label class="checkbox"><input v-model="form.remember" type="checkbox" />Remember me</label>
                    <Link href="/register" class="link small">Create an account</Link>
                </div>
                <button class="btn btn-primary btn-block" style="height: 40px" :disabled="form.processing">
                    <LockKeyhole />{{ form.processing ? 'Signing in…' : 'Log in' }}
                </button>
                <template v-if="demoAccounts.length">
                    <div class="tiny faint">Demo accounts (password: <b>password</b>)</div>
                    <div class="demo-row">
                        <button v-for="a in demoAccounts" :key="a.email" type="button" class="btn btn-outline btn-sm" @click="useDemo(a)">{{ a.label }}</button>
                    </div>
                </template>
            </form>
        </div>

        <div id="steps" class="guest-steps stagger">
            <div v-for="([t, d], i) in steps" :key="t" class="step" :style="{ '--i': i + 2 }">
                <span class="n">{{ i + 1 }}</span>
                <div style="min-width: 0"><b>{{ t }}</b><span>{{ d }}</span></div>
            </div>
        </div>
        <div v-if="announcement" class="guest-announce"><Megaphone /><span class="one-line">{{ announcement }}</span></div>
    </div>

    <Modal :show="aboutOpen" title="About the repository" size="wide" @close="aboutOpen = false">
        <p class="muted" style="font-size: 13.5px">
            A central home for approved capstone projects of the College of Computer Studies, Our Lady of Fatima University – Quezon City,
            replacing scattered folders with one organized, searchable collection.
        </p>
        <div class="grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))">
            <div v-for="[icon, t, d] in features" :key="t" class="panel panel-body" style="box-shadow: none">
                <component :is="icon" style="width: 20px; height: 20px; color: var(--brand-text)" />
                <div class="h-section" style="margin-top: 8px">{{ t }}</div>
                <p class="muted small" style="margin-top: 2px">{{ d }}</p>
            </div>
        </div>
    </Modal>
    <ToastHost />
</template>
