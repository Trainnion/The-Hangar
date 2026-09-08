❯is mariadb is mysql?
*Short answer: MariaDB is MySQL's sibling, not MySQL itself — but for your project it behaves identically.
The backstory
MariaDB is a fork of MySQL. In 2009, when Oracle bought Sun (and therefore MySQL), MySQL's original creator — Michael "Monty" Widenius — forked the codebase to keep a community-governed version alive. He named it after his daughter Maria (just as MySQL was named after his daughter My). So MariaDB started as literally the same code.
How they relate
 	MySQL	MariaDB
Origin	Sun/Oracle	Fork of MySQL by MySQL's original creator
License	GPL + commercial dual-license	Pure GPL
SQL syntax	—	~identical for everyday use
PHP drivers (PDO, mysqli)	✅	✅ same drivers, same code
Your app's SQL (PDO prepared statements)	✅ works	✅ works unchanged
Because it was a fork, MariaDB stays drop-in compatible: same protocol, same SQL dialect for normal CRUD, same client tools. Your PHP code calls new PDO('mysql:host=...') — note it literally still says mysql — and it connects to MariaDB without knowing or caring about the difference. Even XAMPP's own client binary is still called mysql.exe, and when I ran it earlier it reported 10.4.32-MariaDB.
Where they genuinely differ (and one you already saw!)
- Default authentication: MySQL 8 switched to a new auth plugin (caching_sha2_password). That's exactly the error you hit earlier — XAMPP's MariaDB client couldn't talk to the MySQL 8 server. A real, practical incompatibility you personally witnessed. 🙂
- Newer SQL features: they've diverged a bit (e.g., different JSON functions, different default collations, MySQL 8 has some SQL syntax MariaDB handles differently and vice versa).
- Versioning: numbers don't align — MariaDB 10.4 is not "older than MySQL 8"; they're parallel lineages.
- Replication between them is not supported — but you'd never do that.
For THE HANGAR
Everything the project uses — CREATE TABLE, INSERT, SELECT, JOIN, transactions, PDO prepared statements, utf8mb4 — is textbook SQL that behaves the same on both. The seeded schema was built on MariaDB 10.4, and your README just says "MySQL/MariaDB" underneath the hood. So: technically no, MariaDB ≠ MySQL; practically for this project, yes — it's a MySQL-compatible database and nothing in your codebase needs to change.