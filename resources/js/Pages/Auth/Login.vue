<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
const form = useForm({ email: '', password: '', remember: false });
function submit() { form.post('/login'); }
</script>
<template>
    <Head title="Sign in" />
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-6">
        <form class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl" @submit.prevent="submit">
            <div class="mb-8"><p class="text-sm font-bold uppercase tracking-widest text-olfu">OLFU CCS</p><h1 class="mt-2 text-3xl font-bold">Welcome back</h1><p class="mt-2 text-slate-500">Sign in to your academic repository.</p></div>
            <label class="text-sm font-semibold">Email<input v-model="form.email" type="email" class="input" required /></label>
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            <label class="mt-4 block text-sm font-semibold">Password<input v-model="form.password" type="password" class="input" required /></label>
            <button class="btn-primary mt-6 w-full" :disabled="form.processing">Sign in</button>
            <p class="mt-6 text-center text-sm text-slate-500">New to the repository? <Link href="/register" class="font-semibold text-olfu">Create an account</Link></p>
        </form>
    </div>
</template>
