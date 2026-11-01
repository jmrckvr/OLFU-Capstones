<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
defineProps({ role: String, stats: Object, recentProjects: Array, myProjects: Array });
const labels = { pending: 'Pending Moderation', published: 'Published', returned: 'Returned for Correction', hidden: 'Hidden' };
</script>
<template>
    <Head title="Dashboard" /><AppLayout>
        <div class="mx-auto max-w-7xl p-6 lg:p-10">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-semibold text-olfu">OLFU COLLEGE OF COMPUTER STUDIES</p><h1 class="mt-1 text-3xl font-bold">Repository overview</h1><p class="mt-2 text-slate-500">A single home for the CCS community's academic work.</p></div><Link v-if="role === 'student'" href="/upload" class="btn-primary">Upload academic work</Link></div>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="card in [{label:'Total projects',value:stats.projects},{label:'Published works',value:stats.published},{label:'Pending moderation',value:stats.pending},{label:'Registered users',value:stats.users}]" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">{{ card.label }}</p><p class="mt-2 text-3xl font-bold">{{ card.value }}</p></div>
            </div>
            <section class="mt-10"><div class="flex items-center justify-between"><h2 class="text-xl font-bold">{{ role === 'student' ? 'My recent uploads' : 'Recent published projects' }}</h2><Link href="/repository" class="text-sm font-semibold text-olfu">Browse repository →</Link></div>
                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <article v-for="project in (role === 'student' ? myProjects : recentProjects)" :key="project.id" class="rounded-xl border border-slate-200 bg-white p-5"><div class="flex items-start justify-between gap-3"><h3 class="font-bold">{{ project.title }}</h3><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold">{{ labels[project.status] }}</span></div><p class="mt-2 line-clamp-2 text-sm text-slate-500">{{ project.description }}</p><p class="mt-4 text-xs text-slate-400">{{ project.program }} · {{ project.academic_year }}</p></article>
                    <div v-if="!(role === 'student' ? myProjects : recentProjects)?.length" class="rounded-xl border border-dashed border-slate-300 p-8 text-sm text-slate-500">No projects yet.</div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
