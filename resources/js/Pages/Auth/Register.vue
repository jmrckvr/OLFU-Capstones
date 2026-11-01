<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
function submit() { form.post('/register'); }
</script>
<template>
    <Head title="Create account" />
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-6">
        <form class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl" @submit.prevent="submit">
            <p class="text-sm font-bold uppercase tracking-widest text-olfu">OLFU CCS</p><h1 class="mt-2 text-3xl font-bold">Create your account</h1>
            <label class="mt-6 block text-sm font-semibold">Full name<input v-model="form.name" class="input" required /></label>
            <label class="mt-4 block text-sm font-semibold">Email<input v-model="form.email" type="email" class="input" required /></label>
            <label class="mt-4 block text-sm font-semibold">Password<input v-model="form.password" type="password" class="input" required /></label>
            <label class="mt-4 block text-sm font-semibold">Confirm password<input v-model="form.password_confirmation" type="password" class="input" required /></label>
            <button class="btn-primary mt-6 w-full">Create student account</button>
            <p class="mt-6 text-center text-sm text-slate-500">Already registered? <Link href="/login" class="font-semibold text-olfu">Sign in</Link></p>
        </form>
    </div>
</template>
