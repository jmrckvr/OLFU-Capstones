<script setup>
// Compact stat tile. The number counts up from 0 when the page opens.
import { onMounted, ref } from 'vue';

const props = defineProps({ label: String, value: { type: Number, default: 0 }, alert: Boolean });
const shown = ref(0);

onMounted(() => {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !props.value) { shown.value = props.value; return; }
    const start = performance.now();
    const duration = 700;
    const step = (now) => {
        const t = Math.min(1, (now - start) / duration);
        shown.value = Math.round(props.value * (1 - Math.pow(1 - t, 3))); // ease-out
        if (t < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
});
</script>

<template>
    <div class="tile" :class="{ alert: alert && value > 0 }">
        <div class="value">{{ shown }}</div>
        <div class="label" :title="label">{{ label }}</div>
    </div>
</template>
