<script setup>
// Search box with a clear (x) button. Press "/" anywhere on the page to jump into it.
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Search, X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: String,
    placeholder: { type: String, default: 'Search' },
    hotkey: { type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue', 'submit']);
const input = ref(null);

function onKey(e) {
    const el = document.activeElement;
    const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(el?.tagName) || el?.isContentEditable;
    if (props.hotkey && e.key === '/' && !typing && !document.querySelector('.dialog-overlay')) {
        e.preventDefault();
        input.value?.focus();
    }
}
function clear() {
    emit('update:modelValue', '');
    emit('submit');
    input.value?.focus();
}
onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <div class="search">
        <Search class="lead" />
        <input ref="input" class="input" type="text" :placeholder="placeholder" :value="modelValue" :aria-label="placeholder"
            @input="emit('update:modelValue', $event.target.value)" @keydown.enter.prevent="emit('submit')" @keydown.esc="input.blur()" />
        <button v-if="modelValue" type="button" class="clear" aria-label="Clear search" @click="clear"><X style="width: 14px; height: 14px" /></button>
        <kbd v-else-if="hotkey" class="hide-phone" title="Press / to search">/</kbd>
    </div>
</template>
