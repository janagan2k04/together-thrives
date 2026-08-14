# TogetherThrives
2nd Year Grp Project hi

# TogetherThrives — Local Setup Guide (XAMPP / WAMP)

This guide explains how to set up and run the TogetherThrives project locally using **XAMPP or WAMP**.

The recommended setup for this project is:

- **Apache** → runs the application
- **MySQL / MariaDB** → stores the database
- **phpMyAdmin** → imports and manages the database
- **Composer** → installs PHP dependencies

---

# 1. Prerequisites

Install:

- XAMPP **or** WAMP
- Git
- VS Code (recommended)

After installing XAMPP/WAMP, make sure PHP and MySQL are available.

Check PHP:

```bash
php -v
```

---

# 2. Download / Clone the Project

Clone the GitHub repository:

```bash
git clone https://github.com/janagan2k04/together-thrives.git
```

You can also download the repository as a ZIP from GitHub and extract it.

---

# 3. XAMPP Setup

If you are using **XAMPP**, the project must be placed inside:

```text
C:\xampp\htdocs\
```

After cloning or extracting, the final structure should be:

```text
C:\xampp\htdocs\tt\
```

For example:

```text
C:\xampp\htdocs\tt
│
├── app
├── config
├── database
├── docs
├── public
├── resources
├── storage
├── UI
├── .env.example
├── composer.json
└── ...
```

> **Important:** The project folder name can be `tt`. If you use another folder name, change the URL accordingly.

---

# 4. WAMP Setup

If you are using **WAMP**, place the project inside:

```text
C:\wamp64\www\
```

The final structure should be:

```text
C:\wamp64\www\tt\
```

For the rest of this guide, `tt` is used as the project folder name.

---

# 5. Start XAMPP / WAMP

## XAMPP

Open **XAMPP Control Panel**.

Start:

```text
Apache
MySQL
```

Both services should show as running.

## WAMP

Open WAMP and wait until the WAMP icon becomes **green**.

This means the required services are running.

---


# 7. Create the `.env` File

If the repository contains `.env.example`, create `.env` from it.

The `.env` file contains local configuration such as database credentials.

---

# 8. Configure the Database

Open the `.env` file.

Update the database settings according to your local MySQL configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=togetherthrives
DB_USERNAME=root
DB_PASSWORD=
```

For the default XAMPP/WAMP MySQL setup:

```text
Username: root
Password: empty
```

If your MySQL has a password, enter it as:

```env
DB_PASSWORD=your_password
```

---

# 9. Open phpMyAdmin

Open your browser and go to:

```text
http://localhost/phpmyadmin
```

This works with both XAMPP and WAMP in a standard setup.

---

# 10. Create the Database

In phpMyAdmin:

1. Click **New**.
2. Create a database named:

```text
togetherthrives
```

3. Select the database.

The database name must match the value in `.env`:

```env
DB_DATABASE=togetherthrives
```

---

# 11. Import the Database Schema


Look inside the project for the `.sql` files:
```text
database/
    schema.sql        <- import this first
database/seeds
    seed.sql          <- then this
database/migrations
    2026_......sql    <- do NOT import on a fresh database
```

Import **schema.sql then seed.sql** — those two only. The file in `migrations/`
has already been folded into `schema.sql`; importing it on a fresh database fails
with `ERROR 1060: Duplicate column name`. It applies only to a database created
before 2026-08-11. See `database/README.md`.


In phpMyAdmin:

1. Select the `togetherthrives` database.
2. Click **Import**.
3. Click **Choose File**.
4. Select the project's `.sql` schema file.
5. Click **Import / Go**.
6. Wait until phpMyAdmin confirms that the import was successful.

Repeat for `seeds/seed.sql`. Those two files are the whole import — not the
migration, as noted above.

After importing, the database tables should appear under:

```text
togetherthrives
```



# 12. Run TogetherThrives Using XAMPP/ WAMP

If you want to access the project directly through Apache:

Open your browser and go below page:

```text
http://localhost/tt/public/index.php
```


---

# 13. Sign In Accounts

Sign in at `http://localhost/tt/public/login`.

**Only the administrator comes from the seed file.** After importing
`schema.sql` + `seed.sql` on a fresh machine, that is the *only* account you
will have. The other four below were registered through the real sign-up forms
during testing — recreate them yourself at `/register` if you want them, or
just sign in as the admin.

| Name | Role | Email | Password | Status |
|---|---|---|---|---|
| Platform Admin | Administrator | `admin@togetherthrives.lk` | `Admin@123` | active — **seeded** |
| Dilani Fernando | Community Member | `dilani@test.lk` | `Passw0rd1` | active |
| Hope Foundation Lanka | Organization | `hope@test.lk` | `Passw0rd1` | active (approved by the admin) |
| Sunil Bandara | Grama Niladhari | `sunil@test.lk` | `Passw0rd1` | active (approved by the admin) |
| National Blood Bank Colombo | Blood Bank | `bank@test.lk` | `Passw0rd1` | active (approved by the admin) |

**All five now sign in.** Sunil and the blood bank were previously left `pending`
and `rejected` so the admin verification queue was not empty; both have since
been approved, because the weeks 4–5 CRUD work needs them able to sign in —
`/my/donations` is gated to organisations and Grama Niladharis, and `/my/blood`
to organisations and blood banks.

To demonstrate the verification queue or the rejected-account path again, either
register a new account at `/register` and leave it pending, or set a status back
by hand:

```sql
UPDATE users SET status = 'pending'  WHERE email = 'sunil@test.lk';
UPDATE users SET status = 'rejected' WHERE email = 'bank@test.lk';
```

A pending or rejected account is *supposed* to be refused at sign-in with an
explanation — that is the feature working, not a broken password. Approve from
`/admin/verification` while signed in as the admin.

Community accounts skip verification, which is why Dilani can sign in the
moment she registers; organizations, GN officers and blood banks land on
`pending` and wait for the administrator.

> These are throwaway local test credentials for a development database on
> `localhost`. If this project is ever deployed anywhere real, change the
> administrator password first — it is in a file that ships with the repo.

---

# 14. If the Application Does Not Open

Check the following:

### Apache

Make sure Apache is running.

### MySQL

Make sure MySQL is running.

### Project location

For XAMPP:

```text
C:\xampp\htdocs\tt
```

For WAMP:

```text
C:\wamp64\www\tt
```

### Database

Make sure the database exists:

```text
together_thrives
```

### `.env`

Make sure these values are correct:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=together_thrives
DB_USERNAME=root
DB_PASSWORD=
```


# 15. Git Setup for Group Members

After setting up the project, create a feature branch before making changes.

Update the main branch first:

```bash
git checkout main
git pull origin main
```

Create your feature branch:

```bash
git checkout -b feature/your-feature-name
```

Example:

```bash
git checkout -b feature/donation-module
```

---

# 16. Push Your Changes

After completing your work:

```bash
git add .
```

Commit:

```bash
git commit -m "Add donation module"
```

Push:

```bash
git push -u origin feature/donation-module
```

Then create a **Pull Request** on GitHub.

Do not directly push development work to `main`.

---


# 17. Files That Must NOT Be Uploaded to GitHub

Never commit:

```text
.env
```

`.env` holds database credentials and local configuration. It is the one thing
`.gitignore` excludes outright.

### `storage/uploads/` and `storage/logs/` **are** committed — deliberately

This surprises people, so it is worth stating plainly. Both are tracked
(17 upload files, 4 log files), and `.gitignore` says why in a comment: every
contributor needs the seed accounts' proof documents, or the admin verification
queue has nothing to review and the demo cannot be shown.

**That is only safe while they are made-up test documents.** The rule that
follows from it:

> If you ever register with a **real** NIC scan, licence or certificate, untrack
> it before you push. Once pushed it stays in the history even after deletion.

```bash
git rm --cached storage/uploads/nic-documents/<the-file>
```

The file stays on your disk; this only stops git carrying it. The same goes for
`storage/logs/`, which records real account email addresses — fine for the five
seed accounts, not fine for anyone else's.

If the group later decides the documents should not be in the repo at all, the
change is one line in `.gitignore` plus
`git rm --cached -r storage/uploads storage/logs` — but then whoever clones next
has to re-register the seed accounts by hand.

---
