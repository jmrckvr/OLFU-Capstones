// Shared front-end helpers: toasts, confirm dialog, saved preferences, formatting.
// Everything here is plain Vue reactivity, no extra library.
import { reactive, ref } from 'vue';

/* ---------------- Toasts (auto-hiding messages) ---------------- */
export const toasts = reactive([]);
let toastId = 0;

export function toast(message, type = 'success', ms = type === 'error' ? 6000 : 3800) {
    if (!message) return;
    // Same message already showing? Don't stack a duplicate.
    if (toasts.some((t) => t.message === message)) return;
    const id = ++toastId;
    toasts.push({ id, message, type, ms });
    setTimeout(() => dismissToast(id), ms);
}
export function dismissToast(id) {
    const i = toasts.findIndex((t) => t.id === id);
    if (i !== -1) toasts.splice(i, 1);
}

/* ---------------- Confirm dialog (replaces window.confirm) ----------------
   Usage:  if (await confirmDialog({ title: 'Delete?', danger: true })) { ... } */
export const confirmState = reactive({ open: false, title: '', message: '', confirmText: 'Confirm', danger: false, resolve: null });

export function confirmDialog({ title, message = '', confirmText = 'Confirm', danger = false }) {
    return new Promise((resolve) => {
        Object.assign(confirmState, { open: true, title, message, confirmText, danger, resolve });
    });
}
export function answerConfirm(value) {
    confirmState.resolve?.(value);
    Object.assign(confirmState, { open: false, resolve: null });
}

/* ---------------- One-time password dialog (admin creates / resets an account) ---------------- */
export const passwordNotice = ref(null);

/* ---------------- Saved preferences (dark mode, sidebar) ----------------
   Stored in this browser only. Wrapped in try/catch because private windows can block storage. */
function read(key) { try { return localStorage.getItem(key); } catch { return null; } }
function write(key, value) { try { localStorage.setItem(key, value); } catch { /* not saved, still works */ } }

export const prefs = reactive({
    dark: document.documentElement.classList.contains('dark'),
    collapsed: document.documentElement.dataset.sidebar === 'collapsed',
});
export function setDark(on) {
    prefs.dark = on;
    document.documentElement.classList.toggle('dark', on);
    write('ccs.theme', on ? 'dark' : 'light');
}
export function setCollapsed(on) {
    prefs.collapsed = on;
    document.documentElement.dataset.sidebar = on ? 'collapsed' : '';
    write('ccs.sidebar', on ? 'collapsed' : 'expanded');
}

/* ---------------- Formatting ---------------- */
export const STATUS = {
    draft: 'Draft',
    pending: 'Pending Review',
    returned: 'Resubmission Required',
    approved: 'Approved',
};
export const ROLE = { student: 'Student', coordinator: 'Research Coordinator', administrator: 'Administrator' };

// Dates come from Laravel already in Asia/Manila time; we show them in the same zone.
const TZ = 'Asia/Manila';
export function formatDate(value, withTime = false) {
    if (!value) return '—';
    const d = new Date(value);
    return d.toLocaleString('en-PH', {
        timeZone: TZ, month: 'short', day: 'numeric', year: 'numeric',
        ...(withTime ? { hour: 'numeric', minute: '2-digit' } : {}),
    });
}
export function timeAgo(value) {
    if (!value) return '';
    const s = Math.round((Date.now() - new Date(value).getTime()) / 1000);
    if (s < 60) return 'just now';
    const m = Math.round(s / 60); if (m < 60) return `${m} min ago`;
    const h = Math.round(m / 60); if (h < 24) return `${h} hr ago`;
    const d = Math.round(h / 24); if (d < 7) return `${d} day${d > 1 ? 's' : ''} ago`;
    return formatDate(value);
}
export function fileSize(bytes) {
    if (!bytes && bytes !== 0) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}
export function initials(name = '') {
    const parts = name.replace(/^(Prof|Dr|Engr)\.?\s+/i, '').trim().split(/\s+/);
    return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase() || '?';
}
// A stable color per name (same person = same color), all dark enough for white text.
const AVATAR_COLORS = ['#8a1c1c', '#1e5a96', '#1d6b3d', '#7a4b00', '#5b2a86', '#0f6470', '#8a2f5b', '#4b5320'];
export function avatarColor(name = '') {
    let h = 0;
    for (const ch of name) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
    return AVATAR_COLORS[h % AVATAR_COLORS.length];
}

/** APA-style citation for a capstone project. */
export function citation(p) {
    const names = (p.proponents || '').split(',').map((n) => n.trim()).filter(Boolean).map((full) => {
        const parts = full.split(/\s+/);
        const last = parts.pop();
        return `${last}, ${parts.map((x) => x[0] + '.').join(' ')}`.trim();
    });
    const authors = names.length > 1 ? names.slice(0, -1).join(', ') + ', & ' + names.at(-1) : names[0] ?? '';
    const year = (p.academic_year || '').split(/[–-]/).pop();
    return `${authors} (${year}). ${p.title} [Capstone project]. College of Computer Studies, Our Lady of Fatima University.`;
}

export async function copyText(text) {
    try { await navigator.clipboard.writeText(text); toast('Copied to clipboard.'); }
    catch { toast('Could not copy. Select the text and press Ctrl+C.', 'error'); }
}
