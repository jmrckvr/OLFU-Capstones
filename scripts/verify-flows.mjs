/*
 * Interaction test: `npm run test:flows` (needs the app running + demo data).
 * Clicks through the real app in headless Chrome, like a person would:
 *   1. wrong password shows an error on the login card
 *   2. student: form errors, duplicate file guard, submit a project, cancel via the in-page
 *      confirm dialog (Esc closes it without doing anything), row click opens a quick view
 *   3. role blocks: student gets 403 on staff/admin pages
 *   4. coordinator: "Request resubmission" needs feedback (error shows inside the review box), then approve
 *   5. admin: can't deactivate self, add user → one-time password dialog, Esc closes it
 *   6. dark mode + collapsed sidebar survive a reload; sidebar icons don't move when collapsing
 *   7. logout clears the session
 * Afterwards run `npm run seed:demo` to reset the demo data.
 */
import puppeteer from 'puppeteer-core';
import { chromePath } from './chrome.mjs';

const BASE = process.env.BASE_URL ?? 'http://localhost:8000';
const PDF = 'database/demo/docs/library-book-reservation.pdf';
const stamp = Date.now().toString().slice(-5);
const results = [];
const ok = (name, pass, detail = '') => { results.push({ name, pass, detail }); console.log(`${pass ? '✓' : '✗'} ${name}${detail && !pass ? ' → ' + detail : ''}`); };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

const browser = await puppeteer.launch({ executablePath: chromePath(), headless: true });

async function session() {
    const ctx = await browser.createBrowserContext();
    const page = await ctx.newPage();
    page.errors = [];
    page.on('pageerror', (e) => page.errors.push(e.message));
    page.on('console', (m) => { if (m.type() === 'error' && !/403|404|Forbidden/.test(m.text())) page.errors.push(m.text()); });
    await page.setViewport({ width: 1366, height: 768 });
    return { ctx, page };
}
async function login(page, email, password = 'password') {
    await page.goto(BASE + '/login', { waitUntil: 'networkidle0' });
    await page.type('input[type=email]', email);
    await page.type('input[type=password]', password);
    await page.click('form.auth-card button.btn-primary');
    await page.waitForFunction(() => location.pathname !== '/login' || document.querySelector('.error-text'), { timeout: 10000 }).catch(() => {});
    await settle(page);
}
const text = (page) => page.evaluate(() => document.body.innerText);
const clickText = (page, selector, label) => page.evaluate((s, l) => {
    const el = [...document.querySelectorAll(s)].find((e) => e.textContent.trim().includes(l));
    el?.click();
    return !!el;
}, selector, label);
const toastText = async (page) => { await page.waitForSelector('.toast', { timeout: 6000 }).catch(() => {}); return page.evaluate(() => [...document.querySelectorAll('.toast .msg')].map((t) => t.textContent).join(' | ')); };
const settle = (page) => page.waitForNetworkIdle({ idleTime: 300 }).catch(() => {});

/* 1. Wrong password */
{
    const { ctx, page } = await session();
    await login(page, 'juan.delacruz@olfu.edu.ph', 'nope');
    const err = await page.$eval('.error-text', (e) => e.textContent).catch(() => '');
    ok('Wrong password shows an error on the login card', /incorrect/i.test(err), err);
    await ctx.close();
}

/* 2–3. Student */
{
    const { ctx, page } = await session();
    await login(page, 'juan.delacruz@olfu.edu.ph');
    ok('Student lands on the dashboard after login', page.url().endsWith('/dashboard'), page.url());
    ok('Dashboard shows data right after login (no refresh needed)', /My submissions/.test(await text(page)));

    // Form errors
    await page.goto(BASE + '/submissions/create', { waitUntil: 'networkidle0' });
    await clickText(page, '.panel-foot .btn-primary', 'Submit for review');
    await settle(page);
    const errors = await page.$$eval('.error-text', (els) => els.map((e) => e.textContent));
    ok('Empty form shows field errors inside the form', errors.length >= 2, errors.join(', '));

    // Duplicate file guard
    const input = await page.$('input[type=file][multiple]');
    await input.uploadFile(PDF);
    await wait(200);
    await input.uploadFile(PDF);
    await wait(300);
    const rows = await page.$$eval('.file-row', (els) => els.filter((e) => e.textContent.includes('new')).length);
    ok('Same file picked twice is attached only once', rows === 1, `${rows} rows`);

    // Fill and submit
    const title = `Flow Test Project ${stamp}`;
    const set = async (sel, v) => { await page.$eval(sel, (e) => { e.value = ''; }); await page.type(sel, v); };
    await set('input[placeholder="Library Book Reservation System"]', title);
    await set('input[placeholder="Juan Dela Cruz, Ana Reyes"]', 'Juan Dela Cruz, Test Partner');
    await set('input[placeholder="Prof. D. Navarro"]', 'Prof. Test Adviser');
    await set('textarea[placeholder="This study presents…"]', 'This study tests the full submission flow.');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {}), clickText(page, '.panel-foot .btn-primary', 'Submit for review')]);
    await settle(page);
    ok('Submitting redirects to My Submissions', page.url().endsWith('/submissions'), page.url());
    ok('Success toast appears', /Submitted for review/.test(await toastText(page)), await toastText(page));
    ok('New submission is listed as Pending Review', (await text(page)).includes(title));
    await wait(4500);
    ok('Toast hides by itself', !(await page.evaluate(() => document.body.innerText)).includes('Submitted for review.'));

    // Row click opens quick view; action buttons don't
    await page.evaluate(() => document.querySelector('tbody tr.clickable td')?.click());
    await wait(300);
    ok('Clicking a row opens the quick-view dialog', !!(await page.$('.dialog')));
    await page.keyboard.press('Escape');
    await wait(300);
    ok('Esc closes the dialog', !(await page.$('.dialog')));

    // Cancel via confirm dialog: Esc first (nothing happens), then confirm
    const cancelRow = async () => page.evaluate((t) => {
        const row = [...document.querySelectorAll('tbody tr')].find((r) => r.textContent.includes(t));
        [...row.querySelectorAll('button')].find((b) => b.textContent.includes('Cancel'))?.click();
    }, title);
    await cancelRow();
    await wait(300);
    const dialogTitle = await page.$eval('.dialog h2', (e) => e.textContent).catch(() => '');
    ok('Cancel opens an in-page confirm dialog (not a browser popup)', /Cancel this submission/.test(dialogTitle), dialogTitle);
    ok('Row action button did not also open the quick view', (await page.$$('.dialog')).length === 1);
    await page.keyboard.press('Escape');
    await wait(300);
    ok('Esc on the confirm dialog keeps the submission', (await text(page)).includes(title));
    await cancelRow();
    await wait(300);
    await clickText(page, '.dialog .btn-danger', 'Cancel submission');
    await settle(page);
    await wait(400);
    ok('Confirming removes the submission', !(await text(page)).includes(title));

    // Re-submit one for the coordinator test
    await page.goto(BASE + '/submissions/create', { waitUntil: 'networkidle0' });
    await (await page.$('input[type=file][multiple]')).uploadFile(PDF);
    await set('input[placeholder="Library Book Reservation System"]', title);
    await set('input[placeholder="Juan Dela Cruz, Ana Reyes"]', 'Juan Dela Cruz');
    await set('input[placeholder="Prof. D. Navarro"]', 'Prof. Test Adviser');
    await set('textarea[placeholder="This study presents…"]', 'Abstract.');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {}), clickText(page, '.panel-foot .btn-primary', 'Submit for review')]);

    // Role blocks
    for (const url of ['/reviews', '/admin/users', '/admin/projects']) {
        const res = await page.goto(BASE + url, { waitUntil: 'networkidle0' });
        ok(`Student gets 403 on ${url}`, res.status() === 403, String(res.status()));
    }
    ok('No JavaScript errors (student)', !page.errors.length, page.errors.join(' | '));
    await ctx.close();
}

/* 4. Coordinator reviews */
{
    const { ctx, page } = await session();
    await login(page, 'msantos@olfu.edu.ph');
    await page.goto(BASE + '/reviews', { waitUntil: 'networkidle0' });
    const opened = await page.evaluate((t) => {
        const row = [...document.querySelectorAll('tbody tr')].find((r) => r.textContent.includes(t));
        row?.querySelector('a.btn-primary')?.click();
        return !!row;
    }, `Flow Test Project ${stamp}`);
    ok('New submission appears in the coordinator\'s queue', opened);
    await page.waitForNetworkIdle({ idleTime: 400 }).catch(() => {});
    await clickText(page, 'button', 'Request resubmission');
    await settle(page);
    const fbErr = await page.$eval('.panel .error-text', (e) => e.textContent).catch(() => '');
    ok('Returning without feedback shows the error inside the review box', /feedback/i.test(fbErr), fbErr);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {}), clickText(page, 'button', 'Approve')]);
    await settle(page);
    ok('Approve returns to the queue with a toast', page.url().endsWith('/reviews') && /Approved/.test(await toastText(page)), await toastText(page));
    await clickText(page, '.pill', 'History');
    await wait(400);
    ok('History pill shows the decision', (await text(page)).includes(`Flow Test Project ${stamp}`));
    ok('No JavaScript errors (coordinator)', !page.errors.length, page.errors.join(' | '));
    await ctx.close();
}

/* 5–7. Admin */
{
    const { ctx, page } = await session();
    await login(page, 'jcalim@olfu.edu.ph');
    await page.goto(BASE + '/admin/users', { waitUntil: 'networkidle0' });
    const selfDisabled = await page.evaluate(() => {
        const row = [...document.querySelectorAll('tbody tr')].find((r) => r.textContent.includes('You'));
        return [...row.querySelectorAll('button')].filter((b) => /Deactivate|Role/.test(b.textContent)).every((b) => b.disabled);
    });
    ok('Admin cannot deactivate or demote themselves (buttons disabled)', selfDisabled);

    await clickText(page, '.topbar button', 'Add user');
    await wait(300);
    await page.type('.dialog input[placeholder="Prof. Maria Santos"]', `Flow User ${stamp}`);
    await page.type('.dialog input[type=email]', `flow${stamp}@olfu.edu.ph`);
    await clickText(page, '.dialog button', 'Create account');
    await settle(page);
    await wait(400);
    const pwTitle = await page.$eval('.dialog h2', (e) => e.textContent).catch(() => '');
    ok('Creating a user shows the one-time password dialog', /Account created/.test(pwTitle), pwTitle);
    await page.keyboard.press('Escape');
    await wait(300);
    ok('Esc closes the password dialog', !(await page.$('.dialog')));
    ok('New user is in the table', (await text(page)).includes(`Flow User ${stamp}`));

    // Settings survive reload; icons don't move when collapsing
    const iconX = () => page.$$eval('.sb-item svg', (els) => els.slice(0, 4).map((e) => Math.round(e.getBoundingClientRect().left)));
    const before = await iconX();
    await page.click('.collapse-toggle');
    await wait(400);
    const after = await iconX();
    ok('Sidebar icons stay in place when collapsing', JSON.stringify(before) === JSON.stringify(after), `${before} vs ${after}`);
    const darkBefore = await page.evaluate(() => document.documentElement.classList.contains('dark'));
    await page.click('.topbar .icon-btn[aria-label^="Switch to"]');
    await page.reload({ waitUntil: 'networkidle0' });
    const state = await page.evaluate(() => ({ dark: document.documentElement.classList.contains('dark'), collapsed: !!document.querySelector('.shell.collapsed') }));
    ok('Dark mode and collapsed sidebar are remembered after reload', state.dark === !darkBefore && state.collapsed, JSON.stringify(state));

    // Logout
    await page.click('.sb-foot button:last-child');
    await page.waitForNavigation({ waitUntil: 'networkidle0' }).catch(() => {});
    ok('Logout returns to the login page', page.url().includes('/login'), page.url());
    await page.goto(BASE + '/dashboard', { waitUntil: 'networkidle0' });
    ok('After logout, /dashboard asks you to log in again', page.url().includes('/login'), page.url());
    ok('No JavaScript errors (admin)', !page.errors.length, page.errors.join(' | '));
    await ctx.close();
}

await browser.close();
const failed = results.filter((r) => !r.pass);
console.log(`\n${results.length - failed.length}/${results.length} checks passed.`);
process.exit(failed.length ? 1 : 0);
