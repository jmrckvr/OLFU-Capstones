<script setup>
// Shows a new temporary password ONCE, after the admin creates an account or resets a password.
import { computed } from 'vue';
import { Copy, KeyRound } from 'lucide-vue-next';
import Modal from './Modal.vue';
import { copyText, passwordNotice } from '../lib/ui';

const notice = computed(() => passwordNotice.value);
function close() { passwordNotice.value = null; }
</script>

<template>
    <Modal :show="!!notice" :title="notice?.title ?? 'Temporary password'" @close="close">
        <template #icon><div class="dialog-icon"><KeyRound /></div></template>
        <p class="muted" style="font-size: 13.5px">
            Give this password to <b style="color: var(--text)">{{ notice?.email }}</b>.
            It is shown only once. They can change it in My Account.
        </p>
        <div class="flex items-center gap-2">
            <input class="input tabular" style="font-size: 16px; font-weight: 700; letter-spacing: .06em" :value="notice?.password" readonly aria-label="Temporary password" />
            <button type="button" class="btn btn-outline" @click="copyText(notice.password)"><Copy />Copy</button>
        </div>
        <template #footer><button type="button" class="btn btn-primary" @click="close">Done</button></template>
    </Modal>
</template>
