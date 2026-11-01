<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const role = computed(() => user.value?.role);
const nav = computed(() => {
    if (role.value === 'student') return [
        ['Dashboard', '/dashboard'], ['My Uploads', '/dashboard'], ['Upload Academic Work', '/upload'],
        ['Browse Repository', '/repository'],
    ];
    if (role.value === 'coordinator') return [
        ['Repository Overview', '/dashboard'], ['Pending Moderation', '/moderation'],
        ['Published Projects', '/repository'], ['Moderation History', '/moderation'],
    ];
    return [['System Analytics', '/dashboard'], ['User Management', '/dashboard'], ['Total Projects and Uploads', '/repository'], ['Activity Logs', '/dashboard']];
});
const roleName = computed(() => ({ student: 'Student', coordinator: 'Research Coordinator', administrator: 'Administrator' }[role.value] ?? 'Member'));
function logout() { router.post('/logout'); }
</script>

<template>
    <div class="min-h-screen md:flex">
        <aside class="w-full border-b border-slate-200 bg-white md:min-h-screen md:w-72 md:border-b-0 md:border-r">
            <div class="flex items-center gap-3 border-b border-slate-100 px-6 py-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-olfu font-black text-white">O</div>
                <div><p class="font-bold text-slate-900">OLFU CCS</p><p class="text-xs text-slate-500">Academic Repository</p></div>
            </div>
            <div class="px-4 py-5">
                <p class="mb-3 px-3 text-xs font-bold uppercase tracking-wider text-slate-400">{{ roleName }}</p>
                <nav class="space-y-1">
                    <Link v-for="item in nav" :key="item[0]" :href="item[1]" class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-red-50 hover:text-olfu">{{ item[0] }}</Link>
                </nav>
            </div>
            <div class="border-t border-slate-100 px-6 py-5 md:absolute md:bottom-0 md:w-72">
                <p class="truncate text-sm font-semibold">{{ user?.name }}</p><p class="truncate text-xs text-slate-500">{{ user?.email }}</p>
                <button class="mt-4 text-sm font-semibold text-olfu" @click="logout">Sign out</button>
            </div>
        </aside>
        <main class="flex-1"><slot /></main>
    </div>
</template>
