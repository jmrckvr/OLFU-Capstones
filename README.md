# CCS Capstone Repository

A web system for the College of Computer Studies, Our Lady of Fatima University, where students submit capstone projects, the Research Coordinator reviews them, and everyone signed in can browse the approved collection.

Built with **Laravel 12** (PHP) + **Vue 3** (through Inertia), and stores its data in **MySQL from XAMPP**, so you can open every table in **phpMyAdmin**.

| Role | Can do |
|---|---|
| Student | Submit projects (draft → submit), edit and resubmit returned work, browse the repository |
| Research Coordinator | Review queue: approve, or return with feedback; post announcements |
| Administrator | Everything above + manage all projects, users (roles, activate/deactivate, reset password), activity log, CSV export |

---

## Run it on your PC (XAMPP)

**You need:** [XAMPP](https://www.apachefriends.org/) (PHP 8.2+ and MySQL), [Composer](https://getcomposer.org/), [Node.js](https://nodejs.org/) 20+, and Google Chrome (only for the demo images and the browser checks). All free.

1. **Start MySQL**: open the XAMPP Control Panel → click **Start** next to *MySQL* (and *Apache* if you want phpMyAdmin).
2. **Create the database**: open <http://localhost/phpmyadmin> → *New* → name it `ccs_repository` → *Create*.
   (Or in a terminal: `C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE ccs_repository"`.)
3. **Install and set up** (in the project folder):
   ```bash
   composer install
   npm install
   copy .env.example .env        # already points to XAMPP MySQL: root, no password
   php artisan key:generate
   npm run seed:demo             # builds the tables and fills them with demo data
   ```
4. **Start the app**, each command in its own terminal:
   ```bash
   php artisan serve             # the app → http://localhost:8000
   npm run dev                   # live CSS/JS while you edit (keep it running)
   ```
5. Open **<http://localhost:8000>** and click a demo button on the login card.

> If port 8000 is taken by another project, use `php artisan serve --port=8001`.

### Demo accounts (password: `password`)

| Role | Email |
|---|---|
| Student | juan.delacruz@olfu.edu.ph |
| Research Coordinator | msantos@olfu.edu.ph |
| Administrator | jcalim@olfu.edu.ph |

The one-click demo buttons on the login card appear only while `DEMO_LOGIN=true` in `.env`. Set it to `false` for real use.

### Demo data: `npm run seed:demo`

Safe to run as many times as you like. It:
- draws a cover illustration (SVG → PNG) and a short documentation PDF for each sample project into `database/demo/` (only when missing; they are committed to git; add `--force` to `node scripts/demo-assets.mjs` to redraw);
- creates the tables if needed (`php artisan migrate`);
- **rebuilds only the demo accounts' records**: 9 users, 15 projects in every status, reviews, notifications, announcements and a week of activity, so the dashboard chart looks alive. Anything real users made is left alone.

The sample projects themselves are listed in `database/demo/projects.json`. Edit that file to change them.

---

## Checking that it works

| Command | What it checks |
|---|---|
| `php artisan test` | 24 backend tests: every security rule (roles, URL guards, admin lock-out, restricted files, rate limit, no email leaks) and the submit → review → approve cycle. Uses a throwaway in-memory database, never your MySQL data. |
| `npm run build` | The front-end compiles. |
| `npm run test:ui` | Opens every page as each role in headless Chrome at 1280×720, 1440×900, 1920×1080 (light + dark) and phone width. Fails if the window scrolls on desktop, anything sticks out sideways, a label wraps to two lines, or the console shows an error. Screenshots: `storage/app/ui-check/`. |
| `npm run test:flows` | Clicks through the main workflows like a person: submit, cancel with the confirm dialog, Esc to close, coordinator review, admin user management, settings kept after reload, logout. Run `npm run seed:demo` afterwards to reset. |

`test:ui` and `test:flows` need the app running (`php artisan serve`). Set `BASE_URL` if it isn't on port 8000.

---

## Where things are (worth reading first)

| File | What it is |
|---|---|
| `routes/web.php` | Every URL and **who may open it** (`role:` guards). Start here. |
| `app/Policies/ProjectPolicy.php` | The rules for viewing, editing, deleting and reviewing a project, all in one short file. |
| `app/Http/Controllers/` | One controller per page area (`SubmissionController`, `ReviewController`, `Admin/…`). |
| `app/Support/ProjectSaver.php` | Saves the Add/Edit project form, including uploads and duplicate-file protection. |
| `database/migrations/2026_11_01_000001_create_repository_tables.php` | The tables you see in phpMyAdmin, with comments. |
| `resources/js/Layouts/AppLayout.vue` | Sidebar + top bar around every page. |
| `resources/js/Components/` | Shared pieces: `DataTable` (sort + pages), `Modal` (dialogs), `BarChart`, toasts, confirm dialog. |
| `resources/css/polish.css` | The whole look in one commented file: color variables (light + dark), layout, animations. |
| `tests/Feature/SecurityTest.php` | One test per security fix. |

### How the "no page scrolling" layout works
On screens at least 901×620 the window itself never scrolls. Panels marked `.fill` take the leftover height, and their `.scroll` part scrolls inside the panel (see section 3 of `polish.css`). On phones the page scrolls normally.

### Settings stored in the browser
Dark mode and the collapsed sidebar are saved in `localStorage` (`ccs.theme`, `ccs.sidebar`) for that browser only.
