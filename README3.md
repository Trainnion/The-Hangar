# THE HANGAR — README 3: MySQL 8.0 Migration Plan (MariaDB → MySQL)

> Status: **APPROVED** (reviewed against the actual codebase and machine on 2026-09-08)
> Goal: Migrate the app's database from XAMPP's MariaDB (port 3307) to the real
> MySQL 8.0 server (port 3306, Windows service `MySQL80`) **without losing any data**.

**Security note:** the MySQL root password is intentionally written in this document
as `<MYSQL_ROOT_PASSWORD>` — never commit the real password to version control.
Enter it interactively when the CLI prompts for it (`-p` with no value attached).

---

## CREDENTIALS

- MySQL 8.0 root password: `<MYSQL_ROOT_PASSWORD>`
  (Never type this inline on the command line — always use the `-p` flag
  with no value attached and let the CLI prompt for it, so it doesn't end
  up in shell history or process listings.)

## CONTEXT

- `database/config.php` (`hangarDatabaseConfig()`) currently points to:
  `host=127.0.0.1, port=3307, user=root, pass=''` (XAMPP's MariaDB).
- MySQL 8.0 is installed separately and already running on port 3306 as the
  Windows service `MySQL80`, root password: `<MYSQL_ROOT_PASSWORD>`.
- The project uses a marker file `shared/.schema_installed` to skip re-running
  schema creation/seeding on every request (`database/config.php`, `getDBConnection()`,
  lines ~120–126). This marker ALREADY EXISTS from the MariaDB setup — if we just
  switch the port without migrating data, the app will connect to an empty database
  on MySQL 8.0 and `initDatabaseTables()` will NOT run again (because the marker
  file is still present), leaving the app broken with no tables at all.
  This must be handled explicitly, not skipped.

## GOAL

End state is `config.php` pointing at port 3306 (real MySQL) with password
`<MYSQL_ROOT_PASSWORD>`, with all current data (products, orders, order_items,
promo_codes, settings, users, sliders) present and the site fully functional —
not a fresh empty database.

---

## PRE-FLIGHT: EXECUTION-ENVIRONMENT CORRECTIONS (verified on this machine)

These are not optional — the naive commands fail on this exact setup:

1. **PowerShell cannot use `<` input redirection**, and its `>` writes UTF-16LE
   (corrupting SQL dumps). Use `--result-file=` for the export and `cmd /c "... < dump.sql"`
   for the import.
---

## STEPS

### 1. Confirm both servers are actually reachable before touching anything

MySQL 8.0 (port 3306) — PATH default client is fine here:

```
mysql -h 127.0.0.1 -P 3306 -u root -p -e "SELECT VERSION();"
```

Enter `<MYSQL_ROOT_PASSWORD>` when prompted. Confirm the returned version string
does NOT contain "MariaDB".

XAMPP MariaDB (port 3307) — use XAMPP's own client (no `-p`; root has empty password):

```
& "C:\xampp\mysql\bin\mysql.exe" -h 127.0.0.1 -P 3307 -u root -e "SELECT VERSION(); SHOW DATABASES;"
```

Confirm the version string DOES contain "MariaDB", and that `the_hangar_db`
appears in the database list. If connection refused: start MariaDB via the
XAMPP Control Panel (it is not a Windows service on this machine) and retry.

### 2. Export the current data from MariaDB (port 3307) to a SQL dump file

Write the dump OUTSIDE the webroot, using XAMPP's mysqldump, and let it
create the file itself (`--result-file`) to avoid PowerShell encoding corruption:

```
& "C:\xampp\mysql\bin\mysqldump.exe" -h 127.0.0.1 -P 3307 -u root --result-file="C:\backups\hangar_db_backup.sql" the_hangar_db
```

Verify the file was created and isn't empty/near-zero bytes — open it and confirm
it contains `CREATE TABLE` statements for `products`, `orders`, `order_items`,
`promo_codes`, `settings`, `users`, `sliders`.

### 3. Create the target database on MySQL 8.0 (port 3306) and import the dump

Create the database (matching the collation `getDBConnection()` itself uses):

```
mysql -h 127.0.0.1 -P 3306 -u root -p -e "CREATE DATABASE IF NOT EXISTS the_hangar_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Enter `<MYSQL_ROOT_PASSWORD>` when prompted, then import (PowerShell cannot do `<`,
so delegate the redirection to cmd):

```
cmd /c "mysql -h 127.0.0.1 -P 3306 -u root -p the_hangar_db < C:\backups\hangar_db_backup.sql"
```

Enter `<MYSQL_ROOT_PASSWORD>` when prompted again.

Verify the import worked:

```
mysql -h 127.0.0.1 -P 3306 -u root -p -e "USE the_hangar_db; SHOW TABLES; SELECT COUNT(*) FROM products;"
```

Confirm the table list matches what was in MariaDB and the product count is
non-zero (matches what was in the original database).

### 4. Update `database/config.php`'s `hangarDatabaseConfig()` to point at the real MySQL server

### 5. Verify the application actually works against the new connection

- Load the homepage, search page, a product detail page, and the admin
  dashboard — confirm products/sliders still display correctly.
- Log in with an existing account to confirm the `users` table migrated
  correctly (passwords are hashed, so login should work exactly as before).
- Check Orders and Promo Codes in the admin panel show the same data
  that existed before the migration.
- If any page throws a PDOException or shows a blank/broken state, STOP
  and report the exact error — do not proceed to step 6 until the app is
  confirmed fully working against port 3306.

**Known fallback (if PHP's PDO connection specifically fails):** PHP 8's mysqlnd
supports MySQL 8's default `caching_sha2_password` plugin, so this is unlikely,
but if a PDO connection error appears, the first thing to check is the auth
plugin on 3306:

```
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '<MYSQL_ROOT_PASSWORD>';
FLUSH PRIVILEGES;
```

### 6. Once confirmed working, avoid future ambiguity

Stop the XAMPP MariaDB via XAMPP Control Panel (keep Apache running if PHP
files are still served through it — only MariaDB needs to stop). This prevents
any future confusion about which server is "actually" running, and frees up
port 3307 entirely. Windows Services (services.msc) should show `MySQL80` as
the only MySQL-related service still running afterward. (Note: MariaDB was
never a Windows service on this machine — it runs from the XAMPP Control Panel,
so "stop it" means stopping it there or killing the standalone mysqld process.)

### 7. Keep the backup somewhere safe

`C:\backups\hangar_db_backup.sql` (already outside the git repo and the webroot)
is the rollback point — if anything is discovered broken later, the original
MariaDB data is still intact in that dump and in XAMPP itself until step 6 is done.

### 8. Confirm `config.php` stays out of version control

VERIFIED already: `.gitignore` line 17 lists `database/config.php`,
`git check-ignore` confirms it, and `git ls-files` shows the file was **never
tracked** — no history scrubbing is needed. Going forward this file must never
be pushed with the real password in it.

---

## ROLLBACK PLAN (if step 5 fails)

Revert `config.php`'s `'port'` back to `3307` and `'pass'` back to `''` — this
immediately restores the working MariaDB connection since nothing in steps 1–3
modified or deleted the original MariaDB data.

---

## ACCEPTANCE CRITERIA

- `SELECT VERSION();` via the app's actual PHP connection (e.g. temporarily
  echo it from a test script using `getDBConnection()`) returns a MySQL 8.0
  version string with no "MariaDB" in it.
- Every page of the site (storefront, cart, checkout, admin, orders, promos,
  gcash settings) works identically to before the migration, with the same
  data present.
- `shared/.schema_installed` still exists and was not deleted or regenerated.
- `config.php` with the real password is confirmed present in `.gitignore`
  (already verified pre-migration).

---

## COMPATIBILITY NOTES

- MariaDB and MySQL 8 speak the same wire protocol; the app's PHP code
  (`new PDO('mysql:host=...')` — the DSN literally still says `mysql`) connects
  to either without code changes. Everything this project uses —
  `CREATE TABLE`, `INSERT`, `SELECT`, `JOIN`, transactions, PDO prepared
  statements, `utf8mb4`, collation `utf8mb4_unicode_ci` — is identical on both.
- The DB is auto-created by `getDBConnection()` with the same
  `utf8mb4_unicode_ci` collation the manual `CREATE DATABASE` in step 3 uses —
  the manual create is kept anyway so the import can be verified before Apache
  ever touches port 3306.
- The old 3306 port conflict documented in `database/config.php` is resolved by
  this migration: the previously-idle Windows `MySQL80` service becomes the
  app's real database.

```php
function hangarDatabaseConfig(): array {
    return [
        'host'    => '127.0.0.1',
        'port'    => 3306,                      // was 3307 (MariaDB)
        'dbname'  => 'the_hangar_db',
        'user'    => 'root',
        'pass'    => '<MYSQL_ROOT_PASSWORD>',   // was '' (MariaDB default)
        'charset' => 'utf8mb4',
    ];
}
```

Do NOT touch or delete `shared/.schema_installed` — the schema now already
exists on port 3306 via the import in step 3, so this marker correctly staying
in place prevents `initDatabaseTables()` from redundantly re-running against
data that's already there.

2. **`mysql`/`mysqldump` on PATH resolve to MySQL 8.0's binaries**
   (`C:\Program Files\MySQL\MySQL Server 8.0\bin\`), NOT XAMPP's:
   - **Export (port 3307):** use `C:\xampp\mysql\bin\mysqldump.exe` (verified present).
     MySQL 8's `mysqldump` against MariaDB 10.4 fails with
     `Unknown table 'column_statistics' in information_schema` unless
     `--column-statistics=0` is passed — using XAMPP's own dump tool sidesteps this.
   - **Import/connect (port 3306):** the PATH default (MySQL 8 client) is correct.
     Do NOT use XAMPP's MariaDB client against 3306 — its MariaDB 10.4 client
     cannot speak `caching_sha2_password` (the exact incompatibility previously
     encountered and documented in README2.md).
3. **MariaDB is not a Windows service.** `Get-Service` shows only `MySQL80`.
   If port 3307 is unreachable in step 1, start MariaDB via XAMPP Control Panel first.
4. **Backup location must be outside the webroot.** `C:\xampp\htdocs\` is
   Apache-served — a dump inside it would be downloadable over HTTP
   (it contains password hashes). Use `C:\backups\`.
