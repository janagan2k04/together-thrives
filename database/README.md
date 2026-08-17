# Database Setup

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Import `schema.sql` — creates the database and all 45 tables
3. Import `seeds/seed.sql` — districts, cities, categories, screening questions,
   and the six demo accounts

That is the whole setup. **Do not import anything from `migrations/`** on a fresh
database — the one migration there has been folded into `schema.sql`, and running
it now fails with `ERROR 1060: Duplicate column name`. It only applies to a
database created before 2026-08-11.

## Seeded accounts

Six users, ids 1–6, all `active` and admin-verified, each with its
`organizations` / `grama_niladharis` / `blood_banks` profile row where the role
needs one. The sign-in page lists them with a **Use** button, so nobody has to
type a password.

| id | Email | Role | Password |
|---|---|---|---|
| 1 | `admin@togetherthrives.lk` | admin | `Admin@123` |
| 2 | `hope@test.lk` | organization | `Passw0rd1` |
| 3 | `sunil@test.lk` | grama_niladhari | `Passw0rd1` |
| 4 | `bank@test.lk` | blood_bank | `Passw0rd1` |
| 5 | `dilani@test.lk` | community | `Passw0rd1` |
| 6 | `nimal@test.lk` | community | `Passw0rd1` |

The ids are written out rather than left to AUTO_INCREMENT, because the three
profile tables reference them by `user_id` and the seed has to be reproducible.

Change the admin password before any real deployment — it ships in this repo.

**No content is seeded.** No campaigns, items, donation requests or messages;
CLAUDE.md forbids it. Every module screen opens on its empty state and you create
content through the real forms.

## Careful: `schema.sql` ignores the database you name

The file contains its own `CREATE DATABASE IF NOT EXISTS togetherthrives; USE
togetherthrives;`. So this does **not** do what it looks like:

```
mysql -u root some_other_db < database/schema.sql   # still writes to togetherthrives
```

Today that is survivable — the file is all `CREATE TABLE`, so re-running it just
errors out on the first table that already exists and changes nothing. But if
anyone ever adds `DROP TABLE IF EXISTS`, that same command becomes destructive
against the real database. Strip the `USE` line if you need to import elsewhere.

## Rules for the group

- Do NOT alter a table without telling everyone. Post in the group chat first.
- Foreign keys are enforced. Insert parent rows before child rows when seeding.
- Prefer soft delete on the main entities. Never hard-`DELETE` a row that a report
  or another member's table still points at.

## Not every table has the timestamp columns

This trips people up, so check before you assume. **31 of the 45 tables are missing
`updated_at`, `deleted_at`, or both** — mostly child, join and lookup tables:
`cities`, `districts`, `item_images`, `item_categories`, `attendance`,
`event_photos`, `notifications`, `payments`, `certificates`, `audit_logs`,
`password_resets`, `email_verifications`, and more.

Two consequences for your model:

```php
protected bool $softDelete = false;   // no deleted_at
```

and — the one that actually bites — **`Model::update()` is unusable on a table with
no `updated_at`.** It appends `updated_at = NOW()` unconditionally, so the query is
a SQL error rather than a silent no-op. Write those updates yourself:

```php
Database::run("UPDATE item_images SET caption = ? WHERE id = ?", [$caption, $id]);
```

Check your own tables before writing the model:

```sql
SELECT table_name,
       SUM(column_name = 'updated_at') AS has_updated,
       SUM(column_name = 'deleted_at') AS has_deleted
FROM information_schema.columns
WHERE table_schema = 'togetherthrives'
GROUP BY table_name;
```
