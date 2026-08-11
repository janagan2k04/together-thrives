# Database Setup

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Import `schema.sql` — creates the database and all 45 tables
3. Import `seeds/seed.sql` — districts, cities, categories, screening questions, admin user

Default admin login:
  email:    admin@togetherthrives.lk
  password: Admin@123

Change this before the demo.

## Rules for the group
- Do NOT alter a table without telling everyone. Post in the group chat first.
- Every table has `created_at`, `updated_at`, `deleted_at`. Never hard-DELETE.
- Foreign keys are enforced. Insert parent rows before child rows when seeding.
