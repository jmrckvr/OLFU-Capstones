<script setup>
/*
 * Sortable, paged table with a sticky header.
 *   columns: [{ key, label, sortable?, width?, align?, sortBy?(row) }]
 *   rows:    array of objects
 * Custom cells:  <template #cell-title="{ row }">...</template>
 * Row click emits "open". Buttons inside cells must use @click.stop so they don't open the row.
 */
import { computed, ref, watch } from 'vue';
import { ArrowDown, ArrowUp, ChevronLeft, ChevronRight, ChevronsUpDown } from 'lucide-vue-next';

const props = defineProps({
    columns: Array,
    rows: Array,
    rowKey: { type: String, default: 'id' },
    pageSize: { type: Number, default: 12 },
    defaultSort: { type: Object, default: () => ({ key: null, dir: 'asc' }) },
    clickable: Boolean,
    noun: { type: String, default: 'items' },
});
const emit = defineEmits(['open']);

const sort = ref({ ...props.defaultSort });
const page = ref(1);

function toggleSort(col) {
    if (!col.sortable) return;
    sort.value = sort.value.key === col.key
        ? { key: col.key, dir: sort.value.dir === 'asc' ? 'desc' : 'asc' }
        : { key: col.key, dir: 'asc' };
}

const sorted = computed(() => {
    const { key, dir } = sort.value;
    if (!key) return props.rows;
    const col = props.columns.find((c) => c.key === key);
    const get = col?.sortBy ?? ((r) => r[key]);
    return [...props.rows].sort((a, b) => {
        const x = get(a), y = get(b);
        const cmp = typeof x === 'number' && typeof y === 'number'
            ? x - y
            : String(x ?? '').localeCompare(String(y ?? ''), undefined, { numeric: true, sensitivity: 'base' });
        return dir === 'asc' ? cmp : -cmp;
    });
});

const pages = computed(() => Math.max(1, Math.ceil(sorted.value.length / props.pageSize)));
const visible = computed(() => sorted.value.slice((page.value - 1) * props.pageSize, page.value * props.pageSize));
const from = computed(() => (sorted.value.length ? (page.value - 1) * props.pageSize + 1 : 0));
const to = computed(() => Math.min(page.value * props.pageSize, sorted.value.length));

// When a filter shrinks the list, don't stay on a page that no longer exists.
watch(() => props.rows.length, () => { if (page.value > pages.value) page.value = pages.value; });
watch(sort, () => { page.value = 1; });

function onRow(row, e) {
    if (!props.clickable) return;
    if (e.target.closest('button, a, input, select, label')) return; // action buttons don't open the row
    emit('open', row);
}
</script>

<template>
    <div class="fill">
        <div class="table-wrap scroll">
            <table class="table">
                <colgroup><col v-for="c in columns" :key="c.key" :style="c.width ? { width: c.width } : null" /></colgroup>
                <thead>
                    <tr>
                        <th v-for="c in columns" :key="c.key" :class="{ sortable: c.sortable, sorted: sort.key === c.key }" :style="{ textAlign: c.align ?? 'left' }"
                            :aria-sort="sort.key === c.key ? (sort.dir === 'asc' ? 'ascending' : 'descending') : undefined" @click="toggleSort(c)">
                            {{ c.label }}<template v-if="c.sortable">
                                <ArrowUp v-if="sort.key === c.key && sort.dir === 'asc'" class="sort-icon" />
                                <ArrowDown v-else-if="sort.key === c.key" class="sort-icon" />
                                <ChevronsUpDown v-else class="sort-icon" />
                            </template>
                        </th>
                    </tr>
                </thead>
                <TransitionGroup tag="tbody" name="list" class="stagger" :key="page + sort.key + sort.dir">
                    <tr v-for="(row, i) in visible" :key="row[rowKey]" :class="{ clickable }" :style="{ '--i': i }"
                        :tabindex="clickable ? 0 : undefined" @click="onRow(row, $event)" @keydown.enter="clickable && $event.target === $event.currentTarget && emit('open', row)">
                        <td v-for="c in columns" :key="c.key" :style="{ textAlign: c.align ?? 'left' }">
                            <slot :name="`cell-${c.key}`" :row="row">{{ row[c.key] }}</slot>
                        </td>
                    </tr>
                </TransitionGroup>
            </table>
            <slot v-if="!rows.length" name="empty" />
        </div>
        <div v-if="rows.length" class="panel-foot pager">
            <span class="grow">{{ from }}–{{ to }} of {{ rows.length }} {{ noun }}</span>
            <button type="button" class="icon-btn" :disabled="page <= 1" aria-label="Previous page" @click="page--"><ChevronLeft /></button>
            <span class="tabular">{{ page }} / {{ pages }}</span>
            <button type="button" class="icon-btn" :disabled="page >= pages" aria-label="Next page" @click="page++"><ChevronRight /></button>
        </div>
    </div>
</template>
