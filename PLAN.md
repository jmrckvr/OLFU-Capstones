# CCS Capstone Repository: polish plan

Phase 1 audit result. **Nothing has been changed yet.** Each bug lists its file and line, most severe first.
"Confirmed" means I reproduced it. The rest come from reading the code.

---

## 1. Bugs

### Critical: the system can't be demoed as-is
| # | Bug | Where |
|---|-----|-------|
| 1 | **Uploading a project crashes.** The code saves to a disk named `private`, but no such disk exists (`Disk [private] does not have a configured driver`). Downloads would crash the same way. *Confirmed.* | `app/Http/Controllers/ProjectController.php:49`, `:63` / `config/filesystems.php:31` |
| 2 | **Nobody can reach the coordinator or admin side.** Registering always makes a student, and the seeder only makes "Test User". So moderation is unreachable unless you edit the database by hand. | `app/Http/Controllers/AuthController.php:44`, `database/seeders/DatabaseSeeder.php:20` |

### High: security and privacy
| # | Bug | Where |
|---|-----|-------|
| 3 | **Every uploader's email is sent to every logged-in user.** `with('owner')` sends the whole user record (email, role, dates) to the browser on the dashboard, repository and moderation pages. | `DashboardController.php:24`, `ProjectController.php:14`, `ModerationController.php:14` |
| 4 | **No login rate limit.** A script can guess passwords forever. | `routes/web.php:12` |
| 5 | **`role` is mass-assignable.** It's safe today only because `validate()` drops extra fields. The planned "My Account" page would let a student send `role=administrator` and promote themselves. I'll remove it and add a test. | `app/Models/User.php:25` |
| 6 | **"Return" and "Hide" accept an empty reason, and the student never sees the reason anyway.** The template requires feedback when asking for a resubmission. | `ModerationController.php:22`, `Pages/Dashboard.vue` (reason not shown) |

### High: things that silently fail
| # | Bug | Where |
|---|-----|-------|
| 7 | **Validation errors are never shown** on Upload or Register. A wrong file type or a taken email just does nothing. | `Pages/Repository/Create.vue:9`, `Pages/Auth/Register.vue:11-14` |
| 8 | **Success and error messages are never shown.** The server sends them (`flash`), but no component displays them, so "Your work was submitted" is lost. | `HandleInertiaRequests.php:17` + `Layouts/AppLayout.vue` |
| 9 | **The repository shows only the first 12 projects.** The server paginates, but there are no page buttons. | `Pages/Repository/Index.vue:13` |

### Medium
| # | Bug | Where |
|---|-----|-------|
| 10 | **Sidebar links aren't wired.** "My Uploads", "System Analytics", "User Management" and "Activity Logs" all open the dashboard, and "Moderation History" opens the queue. There's also no highlight for the current page. | `Layouts/AppLayout.vue:10-17` |
| 11 | **Browser popup:** `window.prompt()` asks for the moderation reason. | `Pages/Moderation/Index.vue:5` |
| 12 | **Files can never be downloaded from the UI.** The download route exists, but there's no project detail page that links to it. | `Pages/Repository/Index.vue` |
| 13 | **Oversized response:** the queue loads every file row just to show "3 files". Use `withCount` instead. | `ModerationController.php:14` |
| 14 | **Hidden projects stay in the queue forever,** and there's no history view even though a history table exists. | `ModerationController.php:14` |
| 15 | **Students see global stats**, such as the total registered users and everyone's pending count. | `DashboardController.php:14-19` |
| 16 | **Register button isn't disabled while sending**, so a double-click submits twice. The login form has a `remember` field with no checkbox. | `Register.vue:15`, `Login.vue:3` |
| 17 | **Timezone is UTC.** Every date shown in the Philippines would be 8 hours off. | `config/app.php:68` |
| 18 | **Raw status words** like "pending" and "hidden" show in the queue, while the dashboard uses different labels. | `Moderation/Index.vue:8`, `Dashboard.vue:5` |

### Low / cleanup
- The `reports` table has no model and no screen (`migrations/2026_11_01_000001...:48`).
- `featured` is displayed but nothing can set it.
- Academic year and category are free text, so filters can't work reliably (`Create.vue:9`).
- `welcome.blade.php` is unused, and `README.md` is empty.

### Checked and OK
- Admin and coordinator pages **are** blocked by URL (`role:` middleware, `web.php:22-29`). A student who opens `/moderation` gets 403.
- Download checks the project owner and role, and only finds files inside that project.
- Register can't inject a role today (see #5 for why it's still fixed).
- No client-side store exists, so nothing stale survives logout.
- No duplicate-item flows exist yet. I'll guard the new ones (double-submit, double upload).

---

## 2. Real gaps: what the template (PDF) has that this app doesn't

| Template page | Today | Plan |
|---|---|---|
| Public home: hero search, How it works, About, Announcements | Redirects straight to login | Add the public **Home** page |
| Login with demo-account buttons | Plain login | Add demo buttons (Student / Coordinator / Admin) |
| Repository with **year + category** filters | Search + free-text program | Dropdown filters, a "Clear filters" button, paging |
| **Project detail**: abstract, files, "Access denied" for restricted projects | Missing | New page |
| **My Submissions**: Draft / Pending / Resubmission, Edit, Submit, Cancel | Missing | New page |
| **Add project** fields: proponents, adviser, abstract, source link, Save as draft | Missing | New fields + drafts |
| **Review page** with required feedback | `prompt()` popup | In-page review dialog/page |
| Admin **Projects**: view, edit, delete | Missing | New page |
| Admin **Users**: activate/deactivate, reset password | Missing | New page (an admin can't deactivate or demote themselves, with a test) |
| **Notifications** + post announcement | Missing | New page + bell count |
| **My Account**: name, change password | Missing | New page |

**Status names change to match the template:**
`draft` → Draft, `pending` → Pending Review, `returned` → **Resubmission Required**, `published` → **Approved**.
The old `hidden` status becomes a **Restricted** on/off flag: the project is still listed, but its files are for the coordinator and admin only, like page 6 of the PDF.

---

## 3. Backend: XAMPP + MySQL + phpMyAdmin

XAMPP is already installed on this PC (MariaDB 10.4, running on port 3306, with phpMyAdmin).
- Switch `.env` and `.env.example` to `DB_CONNECTION=mysql`, database `ccs_repository`, user `root`, no password (XAMPP's default).
- The database can be created with one command or one click in phpMyAdmin. Then you can see every table at `http://localhost/phpmyadmin`.
- Tests keep using a throwaway in-memory database, so running tests never touches your real data.
- The README gets a step-by-step "Run it with XAMPP" section.

---

## 4. Animations (all turned off under `prefers-reduced-motion`)
- Page switch, cards, dialogs and toasts fade 0→100% while rising 8px, ease-out, 220ms. No bounce.
- Table rows and list items: the same, staggered 30ms apart, with fade-out on remove.
- Dashboard: chart bars grow up from the bottom, and stat numbers count up from 0.
- A thin maroon progress bar at the very top of the page replaces "Loading…" text.

## 5. UI polish
Based on the template's look (maroon `#6B0808` brand, cream page, white bordered panels, Roboto-style type), with these improvements:
- **Layout:** collapsible sidebar (icons only, remembered). Icons and the toggle stay in place, and only the width and label fade change. The top bar holds the page title, breadcrumb and page actions. On desktop the page never scrolls; tables and lists scroll inside their own panel with sticky headers.
- **Components:** pill switches instead of tabs, compact stat tiles, status pills, search + "Clear filters", dashed "Add" cards, in-page dialogs (no browser popups), initials avatars, empty states everywhere, toasts that hide themselves, and dialog errors shown inside the dialog.
- **Tables:** sortable and paged. Clicking a row opens a quick-view dialog. Row buttons don't trigger the row click.
- **Dark mode** toggle (remembered). Every color becomes a variable, so nothing is hard-coded.
- **Readability:** gray text that passes contrast, small text 11–13px with some weight, no label that wraps to 2 lines.
- All new CSS goes in **one commented file**, `resources/css/polish.css`, loaded last.

## 6. Demo data
`npm run seed:demo` can be run twice safely. It creates:
- the 3 demo accounts from the template (Juan Dela Cruz / Prof. Maria Santos / Jeffrey F. Calim) + a few students
- ~14 projects in every status, with cover illustrations (drawn as SVG → PNG files saved in the repo)
- a week of reviews, notifications and an announcement, so the dashboard chart looks alive

## 7. Idea bank (pick any; none are needed for the core)
**Small and high value**
- A. "Copy citation" button on the project page (APA format)
- B. Project cover illustration on cards and the detail page
- C. Keyboard shortcut `/` to jump to search
- D. Download counter per project

**Bigger**
- E. Coordinator dashboard chart: submissions per week + review time
- F. CSV export of the project list (admin)
- G. Activity log page (admin): who approved, edited or deleted what
- H. PDF preview inside the project page (no download needed)

---

## 8. Verification (Phase 4)
`npm run build`, `php artisan test` (a test for every security fix), then headless Chrome checks on every page at
1280×720, 1440×900 and 1920×1080 (light + dark) and 390px phone width: no page scroll, no sideways overflow, no wrapped labels, no console errors.
Plus an interaction script: log in as each role, submit → review → approve, dialogs + Esc, role blocks, settings kept after a reload.
I'll look at the screenshots myself for spacing and overlap issues.

**Structure:** keep the current folders (Pages / Layouts) and add `Components/` for shared pieces (Dialog, Toast, StatusPill…). No big reshuffle.
