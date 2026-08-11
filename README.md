# TogetherThrives
2nd Year Grp Project

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

# 6. Install Project Dependencies

Open Command Prompt inside the project folder.

For XAMPP:

```bash
cd C:\xampp\htdocs\tt
```

For WAMP:

```bash
cd C:\wamp64\www\tt
```

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


Look inside the project for a `.sql` files:
```text
database/
    schema.sql

database/migrations
    2026_......sql

database/seeds
    seed.sql
    
```


In phpMyAdmin:

1. Select the `togetherthrives` database.
2. Click **Import**.
3. Click **Choose File**.
4. Select the project's `.sql` schema file.
5. Click **Import / Go**.
6. Wait until phpMyAdmin confirms that the import was successful.

Import all sql files one by one.

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

# 13. If the Application Does Not Open

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


# 14. Git Setup for Group Members

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

# 15. Push Your Changes

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


# 16. Files That Must NOT Be Uploaded to GitHub

Do not commit:

```text
.env


The `.env` file may contain database credentials and other local configuration.

---
