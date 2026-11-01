<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
const props = defineProps({ projects: Object, filters: Object });
const form = reactive({ search: props.filters?.search ?? '', program: props.filters?.program ?? '' });
function search() { router.get('/repository', form, { preserveState: true }); }
</script>
<template>
    <Head title="Browse repository" /><AppLayout><div class="mx-auto max-w-7xl p-6 lg:p-10">
        <div><p class="text-sm font-semibold text-olfu">DIGITAL LIBRARY</p><h1 class="mt-1 text-3xl font-bold">Browse repository</h1><p class="mt-2 text-slate-500">Discover published academic work from CCS students.</p></div>
        <form class="mt-8 flex flex-col gap-3 sm:flex-row" @submit.prevent="search"><input v-model="form.search" class="input mt-0 flex-1" placeholder="Search title, keywords, program, technology..." /><input v-model="form.program" class="input mt-0 sm:w-56" placeholder="Program" /><button class="btn-primary">Search</button></form>
        <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3"><article v-for="project in projects.data" :key="project.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between gap-3"><h2 class="font-bold">{{ project.title }}</h2><span v-if="project.featured" class="text-xs font-bold text-olfu">FEATURED</span></div><p class="mt-3 line-clamp-3 text-sm text-slate-500">{{ project.description }}</p><div class="mt-5 border-t border-slate-100 pt-4 text-xs text-slate-500">{{ project.program }} · {{ project.academic_year }}<br />Uploaded by {{ project.owner?.name }}</div><div class="mt-4 flex flex-wrap gap-2"><span v-for="tag in project.tags" :key="tag" class="rounded bg-red-50 px-2 py-1 text-xs text-olfu">{{ tag }}</span></div></article></div>
        <p v-if="!projects.data?.length" class="mt-10 text-center text-slate-500">No published projects match your search.</p>
    </div></AppLayout>
</template>
