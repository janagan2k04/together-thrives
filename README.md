# TogetherThrives
2nd Year Grp Project

# TogetherThrives — Local Setup Guide (XAMPP / WAMP)

This guide explains how to set up and run the TogetherThrives project locally using **XAMPP or WAMP**.

The recommended setup for this project is:

- **Apache** → runs the application
- **MySQL / MariaDB** → stores the database
- **phpMyAdmin** → imports and manages the database

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
├── public
├── resources
├── storage
├── .env.example
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

Update the database settings according to your local MySQL configuration (Important change BASE_URL according to your folder structure):

```env
APP_DEBUG=true
BASE_URL=http://localhost/tt/public

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=togetherthrives
DB_USER=root
DB_PASS=

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=
MAIL_PASS=
MAIL_FROM=noreply@togetherthrives.com

WS_HOST=127.0.0.1
WS_PORT=8080

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


# 10. Import the Database


Look inside the project for the `.sql` files:
```text
database/
    schema.sql        <- import this
```

In phpMyAdmin:

1. Click **Import**.
2. Click **Choose File**.
3. Select the project's `.sql` schema file inside database/.
4. Click **Import / Go**.
5. Wait until phpMyAdmin confirms that the import was successful.


After importing, the database tables should appear under:

```text
togetherthrives
```



# 11. Run TogetherThrives Using XAMPP/ WAMP

If you want to access the project directly through Apache:

Open your browser and go below page:

```text
http://localhost/tt/public/index.php
```
