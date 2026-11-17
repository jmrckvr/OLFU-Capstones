// Tiny components written as render functions (no template needed).
// Import like:  import { StatusPill, Avatar, Field, PillSwitch, EmptyState } from '@/Components/Bits';
import { h } from 'vue';
import { Inbox } from 'lucide-vue-next';
import { STATUS, avatarColor, initials } from '../lib/ui';

/** Colored status pill: Draft / Pending Review / Resubmission Required / Approved. */
export const StatusPill = (props) =>
    h('span', { class: ['status', props.status] }, props.label ?? STATUS[props.status] ?? props.status);
StatusPill.props = ['status', 'label'];

/** Circle with initials. The color comes from the name, so a person always gets the same color. */
export const Avatar = (props) =>
    h('span', {
        class: ['avatar', props.size], style: { background: avatarColor(props.name ?? '') },
        title: props.name, 'aria-hidden': 'true',
    }, initials(props.name ?? ''));
Avatar.props = ['name', 'size'];

/** A labelled form field with its error message underneath. */
export const Field = (props, { slots }) =>
    h('label', { class: ['field', { 'has-error': props.error }] }, [
        h('span', { class: 'field-label', title: props.label }, [props.label, props.required ? h('span', { class: 'req' }, ' *') : null]),
        slots.default?.(),
        props.error ? h('span', { class: 'error-text' }, props.error)
            : props.hint ? h('span', { class: 'field-hint', title: props.hint }, props.hint) : null,
    ]);
Field.props = ['label', 'error', 'hint', 'required'];

/** Segmented "pill switch", used instead of tabs. options: [{ value, label, count? }] */
export const PillSwitch = (props, { emit }) =>
    h('div', { class: 'pills', role: 'tablist' }, props.options.map((o) =>
        h('button', {
            type: 'button', role: 'tab', class: ['pill', { on: props.modelValue === o.value }],
            'aria-selected': props.modelValue === o.value, onClick: () => emit('update:modelValue', o.value),
        }, [o.label, o.count !== undefined ? h('span', { class: 'count' }, o.count) : null])));
PillSwitch.props = ['options', 'modelValue'];
PillSwitch.emits = ['update:modelValue'];

/** Friendly message for a list or table with nothing in it. */
export const EmptyState = (props, { slots }) =>
    h('div', { class: 'empty rise' }, [
        h('div', { class: 'empty-icon' }, h(props.icon ?? Inbox)),
        h('b', props.title),
        props.text ? h('p', props.text) : null,
        slots.default?.(),
    ]);
EmptyState.props = ['title', 'text', 'icon'];
