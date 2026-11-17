<script setup>
// The one confirm dialog for the whole app. Open it with confirmDialog({...}) from lib/ui.js.
import { AlertTriangle, HelpCircle } from 'lucide-vue-next';
import Modal from './Modal.vue';
import { answerConfirm, confirmState } from '../lib/ui';
</script>

<template>
    <Modal :show="confirmState.open" :title="confirmState.title" @close="answerConfirm(false)">
        <template #icon>
            <div class="dialog-icon" :class="{ danger: confirmState.danger }">
                <AlertTriangle v-if="confirmState.danger" /><HelpCircle v-else />
            </div>
        </template>
        <p v-if="confirmState.message" class="muted" style="font-size: 13.5px">{{ confirmState.message }}</p>
        <template #footer>
            <button type="button" class="btn btn-ghost" @click="answerConfirm(false)">Cancel</button>
            <button type="button" class="btn" :class="confirmState.danger ? 'btn-danger' : 'btn-primary'" autofocus @click="answerConfirm(true)">
                {{ confirmState.confirmText }}
            </button>
        </template>
    </Modal>
</template>
