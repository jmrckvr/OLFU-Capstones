/*
 * Draws the demo pictures and documents for `npm run seed:demo`.
 *   - database/demo/covers/<slug>.svg + .png   cover illustration per project (drawn with SVG, saved as PNG)
 *   - database/demo/docs/<slug>.pdf            a short "documentation" PDF per project
 * The files are committed to git, so this only redraws when a file is missing.
 * Run with --force to redraw everything. Uses the Chrome installed on this PC (free, offline).
 */
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import puppeteer from 'puppeteer-core';
import { chromePath } from './chrome.mjs';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const demo = join(root, 'database', 'demo');
const data = JSON.parse(readFileSync(join(demo, 'projects.json'), 'utf8'));
const force = process.argv.includes('--force');

mkdirSync(join(demo, 'covers'), { recursive: true });
mkdirSync(join(demo, 'docs'), { recursive: true });

const todo = data.projects.filter((p) => force
    || !existsSync(join(demo, 'covers', `${p.slug}.png`))
    || (p.status !== 'draft' && !existsSync(join(demo, 'docs', `${p.slug}.pdf`))));

if (!todo.length) {
    console.log('Demo images and PDFs already exist (use --force to redraw).');
    process.exit(0);
}

/* ---------- Cover illustrations ---------- */
const palettes = {
    'Information System': ['#6b0808', '#a33a2a', '#f3d9b1'],
    'Web Application': ['#173f6b', '#2f6db5', '#cfe3f7'],
    'Mobile Application': ['#2f4a1f', '#5b8a3a', '#dcefc9'],
    'IoT / Embedded': ['#3d2a5c', '#6b4fa0', '#e2d8f3'],
    'Game Development': ['#7a3b00', '#c26a10', '#fde3bf'],
    'Data Science / AI': ['#0f4a52', '#1f8090', '#cdeef2'],
};
const hash = (s) => [...s].reduce((h, c) => (h * 31 + c.charCodeAt(0)) >>> 0, 7);

function art(kind, light, mid) {
    const card = `fill="#fff" fill-opacity=".95"`;
    switch (kind) {
        case 'database': return `
            <g transform="translate(300 95)">
              ${[0, 1, 2].map((i) => `<g transform="translate(0 ${i * 70})"><ellipse cx="100" cy="30" rx="100" ry="28" fill="${light}"/><rect x="0" y="30" width="200" height="50" fill="${light}"/><ellipse cx="100" cy="80" rx="100" ry="28" fill="${light}" opacity=".85"/><ellipse cx="100" cy="30" rx="100" ry="28" fill="#fff" opacity=".55"/></g>`).join('')}
            </g>
            <g transform="translate(520 150) rotate(6)"><rect width="150" height="190" rx="12" ${card}/><rect x="20" y="24" width="80" height="12" rx="6" fill="${mid}"/>${[0, 1, 2, 3, 4, 5].map((i) => `<rect x="20" y="${56 + i * 20}" width="${110 - (i % 3) * 18}" height="8" rx="4" fill="${mid}" opacity=".35"/>`).join('')}</g>`;
        case 'chip': return `
            <g transform="translate(290 115)">
              ${[0, 1, 2, 3, 4, 5].map((i) => `<rect x="${32 + i * 28}" y="-22" width="10" height="26" rx="3" fill="${light}"/><rect x="${32 + i * 28}" y="216" width="10" height="26" rx="3" fill="${light}"/><rect x="-22" y="${32 + i * 28}" width="26" height="10" rx="3" fill="${light}"/><rect x="216" y="${32 + i * 28}" width="26" height="10" rx="3" fill="${light}"/>`).join('')}
              <rect width="220" height="220" rx="22" fill="#fff" opacity=".95"/>
              <rect x="50" y="50" width="120" height="120" rx="14" fill="${mid}"/>
              <circle cx="110" cy="110" r="22" fill="${light}"/>
            </g>
            <g fill="none" stroke="${light}" stroke-width="10" stroke-linecap="round" opacity=".9">
              <path d="M600 150 a70 70 0 0 1 0 150"/><path d="M640 115 a115 115 0 0 1 0 220"/></g>`;
        case 'phone': return `
            <g transform="translate(320 50)">
              <rect width="170" height="340" rx="28" fill="#1c1617"/><rect x="10" y="12" width="150" height="316" rx="20" fill="#fff"/>
              <rect x="26" y="40" width="118" height="70" rx="12" fill="${mid}"/>
              ${[0, 1, 2].map((i) => `<rect x="26" y="${126 + i * 60}" width="118" height="48" rx="10" fill="${light}"/><circle cx="48" cy="${150 + i * 60}" r="11" fill="${mid}" opacity=".6"/><rect x="66" y="${142 + i * 60}" width="64" height="8" rx="4" fill="${mid}" opacity=".5"/>`).join('')}
            </g>
            <g transform="translate(540 120) rotate(-6)"><rect width="150" height="150" rx="16" ${card}/><rect width="150" height="38" rx="16" fill="${mid}"/><rect y="22" width="150" height="16" fill="${mid}"/>${[0, 1, 2, 3, 4, 5, 6, 7, 8].map((i) => `<rect x="${18 + (i % 3) * 42}" y="${54 + Math.floor(i / 3) * 30}" width="30" height="20" rx="5" fill="${i === 4 ? mid : light}"/>`).join('')}</g>`;
        case 'browser': return `
            <g transform="translate(170 70)">
              <rect width="460" height="300" rx="18" fill="#fff" opacity=".97"/>
              <rect width="460" height="40" rx="18" fill="${light}"/><rect y="22" width="460" height="18" fill="${light}"/>
              <circle cx="26" cy="20" r="7" fill="${mid}"/><circle cx="48" cy="20" r="7" fill="${mid}" opacity=".6"/><circle cx="70" cy="20" r="7" fill="${mid}" opacity=".35"/>
              <rect x="20" y="60" width="100" height="220" rx="10" fill="${light}" opacity=".7"/>
              ${[0, 1, 2].map((i) => `<rect x="${140 + i * 102}" y="60" width="90" height="60" rx="10" fill="${light}"/><rect x="${152 + i * 102}" y="76" width="40" height="10" rx="5" fill="${mid}"/>`).join('')}
              ${[60, 100, 80, 130, 110, 150, 120].map((h, i) => `<rect x="${150 + i * 40}" y="${270 - h}" width="24" height="${h}" rx="5" fill="${mid}" opacity="${0.45 + i * 0.07}"/>`).join('')}
            </g>`;
        case 'network': {
            const layers = [[150, 3], [300, 5], [450, 5], [600, 2]];
            const pts = layers.map(([x, n]) => Array.from({ length: n }, (_, i) => [x + 50, 225 + (i - (n - 1) / 2) * 62]));
            const lines = pts.slice(0, -1).flatMap((layer, li) => layer.flatMap(([x1, y1]) => pts[li + 1].map(([x2, y2]) => `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}"/>`))).join('');
            return `<g stroke="#fff" stroke-opacity=".45" stroke-width="2.5">${lines}</g>
                ${pts.flat().map(([x, y], i) => `<circle cx="${x}" cy="${y}" r="20" fill="#fff"/><circle cx="${x}" cy="${y}" r="10" fill="${i % 3 ? mid : light}"/>`).join('')}`;
        }
        case 'gamepad': return `
            <g transform="translate(230 110)">
              <path d="M90 0 H250 C320 0 350 60 360 120 C372 190 350 240 310 240 C275 240 260 200 230 185 H110 C80 200 65 240 30 240 C-10 240 -32 190 -20 120 C-10 60 20 0 90 0 Z" fill="#fff"/>
              <rect x="62" y="80" width="70" height="22" rx="6" fill="${mid}"/><rect x="86" y="56" width="22" height="70" rx="6" fill="${mid}"/>
              <circle cx="250" cy="70" r="15" fill="${mid}"/><circle cx="285" cy="104" r="15" fill="${light}"/><circle cx="215" cy="104" r="15" fill="${light}"/><circle cx="250" cy="138" r="15" fill="${mid}" opacity=".6"/>
            </g>
            ${[[150, 80, 16], [650, 90, 12], [680, 330, 18], [130, 340, 10]].map(([x, y, r]) => `<path transform="translate(${x} ${y})" d="M0 ${-r} L${r * 0.3} ${-r * 0.3} L${r} 0 L${r * 0.3} ${r * 0.3} L0 ${r} L${-r * 0.3} ${r * 0.3} L${-r} 0 L${-r * 0.3} ${-r * 0.3} Z" fill="#fff" opacity=".85"/>`).join('')}`;
        default: return '';
    }
}

function coverSvg(p) {
    const [dark, mid, light] = palettes[p.category] ?? palettes['Web Application'];
    const h = hash(p.slug);
    const blobs = Array.from({ length: 5 }, (_, i) => {
        const x = (h >> (i * 3)) % 800, y = (h >> (i * 2 + 1)) % 450, r = 60 + ((h >> i) % 90);
        return `<circle cx="${x}" cy="${y}" r="${r}" fill="#fff" opacity="${0.04 + (i % 3) * 0.025}"/>`;
    }).join('');
    return `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450" viewBox="0 0 800 450">
  <defs><linearGradient id="g-${p.slug}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${dark}"/><stop offset="1" stop-color="${mid}"/></linearGradient></defs>
  <rect width="800" height="450" fill="url(#g-${p.slug})"/>${blobs}
  <path d="M0 380 C 200 340 380 400 560 370 S 760 340 800 350 V450 H0 Z" fill="#000" opacity=".12"/>
  ${art(p.art, light, mid)}
</svg>`;
}

/* ---------- Documentation PDF ---------- */
const esc = (s = '') => s.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
function docHtml(p) {
    const paras = (p.abstract || p.description).split('\n').filter(Boolean).map((t) => `<p>${esc(t)}</p>`).join('');
    return `<!doctype html><html><head><meta charset="utf-8"><style>
      @page { size: A4; margin: 22mm 20mm; }
      body { font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.6; color: #111; }
      .cover { height: 245mm; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; page-break-after: always; }
      .cover img { width: 120mm; border-radius: 4mm; margin: 10mm 0; }
      .school { font-size: 13pt; font-weight: bold; letter-spacing: .5pt; }
      h1 { font-size: 20pt; margin: 4mm 0; color: #6b0808; }
      h2 { font-size: 14pt; margin-top: 10mm; color: #6b0808; border-bottom: 1px solid #ccc; padding-bottom: 2mm; }
      p { text-align: justify; text-indent: 10mm; margin: 0 0 4mm; }
      .meta { font-size: 11pt; } .small { font-size: 10pt; color: #555; }
    </style></head><body>
      <div class="cover">
        <div class="school">OUR LADY OF FATIMA UNIVERSITY</div><div>College of Computer Studies · Quezon City</div>
        <img src="data:image/svg+xml;base64,${Buffer.from(coverSvg(p)).toString('base64')}">
        <h1>${esc(p.title)}</h1>
        <div class="meta">A Capstone Project Presented to the Faculty of the College of Computer Studies</div>
        <div class="meta" style="margin-top:8mm">${esc(p.proponents)}</div>
        <div class="meta">${esc(p.program)} · Academic Year ${esc(p.academic_year)}</div>
        <div class="meta">Adviser: ${esc(p.adviser || 'TBA')}</div>
        <div class="small" style="margin-top:14mm">Sample document generated for the CCS Capstone Repository demo.</div>
      </div>
      <h2>Abstract</h2>${paras}
      <h2>Chapter 1 · Introduction</h2>
      <p>${esc(p.description)} This chapter describes the background of the study, the problems observed in the current process, and the objectives the proponents set out to achieve.</p>
      <p>The study uses ${esc(p.technology || 'modern web technologies')} and follows an iterative development model with regular consultation with the adviser and target users.</p>
      <h2>Chapter 2 · Methodology</h2>
      <p>Requirements were gathered through interviews and observation. The system was designed using use-case diagrams, an entity-relationship diagram and wireframes, then built and tested in short cycles.</p>
      <h2>Chapter 3 · Results and Conclusion</h2>
      <p>User acceptance testing with representative users rated the system highly for functionality and ease of use. The proponents recommend further work on reporting, mobile support and integration with existing campus systems.</p>
    </body></html>`;
}

const browser = await puppeteer.launch({ executablePath: chromePath(), headless: true, args: ['--no-first-run', '--disable-gpu'] });
const page = await browser.newPage();
for (const p of todo) {
    const svg = coverSvg(p);
    writeFileSync(join(demo, 'covers', `${p.slug}.svg`), svg);
    await page.setViewport({ width: 800, height: 450, deviceScaleFactor: 1 });
    await page.setContent(`<html><body style="margin:0">${svg}</body></html>`);
    await page.screenshot({ path: join(demo, 'covers', `${p.slug}.png`), clip: { x: 0, y: 0, width: 800, height: 450 } });

    if (p.status !== 'draft') {
        await page.setContent(docHtml(p), { waitUntil: 'load' });
        await page.pdf({ path: join(demo, 'docs', `${p.slug}.pdf`), format: 'A4', printBackground: true });
    }
    console.log(`  drew ${p.slug}`);
}
await browser.close();
console.log(`Done: ${todo.length} project(s).`);
