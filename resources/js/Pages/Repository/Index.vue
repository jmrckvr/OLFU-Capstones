<script setup>
/*
 * Repository: approved projects only. Search + year + category + program filters,
 * paged on the server (8 per page). Filters live in the URL, so Back/Refresh keep them,
 * and they never leak into other pages.
 */
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import { ChevronLeft, ChevronRight, Download, FilterX, LibraryBig, Lock } from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import SearchField from '../../Components/SearchField.vue';
import { EmptyState, PillSwitch } from '../../Components/Bits';

const props = defineProps({ projects: Object, filters: Object, options: Object });

const f = reactive({
    search: props.filters.search ?? '',
    year: props.filters.year ?? '',
    category: props.filters.category ?? '',
    program: props.filters.program ?? '',
    sort: props.filters.sort ?? 'newest',
});
const hasFilters = computed(() => f.search || f.year || f.category || f.program);

function apply(pageNo) {
    // Only send filters that are set, so the URL stays short.
    const query = Object.fromEntries(Object.entries({ ...f, sort: f.sort === 'newest' ? '' : f.sort }).filter(([, v]) => v));
    if (pageNo > 1) query.page = pageNo;
    router.get('/repository', query, { preserveState: true, preserveScroll: true, replace: true, only: ['projects', 'filters'] });
}
function clearFilters() {
    Object.assign(f, { search: '', year: '', category: '', program: '' });
    apply();
}

// Dropdowns apply right away; the search box applies 300 ms after you stop typing.
watch(() => [f.year, f.category, f.program, f.sort], () => apply());
let timer;
watch(() => f.search, () => { clearTimeout(timer); timer = setTimeout(apply, 300); });

const programOptions = computed(() => [{ value: '', label: 'All' }, ...props.options.programs.map((p) => ({ value: p, label: p }))]);
const p = computed(() => props.projects);
</script>

<template>
    <AppLayout title="Repository">
        <div class="toolbar">
            <SearchField v-model="f.search" placeholder="Search title, member, technology or adviser" @submit="apply()" />
            <select v-model="f.year" class="select" aria-label="Academic year">
                <option value="">All academic years</option>
                <option v-for="y in options.academic_years" :key="y">{{ y }}</option>
            </select>
            <select v-model="f.category" class="select" aria-label="Category">
                <option value="">All categories</option>
                <option v-for="c in options.categories" :key="c">{{ c }}</option>
            </select>
            <PillSwitch v-model="f.program" :options="programOptions" />
            <button v-if="hasFilters" type="button" class="btn btn-ghost btn-sm" @click="clearFilters"><FilterX />Clear filters</button>
        </div>

        <section class="panel fill">
            <div class="panel-head">
                <span class="h-section">{{ p.total }} {{ p.total === 1 ? 'project' : 'projects' }}</span>
                <select v-model="f.sort" class="select" style="width: auto; height: 32px; font-size: 12.5px" aria-label="Sort">
                    <option value="newest">Newest first</option>
                    <option value="title">Title A–Z</option>
                    <option value="popular">Most downloaded</option>
                </select>
            </div>
            <div class="scroll">
                <TransitionGroup tag="div" name="list" class="stagger">
                    <Link v-for="(proj, i) in p.data" :key="proj.id" :href="`/projects/${proj.id}`" class="project-card" :style="{ '--i': i }">
                        <img v-if="proj.cover_path" :src="`/projects/${proj.id}/cover`" class="cover" alt="" loading="lazy" />
                        <div v-else class="cover" />
                        <div class="body">
                            <div class="t" :title="proj.title">{{ proj.title }}</div>
                            <div class="small muted one-line">Proponents: {{ proj.proponents }}</div>
                            <div class="meta">
                                <span class="chip">{{ proj.academic_year }}</span>
                                <span class="chip">{{ proj.category }}</span>
                                <span class="chip">{{ proj.program }}</span>
                                <span v-if="proj.technology" class="chip one-line hide-phone" style="max-width: 260px" :title="proj.technology">{{ proj.technology }}</span>
                            </div>
                        </div>
                        <div class="side hide-phone">
                            <span v-if="proj.is_restricted" class="status restricted"><Lock style="width: 11px; height: 11px" />Restricted</span>
                            <span v-else />
                            <span class="small faint nowrap"><Download style="width: 12px; height: 12px; display: inline; vertical-align: -1px" /> {{ proj.downloads }}</span>
                        </div>
                    </Link>
                </TransitionGroup>
                <EmptyState v-if="!p.data.length" :icon="LibraryBig" :title="hasFilters ? 'No projects match' : 'The repository is empty'"
                    :text="hasFilters ? 'Try a different word or clear the filters.' : 'Approved projects will appear here.'">
                    <button v-if="hasFilters" type="button" class="btn btn-outline btn-sm" style="margin-top: 6px" @click="clearFilters"><FilterX />Clear filters</button>
                </EmptyState>
            </div>
            <div v-if="p.last_page > 1" class="panel-foot pager">
                <span class="grow">{{ p.from }}–{{ p.to }} of {{ p.total }}</span>
                <button type="button" class="icon-btn" :disabled="p.current_page <= 1" aria-label="Previous page" @click="apply(p.current_page - 1)"><ChevronLeft /></button>
                <span class="tabular">{{ p.current_page }} / {{ p.last_page }}</span>
                <button type="button" class="icon-btn" :disabled="p.current_page >= p.last_page" aria-label="Next page" @click="apply(p.current_page + 1)"><ChevronRight /></button>
            </div>
        </section>
    </AppLayout>
</template>
