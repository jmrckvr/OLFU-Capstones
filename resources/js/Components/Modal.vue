<script setup>
// In-page dialog. Closes on Esc, on the X, or by clicking the dark backdrop.
// Errors that belong to the dialog are shown inside it (put them in the default slot).
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    title: String,
    size: { type: String, default: '' }, // '' | 'wide' | 'xwide'
    closable: { type: Boolean, default: true },
});
const emit = defineEmits(['close']);
const box = ref(null);
let lastFocus = null;

function close() { if (props.closable) emit('close'); }
function onKey(e) {
    if (e.key !== 'Escape' || !props.show) return;
    // Only the top-most dialog closes.
    const overlays = document.querySelectorAll('.dialog-overlay');
    if (overlays[overlays.length - 1]?.contains(box.value)) { e.stopPropagation(); close(); }
}

watch(() => props.show, async (open) => {
    if (open) {
        lastFocus = document.activeElement;
        document.addEventListener('keydown', onKey);
        await nextTick();
        // Focus the first field (or the dialog itself) so keyboard users land inside.
        (box.value?.querySelector('[autofocus], input:not([type=hidden]):not([readonly]), textarea, select') ?? box.value)?.focus();
    } else {
        document.removeEventListener('keydown', onKey);
        lastFocus?.focus?.();
    }
}, { immediate: true });
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <Transition name="dialog">
            <div v-if="show" class="dialog-overlay" @mousedown.self="close">
                <div ref="box" class="dialog" :class="size" role="dialog" aria-modal="true" :aria-label="title" tabindex="-1">
                    <div class="dialog-head">
                        <slot name="icon" />
                        <h2 :title="title">{{ title }}</h2>
                        <slot name="head-actions" />
                        <button v-if="closable" type="button" class="icon-btn" aria-label="Close" @click="close"><X /></button>
                    </div>
                    <div class="dialog-body"><slot /></div>
                    <div v-if="$slots.footer" class="dialog-foot"><slot name="footer" /></div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
