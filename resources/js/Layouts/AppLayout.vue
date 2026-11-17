<script setup>
/*
 * The frame around every signed-in page.
 *   <AppLayout title="Repository" :crumbs="[['Dashboard', '/dashboard']]">
 *       <template #actions> buttons shown in the top bar </template>
 *       page content
 *   </AppLayout>
 *
 * Sidebar: collapses to icons only (remembered). Only its WIDTH changes; icons stay put.
 * Top bar: breadcrumb + page title on the left, page actions + dark mode + bell on the right.
 */
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import {
    Activity, Bell, ChevronRight, ClipboardCheck, FilePlus2, FolderKanban, LayoutDashboard, LibraryBig,
    LogOut, Menu, Moon, PanelLeftClose, PanelLeftOpen, Sun, UserRound, Users,
} from 'lucide-vue-next';
import ConfirmHost from '../Components/ConfirmHost.vue';
import PasswordNotice from '../Components/PasswordNotice.vue';
import ToastHost from '../Components/ToastHost.vue';
import { Avatar } from '../Components/Bits';
import { ROLE, prefs, setCollapsed, setDark } from '../lib/ui';

const props = defineProps({
    title: String,
    crumbs: { type: Array, default: () => [] }, // [['Label', '/url'], ...] shown before the title
    active: String, // force which menu item is highlighted (e.g. a project opened from the Review Queue)
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role);

const nav = computed(() => {
    const items = [
        { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        { label: 'Repository', href: '/repository', icon: LibraryBig, also: ['/projects/'] },
    ];
    if (role.value === 'student') items.push({ label: 'My Submissions', href: '/submissions', icon: FilePlus2 });
    if (role.value !== 'student') items.push({ label: 'Review Queue', href: '/reviews', icon: ClipboardCheck, badge: page.props.pendingCount });
    items.push({ label: 'Notifications', href: '/notifications', icon: Bell, badge: page.props.unread });
    items.push({ label: 'My Account', href: '/account', icon: UserRound });
    return items;
});
const adminNav = [
    { label: 'Projects', href: '/admin/projects', icon: FolderKanban },
    { label: 'Users', href: '/admin/users', icon: Users },
    { label: 'Activity Log', href: '/admin/activity', icon: Activity },
];

const path = computed(() => page.url.split('?')[0]);
function isActive(item) {
    if (props.active) return item.href === props.active;
    return path.value === item.href || path.value.startsWith(item.href + '/') || (item.also ?? []).some((p) => path.value.startsWith(p));
}

// Phone: the sidebar slides in over the page.
const navOpen = ref(false);
const stop = router.on('navigate', () => { navOpen.value = false; });
onBeforeUnmount(stop);

function logout() { router.post('/logout'); }
</script>

<template>
    <Head :title="title" />
    <div class="shell" :class="{ collapsed: prefs.collapsed, 'nav-open': navOpen }">
        <aside class="sidebar" aria-label="Main menu">
            <Link href="/dashboard" class="sb-brand">
                <span class="sb-logo">CCS</span>
                <span class="sb-brand-text"><b>Capstone Repository</b><span>College of Computer Studies</span></span>
            </Link>

            <nav class="sb-nav">
                <Link v-for="item in nav" :key="item.href" :href="item.href" class="sb-item" :class="{ active: isActive(item) }"
                    :title="prefs.collapsed ? item.label : undefined" :aria-current="isActive(item) ? 'page' : undefined">
                    <component :is="item.icon" />
                    <span class="sb-label">{{ item.label }}</span>
                    <template v-if="item.badge">
                        <span class="sb-badge">{{ item.badge > 99 ? '99+' : item.badge }}</span>
                        <span class="sb-dot" />
                    </template>
                </Link>
                <template v-if="role === 'administrator'">
                    <div class="sb-section">Administration</div>
                    <Link v-for="item in adminNav" :key="item.href" :href="item.href" class="sb-item" :class="{ active: isActive(item) }"
                        :title="prefs.collapsed ? item.label : undefined" :aria-current="isActive(item) ? 'page' : undefined">
                        <component :is="item.icon" />
                        <span class="sb-label">{{ item.label }}</span>
                    </Link>
                </template>
            </nav>

            <div class="sb-foot">
                <button type="button" class="sb-item collapse-toggle" :title="prefs.collapsed ? 'Expand sidebar' : undefined"
                    :aria-label="prefs.collapsed ? 'Expand sidebar' : 'Collapse sidebar'" @click="setCollapsed(!prefs.collapsed)">
                    <PanelLeftOpen v-if="prefs.collapsed" /><PanelLeftClose v-else />
                    <span class="sb-label">Collapse</span>
                </button>
                <Link href="/account" class="sb-user" :title="prefs.collapsed ? user?.name : undefined">
                    <Avatar :name="user?.name" />
                    <span class="who"><b>{{ user?.name }}</b><span>{{ ROLE[role] }}</span></span>
                </Link>
                <button type="button" class="sb-item" :title="prefs.collapsed ? 'Log out' : undefined" @click="logout">
                    <LogOut /><span class="sb-label">Log out</span>
                </button>
            </div>
        </aside>
        <Transition name="fade"><div v-if="navOpen" class="nav-scrim" @click="navOpen = false" /></Transition>

        <div class="main">
            <header class="topbar">
                <button type="button" class="icon-btn menu-btn" aria-label="Open menu" @click="navOpen = true"><Menu /></button>
                <div class="tb-titles">
                    <div v-if="crumbs.length" class="crumbs">
                        <template v-for="([label, href], i) in crumbs" :key="i">
                            <Link :href="href">{{ label }}</Link><ChevronRight style="width: 12px; height: 12px; flex: none" />
                        </template>
                    </div>
                    <h1 class="tb-title" :title="title">{{ title }}</h1>
                </div>
                <div class="tb-actions">
                    <slot name="actions" />
                    <span v-if="$slots.actions" class="tb-sep hide-phone" />
                    <button type="button" class="icon-btn" :aria-label="prefs.dark ? 'Switch to light mode' : 'Switch to dark mode'"
                        :title="prefs.dark ? 'Light mode' : 'Dark mode'" @click="setDark(!prefs.dark)">
                        <Sun v-if="prefs.dark" /><Moon v-else />
                    </button>
                    <Link href="/notifications" class="icon-btn" aria-label="Notifications" title="Notifications">
                        <Bell /><span v-if="page.props.unread" class="dot">{{ page.props.unread > 9 ? '9+' : page.props.unread }}</span>
                    </Link>
                </div>
            </header>

            <main class="content page-enter">
                <slot />
            </main>
        </div>
    </div>
    <ToastHost />
    <ConfirmHost />
    <PasswordNotice />
</template>
