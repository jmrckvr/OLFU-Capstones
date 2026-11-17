/*
 * Layout checker: `npm run test:ui` (needs the app running: php artisan serve, and demo data).
 *
 * Logs in as each role with headless Chrome and opens every page at
 * 1280×720, 1440×900, 1920×1080 (light + dark) and 390px phone width. For each it checks:
 *   - desktop: the WINDOW never scrolls (panels scroll inside)
 *   - every size: nothing sticks out sideways
 *   - labels / pills / buttons / titles never wrap to a 2nd line
 *   - no errors in the browser console
 * Screenshots go to storage/app/ui-check/ (git-ignored) so you can look at them.
 *
 *   BASE_URL=http://localhost:8000 npm run test:ui
 */
import { execSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import puppeteer from 'puppeteer-core';
import { chromePath } from './chrome.mjs';

const BASE = process.env.BASE_URL ?? 'http://localhost:8000';
const OUT = process.env.OUT_DIR ?? 'storage/app/ui-check';
mkdirSync(OUT, { recursive: true });

// Look up a few project ids from the demo data.
const ids = JSON.parse(execSync('php artisan demo:ids').toString());

const ROLES = {
    student: { email: 'juan.delacruz@olfu.edu.ph', pages: ['/dashboard', '/repository', `/projects/${ids.approved}`, `/projects/${ids.restricted}`, '/submissions', '/submissions/create', `/submissions/${ids.returned}/edit`, '/notifications', '/account'] },
    coordinator: { email: 'msantos@olfu.edu.ph', pages: ['/dashboard', '/reviews', '/reviews#history', `/projects/${ids.pending}`, '/repository', '/notifications'] },
    administrator: { email: 'jcalim@olfu.edu.ph', pages: ['/dashboard', '/admin/projects', '/admin/projects/create', '/admin/users', '/admin/activity', '/account'] },
};
const GUEST = ['/', '/register'];
const SIZES = [
    { name: '1280', width: 1280, height: 720, themes: ['light', 'dark'] },
    { name: '1440', width: 1440, height: 900, themes: ['light', 'dark'] },
    { name: '1920', width: 1920, height: 1080, themes: ['light', 'dark'] },
    { name: 'phone', width: 390, height: 844, themes: ['light'], phone: true },
];

// Elements that must stay on one line.
const ONE_LINE = '.tile .label, .field-label, .sb-label, .pill, .btn, .status, .chip, .tb-title, .h-section, .crumbs, .table th, .eyebrow, .fact b, .fact span, .step b, .project-card .t, .sb-user .who b, .dialog-head h2';

const problems = [];
const browser = await puppeteer.launch({ executablePath: chromePath(), headless: true, args: ['--no-first-run'] });

async function check(page, label, { phone }) {
    await new Promise((r) => setTimeout(r, 800)); // let entrance animations finish
    const r = await page.evaluate((sel, phone) => {
        const se = document.scrollingElement;
        const wrapped = [];
        for (const el of document.querySelectorAll(sel)) {
            if (!el.offsetParent && getComputedStyle(el).position !== 'fixed') continue;
            if (!el.textContent.trim()) continue;
            // Count lines of TEXT only (icons and badges sit on their own baseline and are ignored).
            const centers = [];
            const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
            for (let n = walker.nextNode(); n; n = walker.nextNode()) {
                if (!n.textContent.trim()) continue;
                const range = document.createRange();
                range.selectNodeContents(n);
                for (const rect of range.getClientRects()) if (rect.width > 1) centers.push(rect.top + rect.height / 2);
            }
            const fs = parseFloat(getComputedStyle(el).fontSize);
            const lines = centers.sort((a, b) => a - b).filter((c, i, arr) => i === 0 || c - arr[i - 1] > fs * 0.9).length;
            if (lines > 1) wrapped.push(el.textContent.trim().slice(0, 40));
        }
        return {
            pageScroll: !phone && se.scrollHeight > window.innerHeight + 1,
            sideways: se.scrollWidth > window.innerWidth + 1,
            wrapped,
        };
    }, ONE_LINE, phone);
    if (r.pageScroll) problems.push(`${label}: page scrolls vertically`);
    if (r.sideways) problems.push(`${label}: sideways overflow`);
    if (r.wrapped.length) problems.push(`${label}: wrapped → ${[...new Set(r.wrapped)].slice(0, 4).join(' | ')}`);
}

async function run(role, size, theme) {
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    const errors = [];
    page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
    page.on('pageerror', (e) => errors.push(e.message));
    await page.evaluateOnNewDocument((t) => { try { localStorage.setItem('ccs.theme', t); localStorage.setItem('ccs.sidebar', 'expanded'); } catch {} }, theme);
    await page.setViewport({ width: size.width, height: size.height });

    const shot = async (path) => {
        const tag = `${role}-${path.replace(/[^a-z0-9]+/gi, '_') || 'home'}-${size.name}-${theme}`;
        await check(page, tag, size);
        await page.screenshot({ path: `${OUT}/${tag}.png` });
    };

    if (role === 'guest') {
        for (const path of GUEST) { await page.goto(BASE + path, { waitUntil: 'networkidle0' }); await shot(path); }
    } else {
        await page.goto(BASE + '/login', { waitUntil: 'networkidle0' });
        await page.type('input[type=email]', ROLES[role].email);
        await page.type('input[type=password]', 'password');
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {}), page.click('form.auth-card button.btn-primary')]);
        for (const path of ROLES[role].pages) {
            const [url, hash] = path.split('#');
            await page.goto(BASE + url, { waitUntil: 'networkidle0' });
            if (hash === 'history') await page.evaluate(() => [...document.querySelectorAll('.pill')].find((b) => b.textContent.includes('History'))?.click());
            await shot(path);
        }
    }
    errors.filter((e) => !/favicon|ERR_ABORTED/.test(e)).forEach((e) => problems.push(`${role} ${size.name} ${theme}: console error → ${e.slice(0, 160)}`));
    await ctx.close();
}

for (const size of SIZES) {
    for (const theme of size.themes) {
        for (const role of ['guest', 'student', 'coordinator', 'administrator']) {
            await run(role, size, theme);
            process.stdout.write('.');
        }
    }
}
await browser.close();

console.log(`\nScreenshots: ${OUT}`);
if (problems.length) {
    console.log(`\n${problems.length} problem(s):`);
    problems.forEach((p) => console.log('  ✗ ' + p));
    process.exit(1);
}
console.log('✓ No page scroll on desktop, no sideways overflow, no wrapped labels, no console errors.');
