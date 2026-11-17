<script setup>
// Students create their own account. Coordinator and admin accounts are made by the admin (Users page).
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserPlus } from 'lucide-vue-next';
import { Field } from '../../Components/Bits';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
function submit() {
    form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
}
</script>

<template>
    <Head title="Create account" />
    <div class="guest">
        <nav class="guest-nav">
            <Link href="/" class="brand">CCS Capstone Repository</Link>
            <Link href="/login" class="btn btn-sm" style="background: #fff; color: #6b0808">Log in</Link>
        </nav>
        <div class="guest-main" style="grid-template-columns: minmax(0, 420px); justify-content: center">
            <form class="auth-card rise" novalidate @submit.prevent="submit">
                <div>
                    <h2>Create a student account</h2>
                    <p class="muted small" style="margin-top: 2px">Coordinator and admin accounts are created by the administrator.</p>
                </div>
                <Field label="Full name" :error="form.errors.name" required>
                    <input v-model="form.name" class="input" autocomplete="name" placeholder="Juan Dela Cruz" />
                </Field>
                <Field label="Email" :error="form.errors.email" required>
                    <input v-model="form.email" class="input" type="email" autocomplete="email" placeholder="juan.delacruz@olfu.edu.ph" />
                </Field>
                <div class="grid gap-3" style="grid-template-columns: 1fr 1fr">
                    <Field label="Password" :error="form.errors.password" hint="At least 8 characters" required>
                        <input v-model="form.password" class="input" type="password" autocomplete="new-password" />
                    </Field>
                    <Field label="Confirm password" required>
                        <input v-model="form.password_confirmation" class="input" type="password" autocomplete="new-password" />
                    </Field>
                </div>
                <button class="btn btn-primary btn-block" style="height: 40px" :disabled="form.processing">
                    <UserPlus />{{ form.processing ? 'Creating…' : 'Create account' }}
                </button>
                <p class="small muted" style="text-align: center">Already registered? <Link href="/login" class="link">Log in</Link></p>
            </form>
        </div>
    </div>
</template>
