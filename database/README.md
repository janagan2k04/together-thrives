# Database Setup

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Import `schema.sql` — creates the database and all 45 tables
3. Import `seeds/seed.sql` — districts, cities, categories, screening questions, admin user

That is the whole setup. **Do not import anything from `migrations/`** on a fresh
database — the one migration there has been folded into `schema.sql`, and running
it now fails with `ERROR 1060: Duplicate column name`. It only applies to a
database created before 2026-08-11.

Default admin login:
  email:    admin@togetherthrives.lk
  password: Admin@123

Change this before the demo. It is the only seeded account — every other user is
created through the registration forms.

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
