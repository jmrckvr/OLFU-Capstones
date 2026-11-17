<script setup>
/*
 * Notifications (template page 15).
 * Personal notices ("Your submission was approved") + announcements for everyone.
 * Coordinator/admin can post an announcement. Clicking a notice marks it read and opens its link.
 */
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Bell, CheckCheck, Megaphone, Send, Trash2 } from 'lucide-vue-next';
import AppLayout from '../../Layouts/AppLayout.vue';
import { EmptyState, Field, PillSwitch } from '../../Components/Bits';
import { confirmDialog, timeAgo } from '../../lib/ui';

const props = defineProps({ items: Array, canAnnounce: Boolean });

const filter = ref('all');
const unreadCount = computed(() => props.items.filter((i) => !i.read).length);
const shown = computed(() => props.items.filter((i) =>
    filter.value === 'all' || (filter.value === 'unread' ? !i.read : i.kind === 'announcement')));

function open(item) {
    if (!item.read) router.post(`/notifications/${item.id}/read`, {}, { preserveScroll: true, preserveState: true, only: ['items', 'unread'] });
    if (item.link) router.visit(item.link);
}
function readAll() { router.post('/notifications/read-all', {}, { preserveScroll: true }); }

const form = useForm({ message: '' });
function announce() { form.post('/announcements', { preserveScroll: true, onSuccess: () => form.reset() }); }
async function removeAnnouncement(item) {
    if (await confirmDialog({ title: 'Remove this announcement?', message: 'Everyone will stop seeing it.', confirmText: 'Remove', danger: true })) {
        router.delete(`/announcements/${item.id.slice(1)}`, { preserveScroll: true });
    }
}
</script>

<template>
    <AppLayout title="Notifications">
        <template #actions>
            <button type="button" class="btn btn-outline" :disabled="!unreadCount" @click="readAll"><CheckCheck /><span class="btn-label">Mark all read</span></button>
        </template>

        <form v-if="canAnnounce" class="panel rise" style="flex: none" @submit.prevent="announce">
            <div class="panel-body flex items-start gap-3" style="padding: 12px 16px">
                <span class="file-icon" style="width: 38px; height: 38px; background: var(--warn-bg); color: var(--warn)"><Megaphone /></span>
                <Field :error="form.errors.message" style="flex: 1" label="Post an announcement">
                    <input v-model="form.message" class="input" maxlength="500" placeholder="Capstone uploads close on the last Friday of the month." />
                </Field>
                <button class="btn btn-primary" style="margin-top: 22px" :disabled="form.processing || !form.message.trim()"><Send /><span class="hide-phone">Post</span></button>
            </div>
        </form>

        <div class="toolbar">
            <PillSwitch v-model="filter" :options="[
                { value: 'all', label: 'All', count: items.length },
                { value: 'unread', label: 'Unread', count: unreadCount },
                { value: 'announcement', label: 'Announcements', count: items.filter((i) => i.kind === 'announcement').length },
            ]" />
        </div>

        <section class="panel fill">
            <div class="scroll">
                <TransitionGroup tag="div" name="list" class="stagger">
                    <div v-for="(item, i) in shown" :key="item.id" class="note clickable" :class="{ unread: !item.read }" :style="{ '--i': i }" role="button" tabindex="0" @click="open(item)" @keydown.enter="open(item)">
                        <span class="ico" :class="{ announce: item.kind === 'announcement' }"><Megaphone v-if="item.kind === 'announcement'" /><Bell v-else /></span>
                        <div class="text">
                            <div>{{ item.message }}</div>
                            <div class="tiny faint" style="margin-top: 3px">
                                {{ item.kind === 'announcement' ? `Announcement${item.author ? ' by ' + item.author : ''}` : 'For you' }} · {{ timeAgo(item.at) }}{{ item.read ? '' : ' · Unread' }}
                            </div>
                        </div>
                        <button v-if="item.can_delete" type="button" class="icon-btn" title="Remove announcement" aria-label="Remove announcement" @click.stop="removeAnnouncement(item)"><Trash2 /></button>
                    </div>
                </TransitionGroup>
                <EmptyState v-if="!shown.length" :icon="Bell" :title="filter === 'unread' ? 'All caught up' : 'No notifications yet'" text="Updates about submissions and announcements will appear here." />
            </div>
        </section>
    </AppLayout>
</template>
