# OLFU CCS Academic Repository

A Laravel 12 + Inertia.js + Vue 3 repository for academic works from the OLFU College of Computer Studies.

## Current foundation

- Student registration and session authentication.
- Role-aware navigation for `student`, `coordinator`, and `administrator`.
- Project metadata and multi-file upload validation.
- Private storage for pending, returned, and hidden submissions.
- Published repository search by title, description, program, and technology.
- Coordinator moderation decisions with reason and persistent history.
- Secure, authorization-checked file downloads.
- Responsive OLFU-branded Inertia dashboards.

## Local setup

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

The default local database is SQLite. Configure MySQL in `.env` when deploying.

## Roles

New registrations are students. Administrator-created account management and coordinator assignment should be used for staff accounts; role values are `student`, `coordinator`, and `administrator`.

The Research Coordinator workflow is content moderation only. It does not include academic grading or capstone evaluation.
