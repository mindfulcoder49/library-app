# Check It Out - DEI Book Sharing Platform

A Laravel 12 + Inertia + Vue application for internal Diversity, Equity, and Inclusion book sharing.

This project supports:
- Lenders sharing books with coworkers.
- Borrowers requesting and returning books.
- Librarian/admin verification workflows.
- Bulk CSV ingestion with dry-run preview.
- Faceted catalog search with pagination.
- Office-aware reporting for DEI program visibility.

## Tech Stack

- Backend: Laravel 12, PHP 8.2+
- Frontend: Inertia.js + Vue 3 + Tailwind CSS
- Build tool: Vite `4.5.3` (pinned for shared-host compatibility)
- Auth scaffold: Laravel Breeze (Vue)
- Local containers: Laravel Sail (MySQL, Redis, Mailpit)

## Why Vite Is Pinned

This app intentionally pins Vite to `4.5.3` and plugin versions compatible with older Node runtimes commonly found on shared hosting.

Pinned dev dependencies in `package.json`:
- `vite: 4.5.3`
- `@vitejs/plugin-vue: 4.5.2`
- `laravel-vite-plugin: 0.8.1`

If these are upgraded, production builds may fail on hosts that do not run modern Node versions.

## Core Features

### Authentication and Profiles

- Registration captures:
  - First name, last name, employee ID, email
  - Primary office location
  - Share locations (multi-office sharing)
  - Lender/borrower role preferences
  - Lender/borrower guideline consent
- Profile editing supports updating all registration-time fields.

### Book and Catalog Management

- Add single books manually.
- Bulk import books from CSV with:
  - `Preview (Dry Run)` mode
  - row-level import error reporting
  - import summary and sampled preview rows
- Book metadata includes title, ISBN, author(s), category, language, type, description.
- Each uploaded copy is a `BookItem` owned by a lender.

### Verification Queue (Admin/Site Owner)

- Dedicated pending verification page.
- Full book metadata visible in queue cards.
- Multi-select controls:
  - Select all
  - Deselect all
  - Verify all selected
- Single-item actions:
  - Verify and publish
  - Edit
  - Remove

### Browse Catalog

- Pagination enabled.
- Faceted filtering with counts:
  - Category
  - Office
  - Language
  - Book type
- Search supports:
  - `q` across title, ISBN-10, ISBN-13, description, book type, and author
  - direct title/author filters
- Browse results include:
  - full book metadata
  - lender and office
  - lender comments
  - Amazon search link based on ISBN

### Loan Workflow

- Borrower can request available books.
- Lender can approve/reject requests.
- Lender can mark handoff/shared.
- Borrower or lender can mark returned/cancelled.
- Waiting list entries are created when requesting unavailable books.

### Admin Operational Controls

Admins and site owners can:
- Verify books individually or in bulk.
- Edit any book item.
- Remove any book item.
- Move any item back to `pending_verification`.
- View all active requests across the system.
- View all active current loans across the system.

## Role and Permission Model

### Standard user

- Can edit own profile.
- Can add/import own books.
- Can edit own books.
- Can remove/reshelve own books.
- Can request books if borrower role enabled.
- Can manage own borrowing/lending lifecycle actions.

### Administrator or Site Owner

- All standard-user capabilities.
- Can verify queue items (single and bulk).
- Can edit/remove any book item.
- Can move any book item to pending verification.
- Can view global loan requests and global current loans.

## Guidelines (Business Rules)

### Guidelines for Lenders

- Willing to share the books offered
- Share only books that are in good shape
- Share books about Diversity, Inclusion & Equity or written by Diverse & Underrepresented Authors
- Contact the Borrower in a timely manner and exchange the book

### Guidelines for Borrowers

- Treat the books borrowed respectfully
- Agree with the Lender a time and place to exchange the book
- Return the book on time or request an extension

## Data Model Overview

Primary models:
- `User`
- `Book`
- `BookItem`
- `Author`
- `Category` (tiered)
- `Language`
- `Country`, `City`, `OfficeLocation`
- `Loan`
- `WaitingListEntry`

Important relationships:
- `Book` belongs to `Category`, `Language`, and has many `Author` via pivot.
- `BookItem` belongs to `Book` and `lender (User)`.
- `Loan` belongs to `BookItem`, `lender (User)`, `borrower (User)`.
- `User` has one primary office and many share locations.

## Statuses and Lifecycle

### Book item statuses

- `pending_verification`
- `available`
- `loan_pending`
- `checked_out`
- `removed`

Typical transitions:
- New add/import -> `pending_verification`
- Admin verify -> `available`
- Request created -> `loan_pending`
- Book shared -> `checked_out`
- Return recorded -> `available`
- Remove -> `removed`
- Admin can move back to `pending_verification`

### Loan statuses

- `requested`
- `approved`
- `shared`
- `borrowed`
- `returned`
- `cancelled`
- `rejected`

## CSV Import Specification

Import path: `Books -> Add New Book -> Bulk Upload via CSV`

Accepted delimiters:
- Comma-separated (CSV)
- Tab-separated (TSV/plain text)

Required practical data:
- At minimum, a row must include `Title` to import.

Expected columns:
- `ISBN-Emp`
- `ISBN`
- `Title`
- `Author`
- `Language`
- `Book Type`
- `Category 1`
- `Category 2`
- `Category 3`
- `Description`
- `Lender Comments`
- `Lender ID`
- `Office Locations`
- `Status`

Behavior notes:
- Non-admin users: imported rows are always assigned to the uploader; `Lender ID` is ignored.
- Admin/site owner users: `Lender ID` may be applied when provided.
- `Office Locations` supports comma-separated values (example: `OCB, CCB, JAB`).
- Dry run does not persist data and returns preview + validation feedback.

## Key Routes

Public:
- `GET /` home
- `GET /catalog` browse catalog
- `GET /guidelines` guidelines page

Authenticated:
- `GET /dashboard`
- `GET /help`
- `GET /books/mine`
- `GET /books/create`
- `GET /books/pending-verification` (admin/site owner)
- `GET /books/{bookItem}/edit`
- `POST /books`
- `PATCH /books/{bookItem}`
- `POST /books/import-csv`
- `POST /books/verify-bulk` (admin/site owner)
- `PATCH /books/{bookItem}/verify` (admin/site owner)
- `PATCH /books/{bookItem}/mark-pending` (admin/site owner)
- `PATCH /books/{bookItem}/remove`
- `PATCH /books/{bookItem}/reshelve`
- `GET /loans/borrowed`
- `GET /loans/requests`
- `POST /catalog/{bookItem}/request`
- `PATCH /loans/{loan}/approve`
- `PATCH /loans/{loan}/reject`
- `PATCH /loans/{loan}/share`
- `PATCH /loans/{loan}/return`
- `PATCH /loans/{loan}/cancel`
- `GET /reports`
- `GET/PATCH/DELETE /profile`

## Local Development

### Prerequisites

- Docker Desktop (for Sail), or local PHP/MySQL/Node stack
- PHP 8.2+
- Composer
- Node/NPM

### Quick start with Sail

```bash
cp .env.example .env
composer install
npm install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
npm run build
```

Run tests:

```bash
./vendor/bin/sail artisan test
```

### Non-Sail quick start

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

## Seeded Reference Data

`ReferenceDataSeeder` creates baseline:
- Countries: United States, United Kingdom, India
- Cities: Boston, New York, London, Bangalore
- Office locations: Boston, New York, London, Bangalore, Degreed (virtual)
- Languages: English, Spanish, French, Portuguese, Hindi
- Tiered DEI/Leadership categories

`DatabaseSeeder` creates admin user:
- Email: `admin@example.com`
- Password: `password`
- Flags: administrator + site owner enabled

Change credentials immediately outside local/dev environments.

## Deployment Notes

- Build assets using `npm run build` before production deploy.
- Keep Vite and Vue plugin pinned to versions listed above for host compatibility.
- Ensure production Node runtime supports your chosen Vite version.
- Run `php artisan config:cache` and `php artisan route:cache` in production as needed.

## Troubleshooting

### Build fails with `crypto.hash is not a function`

Cause: Using Vite 7+ with an older Node runtime.

Fix:
- Use pinned Vite 4.5.3 toolchain in this repository.
- Reinstall deps and rebuild:

```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### CSV preview/import returns errors

- Confirm file extension and MIME (`.csv` or `.txt`).
- Ensure headers match template names.
- Use quoted fields for commas in text.
- Run dry run first; inspect row-level error block shown in UI.

### Inertia page JSON appears in browser

- Verify requests are made through Inertia frontend, not direct API-style calls.
- Confirm proper `X-Inertia` headers are being sent by the app UI.
- Clear browser cache/hard refresh after frontend deploy.

## Testing

Current baseline test suite:
- Auth and profile flows
- Basic feature smoke coverage

Run:

```bash
./vendor/bin/sail artisan test
```

## Project Structure (High Value Files)

- Routes: `routes/web.php`
- Book workflows: `app/Http/Controllers/BookItemController.php`
- Catalog + facets: `app/Http/Controllers/CatalogController.php`
- Loan workflow: `app/Http/Controllers/LoanController.php`
- Reports: `app/Http/Controllers/ReportController.php`
- Registration: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Profile updates: `app/Http/Controllers/ProfileController.php`, `app/Http/Requests/ProfileUpdateRequest.php`
- Browse page: `resources/js/Pages/Catalog/Index.vue`
- Add/import page: `resources/js/Pages/Books/Create.vue`
- Verification queue: `resources/js/Pages/Books/PendingVerification.vue`
- Guidelines: `resources/js/Pages/Guidelines.vue`
- Help center: `resources/js/Pages/Help/Index.vue`
- Seeders: `database/seeders/ReferenceDataSeeder.php`, `database/seeders/DatabaseSeeder.php`

## Security and Privacy

- This is intended for internal organizational use.
- Do not commit real employee IDs or credentials.
- Rotate default credentials and app keys in production.
- Use HTTPS and secure session/cookie settings in production.

---

For enhancements (policy hardening, richer report export, ISBN metadata enrichment, queue notifications), extend controllers/services behind existing route and Inertia patterns.
