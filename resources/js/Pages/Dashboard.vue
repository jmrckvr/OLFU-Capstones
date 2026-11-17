<script setup>
/*
 * Dashboard, one page for all three roles:
 *   student      → own numbers, recently approved, my submissions
 *   coordinator  → repository numbers, 7-day chart, pending queue, recently approved
 *   admin        → totals by program, 7-day chart, recent activity, recently approved
 */
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Activity, ClipboardCheck, Clock, FileDown, FilePlus2, LibraryBig, Plus, Users } from 'lucide-vue-next';
import AppLayout from '../Layouts/AppLayout.vue';
import BarChart from '../Components/BarChart.vue';
import StatTile from '../Components/StatTile.vue';
import { Avatar, EmptyState, StatusPill } from '../Components/Bits';
import { formatDate, timeAgo } from '../lib/ui';

const props = defineProps({
    tiles: Array, recent: Array, mine: Array, chart: Array,
    avgReviewHours: Number, queue: Array, activity: Array,
});
const page = usePage();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value.role);
const firstName = computed(() => user.value.name.replace(/^(Prof|Dr)\.?\s+/i, '').split(' ')[0]);
</script>

<template>
    <AppLayout :title="`Welcome, ${firstName}`">
        <template #actions>
            <template v-if="role === 'student'">
                <Link href="/repository" class="btn btn-outline hide-phone"><LibraryBig /><span class="btn-label">Browse</span></Link>
                <Link href="/submissions/create" class="btn btn-primary"><FilePlus2 /><span class="btn-label">New submission</span></Link>
            </template>
            <template v-else-if="role === 'coordinator'">
                <Link href="/reviews" class="btn btn-primary"><ClipboardCheck /><span class="btn-label">Review projects</span></Link>
            </template>
            <template v-else>
                <a href="/admin/projects/export" class="btn btn-outline hide-phone"><FileDown /><span class="btn-label">Export CSV</span></a>
                <Link href="/admin/users" class="btn btn-primary"><Users /><span class="btn-label">Manage users</span></Link>
            </template>
        </template>

        <div class="tiles stagger">
            <StatTile v-for="(t, i) in tiles" :key="t.label" :label="t.label" :value="t.value" :alert="t.alert" :style="{ '--i': i }" />
        </div>

        <!-- Student -->
        <div v-if="role === 'student'" class="row-fill" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr))">
            <section class="panel fill rise">
                <div class="panel-head">
                    <span class="h-section">My submissions</span>
                    <Link href="/submissions" class="link small">View all</Link>
                </div>
                <div class="scroll">
                    <Link v-for="p in mine" :key="p.id" :href="`/projects/${p.id}`" class="list-row">
                        <div class="grow"><div class="title">{{ p.title }}</div><div class="sub">{{ p.academic_year }} · updated {{ timeAgo(p.updated_at) }}</div></div>
                        <StatusPill :status="p.status" />
                    </Link>
                    <div style="padding: 12px 16px">
                        <Link href="/submissions/create" class="add-card"><Plus />New submission</Link>
                    </div>
                </div>
            </section>
            <section class="panel fill rise" style="animation-delay: 60ms">
                <div class="panel-head">
                    <span class="h-section">Recently approved</span>
                    <Link href="/repository" class="link small">Browse repository</Link>
                </div>
                <div class="scroll">
                    <Link v-for="p in recent" :key="p.id" :href="`/projects/${p.id}`" class="list-row">
                        <img v-if="p.cover_path" :src="`/projects/${p.id}/cover`" class="thumb" alt="" /><span v-else class="thumb" />
                        <div class="grow"><div class="title">{{ p.title }}</div><div class="sub">{{ p.category }} · {{ p.academic_year }}</div></div>
                    </Link>
                    <EmptyState v-if="!recent.length" title="Nothing approved yet" text="Approved projects will show up here." />
                </div>
            </section>
        </div>

        <!-- Coordinator and admin -->
        <div v-else class="row-fill" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr))">
            <section class="panel fill rise">
                <div class="panel-head">
                    <span class="h-section">Last 7 days</span>
                    <span v-if="avgReviewHours !== null" class="chip" title="Average time from submission to review decision (last 30 days)">
                        <Clock style="width: 12px; height: 12px; margin-right: 4px" />Avg review {{ avgReviewHours }} h
                    </span>
                </div>
                <div class="panel-body fill" style="padding-left: 20px"><BarChart :days="chart" /></div>
            </section>

            <section class="panel fill rise" style="animation-delay: 60ms">
                <template v-if="role === 'coordinator'">
                    <div class="panel-head">
                        <span class="h-section">Waiting for review</span>
                        <Link href="/reviews" class="link small">Open queue</Link>
                    </div>
                    <div class="scroll">
                        <Link v-for="p in queue" :key="p.id" :href="`/projects/${p.id}`" class="list-row">
                            <div class="grow"><div class="title">{{ p.title }}</div><div class="sub">{{ p.proponents }}</div></div>
                            <span class="small faint nowrap">{{ timeAgo(p.submitted_at) }}</span>
                        </Link>
                        <EmptyState v-if="!queue.length" :icon="ClipboardCheck" title="Queue is clear" text="No submissions are waiting for review." />
                    </div>
                </template>
                <template v-else>
                    <div class="panel-head">
                        <span class="h-section">Recent activity</span>
                        <Link href="/admin/activity" class="link small">Activity log</Link>
                    </div>
                    <div class="scroll">
                        <div v-for="a in activity" :key="a.id" class="list-row">
                            <Avatar :name="a.user?.name ?? 'System'" size="sm" />
                            <div class="grow"><div class="title" style="font-weight: 500" :title="a.description">{{ a.description }}</div><div class="sub">{{ a.user?.name ?? 'System' }} · {{ timeAgo(a.created_at) }}</div></div>
                        </div>
                        <EmptyState v-if="!activity.length" :icon="Activity" title="No activity yet" />
                    </div>
                </template>
            </section>

            <section class="panel fill rise" style="animation-delay: 120ms">
                <div class="panel-head">
                    <span class="h-section">Recently approved</span>
                    <Link href="/repository" class="link small">Browse</Link>
                </div>
                <div class="scroll">
                    <Link v-for="p in recent" :key="p.id" :href="`/projects/${p.id}`" class="list-row">
                        <img v-if="p.cover_path" :src="`/projects/${p.id}/cover`" class="thumb" alt="" /><span v-else class="thumb" />
                        <div class="grow"><div class="title">{{ p.title }}</div><div class="sub">{{ p.academic_year }} · {{ formatDate(p.approved_at) }}</div></div>
                    </Link>
                    <EmptyState v-if="!recent.length" title="Nothing approved yet" />
                </div>
            </section>
        </div>
    </AppLayout>
</template>
