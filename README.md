# THE HANGAR — G.O.S (Gund-Order System)

A full-stack e-commerce web application for a Gundam model-kit shop, built in plain PHP + MySQL and designed to run on **XAMPP**. It includes a customer-facing storefront (browse, search, cart, promo codes, GCash QR checkout) and a complete admin panel (products, orders, sliders, promo codes, payment management).

---

## Table of Contents
1. [Features](#features)
2. [Tech Stack](#tech-stack)
3. [Requirements](#requirements)
4. [Quick Start (XAMPP)](#quick-start-xampp)
5. [Configuration](#configuration)
6. [Database](#database)
7. [Default Accounts](#default-accounts)
8. [Directory Structure](#directory-structure)
9. [User Flows](#user-flows)
10. [GCash Payment System](#gcash-payment-system)
11. [Order Fulfilment Workflow](#order-fulfilment-workflow)
12. [Admin Panel Reference](#admin-panel-reference)
13. [Security Notes](#security-notes)
14. [Development Notes](#development-notes)
15. [Go-Live Checklist](#go-live-checklist)

---

## Features

### Storefront (`/homepage/`)
- Product catalog with New Releases / Best Sellers / Model Kit flags, grades (MG/RG/PG/...), scales, brands and stock status
- Live product search (`/homepage/search/`, backed by `api_search.php`)
- Product detail pages
- Cart persisted in `localStorage` (`/homepage/cart/`)
- Promo code validation at checkout (percent or fixed-amount discounts)
- **GCash QR checkout** (scan → pay → reference number) **or Cash on Delivery**
- **Order tracking** — logged-in customers can review active / to-pay / past orders with status, courier, items and totals (`/homepage/orders/`)
- Customer login/registration (`/login/`)

### Admin Panel (`/admin/`)
- Dashboard with stats (products, pending orders) and recent activity
- Products CRUD with image upload / crop support
- Order management: list, filter, search, detail view, status workflow, **GCash payment verification**
- Homepage sliders management (sections 1, 2, 7)
- Promo code management
- **GCash & Payments**: upload/replace/remove the GCash QR image used at checkout

---

## Tech Stack

| Layer     | Technology |
|-----------|------------|
| Backend   | PHP 8+ (no framework), sessions |
| Database  | MySQL / MariaDB via **PDO** (prepared statements only) |
| Frontend  | Vanilla HTML/CSS/JavaScript (no build step) |
| Server    | Apache (XAMPP) |
| Auth      | PHP sessions, `password_hash()` / `password_verify()` |
| Payments  | GCash QR scan-to-pay (manual verification) — PSP integration reserved |

---

## Requirements

- **XAMPP** (Apache + MySQL + PHP 8.x)
- A modern browser
- No Composer, npm, or build tools required

---

## Quick Start (XAMPP)

1. Install/launch **XAMPP** and start **Apache** and **MySQL**.
2. Place the project at `C:\xampp\htdocs\TheHangar` (or clone it there).
3. Ensure `database/config.php` matches your local MySQL (defaults: `127.0.0.1:3306`, db `the_hangar_db`, user `root`, empty password — XAMPP defaults).
4. Open **http://localhost/TheHangar/** → redirects to the storefront.
   - On the first DB connection the app **creates its own database/tables and seeds demo data** (users, products, sliders, promo codes).
5. Admin panel: **http://localhost/TheHangar/admin/** (log in with a seeded admin account, see below).
6. To receive real payments, upload your GCash QR in **Admin → GCASH & PAYMENTS** (see [GCash Payment System](#gcash-payment-system)).

> Schema upgrades for databases created before the payment feature are applied automatically and idempotently on every connection (`ensureOrderPaymentColumns()` in `shared/db.php`) — no manual SQL to run.

## Configuration

All configuration lives in **`database/config.php`** (gitignored — credentials never reach version control).

### Database settings (`hangarDatabaseConfig()`)
| Key      | Default        | Notes |
|----------|----------------|-------|
| `host`   | `127.0.0.1`    | TCP is forced instead of the localhost socket |
| `port`   | `3306`         | Default MySQL/MariaDB port |
| `dbname` | `the_hangar_db`| Auto-created on first run if missing |
| `user`   | `root`         | XAMPP default |
| `pass`   | *(empty)*      | XAMPP default — **change for production** |
| `charset`| `utf8mb4`      | |

### Payment settings (`hangarPaymentConfig()`)
| Key                  | Default     | Purpose |
|----------------------|-------------|---------|
| `mode`               | `'qr'`      | `qr` (scan-to-pay, manual verify) · `stub` (dev auto-pay) · `live` (reserved for PSP) |
| `qr_image_url`       | *(empty)*   | Fallback only — the **authoritative** QR is stored in the DB `settings` table and managed from the admin panel |
| `gcash_merchant_id`  | *(empty)*   | Placeholder for a future PSP integration |
| `gcash_secret`       | *(empty)*   | Placeholder for a future PSP integration |
| `gcash_callback_url` | placeholder | Public HTTPS webhook URL for a future PSP integration |

---

## Database

Single source of truth: **`shared/db.php`**. Every page calls `getDBConnection()` (or the `getConnection()` alias), which:

1. Creates the database `the_hangar_db` if it doesn't exist
2. Creates all tables `IF NOT EXISTS` (fresh installs self-provision)
3. Seeds demo users/products/sliders/promo codes only when the tables are empty
4. Runs `ensureOrderPaymentColumns()` — idempotent migration for pre-payment-era databases

### Tables

| Table          | Purpose | Key columns |
|----------------|---------|-------------|
| `users`        | Accounts & roles | `username` (unique), `email`, `password` (bcrypt), `role` (`admin`/`user`) |
| `products`     | Catalog | `name`, `grade`, `scale`, `price`, `sold_count`, `brand`, `stock_status`, `image_url`, `is_new_release`, `is_best_seller`, `is_model_kit` |
| `sliders`      | Homepage banners | image, title/subtitle, section placement, `active` |
| `promo_codes`  | Discounts | `code` (unique), `type` (`percent`/`fixed`), `value`, `active` |
| `orders`       | Checkout results | `order_code`, `subtotal`, `discount`, `shipping`, `total`, `promo_code`, `status`, `payment_method`, `payment_status`, `payment_ref`, `gcash_ref`, `customer_name/email/phone`, `shipping_address`, `logistics` (courier), `created_at` |
| `order_items`  | Line items (price snapshots) | `order_id`, `product_id`, `name_snapshot`, `price_snapshot`, `quantity` |
| `settings`     | Key/value store (e.g. GCash QR path) | `skey` (PK), `svalue`, `updated_at` |

### Orders — status values

| Column           | Values | Meaning |
|------------------|--------|---------|
| `status`         | `pending` → `processing` → `shipped` → `completed` · `cancelled` | Fulfilment workflow (admin-controlled) |
| `payment_status` | `payment_pending` · `paid` · `cod` | QR flow saves `payment_pending`; admin verification sets `paid`; Cash on Delivery saves `cod` (settled at the door, no online verify) |
| `payment_method` | `gcash` · `cod` | Set at checkout |

Seeded promo codes: `PILOT10` (10% off) and `GUNDAM2026` (₱500 off).

---

## Default Accounts

None are seeded. Accounts are created via normal registration; promote an admin manually with:

```sql
UPDATE `users` SET `role` = 'admin' WHERE `username` = 'your_username';
```

> ⚠️ Never commit credentials to source control. Seeded demo products/sliders are created on first run and can be edited/deleted in the admin panel.

## Directory Structure

```
TheHangar/
├── index.php                  # Root redirect → homepage/
├── README.md                  # This documentation
├── TASKS.md                   # Working task list / blockers
│
├── shared/
│   ├── db.php                 # DB connection, schema, seeding, migrations, settings helpers
│   └── bootstrap.php          # Shared bootstrap (includes config + db)
│
├── database/
│   └── config.php             # DB credentials + payment mode config (gitignored)
│
├── homepage/                  # STOREFRONT
│   ├── index.php              # Catalog home (sections: hero, new releases, best sellers...)
│   ├── product-details.php    # Product detail page
│   ├── db_helper.php          # Storefront DB queries
│   ├── footer.php
│   ├── script.js / style.css
│   ├── search/                # Live search (search.php + api_search.php)
│   ├── orders/                # Customer "MY ORDERS" tracking (orders.php + orders.css)
│   └── cart/                  # Cart + checkout
│       ├── cart.php           # Cart page & checkout UI (GCash QR deck / COD)
│       ├── cart.js            # Cart state (localStorage) + checkout AJAX
│       ├── cart.css
│       └── api_checkout.php   # Promo validation + order creation + courier/payment (JSON API)
│
├── login/                     # Customer & admin login/registration
│   ├── index.php
│   ├── style.css
│   └── logout.php
│
├── admin/                     # ADMIN PANEL (session-guarded)
│   ├── auth.php               # requireAdmin() guard, session helpers
│   ├── index.php              # Dashboard
│   ├── products.php           # Products CRUD + image upload/crop
│   ├── orders.php             # Order list/detail, status workflow, payment verify
│   ├── receipt.php            # Print-ready order receipt (A4 / save-as-PDF)
│   ├── sliders.php            # Homepage slider management
│   ├── promos.php             # Promo code management
│   ├── gcash.php              # GCash QR upload/replace/remove
│   ├── upload_helper.php      # handleAssetUpload(), adminAssetUrl(), CSRF helpers
│   └── style.css
│
├── assets/
│   └── uploads/               # Admin-managed uploads
│       ├── products/
│       ├── sliders/
│       └── gcash_qr/          # GCash QR images (managed via Admin → GCash & Payments)
│
└── promotional/               # Legacy/branding images (e.g. Asset 8.png logo)
```

---

## User Flows

### Customer (storefront)
1. **Browse** `http://localhost/TheHangar/` → catalog, search, product details
2. **Add to cart** → cart state lives in `localStorage`, rendered by `cart.js`
3. **Checkout** (`/homepage/cart/cart.php`):
   - Apply promo code (validated server-side via `api_checkout.php?action=validate_promo`)
   - Fill in contact details (name, email, phone), delivery address, and choose **delivery courier** (J&T / NinjaVan)
   - Choose payment: **GCash** (scan QR → pay exact total → paste reference) **or Cash on Delivery** (pay the courier on arrival)
4. **Dispatch order** → `api_checkout.php` re-validates stock & totals **server-side** (cart prices are never trusted), writes `orders` + `order_items` in one transaction, and shows a confirmation with the order code and payment status
5. **Track orders** (`/homepage/orders/`) — while logged in, view **MY ORDERS** (active), **TO PAY** (COD awaiting door payment, or GCash awaiting verification), and **PAST ORDERS**; expand any order to see items, status, courier, delivery address and totals

### Admin
1. Log in at `/login/` with an admin account → **COMMAND DASHBOARD** link, or go straight to `/admin/`
2. Manage products, sliders, promo codes
3. Watch **ORDERS** for `AWAITING VERIFY` payments → verify against the GCash app → advance fulfilment status

---

## GCash Payment System

> **Why QR + manual verification?** GCash offers **no public self-serve sandbox** for individual accounts, and automated merchant APIs require a registered business through a PSP (e.g. PayMongo). The QR flow works today with zero third-party accounts.

### How the `qr` mode works (default)
1. **Setup (once):** Admin → **GCASH & PAYMENTS** → upload your personal GCash QR (from the GCash app: Profile → My QR Code). Stored in the `settings` table (`gcash_qr_url`), served at checkout.
2. **Customer pays:** checkout shows the QR + exact total; customer pays in the GCash app and submits the **GCash reference number**.
3. **Order saved:** `api_checkout.php` stores the order with `payment_status = 'payment_pending'` and the reference in `gcash_ref`. Missing reference → order rejected (HTTP 400).
4. **Verification:** the order shows an **AWAITING VERIFY** badge in Admin → ORDERS. After confirming the reference in your own GCash app, click **VERIFY GCASH PAYMENT** → `payment_status = 'paid'`, `payment_ref` stamped.
5. **Fulfilment unlocks:** status changes to `processing/shipped/completed` are **refused while payment is unconfirmed** (`unpaid` message).

### Other modes
- **`stub`** — dev-only auto-approval: orders are saved as `paid` immediately (with a `GCASH-STUB-*` reference) so the whole flow can be tested locally without a QR. Switch back to `qr` before going live.
- **`live`** — reserved for an automated PSP integration (PayMongo-style): needs merchant credentials, a public HTTPS webhook, and signature verification. The code branch exists but is intentionally inert; `gcash_*` credential keys in `database/config.php` are placeholders.

### Cash on Delivery (COD)
At checkout the customer picks **GCash (QR scan-to-pay)** or **Cash on Delivery**. COD orders are stored with `payment_method = 'cod'` and `payment_status = 'cod'` — no upfront payment reference is required and no admin verification is needed. They ship once `status` is advanced (the fulfilment gate treats `cod` like `paid`), and payment is collected by the courier at the door. The receipt and admin badge show **CASH ON DELIVERY**.

### Where QR images live
`assets/uploads/gcash_qr/` — managed entirely from the admin page (upload, reuse a previous image, or remove). Removing the QR makes checkout display a *"QR not set yet — upload it in Admin"* notice instead of an image.

---

## Order Fulfilment Workflow

```
Checkout (payment_pending)          ──admin VERIFY──▶  payment: paid
     │
     ▼
status: pending  ──▶  processing  ──▶  shipped  ──▶  completed
     │
     └──────────────────▶  cancelled   (any time before completion)
```

- Every transition is a CSRF-protected admin POST in `admin/orders.php`
- `processing → shipped → completed` is blocked until `payment_status = 'paid'`
- `order_items` keeps immutable snapshots (`name_snapshot`, `price_snapshot`, `quantity`) so later product edits never distort historical orders

## Admin Panel Reference

All admin pages live under `/admin/` and are guarded by `auth.php` (`requireAdmin()` redirects non-admins to the login page). Every mutating action is a POST with a CSRF token (`csrfValid()`), and image uploads funnel through `upload_helper.php` (extension whitelist: jpg/jpeg/png/webp).

| Page | File | What it does |
|------|------|--------------|
| Dashboard | `index.php` | Stats (total products, pending orders, ...), recently added products, quick links |
| Products | `products.php` | Create/edit/delete products, image upload or crop, flags (new release / best seller / model kit), stock status |
| Orders | `orders.php` | Order list (search + status filter, stat cards), detail view (line items, payment info, customer/delivery data, editable **LOGISTICS/COURIER**), **VERIFY GCASH PAYMENT**, fulfilment status form, **PRINT / SAVE RECEIPT** (`receipt.php` — print-ready A4 receipt incl. courier) |
| Sliders | `sliders.php` | Manage homepage banners for sections 1, 2, 7 |
| Promo Codes | `promos.php` | Create/edit/delete promo codes (`percent` / `fixed`, active toggle) |
| GCash & Payments | `gcash.php` | Upload/replace/remove the checkout GCash QR; shows current payment mode and where to verify payments |

**Sidebar order:** Dashboard · Products · Orders · Sliders · Promo Codes · GCash & Payments

---

## Security Notes

- **SQL injection:** all queries use PDO **prepared statements** with bound parameters; no string-interpolated SQL.
- **Passwords:** `password_hash()` (bcrypt) on write, `password_verify()` on login — never stored or logged in plain text.
- **Sessions:** PHP sessions drive both storefront and admin auth (`$_SESSION['user_role']`, `hangar_admin_logged`).
- **CSRF:** every admin POST (and GET-deletes) validates a per-session token (`csrfToken()` / `csrfValid()` / `csrfField()` in `upload_helper.php`).
- **Uploads:** restricted to `jpg/jpeg/png/webp`, stored under `assets/uploads/` with generated filenames (`prefix_time_rand.ext`).
- **Price integrity:** checkout totals, discounts, and stock are recomputed server-side from the DB; client cart data is never trusted.
- **Credentials:** `database/config.php` is gitignored.
- **Known gaps (acceptable for a class/pilot project, fix before real production):**
  - No HTTPS enforcement, rate limiting, or login throttling
  - Email verification / password reset not implemented
  - Customer names/phones are not OTP-verified; GCash reference numbers are manually reviewed (human-in-the-loop by design)
  - Admin pages rely on session role only — no 2FA or audit log

---

## Development Notes

- **No build step.** Edit files and refresh; Apache serves PHP directly.
- **Testing payments locally without a QR:** set `'mode' => 'stub'` in `database/config.php` — checkout auto-marks orders `paid` with a `GCASH-STUB-*` reference. Set it back to `'qr'` afterwards.
- **Schema changes:** add them to the table definitions in `shared/db.php` **and**, for existing databases, as an idempotent `ALTER` in `ensureOrderPaymentColumns()` (wrap in try/catch; ignore duplicate-column errors — MySQL lacks `ADD COLUMN IF NOT EXISTS`).
- **Settings:** store small app configuration (like the QR path) in the `settings` key/value table via `getSetting($pdo, $key, $default)` / `setSetting($pdo, $key, $value)` instead of hardcoding.
- **Lint everything after edits:** `php -l <file>` for each touched PHP file.
- **Session sharing:** the login page, storefront, and admin panel share one PHP session, so logging in as `admin` unlocks both storefront session banner and `/admin/`.
- **Known environment quirk:** the standalone `php.exe` CLI may lack the PDO MySQL driver (`could not find driver`) — DB code paths are exercised through Apache/XAMPP instead, which loads `php_pdo_mysql`. The auto-migrations run on the first page load after any change.

---

## Go-Live Checklist

When moving from localhost/pilot to a real deployment:

1. **Change the DB credentials** in `database/config.php` (strong root password or a dedicated user).
2. **Change all seeded passwords** (admin + demo users) or delete the demo accounts.
3. **Switch `'mode' => 'qr'`** if it was ever set to `stub`, and upload the real GCash QR in Admin → GCASH & PAYMENTS.
4. **Serve over HTTPS** (required by GCash app links, cookies, and basic hygiene).
5. **Verify fulfilment policy:** confirm the "no dispatch until payment verified" rule matches your shop's actual process.
6. **Optional — automated payments:** register with a PSP (e.g. PayMongo), fill in the `gcash_*` credential keys, implement the `live` branch + webhook signature verification in `homepage/cart/api_checkout.php`, then flip `'mode' => 'live'`.
7. **Backups:** schedule `mysqldump` of `the_hangar_db` and backups of `assets/uploads/`.
8. **Housekeeping:** remove `TASKS.md`/scratch files, review the `promotional/` legacy folder, and confirm `.gitignore` covers config + uploads as intended.

---

*Documentation generated from the codebase as of the current working tree (`main`). Refer to `TASKS.md` for the change log of recent features.*



