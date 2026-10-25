# TogetherThrives
2nd Year CS Grp Project

# About TogetherThrives

TogetherThrives is a **centralised disaster response and community support
system** for Sri Lanka. When a flood, landslide, cyclone or fire hits, the help a
community needs - volunteers, relief goods, money, blood - is usually spread
across phone calls, social media posts and separate organisations. TogetherThrives
brings it onto one platform, where every account and every post is checked by an
administrator before anyone else sees it, so the public can trust what they read.

It is built in PHP 8 and MySQL on a custom MVC, with no framework.

## What the system does

The platform has four modules, one per group member, plus shared features.

| Module | What it is for |
|---|---|
| **Cleanup & Volunteering** | Organisations and Grama Niladhari officers run cleanup campaigns for disaster-hit areas. Volunteers join, are checked in on the day, chat in the campaign's group chat, rate the campaign and receive a participation certificate. The organiser publishes an impact report. |
| **Item & Fund Donation** | Organisations, Grama Niladhari officers and the administrator post relief requests for goods or money. Donors reserve item quantities or pay into a fund (sandbox payment with a receipt). The requester confirms what arrived and publishes the outcome. |
| **Blood Donation** | Blood banks, organisations and Grama Niladhari officers run blood camps; registered users can post critical blood requests for emergencies. Donors answer the camp's medical screening form, which scores their eligibility automatically. Donations are recorded and certificates issued. |
| **Reuse Item Donation** | A household lists something it no longer needs and another member requests it. A disaster-affected requester attaches a Grama Sevaka letter; the donor reads it and chooses the recipient. The two talk through in-app messages and either side can mark the item handed over. |

Shared across the platform:

- **Disaster reports** filed by Grama Niladhari officers and the administrator
- **Dengue alerts** for high-risk districts, with photos and cleanup recommendations
- **Announcements** and in-app **notifications**, with district-based alerts when a campaign or critical request is approved
- **Text-only messaging** - one-to-one for reuse, group chats for cleanup and blood campaigns
- **Donations to the platform** itself, from the Support page
- **Report a problem** and complaints handling
- Account emails (confirmation, password reset, approval decisions) when mail is configured in `.env`
- English, Sinhala and Tamil on the shared screens, and a dark mode

## Who the users are

| User | Who they are | What they can do |
|---|---|---|
| **Community Member** | A member of the public | Volunteer at cleanup campaigns, donate items or money to relief requests, donate blood, give and request reuse items, post critical blood requests, message and get notified |
| **Organization** | A non-profit or community group | Run cleanup campaigns and blood camps, post relief requests and critical blood requests, publish outcomes. Organisations do not donate blood or use the reuse module - that is person to person |
| **Grama Niladhari (GN) officer** | The government officer for a GN division | File disaster reports, recommend cleanup priorities and confirm campaigns in their division, run cleanup campaigns and blood camps, post relief requests |
| **Blood Bank** | A hospital or national blood bank | Run blood camps, record donations, post critical blood requests and publish camp outcomes |
| **Administrator** | The platform team | Approve or reject every account and every post, manage and suspend accounts, raise dengue alerts, publish announcements, handle complaints, read analytics and system logs |

**Nothing goes public without approval.** Every new account (community members
included) waits for an administrator after confirming its email, and every post -
cleanup campaign, relief request, blood camp, critical blood request, reuse
listing and disaster report - is reviewed before it is published. Editing an
approved post sends it back for review.

## The team

| Member | Module | Shared functionality |
|---|---|---|
| Dilsha | Cleanup & Volunteering | Report a problem; district notifications for approved cleanup campaigns; GN cleanup priorities and campaign confirmation |
| Janudi | Item & Fund Donation | Sandbox payments and receipts; donating to the platform; Sinhala language support |
| Shenol | Blood Donation | In-app text messaging and group chats; My Activities; district notifications for approved blood camps and critical requests |
| Janagan | Reuse Item Donation | Registration, sign-in and sessions; account and content verification; all admin screens (accounts, admin management, disaster reports, dengue alerts, announcements, complaints, system logs); profile, settings and notifications; email delivery; Tamil language support |

---

# TogetherThrives - Local Setup Guide (XAMPP / WAMP)

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

# 11. Import the Database

The whole database is **one file**:

```text
database/
    togetherthrives-full.sql   <- import this, and nothing else
```

It creates the `togetherthrives` database, all 48 tables, and the seed rows.
Every schema change made during the project - the old `migrations/` files and
the 2026-09-21 and 2026-10 upgrades - is already folded into it.

In phpMyAdmin:

1. Click **Import** (no database needs to be selected - the file creates it).
2. Click **Choose File** and select `database/togetherthrives-full.sql`.
3. Click **Import / Go**.
4. Wait until phpMyAdmin confirms that the import was successful.

> **Import through phpMyAdmin, not the `mysql` command line.** On this XAMPP
> build piping a file into `mysql` has taken the MariaDB server down mid-`ALTER`.
> phpMyAdmin's Import tab goes through Apache and is safe.

**For a fresh database only.** If `togetherthrives` already has tables, the
import stops at the first `CREATE TABLE` with "table already exists". To start
over, drop the `togetherthrives` database in phpMyAdmin first, then import.
A database from before 2026-10 that shows `Unknown column` on a page is fixed
the same way: drop it and re-import (this wipes anything created in the app).

The seed rows are the districts, cities, item categories, default screening
questions, and the **six demo accounts** in section 13 - all active and
admin-verified, so the system is demonstrable the moment the import finishes.
It seeds no content: no campaigns, no items, no donation requests. Those are
created through the app.

**`cities` holds one row per district** - 25 rows, each named after its district,
mirroring `districts` one for one. Every form on the site asks for a **district**;
the column and the table keep the name `city_id` / `cities` only because six
tables carry that foreign key.

After importing, the tables appear under:

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

**All six accounts come from the seed file.** Importing `togetherthrives-full.sql`
on a fresh machine gives you every account below, already `active`
and already admin-verified - there is nothing to register and nothing to
approve before you can demonstrate the system.

| Name | Role | Email | Password |
|---|---|---|---|
| Platform Super Admin | Administrator | `admin@togetherthrives.com` | `Password@123` |
| Keerthy Ganesh | Community Member | `keerthy@test.com` | `Password@123` |
| Janindu Perera | Community Member | `janindu@test.com` | `Password@123` |
| Hope Foundation Lanka | Organization | `hope@test.com` | `Password@123` |
| Ajmal Riyaz | Grama Niladhari | `ajmal@test.com` | `Password@123` |
| National Blood Bank Colombo | Blood Bank | `bank@test.com` | `Password@123` |

**You do not have to type any of them.** The sign-in page lists all six with a
**Use** button that fills the form, so a marker can get into any role without
being told the passwords. Set `DEMO_ACCOUNTS=false` in `.env` to hide that panel
- it prints working passwords to anyone who can reach `/login`, which is fine on
`localhost` and nowhere else.

**Two community members, on purpose.** Reuse needs a giver and a receiver: one
account lists an item, the other requests it. With a single community account
you cannot demonstrate the request → select → handover flow at all.

The role profile rows (`organizations`, `grama_niladharis`, `blood_banks`) are
seeded alongside the users and carry `verified_at`, so the organization, GN and
blood bank open their own dashboards straight away.

> `document_path` is deliberately `NULL` on the seeded profiles. The original
> registrations uploaded real PDFs that exist on one laptop only; a path pointing
> at a missing file would 404 through `FileController`. The only visible effect
> is that a donor profile reads "No NIC document submitted".

To demonstrate the verification queue or the rejected-account path, either
register a new account at `/register` and leave it pending, or set a seeded
status back by hand:

```sql
UPDATE users SET status = 'pending'  WHERE email = 'ajmal@test.com';
UPDATE users SET status = 'rejected' WHERE email = 'bank@test.com';
```

A pending or rejected account is *supposed* to be refused at sign-in with an
explanation - that is the feature working, not a broken password. Approve from
`/admin/verification` while signed in as the admin.

Community accounts skip verification, which is why a newly registered community
member can sign in immediately; organizations, GN officers and blood banks land
on `pending` and wait for the administrator. The seeded six bypass this only
because the seed writes them in already approved.

> These are throwaway local test credentials for a development database on
> `localhost`. If this project is ever deployed anywhere real, change the
> administrator password first - it is in a file that ships with the repo.

---

# 14. What Each Member Built - the CRUD Flows

Each of the four members owns one module and one CRUD entity. All four share the
same spine - **create -> an administrator approves -> the public sees it**,
editing an approved row sends it back for re-review, and delete is a soft delete
plus a cancelled status, so nothing is ever erased.

Since 2026-08-18 that spine has no exceptions. Reuse listings used to publish the
moment they were saved and critical blood requests were not stored at all; both
go through the queue now. One service decides for all five post types, and the
author is notified either way.

Every route named here works today. Sign in with the account named in each flow.

**All four members satisfy all four CRUD operations.** Verified end to end on
2026-08-18 by driving the real forms over HTTP as each role: 48 assertions,
48 passed. Each entity was created, listed, read in detail, approved, edited,
refused to a stranger, and deleted - and the delete checked to be a soft delete
that leaves the row in its owner's list. The per-operation result is in the
table at the end of this section.

---

## M1 - Dilsha - Cleanup & Volunteering

**Entity:** `cleanup_events` · **Routes:** `config/routes/cleanup.php` · **Namespace:** `Cleanup\`

| Operation | Route | Verified |
|---|---|---|
| CREATE | `POST /org/cleanup` | row stored `draft`, organiser redirected to it |
| READ (list) | `GET /org/cleanup` · `GET /cleanup` | own list shows it as Pending Approval; public list does not, until approved |
| READ (detail) | `GET /org/cleanup/{id}` · `GET /cleanup/{id}` | opens with the stored values |
| UPDATE | `PUT /org/cleanup/{id}` | changes saved, campaign leaves the public page, a stranger gets 403 |
| DELETE | `DELETE /org/cleanup/{id}` | `cancelled` + soft delete: off the public page, still in the organiser's list |

**The flow:**

1. **CREATE.** The organization (`hope@test.com`) fills the campaign form at
   `/org/cleanup/create` - title, date, meeting point, district, volunteer limit,
   safety guidelines. The row is saved as **`draft`**, which is this module's
   "pending": invisible to everyone but its author.
2. **Administrator review.** The admin (`admin@togetherthrives.com`) sees it in
   the queue at `/admin/cleanup`.
   - **Approve** -> status becomes `published`.
   - **Reject** -> status becomes `cancelled` and the reason is written to
     `cancel_reason`. Cleanup has a narrower ENUM than the other three modules,
     so a rejection is expressed this way rather than as a `rejected` state.
3. **READ (list).** The published campaign now appears on the public browse page
   `/cleanup`, which **guests can open without signing in**.
4. **READ (detail).** `/cleanup/{id}` shows the full campaign.
5. **Volunteers join.** A community member (`keerthy@test.com`) signs up at
   `/cleanup/{id}/join`. The form validates and reports back; the
   `event_registrations` row itself has no writer yet, so the volunteer counter
   on the campaign stays at 0.
6. **UPDATE.** The organization edits at `/org/cleanup/{id}/edit`. An approved
   campaign **returns to `draft`** on save - the date and meeting point are
   exactly what the administrator checked, so a silent edit would publish
   unreviewed details.
7. **DELETE.** `/org/cleanup/{id}` (DELETE) soft deletes the row and marks it
   `cancelled`. It stays in the organization's own list and drops off the public
   page.
8. **After the event.** Attendance is marked at `/org/cleanup/{id}/volunteers`,
   volunteers leave feedback at `/cleanup/{id}/feedback`, and an appreciation
   certificate prints from `/cleanup/certificate/{id}`.

> A community member can also **propose** a campaign at `/cleanup/create`. The
> proposal is validated and passed on rather than stored: `cleanup_events` is
> owned by an `organization_id`, and the group ruled that campaigns stay
> organisation-hosted rather than inventing a proxy organisation row. The form
> says so on screen.

---

## M2 - Janudi - Item & Fund Donation

**Entity:** `donation_requests` (+ `requested_items` child rows) · **Routes:** `config/routes/donation.php` · **Namespace:** `Donation\`

| Operation | Route | Verified |
|---|---|---|
| CREATE | `POST /my/donations` | parent row `pending` **and both item lines** stored in one transaction |
| READ (list) | `GET /my/donations` · `GET /donations` | own list shows it; public list does not, until approved |
| READ (detail) | `GET /my/donations/{id}` · `GET /donations/{id}` | opens, and lists every item line |
| UPDATE | `PUT /my/donations/{id}` | changes saved, **a third item line added**, request leaves the public list, a stranger gets 403 |
| DELETE | `DELETE /my/donations/{id}` | `cancelled` + soft delete: off the public list, still in the requester's |

**The flow:**

1. **CREATE.** A Grama Niladhari (`ajmal@test.com`) or an organization
   (`hope@test.com`) raises a request at `/my/donations/create` and lists the
   individual things needed - rice, tarpaulins, medicine - each with a quantity.
   That writes **one parent row plus one `requested_items` child row per line**,
   inside a transaction. The parent is saved **`pending`**.
2. **Administrator review.** `/admin/donations` - Approve -> `open`, Reject ->
   `rejected`. A `pending` request is invisible to donors, which is what the
   create form promises.
3. **READ (list).** `/donations`, open to guests.
4. **READ (detail).** `/donations/{id}` shows the request and every item line
   with how much is still needed.
5. **Donors respond.** Either reserve an item (`POST /donations/{id}/reserve`)
   or donate money through the sandbox checkout at `/donations/{id}/checkout`,
   which ends on a receipt at `/donations/{id}/receipt`.
6. **UPDATE.** `/my/donations/{id}/edit`. Editing an `open` request **sends it
   back to `pending`**, same rule as the other modules.
7. **DELETE.** `/my/donations/{id}` (DELETE) soft deletes and marks the request
   `cancelled`.

> The reservation and payment steps **validate and report back but do not store
> yet** - both say so on screen. `item_reservations`, `fund_donations` and
> `payments` have no writer. The CRUD entity itself, `donation_requests` with
> its child rows, is fully real.

---

## M3 - Shenol - Blood Donation

**Entity:** `blood_campaigns` · **Routes:** `config/routes/blood.php` · **Namespace:** `Blood\`

| Operation | Route | Verified |
|---|---|---|
| CREATE | `POST /my/blood` | row stored `pending`, organiser redirected to it |
| READ (list) | `GET /my/blood` · `GET /blood` | own list shows it; the donor hub does not, until approved |
| READ (detail) | `GET /my/blood/{id}` · `GET /blood/{id}` | opens with the date range and full address |
| UPDATE | `PUT /my/blood/{id}` | changes saved, camp leaves the hub, a stranger gets 403 |
| DELETE | `DELETE /my/blood/{id}` | `cancelled` + soft delete: off the hub, still in the organiser's list |

**The flow:**

1. **CREATE.** A blood bank (`bank@test.com`) or an organization
   (`hope@test.com`) posts a camp at `/my/blood/create` - title, venue, full
   address, district, start and end date, opening hours, donors required,
   contact number. Saved **`pending`**.
2. **Administrator review.** `/admin/blood/campaigns` - Approve -> `published`,
   Reject -> `rejected`. Until then no donor can see it.
3. **READ (list).** The donor hub at `/blood`, open to guests. A camp drops off
   the hub once its **last** day has passed, so day two of a three-day drive is
   still joinable.
4. **READ (detail).** `/blood/{id}`.
5. **Donors join.** "Join This Camp" does not register the donor directly - it
   sends them to the **medical screening form** at `/blood/{id}/screening`
   first. The fourteen questions come from `screening_questions`, the answers
   are scored, the verdict lands on `/blood/screening/submitted`, and an
   eligible donor collects an appreciation certificate at
   `/blood/certificate/{id}`. The scoring runs for real; the
   `screening_responses` row has no writer yet, so the eligible / ineligible
   counters on the organiser's detail screen stay at 0.
6. **UPDATE.** `/my/blood/{id}/edit`. Editing a `published` camp **returns it to
   `pending`**.
7. **DELETE.** `/my/blood/{id}` (DELETE) soft deletes and marks it `cancelled`,
   so the organiser's history and any screening already done against it survive.

> **Critical blood requests are a different screen and a different table**
> (`critical_blood_requests`), reached from the Campaign Type switch on
> `/my/blood/create` or from the donor hub. Posting one is a *requester* action,
> so any signed-in account may do it; only responding to one ("I Can Donate") is
> restricted to individual donors.
>
> They go through the same queue: created `pending`, invisible to donors until an
> administrator verifies them, and dropped from the hub once the deadline passes.
> Until 2026-08-18 the form validated the input and threw it away.
>
> Only individual donors browse `/blood`. Blood banks and organizations collect
> or coordinate blood rather than give it, so both roles are blocked from the
> donor subtree - see `CLAUDE.md`.

---

## M4 - Janagan - Reuse Item Donation

**Entity:** `reuse_items` · **Routes:** `config/routes/reuse.php` · **Namespace:** `Reuse\`

| Operation | Route | Verified |
|---|---|---|
| CREATE | `POST /my/reuse` | row stored `pending` with its photo; donor redirected to it |
| READ (list) | `GET /my/reuse` · `GET /reuse` | own list shows it; the browse grid does not, until approved |
| READ (detail) | `GET /reuse/{id}` | opens for the donor; **404 for anyone else** while pending |
| UPDATE | `PUT /my/reuse/{id}` | changes saved, listing leaves the grid, a stranger gets 403 |
| DELETE | `DELETE /my/reuse/{id}` | `removed` + soft delete, and **`POST /my/reuse/{id}/relist` brings it back** |

**The flow:**

1. **CREATE.** A community member (`keerthy@test.com`) lists something they no
   longer need at `/my/reuse/create` - title, category, condition, district, and
   at least one photo. Saved **`pending`**: visible to its donor and to nobody else.
2. **Administrator review.** `/admin/verification` - Verify -> `available`,
   Reject -> `rejected`. The donor gets a notification either way. Until
   2026-08-18 a listing went public on save; it is the same rule as the other
   three modules now.
3. **READ (list).** The browse grid at `/reuse`, open to guests.
4. **READ (detail).** `/reuse/{id}` - photo gallery, condition, donor profile.
   A pending, rejected or withdrawn listing 404s for anyone but its donor.
5. **Someone requests it.** A second community member (`janindu@test.com`) submits
   a request from the detail page (`POST /reuse/{id}/request`) with a reason. A
   requester who is disaster-affected attaches a **GS verification letter** to
   the same form.
   Two rules are enforced here: one request per person per item, and a
   **category cooldown** so a recent recipient cannot immediately queue again.
6. **The donor is notified.** A real row is written to `notifications` -
   "New request for your item" - linking to the donor's request list.
7. **Administrator reviews the GS letter.** `/admin/reuse/letters`. An approved
   letter gives that requester **priority under the fair-distribution rules**
   when several people want the same item. This is a *second* administrator
   check, separate from the one that published the listing.
8. **The donor decides.** At `/my/reuse/{id}/requests` they can:
   - **Select** a recipient -> the item becomes `reserved`, every other pending
     request is turned down automatically and each of those requesters is
     notified;
   - **Reject** a single request;
   - **Mark handed over** -> the item becomes `donated`, the recipient is
     notified, and their category cooldown starts.
9. **The recipient reviews the donor.** From `/my/activities`, which writes to
   `reuse_feedback` and shows on the donor's public profile.
10. **UPDATE.** `/my/reuse/{id}/edit` - details and photos, with at least one
    photo always kept. An approved listing **returns to `pending`**; one that has
    already found a recipient is left alone, because sending it back would strip
    a decision already acted on.
11. **DELETE.** `/my/reuse/{id}` (DELETE) is a **Withdraw**: soft delete plus
    status `removed`, so the donor's history and any requests attached to it
    survive. **Relist** puts it back **into the queue**, not straight into the
    grid - otherwise relisting would be a way to republish rejected content.
    A completed donation cannot be withdrawn.

> **Reuse is the only module an administrator checks twice**: once on the
> listing, and again on any **GS verification letter** attached to a request for
> it. The second check is the one that decides whether "I am disaster-affected,
> put me first" is true.

---

## Where the four flows differ

| | Cleanup | Donation | Blood | Reuse |
|---|---|---|---|---|
| Who creates | Organization / GN officer | GN / Organization | Blood bank / Organization / GN officer | Community member |
| State on create | `draft` | `pending` | `pending` | `pending` |
| Admin approves the entity | Yes | Yes | Yes | Yes |
| Published state | `published` | `open` | `published` | `available` |
| Rejection | `cancelled` + reason | `rejected` | `rejected` | `rejected` |
| Edit after approval | back to `draft` | back to `pending` | back to `pending` | back to `pending` |
| Delete | soft + `cancelled` | soft + `cancelled` | soft + `cancelled` | soft + `removed`, reversible |
| Child rows written | none yet | `requested_items` | none yet | `item_images`, `item_requests`, `gs_verifications`, `notifications`, `reuse_feedback` |
| What the admin checks | the campaign | the request | the camp **and** the urgent request | the listing **and** the GS letter |

**"Child rows written" is the honest column.** All four entities are fully real -
created, listed, read, edited and deleted against the database. What differs is
how far past the entity each module has got. M2 writes its `requested_items`
lines in the same transaction as the parent; M4 writes images, requests, GS
letters, notifications and feedback. M1's volunteer sign-up and M3's screening
form both render, validate and report back, but their child tables
(`event_registrations`, `screening_responses`) have no writer yet, which is why
the counters that read them show 0.

**Those counters show 0 rather than explaining themselves.** Until 2026-08-27
each of those screens carried a note saying which table was not written yet; the
group had every such note taken off every page. A dashboard figure is a claim
about the signed-in account, so it is counted or it is left out - see
`app/services/DashboardService.php`.

## CRUD test results

Run on 2026-08-18 against the running app, driving the real forms over HTTP as
each role - not unit tests against the models, so route, middleware, CSRF,
validation, service, model and view are all exercised.

| Member | Entity | CREATE | READ | UPDATE | DELETE |
|---|---|---|---|---|---|
| M1 · Dilsha | `cleanup_events` | 1/1 | 6/6 | 3/3 | 2/2 |
| M2 · Janudi | `donation_requests` | 2/2 | 5/5 | 4/4 | 2/2 |
| M3 · Shenol | `blood_campaigns` | 1/1 | 5/5 | 3/3 | 2/2 |
| M4 · Janagan | `reuse_items` | 1/1 | 5/5 | 3/3 | 3/3 |

**48 assertions, 48 passed.** Every member satisfies all four operations.

Each entity was additionally checked for three things the interim criteria ask
for and that a CRUD test can silently miss:

- **the post is not public before an administrator approves it** - checked by
  loading the public list as a *different* account and confirming the title is
  absent;
- **the author is notified when the decision is taken** - checked on
  `/notifications` as the author;
- **a stranger cannot edit or delete it** - checked by opening the edit form as
  another account and expecting 403.

---

# 14b. Changes on 2026-08-27

A round of group-requested corrections. Full detail in `CLAUDE.md`; the parts
that change how you set the project up or demonstrate it are here.

**Two migrations**, since folded into `togetherthrives-full.sql`. Dropping the
database and re-importing that one file brings it up to date.

**Every form asks for a district, not a city.** `cities` now holds one row per
district - 25 rows, named after the district. The old list had 37 sub-district
entries covering only 13 of the 25 districts, so a member in Mannar had nothing
to pick, and the group cannot list every city in the country. The `city_id`
column and the `cities` table keep their names because `schema.sql` is frozen and
six tables carry that foreign key; what a row *means* changed, not where it is
stored.

**A Grama Niladhari officer hosts campaigns.** `ajmal@test.com` no longer browses
`/cleanup` or `/blood` - an officer runs camps for their division rather than
volunteering at somebody else's. The sidebar gives him **My Cleanup Campaigns**
and **My Blood Campaigns** instead, and both work end to end. Posting an urgent
*critical blood request* stays closed to the role.

> `routes/cleanup.php` and `routes/blood.php` both gained `grama_niladhari`.
> **Dilsha and Shenol: pull before you next edit those files.** The full ledger
> of cross-module edits is in `CLAUDE.md` under Module ownership.

**Every dashboard counts real rows.** Four of the five drew hard-coded arrays -
Keerthy Ganesh's homepage claimed 14 cleanup camps joined and 23 items donated
on an account that had done neither. They are queries now
(`app/services/DashboardService.php`), so a fresh import shows 0 almost
everywhere, which is the true answer. The public organization directory lost its
six sample NGOs for the same reason, and disaster reports became real rows.

**No implementation notes in page copy.** 38 `sample-note` blocks and about a
dozen inline ones are gone. Where a note existed only to explain that a form did
not save, the form was made to save instead: My Profile, Settings and disaster
reporting all write now.

**My Listings.** The community sidebar had no link to `/my/reuse`, so a member
who posted an item could not find it while it waited for approval and reasonably
concluded nothing had been stored. The link is there now.

**Smaller fixes.** Certificate signature blocks removed and the certificate id no
longer clipped out of the printed PDF; the focus highlight taken off every input
on every page; `+` dropped from button labels; the guest topbar's "Colombo, Sri
Lanka" removed; every file upload acknowledges the file it received, PDFs
included; a default `.icon` size added, which is what was making the map pin on a
reuse listing render the full width of the column.

**Verified:** 92 signed-in page loads across all five roles plus 13 guest pages,
zero failures and no PHP notice, warning or fatal leaking into any page; the
role blocks checked as each blocked role; `php -l` clean across `app/`, `config/`
and `public/`; and a fresh `schema.sql` + `seed.sql` import into an empty
database checked to need no migration run.

---

# 14c. Changes on 2026-09-04

The round that finished what 2026-08-27 started. Full detail in `CLAUDE.md`; what
matters for setting the project up or demonstrating it is here.

**No migration. No schema change.** Every table this round writes to was already
in `schema.sql` and empty. If your database is current you need do nothing.

**Every screen reads a real table.** All four `SampleData` services are deleted.
The screens that were still printing invented rows - the cleanup volunteer list,
admin announcements (with its made-up "12,480 viewed" column), the donor's My
Donations tab, the blood My Registrations and My Donations tabs, the organiser's
donor table, the donor wall on a fund request, the payment receipt, the
complaints queue, the dengue map, four sample administrators, and the
notifications screen when an account had none - all read their own table now, and
show an empty state when it is empty.

**The flows that fill those tables were written.** Emptying a screen that has no
way to fill up is only half an answer, so:

| A user does this | and it writes |
|---|---|
| Joins a cleanup campaign | `event_registrations` |
| Is approved / checked in / put in a zone | `attendance`, `work_areas` |
| Rates a campaign afterwards | `event_feedback` |
| Completes a blood screening form | `screening_responses` - **this is the registration** |
| Is recorded as having donated | `blood_donation_records` |
| Reserves an item on a donation request | `item_reservations` + `qty_reserved` |
| Has that delivery confirmed | `qty_received` |
| Pays into a fund request | `fund_donations`, `payments`, `collected_amount` |
| Reports a problem | `reports` - and an admin answers it at `/admin/complaints` |
| Reads an admin announcement | `announcements` + one notification per account |
| Raises a dengue alert | `dengue_risk_districts` |

Certificates are real rows too, so the number on a printed sheet is the number
the system still knows next year.

> This touched all four modules. `routes/cleanup.php`, `routes/blood.php` and
> `routes/donation.php` each gained routes, and **one route moved**:
> `/donations/{id}/receipt` is now `/donations/receipt/{id}`, because a receipt
> belongs to a donation and a donor can give twice. **Dilsha, Shenol and Janudi:
> pull before you next edit those files.** The ledger is in `CLAUDE.md` under
> Module ownership, and the per-file detail is in `WEEK1-CHECKLIST.md`.

**A field with no column is no longer drawn.** The 2026-08-27 round removed the
copy admitting input was thrown away; this one removes the fields. Roughly thirty
went, across the cleanup and donation forms, the join form, `/settings` and
`/admin/settings`. The Add Administrator form went with them - it offered three
admin roles that `users.role` does not have. `/settings` saves language, which is
the one preference the frozen schema stores.

**Demonstrating it.** With a fresh import everything reads 0, which is correct.
To show the flows, sign in as Hope Foundation and post a campaign, approve it as
Platform Super Admin, then join it as Keerthy - the volunteer list, the attendance
screen, My Activities and the certificate all fill in as you go.

**Verified:** 97 page loads across a guest and all six accounts with zero
failures and no PHP notice, warning or fatal in any page; 57 end-to-end
assertions driven over real HTTP against the real forms as the role that uses
them, all passing, including the refusals a happy path misses (403 on another
organiser's volunteer list, 403 on a certificate you did not earn, 404 on someone
else's receipt, over-reserving refused); 22 role-block checks including the paths
that must stay open; `php -l` clean; 166 routes resolving to a real controller
method. The demo database was left exactly as it was found.

---

# 15. If the Application Does Not Open

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


# 16. Git Setup for Group Members

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

# 17. Push Your Changes

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


# 18. Files That Must NOT Be Uploaded to GitHub

`.gitignore` was rewritten on 2026-08-18. It now excludes:

| Pattern | Why |
|---|---|
| `.env`, `.env.local` | database credentials and local configuration. `.env.example` is the committed template. |
| `/vendor/` | no Composer package is in use yet - this is here so that the day someone runs `composer install`, a few thousand vendor files do not land in a commit. |
| `storage/logs/*.log` | **newly excluded** - see below. |
| `storage/tmp/`, `*.tmp` | scratch space. |
| `public/_tmp_*.php` | one-shot scripts used to run a migration through Apache (section 11). They are meant to be deleted straight after use; this makes a forgotten one un-committable rather than an unauthenticated database endpoint in the repo. |
| `.vscode/`, `.idea/`, `Thumbs.db`, `desktop.ini`, `.DS_Store` | editor and OS noise. |

### `storage/logs/` is no longer committed (changed 2026-08-18)

The three log files used to be tracked. They churned on **every page load** -
one afternoon of testing added 636 lines to `audit.log` alone - which meant a
merge conflict for four people on every pull, and they record real account email
addresses.

Nothing is lost. `Logger::write()` creates the folder if it is missing and
`file_put_contents(..., FILE_APPEND)` creates the file on the first write, so a
fresh clone rebuilds all three the moment the app runs. `storage/logs/.gitkeep`
is still tracked, so the folder itself survives.

**If you are pulling this change**, the three files stay on your disk untouched;
git simply stops carrying them. Nothing to do.

### `storage/uploads/` is not committed (changed 2026-10-09)

Only the empty folder and its `.gitkeep` are in the repo. Uploads are NIC scans,
registration documents and photos - personal data that has no place on GitHub.
Nothing depends on them: the seed accounts in `togetherthrives-full.sql` have no
document paths, and every post is created through the app, so whoever clones
starts with an empty, working uploads folder. `FileUploader` creates the
sub-folders on first upload.

> Files committed before this change are still in the git history. If any were
> real documents, the history needs rewriting, not just this ignore rule.

### `CRUD-Flows.pdf` is committed too

It is a deliverable, not a build artefact, so it is deliberately not ignored.

---
