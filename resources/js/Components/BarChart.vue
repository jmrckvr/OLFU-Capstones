<script setup>
/*
 * 7-day grouped bar chart: "Submitted" vs "Reviewed" per day.
 * Bars grow up from the bottom; hovering a day shows the exact numbers.
 * Colors come from --series-1 / --series-2 in polish.css (checked for color-blind safety).
 * The legend names both series, so color is never the only clue.
 */
import { computed, ref } from 'vue';

const props = defineProps({ days: Array });
const hover = ref(null);

const max = computed(() => {
    const m = Math.max(1, ...props.days.flatMap((d) => [d.submitted, d.reviewed]));
    return Math.ceil(m / 2) * 2; // round up to an even number so the mid gridline is a whole number
});
const pct = (v) => `${(v / max.value) * 100}%`;
const totals = computed(() => ({
    submitted: props.days.reduce((s, d) => s + d.submitted, 0),
    reviewed: props.days.reduce((s, d) => s + d.reviewed, 0),
}));
</script>

<template>
    <div class="chart">
        <div class="chart-legend">
            <span><i style="background: var(--series-1)" />Submitted · {{ totals.submitted }}</span>
            <span><i style="background: var(--series-2)" />Reviewed · {{ totals.reviewed }}</span>
        </div>
        <div class="chart-plot" style="margin-left: 18px" role="img" :aria-label="`Last 7 days: ${totals.submitted} submitted, ${totals.reviewed} reviewed`">
            <div class="chart-grid" aria-hidden="true">
                <span><em>{{ max }}</em></span><span><em>{{ max / 2 }}</em></span><span />
            </div>
            <div v-for="(d, i) in days" :key="d.date" class="chart-day" @mouseenter="hover = i" @mouseleave="hover = null">
                <div class="chart-bar s1" :style="{ height: pct(d.submitted), '--i': i }" />
                <div class="chart-bar s2" :style="{ height: pct(d.reviewed), '--i': i }" />
                <Transition name="fade">
                    <div v-if="hover === i" class="chart-tip">
                        {{ d.date }} · <i style="background: var(--series-1)" />{{ d.submitted }} submitted ·
                        <i style="background: var(--series-2)" />{{ d.reviewed }} reviewed
                    </div>
                </Transition>
            </div>
        </div>
        <div class="chart-x" style="margin-left: 18px">
            <span v-for="d in days" :key="d.date">{{ d.label }}</span>
        </div>
    </div>
</template>
