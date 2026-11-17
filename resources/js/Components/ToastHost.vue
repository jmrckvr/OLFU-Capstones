<script setup>
// Auto-hiding messages in the bottom-right corner. Show one with toast('Saved') from lib/ui.js.
import { CircleAlert, CircleCheck, Info, X } from 'lucide-vue-next';
import { dismissToast, toasts } from '../lib/ui';
const icons = { success: CircleCheck, error: CircleAlert, info: Info };
</script>

<template>
    <div class="toasts" aria-live="polite">
        <TransitionGroup name="toast">
            <div v-for="t in toasts" :key="t.id" class="toast" :class="t.type" role="status">
                <component :is="icons[t.type] ?? Info" class="ico" />
                <span class="msg">{{ t.message }}</span>
                <button type="button" class="icon-btn" style="width: 24px; height: 24px" aria-label="Dismiss" @click="dismissToast(t.id)">
                    <X style="width: 14px; height: 14px" />
                </button>
                <span class="bar" :style="{ animationDuration: t.ms + 'ms' }" />
            </div>
        </TransitionGroup>
    </div>
</template>
