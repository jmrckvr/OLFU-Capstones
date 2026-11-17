// Finds Google Chrome (or Edge) already installed on this PC, for puppeteer-core.
// Set CHROME_PATH to override. Nothing is downloaded.
import { existsSync } from 'node:fs';

const candidates = [
    process.env.CHROME_PATH,
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    `${process.env.LOCALAPPDATA}/Google/Chrome/Application/chrome.exe`,
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
];

export function chromePath() {
    const found = candidates.find((p) => p && existsSync(p));
    if (!found) throw new Error('Chrome not found. Install Google Chrome or set CHROME_PATH to chrome.exe.');
    return found;
}
