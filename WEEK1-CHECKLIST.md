# Setup, Authentication & Modules — weeks 1–6

**Weeks 1–2: Done** ✅  
Scaffold and authentication complete. All four roles register, admin approves,
each lands on its dashboard with the right sidebar.

**Week 3: done** ✅ — **M4 · Admin + shared** (all 12 admin screens and every
shared screen), **M3 · Blood** (all 12 frames in `UI/Blood/`), **M1 · Cleanup**
(all 10 frames in `UI/Cleanup/`) and **M2 · Donation** (all 10 frames in
`UI/Donation/`). **There are no dead links left in any sidebar** — every entry
in all five role sidebars opens a page.
Details in the four week 3 sections below.

**Weeks 4–5: all four done** ✅ — **M4 · Reuse** (`reuse_items`),
**M3 · Blood** (`blood_campaigns`), **M2 · Donation** (`donation_requests`
plus its `requested_items` child rows) and **M1 · Cleanup** (`cleanup_events`)
all have full working CRUD: real forms that save, real ownership checks, real
soft deletes, and one admin review queue each.
Details in the weeks 4–5 sections below.

**Week 6: done** ✅ — the whole app was crawled as all six callers (328 page
loads). Three dead links were found and fixed, all 67 form actions were matched
against the router, and both arms of the end-to-end flow pass. Zero 404s left.
Details in the week 6 section below.

**The interim deliverable is complete.** Weeks 1–6 are all ✅.

Read the two sections directly below before you pull or start building — they are
the things most likely to bite you.

---

## Open actions — still outstanding (git hygiene, not code)

- [x] ~~**`git rm --cached -r storage/uploads`**~~ — **the group decided the other
      way, and `.gitignore` now says so in a comment:** the proof documents are
      committed on purpose, because every contributor needs the seed accounts'
      documents for the verification queue to have anything in it. 16 files are
      tracked (NIC, org, GN and blood-bank documents, plus item photos).
      **This only stays safe while they are made-up test documents.** If anyone
      ever registers with a real NIC scan, it must not be committed — untrack it
      before pushing. This bullet used to claim `.gitignore` excluded the folder;
      it never did, and that claim is what needed fixing.
- [ ] **Push to GitHub and everyone pulls.** The scaffold and auth are stable
      enough to share. One person pushes; everyone else clones and confirms the
      landing page loads.
- [ ] **Agree on branch naming:** `feature/m1-cleanup-frontend` (week 3),
      `feature/m1-cleanup-backend` (weeks 4–5). One branch per person per week.
- [ ] **Agree: never push to `main` directly.** Always make a PR. Always pull
      `main` before you start a session.
- [x] ~~**Assign who builds `/support`.**~~ Built in week 3 by M4 — it is the
      "Support the System" frame, with the sandbox checkout behind it.

## Full system test — run after week 6

Everything below was exercised against the running app with scripted browser
sessions: real forms, real uploads, real CSRF tokens. Every row and file created
was deleted afterwards, and `users` is back to exactly the five seed accounts.

| Suite | Scope | Result |
|---|---|---|
| Authorization matrix | every GET route × 6 callers = **576 pairs**, expectation derived from each route's own middleware | **0 defects** |
| CRUD lifecycles | all four modules, create → review → approve → edit → reject → resubmit → delete | **44/44** |
| Validation | missing, over-long, tampered ENUM, bad FK, zero/negative, bad date order | **25/25** |
| Security | traversal, IDOR, CSRF, SQLi, XSS, ownership, session, login | **48/48** |
| Document access | the verification-document route, pending → approved | **7/7** |
| End-to-end flow | both arms (community, and organisation-with-approval) | **15/15** |
| Link crawl | 328 page loads across 6 callers | **0 dead links** |
| PHP diagnostics | 276 rendered pages | **0 leaks** |

**The 24 "mismatches" the authorization matrix reports are all correct behaviour**
that a middleware-only model cannot predict, and each was confirmed by hand:
8 are controller-level **ownership** checks on `/my/reuse/*` (the middleware is
`auth` only, the controller then refuses a non-owner); 5 are `/` redirecting a
signed-in user to their own dashboard; 6 are `/language/{code}` redirecting back
after switching; 1 is an invalid password-reset token bouncing to
`/forgot-password`; 4 are `/file/{folder}/{name}` refusing a caller who does not
own the file. No route lets a caller reach something it should not.

### Two bugs found and fixed ✅

Both sat in frozen files. They were written up first and **fixed only after the
group gave explicit permission** — see "Frozen files that changed" below.

1. **`/file/{path}` was a dead route, and `upload_url()` with it.** A router
   param compiles to `([^/]+)`, but every stored path is `folder/name.ext`, so
   `/file/nic-documents/abc.pdf` could never match — it 404'd, and URL-encoding
   the slash did not help. `upload_url()` builds exactly that URL, so **the
   helper CLAUDE.md tells you to use produced a 404**, and
   `FileController::serve()` with its `mayRead()` check was dead code.

   **Fix:** the route now takes the two segments it always had —
   `/file/{folder}/{name}` — and `FileController::serve()` takes them as two
   params and rejoins them. `upload_url()` was left alone: it already builds the
   right URL, which now matches. Nothing else changed.

   This made `mayRead()` live for the first time, so it was tested hard rather
   than assumed: an owner reads their own NIC scan, an organisation reads its
   own document, an admin reads any, and **every crossed pair is refused** — a
   community member cannot read an organisation's document, an organisation
   cannot read a community member's NIC, a blood bank cannot read a GN's, a
   guest gets nothing, and a well-formed path that exists in no row is refused
   rather than served. Twelve path-traversal payloads (including the
   two-segment, double-encoded and backslash forms the new route shape allows)
   are all refused.

2. **A CSRF failure answered HTTP 500, though it rendered the 419 page.**
   `CsrfMiddleware` throws `HttpException(419)` and `views/errors/419.php` is
   what the browser showed, but the status line came back
   `500 Internal Server Error`.

   **Cause, measured rather than guessed:** `http_response_code()` silently
   turns a code it has no reason phrase for into 500. A probe confirmed 403,
   422 and 429 pass through untouched while 419 becomes 500, and that sending
   the status line by hand works.

   **Fix:** `App::renderError()` now carries a small reason-phrase table and
   sends the status line itself for codes PHP cannot express, falling back to
   `http_response_code()` for everything else. A CSRF failure now answers
   `419 Authentication Timeout`. There was never a security impact — the request
   was always refused and nothing written — but an expired session no longer
   reads as a server error in the logs.

### Frozen files that changed

`CLAUDE.md` lists these as never-edit. **Both were changed with explicit
permission** after the bugs above were reported and the group agreed. Pull
before you start, and do not treat this as a precedent — the rule still stands.

| File | Change |
|---|---|
| `config/routes/web.php` | `/file/{path}` → `/file/{folder}/{name}` |
| `app/core/App.php` | `renderError()` sends the status line itself for codes PHP cannot express |

One ordinary file changed with them: `app/controllers/FileController.php`,
whose `serve()` now takes two params.

**Re-verified after the fixes, with no regression:** all four CRUD lifecycles
(44/44), validation (25/25), security (48/48 — up from 35 because the live
`/file` route added real cases), documents (7/7), both end-to-end arms, 328 page
loads with zero dead links, 276 pages with zero PHP diagnostics, and 67 form
actions all matching a route.

## Three rules weeks 3–6 depend on

Learned the hard way. They will cost someone a day each if rediscovered.

**Week 3 (frontend): A role must never borrow another role's sidebar.**
`layouts/app.php` mapped blood banks to the org sidebar — both run campaigns.
But `/org/*` is gated `role:organization`, so blood banks saw links that
answered **403** and had no working navigation. When you add a new screen in
week 3, check which sidebars link to it. If two roles need it, widen that
route's `role:` list deliberately — do not reuse another role's sidebar.

**Weeks 4–5 (backend): Child and lookup tables need `$softDelete = false` —
and check the timestamp columns too.** `Model::update()` writes
`updated_at = NOW()` unconditionally, and **`Model::create()` writes
`created_at = NOW()` unconditionally**. If your table lacks either column you get
a SQL error on that operation.

- **No `updated_at`** (so `update()` fails): `item_images`, `gs_verifications`,
  `reuse_feedback`, `screening_responses`, `blood_donation_records`, `payments`,
  `attendance`, plus the week 2 tables (`notifications`, `password_resets`,
  `email_verifications`).
- **No `created_at` either** (so `create()` fails as well): **`requested_items`**
  (only `deleted_at` — M2 hit this, its inserts go through `Database::run()`),
  `distribution_cooldowns`, `cities` and `districts` (no timestamps at all).

*Correction:* an earlier version of this list included `item_requests`. It
actually has all three columns, so `create()` and `update()` both work on it.
The list above was checked against `information_schema` on 12 Aug 2026.

Their writes go through `Database::run()` instead. **Check your own tables in
`schema.sql` before assuming `create()` or `update()` works.**

**Every session (weeks 3–6): Lint before you commit.**  
`php -l app/controllers/your/file.php` catches syntax errors. Opening the URL
catches everything else. A commit that does not run blocks whoever pulls next.

---

## Environment (do this on every machine)
- [x] Copy `.env.example` to `.env` and set `BASE_URL` to your own path
      (this project runs at `http://localhost/tt/public`)
- [x] Import `database/schema.sql`, **then** `database/seeds/seed.sql`
      — the seed is easy to forget and the app looks broken without it
- [x] ~~Run every file in `database/migrations/`~~ — no longer needed for a fresh
      import. `users.nic_document_path` is now part of `schema.sql` itself, so
      schema + seed is the whole setup. Only a database created before
      2026-08-11 still needs the migration.
- [x] Confirm `http://localhost/tt/public/` loads the homepage
- [x] Confirm `admin@togetherthrives.lk` / `Admin@123` signs in

## Done in the week 1 scaffold
- [x] Folder structure + autoloader
- [x] Front controller, .htaccess rewrite
- [x] Router with `{param}` support, groups, middleware
- [x] Base Controller (view / json / redirect / validate)
- [x] Base Model (create / find / all / update / soft delete / count / exists)
- [x] Database wrapper (PDO, prepared statements, transactions)
- [x] Auth (attempt / login / logout / role checks / password hashing)
- [x] Validator (18 rules incl. `nic_lk`, `phone_lk`, `unique`)
- [x] Session + flash + old-input repopulation
- [x] CSRF token + middleware
- [x] 5 middleware: auth, guest, role, verified, csrf
- [x] FileUploader with real MIME sniffing
- [x] Logger (app / error / audit)
- [x] Global helpers (`e`, `url`, `asset`, `old`, `error_for`, `format_money`, `time_ago`)
- [x] Layouts: guest, app, auth + role sidebars + error pages
- [x] CSS design tokens ported from Figma + component library
- [x] Full schema: 45 tables, all FKs resolve
- [x] Seed data: 25 districts, 37 cities, 11 categories, 8 screening questions, admin user
- [x] Split route files — one per member, no merge conflicts
- [x] ~~Export icons from Figma~~ — replaced by inline SVG in
      `app/views/partials/icons.php`, used as `<?= icon('mail') ?>`. No image files needed.
- [x] Assign the four CRUD entities formally — recorded in the CLAUDE.md ownership table

---

## Week 2 — Authentication ✅

**Done means:** all four account types register, admin approves, they log in,
and each lands on the correct dashboard with the correct sidebar.

Signed off by walking that sentence end to end, one account per role:

| Role | Registers | Admin approves | Lands on | Sidebar |
|---|---|---|---|---|
| community | ✅ active at once | n/a | `/dashboard` | own |
| organization | ✅ pending | ✅ | `/org/dashboard` | own |
| grama_niladhari | ✅ pending | ✅ | `/gn/dashboard` | own |
| blood_bank | ✅ pending | ✅ | `/blood-bank/dashboard` | own |
| admin | seed only | n/a | `/admin/dashboard` | own, 11/11 links live |

No role is shown a link it is not allowed to open. Everything still unreachable is
an unbuilt module from weeks 3–5, not a permissions fault.

### Phase 0 — Design foundation ✅
- [x] `pages/auth.css` — split auth shell, icon inputs, password toggle, checkbox,
      role-radio cards, file dropzone
- [x] `pages/landing.css` — hero, stats strip, module cards, role cards, bands, CTA
- [x] `layouts/auth.php` rewritten as the two-column shell; `layouts/auth-centre.php` added
- [x] `partials/icons.php` — 28 inline SVG icons

### Phase 1 — M4 · Login, logout, session ✅
- [x] Homepage rebuilt to the Figma design (8 sections)
- [x] `/register` account-type chooser
- [x] `AuthController@showLogin / login / logout`
- [x] `views/auth/login.php`
- [x] `Admin\AdminDashboardController` + `/org`, `/gn`, `/blood-bank` dashboard routes
      — `Auth::homeFor()` pointed at three routes that did not exist
- [x] Verified: sign in → correct dashboard + sidebar → sign out; wrong password,
      pending account and invalid email all show the right message

### Phase 2 — M1 · Registration ✅
- [x] `Organization`, `GramaNiladhari`, `BloodBank`, `City`, `District` models
      (`City` and `District` are lookups — `$softDelete = false`, and they have no
      `created_at`, so always pass an explicit `$orderBy` to `all()`)
- [x] `RegistrationService` — two-table insert inside `Database::transaction()`
- [x] Community form → `STATUS_ACTIVE`, can sign in immediately
- [x] Organization, GN and Blood Bank forms → `STATUS_PENDING` + proof document
- [x] `/registration-submitted` success screen
- [x] Verified: all four roles create correct rows in both tables; duplicate email,
      bad phone/NIC, mismatched password and unchecked terms all rejected with the
      field highlighted and old input kept; a failed detail insert rolls the user row
      back and deletes the orphaned upload

### Phase 3 — M3 · Admin verification queue ✅
- [x] `Notification` model (`notifications` has no `deleted_at` — `$softDelete = false`)
- [x] `VerificationService` — approve / reject / suspend / reinstate, each writing the
      status change **and** the applicant's notification in one transaction, and
      stamping `verified_at` / `verified_by` on the detail row
- [x] `Admin\OrgVerificationController@index / show / document / approve / reject`
- [x] `Admin\UserManagementController` + `Admin\OrganizationController` — the Users
      and Organizations frames, sharing the same service
- [x] `FileController@serve` — `/file/{path}` was routed in `web.php` but had no
      controller, so every uploaded document 500'd. (The route still could not
      match a real two-segment path; that was only found by the full system test
      much later, and is fixed now.)
- [x] Admin shell from `admin.svg`: sidebar with icons, section groups and a live
      pending badge; topbar with search and unread count; `pages/admin.css`
- [x] The other eight sidebar screens route to `Admin\PlaceholderController` so the
      sidebar matches the design with no dead links
- [x] Verified: pending org → approve → signs in and lands on `/org/dashboard`;
      reject writes the reason into the applicant's notification and blocks sign-in;
      suspend and reinstate round-trip; approving twice is refused; CSRF and
      `role:admin` both enforced; documents are admin-only and traversal is refused

### Phase 4 — M2 · Password reset + email verification ✅
- [x] `PasswordReset` + `EmailVerification` models — no `deleted_at` **and no
      `updated_at`** (see the rule at the top of this file)
- [x] `Services\Token` — one place for token length, hashing and TTLs, so the two
      features cannot drift apart on the security-critical parts
- [x] `PasswordResetService` / `EmailVerificationService`
- [x] `PasswordController@showForgot / sendLink / showReset / reset`
- [x] `AuthController@verifyEmail` + registration now issues a confirmation link
- [x] Forgot-password screen built from `docs/ui-reference/03-forgot-password.png`;
      reset and verify screens follow the same card (no frames exist for those two)
- [x] Verified: full round trip on a real account; expired, forged, spent and
      replayed tokens all refused; mismatched and weak passwords rejected with the
      field highlighted; old password stops working and the new one signs in;
      CSRF and `guest` both enforced; throttle stops at 5 links per address per hour

**Tokens are hashed at rest.** The link carries the raw token, the table stores
only its SHA-256. Someone who reads `password_resets` — the group all share a root
MySQL login with no password — still cannot take over an account. It also means
lookup is a hash match rather than a string comparison, so there is no timing side
channel. Reset links last 1 hour, confirmation links 48 hours.

**The forgot-password form never says whether an address is registered.** Same
screen, same message, whether the account exists, does not exist, or is being
throttled. A form that answers "no such account" is a free way to test who is a
member. This is why `sendLink()` ignores what the service returns.

**Nothing is gated on `email_verified_at` yet.** Two different things share the
word "verified" in this schema — `email_verified_at` is the address check,
`users.status` is the administrator decision, and only the second one controls
access (`VerifiedMiddleware` reads `status`). Turning an unconfirmed address into
a sign-in block would lock out every account registered before this phase, so it
is left for the group to decide.

### Phase 5 — sign-off fix · blood bank navigation ✅
- [x] `partials/sidebar-blood-bank.php` + one arm of the `match` in `layouts/app.php`

Found while checking the week-2 criterion literally, role by role, rather than
while building a feature — every phase had passed its own tests. The rule this
produced is at the top of this file.

## Decisions taken in week 2

The Figma auth screens ask for fields the frozen schema has no columns for.
Agreed rulings — do not re-litigate these per form:

- **`username` dropped.** No column on `users`. The sign-in field is labelled
  "Email Address" because `Auth::attempt()` looks up by email only.
- **Unmapped fields dropped.** Not built: Date of Registration, Primary Focus Area,
  Organization Size, Date of Birth, Gender, Address. Forms are shorter than the Figma
  on purpose.
- **GN Division Code dropped** from the form. `grama_niladharis.division_code` is
  `NOT NULL`, so registration stores it empty and the administrator fills it in when
  verifying the officer.
- **Community NIC is a file upload**, matching the Figma frame. This is what forced
  the `schema.sql` change below — `users` had nowhere to record a document path
  (`nic` is `VARCHAR(15)`, and a stored path is ~50 characters).
- **Blood Bank signup adapted from the Organization form** — no design frame exists,
  but the route and `blood_banks` table do.
- **`/register` is a standalone page** reusing the four role cards from the homepage.

Rendered PNGs of every auth screen are in `docs/ui-reference/` — the source SVGs are
too large to open comfortably.

---

---

## Week 3 — Frontend render-out: **ALL Figma UIs** (all four members, parallel)

**Deliverable:** EVERY Figma frame exists as an HTML page. No backend, no data
persistence — just layout, styling, forms that redirect without saving, and
sample data to show what the page looks like with content.

**Follow the Figma designs — "good enough" is OK.**

Use Figma as a reference, not a pixel-perfect spec. Match the layout and design intent.

What must match:
- Layout and structure (where things go)
- Component types (use existing `.card`, `.table`, `.field` classes)
- Color scheme and hierarchy (use CSS tokens from variables.css, not custom colors)

What's optional:
- Exact spacing (within ~5px is fine)
- Font sizes (close enough is good)
- Shadows and subtle effects

Figma source files are in `UI/` folder. Open the frame to understand the design,
then build it using existing CSS classes and sample data. Focus on getting the
layout right, not the spacing perfect.

**Scope is large:** Not just the four CRUD modules, but also shared screens,
admin panels, user profiles, settings, notifications, messaging, dashboards,
support, disaster reporting, etc. for all five user types.

**Week 3 Workload Division — DECIDED:**

**M4 · Janagan (You) — Heavy lift: Admin + Shared screens**
- Reason: Reuse CRUD already done ✅, so you have capacity for admin + shared foundation
- **Admin (12 screens):** Dashboard, Verification, Users, Organizations, Suspended, Complaints, Announcements, Dengue, Admin Mgmt, Logs, Analytics, Settings
- **Shared (11 screens):** Dashboards (5 roles), sidebars + top nav, profiles + settings, notifications, messaging, organizations, my activities, support, disaster report, payment/checkout, error pages (404/403/500) + system pages (registration/verification/reset)
- **Workload: ~50% of week 3**

**M1 · Dilsha (Cleanup) — Cleanup module only**
- Browse, create, detail, edit, volunteer sign-up, feedback, activities
- Focus entirely on cleanup screens — no shared screens
- **Workload: ~15% of week 3**

**M2 · Janudi (Donation) — Donation module only**
- Browse requests, create form, detail, edit, pledge flow, status flow, activities
- Focus entirely on donation screens — no shared screens
- **Workload: ~15% of week 3**

**M3 · Shenol (Blood) — Blood module only**
- Campaign browse, detail, screening form, eligibility, appointment, activities
- Focus entirely on blood screens — no shared screens
- **Workload: ~15% of week 3**

**Remaining ~5%:** Landing page verification, edge cases

**Payment/Checkout (sandbox):** Forms for card input (cardholder name, card
number, expiry, CVV, billing address), amount display, confirmation screen. No
backend processing — form just redirects after "submit." Use this for donation
pledges, cleanup fees, blood bank registrations, etc.

**Verify:** 
- Every sidebar link opens a page (zero 404s)
- Every role can access only their screens
- Every form renders without errors (clicking submit redirects, no save)
- Guest can browse public pages
- Sample data: lists with 5+ items, detail pages with content
- Notifications panel has sample notifications
- Chat/messaging has structure (or placeholder if not in Figma)

**Pattern to copy:** The reuse module's frontend (`app/views/reuse/*.php`,
`public/assets/css/pages/reuse.css`) shows layout, CSS classes, form structure,
and how to loop sample data for lists.

---

## Week 3 — M4 · Admin + shared screens ✅

**Done means** (from the plan): every screen in this member's scope renders,
every sidebar and topbar link opens a page, every form renders and behaves, and
each role sees only its own screens.

Signed off by walking that sentence role by role, the same way week 2 was —
not by testing each screen as it was built. Every path below returned 200 as
the role that owns it; a community member gets 403 on `/admin/*` and on
`/gn/disasters`.

### Admin — 12 screens

| Screen | Route | Data |
|---|---|---|
| Dashboard | `/admin/dashboard` | **real** — pending counts, accounts by role, recent registrations |
| Verification Requests | `/admin/verification` | real (week 2, untouched) |
| Users | `/admin/users` | real (week 2, untouched) |
| Organizations | `/admin/organizations` | real (week 2, untouched) |
| GS Letter Review | `/admin/reuse/letters` | real (reuse module, untouched) |
| Suspended Users and Posts | `/admin/suspended` | **real accounts** + sample posts |
| Complaints & Feedback | `/admin/complaints`, `/admin/complaints/{id}` | sample |
| Announcements | `/admin/announcements` + create/edit | sample |
| Dengue Alert | `/admin/dengue` | sample alerts, **real district list** |
| Admin Management | `/admin/admins` | **real admins** + sample rows |
| System Logs | `/admin/logs` | **real** — reads `storage/logs/{audit,error,app}.log` |
| Analytics & Stats | `/admin/analytics` | **real** — live counts per module |
| Settings | `/admin/settings` | form only, nothing persisted |

`Admin\PlaceholderController` and `views/admin/placeholder.php` are **deleted**.
`config/routes/admin.php` had its eight placeholder targets swapped, exactly as
the comment in that file asked; nothing above that block was touched.

### Shared screens

Dashboards for all five roles · sidebars and both topbars · profile · settings ·
notifications · messaging · organizations directory and profile · support
(fund the platform) · report a problem · dengue risk map · disaster reporting
(list, create, detail) · sandbox checkout and receipt · search.

New controllers live in `app/controllers/` (root namespace) and
`app/controllers/admin/`; new views under `app/views/{home,profile,notifications,
messages,organizations,support,report,dengue,search,checkout,gn,admin}/`.
Week-3 placeholder content is in **one file**, `app/services/SampleData.php`,
so it is obvious what to delete when the real queries land.

### Decisions taken

- **`config/routes/web.php` was not edited** — it is on the do-not-modify list
  in CLAUDE.md. It already declared `/profile`, `/settings`, `/notifications`,
  `/messages`, `/organizations` and `/support`, so those only needed
  controllers. The shared screens it does *not* declare — `/search`, `/report`,
  `/dengue`, `/checkout`, `/gn/disasters`, and the `POST /settings` — are
  declared in `routes/reuse.php` under a labelled block, the same reasoning as
  the GS letter review routes. If the group opens web.php, move them.
- **One dashboard controller method, four views.** `/org/dashboard` and
  `/blood-bank/dashboard` live in Members 1 and 3's route files and both point
  at `HomeController@dashboard`. Rather than ask them to repoint their files,
  that method now picks the view from `Auth::role()`.
- **No organization or blood-bank dashboard frame exists.** Cleanup-Org.svg and
  Blood-Org.svg both start at their module list. Those two dashboards follow the
  GN frame's shape, which is the house pattern for a role landing page.
- **Profile drops Username, Date of Birth, Gender and postal address** — the
  same week-2 ruling that shortened the registration forms. No columns exist.
- **Messaging renders but cannot send.** `conversations`,
  `conversation_members` and `messages` are in the schema and nothing writes to
  them; the compose box is `disabled` rather than posting into a void.
- **System Logs reads the log files, not `audit_logs`.** `Logger::audit()` has
  been writing them since week 2, so the screen shows real history today.
  Moving the logger onto the table later would not change the screen.
- **No new seed content.** Nothing was written to the database — re-checked
  after running every form: `announcements`, `disaster_reports`, `reports`,
  `dengue_risk_districts` and `payments` are all still empty, and there is still
  exactly one administrator.

### Fixed while sweeping the navigation

- **`nav_active()` lit two sidebar links at once.** It is a bare
  `str_starts_with`, so `/blood-bank/dashboard` counted as being inside
  `/blood` and a blood bank saw its dashboard *and* "Browse Campaigns"
  highlighted. The helper is in `app/core/Helpers.php`, which nobody may edit,
  so `views/partials/nav-active.php` adds `sidebar_active()` — the same test on
  a path boundary — and both sidebar partials use it. Delete that file if the
  group ever agrees to fix the helper itself.
- **`/support` used to answer 500 on every role.** That was the last open action
  at the top of this file. It is a real screen now.
- **The topbar Report link pointed at `/login`** for signed-in users. It points
  at `/report`. The guest topbar still sends people to sign in, which is right —
  reporting needs an account.
- **The admin topbar's bell and inbox pointed at `/admin/verification` and
  `/admin/complaints`.** They now open the real Notifications and Messages
  screens, which is what the `Notifications - Admin` and `Messages - Admin`
  frames show.
- **Role sidebars had no icons.** `sidebar-render.php` now takes an optional
  third element per item, so all four sidebars match their frames.

### Not done — belongs to other members

`/cleanup`, `/donations` and `/blood` still answer **500**: their controllers do
not exist. They are M1, M2 and M3's week 3 screens, and they are linked from the
community, organization, GN and blood-bank sidebars, which are otherwise
complete. Until those three land, "zero dead links" is not true for the group as
a whole even though it is true for every screen in M4's scope.

---

## Week 3 — M3 · Blood ✅

Built on request. All 12 frames in `UI/Blood/` render, every route answers for
every role that may open it, and every form validates against the real frozen-
schema columns without writing a row.

**Ownership note:** this is Shenol's module. `config/routes/blood.php`, the
`Blood\` namespace and `app/views/blood/` are M3's under CLAUDE.md's one-file-
per-member rule. It was built at the project owner's explicit request. Whoever
owns it from here is free to change any of it — read the decisions below first.

### What exists

| Piece | File |
|---|---|
| Sample data — self-contained, delete in weeks 4–5 | `app/services/BloodSampleData.php` |
| Campaign controller — hub, camp detail, organiser CRUD | `app/controllers/blood/BloodCampaignController.php` |
| Critical request controller | `app/controllers/blood/CriticalRequestController.php` |
| Screening controller, incl. eligibility scoring | `app/controllers/blood/ScreeningController.php` |
| Certificate controller | `app/controllers/blood/CertificateController.php` |
| Routes — the original six CRUD routes unchanged, the rest added | `config/routes/blood.php` |
| Stylesheet + its `main.css` import line | `public/assets/css/pages/blood.css` |
| Bare layout for printable documents | `app/views/layouts/blank.php` |
| 10 views | `app/views/blood/` |

| View | Route | Frame |
|---|---|---|
| `index.php` | `/blood` (+ `?tab=`) | Blood-User — "Blood Donation", and the My Donations / My Registrations frames |
| `show.php` | `/blood/{id}` | Blood-User — "Camp Details" |
| `critical-form.php` | `/blood/critical/create` | Blood-User — "Post a Critical Blood Request" |
| `critical-show.php` | `/blood/critical/{id}` | Blood-User — "Critical Request Details" |
| `screening.php` | `/blood/{id}/screening` | Blood-User — "Blood donation Form" |
| `screening-done.php` | `/blood/screening/submitted` | Blood-User — "Form Submitted" |
| `certificate.php` | `/blood/certificate/{id}` | Blood-User — A4 certificate |
| `manage.php` | `/my/blood` | Blood-Org 1 — "Your Posted Blood Donation Campaigns" |
| `form.php` | `/my/blood/create`, `/{id}/edit` | Blood-Org 2 — "Create New Blood Campaign" |
| `detail.php` | `/my/blood/{id}` | Blood-Org 3 — campaign detail + registered donors |

### How it was signed off

Walked the same way week 2 and M4's week 3 were — against the criterion, role by
role, not screen by screen as each was built.

- **Every route, every role.** 18 community URLs, 10 as a blood bank, 4 as an
  organization, 2 as a guest. All 200. A community member gets 403 on
  `/my/blood`; an unknown id gets 404 on `/blood/99`, `/blood/critical/99` and
  `/my/blood/99` rather than a blank page.
- **Every form, valid and invalid.** Campaign create / update (PUT) / destroy
  (DELETE), critical request, join, respond, and screening. Valid submissions
  land on the target page, invalid ones bounce back with the error on the right
  field and the old input still in the box. A missing CSRF token is refused on
  all of them.
- **Screening scoring checked against its own rule.** All fourteen answered
  "no" → 4 deferrals, because q1–q4 disqualify on "no". q1–q4 "yes" and the rest
  "no" → eligible. Adding q5 and q6 → 2 deferrals. A blank answer is an error,
  not an assumed "no" — 13 unanswered questions produced 13 highlighted rows.
- **Nothing was written.** `blood_campaigns`, `critical_blood_requests`,
  `screening_responses` and `blood_donation_records` are all still empty after
  running every form, and `users` / `reuse_items` are untouched.
- **No regressions.** Swept M4's admin and shared screens and the reuse module
  afterwards — all 200.

### Decisions taken

- **No model yet.** `Models\Blood\BloodCampaign` is deliberately not written.
  The model plus real queries *is* M3's weeks 4–5 CRUD deliverable, and week 3
  needs no database reads. The controllers read `BloodSampleData` instead.
- **The forms validate against the real columns.** `validateCampaign()` and the
  critical-request rules use the actual `blood_campaigns` and
  `critical_blood_requests` columns and ENUMs, so weeks 4–5 keeps them and
  replaces only the flash with a `create()` call.
- **Two Figma fields are collected and dropped:** the blood types a campaign
  needs, and a camp's End Date — `campaign_date` is a single DATE. Neither has a
  column, and both are called out in a hint under the field itself rather than
  silently vanishing. Same ruling as the week 2 registration forms.
- **`organizer_type` is not on the form.** It follows from whoever is signed in,
  so weeks 4–5 sets it from `Auth::role()` rather than trusting a posted field.
- **Screening is scored for real.** `ScreeningController::verdict()` applies each
  question's disqualifying answer, the way `screening_responses.result` will.
  The verdict is flashed, not stored.
- **Joining goes through screening.** "Join This Camp" and "I Can Donate" both
  redirect to the screening form rather than registering directly, because both
  frames promise that contact details are shared only after an eligibility
  check. A donor who screens out has their contact withheld on the organiser's
  donor table too.
- **The campaign-type switch is two links, not a radio.** A camp and an urgent
  request are different tables and different screens, so the second card links
  to `/blood/critical/create`.
- **Certificate gets its own layout.** It is an A4 document, not an app screen,
  so `layouts/blank.php` renders it with no chrome, and a `@media print` block
  hides the buttons so Ctrl+P gives the sheet alone.
- **One eligible-donor figure on the organiser detail.** Counting the five-row
  donor sample would contradict the "Registered" tile above it, so both the tile
  and the group-chat line read a single variable.

### Rendering the Figma frames, if you need to go back to them

Both blood SVGs are single wide exports with the text as paths, and the frames
are **not** evenly spaced — guessing a stride gives you half-frames. Detect the
frame edges instead: render the whole SVG small, then scan for runs of non-black
columns. `Blood-User.svg` has 9 frames at x = 0, 2868, 4468, 6068, 8220, 9960,
12120, 13684, 15284 (all 1440 wide except the certificate at 1124).
`Blood-Org.svg` has 3 frames at x = 0, 1540, 3080.

`storage/` is `Deny from all` in Apache, so a wrapper page there cannot load
anything over http — render with `file://` URLs.

### Shared files touched

- `public/assets/css/main.css` — `@import 'pages/blood.css';` moved out of the
  commented block. M1 and M2's lines are still commented where they were.
- `app/views/layouts/blank.php` — new, and general-purpose: any printable
  receipt or letter can use it.

Nothing else outside `app/controllers/blood/`, `app/views/blood/`,
`app/services/BloodSampleData.php`, `config/routes/blood.php` and
`public/assets/css/pages/blood.css` was changed.

---

## Week 3 — M1 · Cleanup ✅

Built parallel to M3. All 10 frames in `UI/Cleanup/` render, every route answers
for every role that may open it, and every form validates against the real frozen-
schema columns without writing a row.

### What exists

| Piece | File |
|---|---|
| Sample data — self-contained, delete in weeks 4–5 | `app/services/CleanupSampleData.php` |
| Main controller — hub, detail, org CRUD | `app/controllers/cleanup/CleanupEventController.php` |
| Volunteer registration controller | `app/controllers/cleanup/VolunteerController.php` |
| Feedback controller | `app/controllers/cleanup/FeedbackController.php` |
| Attendance/volunteer list controller | `app/controllers/cleanup/AttendanceController.php` |
| Certificate controller | `app/controllers/cleanup/CertificateController.php` |
| Routes — critical order enforced (literals before params) | `config/routes/cleanup.php` |
| Stylesheet + its `main.css` import line | `public/assets/css/pages/cleanup.css` |
| Bare layout for printable documents | `app/views/layouts/blank.php` (shared with blood) |
| 10 views | `app/views/cleanup/` |

| View | Route | Frame | Access |
|---|---|---|---|
| `index.php` | `/cleanup` (+ `?tab=`) | Cleanup-User — Browse, All · Joined tabs, filters | public / auth |
| `show.php` | `/cleanup/{id}` | Cleanup-User — Campaign detail with Join | public / auth |
| `propose.php` | `/cleanup/create` | Cleanup-User — Community proposal form | auth, community |
| `join.php` | `/cleanup/{id}/join` | Cleanup-User — Volunteer sign-up | auth, community |
| `feedback.php` | `/cleanup/{id}/feedback` | Cleanup-User — Post-event feedback | auth, community |
| `certificate.php` | `/cleanup/certificate/{id}` | Cleanup-User — A4 certificate | auth, community |
| `manage.php` | `/org/cleanup` | Cleanup-Org 1 — Your Posted Campaigns list | auth, organization |
| `form.php` | `/org/cleanup/create`, `/{id}/edit` (+ `?step=2`) | Cleanup-Org 2 — Create/edit form, two pages | auth, organization |
| `detail.php` | `/org/cleanup/{id}` | Cleanup-Org 3 — Campaign detail + stats | auth, organization |
| `volunteers.php` | `/org/cleanup/{id}/volunteers` | Cleanup-Org 4 — Registered volunteers & attendance | auth, organization |

### How it was signed off

Walked the same way week 2 and M4's week 3 were — against the criterion, role by
role, not screen by screen as each was built.

- **Every route, every role.** 13 public and 20 authenticated routes. All 200. An
  unknown campaign id returns 404. A community member gets 403 on `/org/cleanup`;
  an organization gets 403 on `/cleanup/create`.
- **Every form, valid and invalid.** Community proposal (6 fields), volunteer
  sign-up (6 fields), feedback (rating + comment), organization create/edit
  (9 fields). Valid submissions land on the target page, invalid ones bounce back
  with the error on the right field and the old input still in the box. A missing
  CSRF token is refused on all of them.
- **The nine fields validated are real columns.** `validateEvent()` checks title,
  description, category, event_date, start_time, end_time, meeting_point, city_id,
  volunteer_limit, and safety_guidelines — only those nine. They map exactly to
  `cleanup_events` columns; everything else collected by the form is noted as
  dropped.
- **Nothing was written.** `cleanup_events`, `event_registrations`, `attendance`,
  `event_feedback` and `certificates` are all still empty after running every
  form, and `users` / `cities` are untouched.
- **No regressions.** Swept M4's admin and shared screens, M3's blood module, the
  reuse module and `/my/activities` afterwards — all 200.

### Decisions taken

- **No model yet.** `Models\Cleanup\CleanupEvent` is deliberately not written.
  The model plus real queries *is* M1's weeks 4–5 CRUD deliverable, and week 3
  needs no database reads. The controllers read `CleanupSampleData` instead.
- **Community ownership is unresolved.** The form shows "Create Campaign" to community
  members, the form validates, and it redirects after submission (week 3 saves
  nothing). But `cleanup_events.organization_id` is `NOT NULL`, so a community
  member cannot own a row in the frozen schema. The screen proposes a campaign for
  an administrator to attach to a verified organisation; that rule needs the
  group's decision before the backend is written. This is flagged in the form
  itself, in the controller, and in CLAUDE-CODE-PLAN.md.
- **Figma fields are collected and dropped:** campaign type, disaster severity,
  district, GN division, exact location, disaster description, disaster photos,
  registration deadline, minimum age, team leader name, work areas, emergency
  hotline, nearest hospital, skill checkboxes, equipment provided (from step 1),
  waste photos, certificate template selection, and feedback form selection.
  Each has a hint explaining why (no column, or collected after the event).
  This is the same ruling as the week-2 registration forms and the blood module.
- **The form spans two pages** with `?step=1` and `?step=2` in the query string.
  Step 1 holds Disaster Information, Campaign Info, and Event Schedule; Step 2
  holds Safety Guidelines, Equipment, Waste Management, Communication, and
  Visibility. Next is a link (not AJAX), so you can reload and Back works.
- **`layout: 'blank'` for the certificate.** It is an A4 document, not an app
  screen, so `layouts/blank.php` renders it with no chrome, and a `@media print`
  block hides the buttons so Ctrl+P gives the sheet alone.
- **Notes are `.cl-note` blocks, not `.alert--success`.** The app.js script
  deletes every `.alert--success` six seconds after load, which broke form copy
  that should stay (a consent checkbox, permanent approval notices). The cleanup
  module uses `.cl-note` for permanent inline warnings instead, with color
  variants (`.cl-note--green`, `.amber`, `.red`, `.blue`). `.alert--info` and
  `.alert--error` are not auto-removed.
- **Sample-note prose is wrapped in a `<span>`.** `.sample-note` is `display:flex`,
  so every inline `<code>` or link became a flex item, breaking the sentence into
  gapped chunks. Wrapping in a span keeps it together.

### ~~One open question for weeks 4–5~~ — settled ✅

`cleanup_events.organization_id` is NOT NULL and foreign-keyed to `organizations`,
so a community member has no row to own a campaign with. **Option 4 was taken:
campaigns stay organisation-owned.** `/cleanup/create` is kept — it renders and
validates — but it is a *proposal* that stores nothing and says so on the screen.
The frozen schema is unchanged and no proxy organisation is invented. See the
weeks 4–5 M1 section below for how it reads to a community member.

### ~~Not done — belongs to M2~~ — closed

`/donations` answered 500 when this section was written; it was the last dead link
in the sidebars. M2 built it later the same week, so nothing here is outstanding.

### Shared files touched

- `public/assets/css/main.css` — `@import 'pages/cleanup.css';` added
- `config/routes/cleanup.php` — completely rewritten with correct route ordering
- `public/assets/css/pages/cleanup.css` — new, 600+ lines, module styling

Nothing else outside `app/controllers/cleanup/`, `app/views/cleanup/`,
`app/services/CleanupSampleData.php`, `config/routes/cleanup.php` and
`public/assets/css/pages/cleanup.css` was changed.

---

## Week 3 — M2 · Donation ✅

Built last of the four. All 10 frames in `UI/Donation/` render, every route
answers for every role that may open it, and every form validates against the
real frozen-schema columns without writing a row.

**Ownership note:** this is Janudi's module, built at the project owner's
request the same way M3's blood and M1's cleanup were. The six CRUD routes
`config/routes/donation.php` already declared are unchanged in shape; the rest
of the Figma's screens were added around them.

### What exists

| Piece | File |
|---|---|
| Sample data — self-contained, delete in weeks 4–5 | `app/services/DonationSampleData.php` |
| Request controller — browse, detail, requester CRUD | `app/controllers/donation/DonationRequestController.php` |
| Item reservation controller | `app/controllers/donation/ItemReservationController.php` |
| Checkout, payment and receipt controller | `app/controllers/donation/PaymentController.php` |
| Routes | `config/routes/donation.php` |
| Stylesheet + its `main.css` import line | `public/assets/css/pages/donation.css` |
| 9 views | `app/views/donation/` |

| View | Route | Frame |
|---|---|---|
| `index.php` | `/donations` (+ `?tab=`) | Donation-User — "Donation", plus the Request Type dropdown |
| `show-items.php` | `/donations/{id}` when items | Donation-User — "Donation's Further Details - item" |
| `show-funds.php` | `/donations/{id}` when funds | Donation-User — "Fund Request Details (Community)" |
| `checkout.php` | `/donations/{id}/checkout` | Donation-User — "Fund Payment Checkout" |
| `receipt.php` | `/donations/{id}/receipt` | Donation-User — "Payment Success & Receipt" |
| `manage.php` | `/my/donations` (+ `?tab=`) | Donation-Org — "Donation Requests (Org)" + both dropdowns |
| `form.php` | `/my/donations/create`, `/{id}/edit` (+ `?type=`) | Donation-Org — "Create Donation Request" *and* "· Funds" |
| `detail-items.php` | `/my/donations/{id}` when items | Donation-Org — "View Details · Items" |
| `detail-funds.php` | `/my/donations/{id}` when funds | Donation-Org — "View Details · Funds" |

### How it was signed off

Walked against the criterion role by role, the same way weeks 2 and the other
three week-3 modules were — not screen by screen as each was built.

- **Every route, every role.** Guest, community, organisation, Grama Niladhari
  and admin. All 200 where allowed. An unknown id is 404 on both
  `/donations/{id}` and `/my/donations/{id}`; a guest is redirected to sign in
  from `/my/donations` and from a checkout; a community member gets 403 on
  `/my/donations`; **a fund checkout on an item request is 404**, because the
  URL is wrong rather than the state.
- **Both requester roles were tested, not just the organisation.** GN is half of
  `donation_requests.requester_type` and its sidebar links `/my/donations`, so
  it got the whole workspace sweep. (`sunil@test.lk` is **active**, not pending
  as the test-account table below says — a previous session approved it.)
- **Every form, valid and invalid.** Create (items and funds), update (PUT),
  cancel (DELETE), reserve, and pay. Valid submissions land on the target page;
  invalid ones bounce to the referring form with the error on the right field
  and the typed values still in the boxes — checked field by field, including
  that the two *omitted* fields are the only two left empty. A missing CSRF
  token is refused on all of them.
- **The type-specific rules were checked separately.** `pickup_point` is
  required for an item request and `target_amount` for a fund one; a
  `target_amount` under the minimum is rejected, and a reserve of quantity 0 is
  rejected (proved by pointing the referer somewhere other than the success
  target, so the bounce and the success were distinguishable).
- **Nothing was written.** `donation_requests`, `requested_items`,
  `item_reservations`, `fund_donations`, `payments` and `donation_impacts` are
  all still empty after running every form, and `users` / `reuse_items` are
  untouched.
- **No regressions.** Swept M4's admin and shared screens, M1's cleanup, M3's
  blood and the reuse module afterwards — all 200, and no PHP warning on any
  public page.

### Decisions taken

- **No model yet.** `Models\Donation\DonationRequest` is deliberately not
  written — that is M2's weeks 4–5 deliverable, and week 3 needs no reads.
- **A title field was added to the create form.** The Figma has none, but
  `donation_requests.title` is `NOT NULL` and every card and list prints it.
  Asking for it beats inventing one at save time. Same call as the reuse
  module's "quantity added to the form".
- **The two request types are separate views, not one branching template.**
  An item request lists things to reserve and a fund request takes money; the
  Figma draws them as four different frames (two community, two org) and they
  are four different files. The controller picks on `request_type`.
- **The create form's type is a query string**, so Relief Items ↔ Monetary Funds
  is a normal link with a working back button, not JavaScript.
- **Figma fields with no column are collected and dropped, each saying so:**
  number of affected people, urgency level (both per request and per item),
  minimum donation, contact number, additional instructions, payout bank and
  account number, and the whole "How the Funds Will Be Used" breakdown. Same
  ruling as the week 2 registration forms and M1's and M3's forms.
- **Item category becomes `requested_items.item_name`.** That table has
  `item_name`, `unit` and `qty_required` but no category and no urgency column.
- **No card details are collected, in week 3 or later.** The four card inputs on
  the checkout form have no `name` attribute, so they are not even posted. The
  note on the screen says exactly that.
- **Tab counts are the real sample counts** (5 / 2 / 3), not the Figma's
  invented 12 / 8 / 4.

### Notes for whoever writes the backend

- **`requested_items` has a `deleted_at` but no `created_at` or `updated_at`**,
  so `Model::update()` fails on it — its writes go through `Database::run()`.
  **`payments` has only `created_at`**, so it needs `$softDelete = false` and
  cannot use `update()` either. `donation_impacts` is the same. This is the rule
  at the top of this file catching three more tables.
- **A reservation must move `requested_items.qty_reserved` in the same
  transaction** as the `item_reservations` insert, or the same stock can be
  promised twice.
- **`donation_requests.status` also allows `rejected`**, which has no constant
  in `config/constants.php` and no frame. Either add the constant or leave the
  value unused — do not half-use it.

### Shared files touched

- `public/assets/css/main.css` — `@import 'pages/donation.css';` moved out of
  the commented block. **That block is now empty: all four module stylesheets
  are live.**

---

## Week 3 fixes to other members' modules

Two bugs found by sweeping the public pages as a **guest** after M2 landed. Both
are the same root cause and both are recorded here because they are outside M2's
own files.

- **`/blood` and `/blood/{id}` rendered in `layouts/app` for guests.** Both are
  declared without `auth` in `routes/blood.php`, but
  `BloodCampaignController@index/@show` never chose a layout, so a signed-out
  visitor got a sidebar built for no role and `topbar-user.php` raised
  `Warning: Trying to access array offset on value of type null` — printing the
  server's filesystem path onto a public page. Fixed with the house
  `publicLayout()` helper the reuse, cleanup and donation controllers already
  use.
- **`/cleanup` and `/cleanup/{id}` sat flush against the window edge for
  guests.** `.guest-main` has no padding of its own — the landing page wants
  full-bleed bands — so a public module page has to pad itself. Fixed the way
  `pages/reuse.css` does it: the view is wrapped in `.cl-page` and
  `pages/cleanup.css` pads that under `.guest-body`. Donation uses `.don-page`
  for the same reason.

**The rule this produces, for week 6's audit:** a page reachable without signing
in has to be *opened signed out*. Both bugs were invisible to every test that
logged in first, and both were in modules whose own sign-off had passed.

---

## Weeks 4–5 — Backend CRUD (each member, their own module)

**Deliverable:** One CRUD entity per member, fully working (Create, Read list,
Read detail, Update, Delete). Real data in the database, real forms that save.

- **M1 · Dilsha:** CleanupEvent CRUD — **done ✅** (see below)
- **M2 · Janudi:** DonationRequest CRUD — **done ✅** (see below)
- **M3 · Shenol:** BloodCampaign CRUD — **done ✅** (see below)
- **M4 · Janagan:** ReuseItem CRUD — done ✅ (the worked example)

**Pattern:** Follow the reuse module's worked example (sessions 1–5 in the
section below). One session per day, one commit per session.

### The pattern all four modules follow

It worked three times, and M1 was then built to it. This is the house shape.

**1. The approval lifecycle.** A new row is created `pending` and is invisible to
the public. An administrator approves it, and only then does it appear. Editing
an already-approved row sends it **back to `pending`**, because the fields an
admin checked (venue, dates, contact, target) are exactly the ones an edit can
change. Delete is a **soft delete plus a `cancelled` status** — the owner still
sees it in their own list, the public gets a 404.

**2. Each module owns its own admin queue.** `pending` is useless if nothing can
approve it, and none of the three modules had an approval screen. Each one
declares its queue **in its own route file**, not in `routes/admin.php`:

| Module | Route | Controller |
|---|---|---|
| M4 · Reuse | `/admin/reuse/letters` | `Controllers\Reuse\GsLetterController` |
| M3 · Blood | `/admin/blood/campaigns` | `Controllers\Blood\CampaignReviewController` |
| M2 · Donation | `/admin/donations` | `Controllers\Donation\RequestReviewController` |
| M1 · Cleanup | `/admin/cleanup` | `Controllers\Cleanup\CampaignReviewController` |

Each adds one line to `partials/sidebar-admin.php` with a live pending-count
badge, and routes the screen from its **own** route file rather than editing
another member's.

**3. Fields the Figma draws that have no column.** Collect-and-drop with a hint
under the field on the *create form* (so the screen still matches the design),
but **leave them off the read screens** — a permanently blank field or an
invented number is worse than an absent one. Never fabricate a figure.

**4. Ownership on every write.** `ownedOrFail()` loads the row *including*
soft-deleted ones and 403s if `auth()['id']` is not the owner and the caller is
not an admin. Never trust the id in the URL.

---

### M3 · Blood — `blood_campaigns` CRUD ✅

**Files:** `app/models/blood/BloodCampaign.php`,
`app/controllers/blood/BloodCampaignController.php`,
`app/controllers/blood/CampaignReviewController.php`,
`app/views/blood/admin-campaigns.php`, and the manage/detail/form/show/index
views rewired from sample data to real columns.

- Organiser (blood bank **or** organization) creates at `/my/blood/create` →
  `pending`. Admin approves at `/admin/blood/campaigns` → `published` → the camp
  appears on `/blood`.
- `organizer_type` is set from `Auth::role()`, never from the form.
- Registered / eligible / not-eligible counters are counted from
  `screening_responses`, so they honestly read 0 until screening is written.
- **Dropped, not faked:** the Figma's "Viewed" counter and "Camp Type" readout
  have no column, as do the blood-types list and the multi-day end date.
- `ScreeningController` now looks the camp up in the database and refuses one
  that is not yet approved.
- Still sample data, and labelled as such on screen: critical requests, screening
  verdicts, donation history, certificates.

### M2 · Donation — `donation_requests` CRUD ✅

**Files:** `app/models/donation/DonationRequest.php`,
`app/models/donation/RequestedItem.php`, `app/services/DonationService.php`,
`app/controllers/donation/DonationRequestController.php`,
`app/controllers/donation/RequestReviewController.php`,
`app/views/donation/admin-requests.php`, and the seven module views rewired to
real columns.

- Requester (organisation **or** Grama Niladhari) creates at
  `/my/donations/create` → `pending`. Admin approves at `/admin/donations` →
  `open` → it appears on `/donations`.
- **This entity has child rows.** An item request owns `requested_items` lines
  written on the same form, so create/update/cancel all run inside
  `Database::transaction()` in `DonationService`. Updating **replaces** the line
  set by soft-deleting the old rows and inserting the new ones — a line a donor
  already reserved against keeps its row.
- Rejecting requires a reason (`reject_reason`), which the requester's own detail
  screen prints back to them. Approving does not.
- Switching a request between items and funds **clears the column that no longer
  applies** (`pickup_point` / `target_amount`), or a fund request would keep a
  stale pickup point.
- `ref` (DR-2026-0001) is computed in SQL from the id and the created year —
  there is no reference column.
- **Dropped, not faked:** urgency, disaster type, affected-people count, minimum
  donation, pickup address and hours, payout bank details, and the "How Funds
  Will Be Used" breakdown. The week-3 raised-vs-transferred split on the fund
  detail screen was derived as two thirds of the raised figure — removed, because
  on a real request that invents money movements.
- Still sample data, and labelled as such: reservations, fund donations,
  payments, receipts, the donor wall, and the "My Donations" giving history.

**Two traps this module hit, both worth knowing:**

1. `requested_items` has `deleted_at` but **no `created_at`** — see the timestamp
   rule near the top of this file. Its inserts use `Database::run()`.
2. `Controller::validate()` flashes all raw input on failure, but a **custom**
   bounce that flashes only the validated array wipes repeated inputs like
   `item_name[]`. Flash `$data + Request::all()` and rebuild the rows in the view
   from `form_state()['old']` — `old()` only returns scalars.

---

### M1 · Cleanup — `cleanup_events` CRUD ✅

**Files:** `app/models/cleanup/CleanupEvent.php`,
`app/controllers/cleanup/CleanupEventController.php`,
`app/controllers/cleanup/CampaignReviewController.php`,
`app/views/cleanup/admin-events.php`, plus the module's own views rewired from
sample data to real columns, and `app/services/CleanupSampleData.php` cut down to
only what is still sample.

- An organisation creates at `/org/cleanup/create` → `draft`. An admin approves at
  `/admin/cleanup` → `published` → the campaign appears on `/cleanup`. Editing an
  approved campaign sends it back to `draft`. Cancelling is a soft delete plus
  `status = cancelled`.
- Registered / pending / checked-in / feedback counters are counted from
  `event_registrations`, `attendance` and `event_feedback`, so they honestly read
  0 until volunteer sign-up is written.
- Still sample data, and labelled as such on screen: the volunteer table,
  attendance, the campaign group chat, collected waste, the feedback questionnaire
  and the certificate figures.

**Three things this table forced that the other three did not:**

1. **`cleanup_events.status` has no `pending` and no `rejected`**, and there are no
   `reviewed_by` / `reviewed_at` columns. The ENUM is
   `draft·published·ongoing·completed·cancelled` plus a `cancel_reason`. So the
   house lifecycle is mapped on: **`draft` is "pending"**, and **a rejection is
   `cancelled` with the note in `cancel_reason`, left un-deleted**. An organiser's
   own withdrawal is the same status but *also* soft deleted, and `deleted_at` is
   what tells the two apart — the manage screen prints "Sent back by an
   administrator: …" for one and "You cancelled this campaign" for the other.
   Editing a rejected campaign clears the reason and resubmits it as `draft`.
2. **`organization_id` points at `organizations(id)`, not at the account.** Blood
   and donation both hang off `users(id)`; this one needs the profile row, so every
   write resolves it first and an organisation account with no profile row is
   refused rather than 500ing on the foreign key.
3. **The two-page form could not actually submit.** Week 3's "Next" was a plain
   link, so page 2 posted only the safety guidelines and every page-1 field came
   back required-and-empty. Next is now a **submit** that validates page 1's
   fields, stores them in the session against a per-form key, and redirects to
   `?step=2`, where they are re-posted as hidden inputs. Opening the form with no
   `?step` clears that draft, so `?step=1` / `?step=2` round-trip with Back while
   entering fresh from the sidebar starts clean.

**The community proposal question is settled — it stores nothing.**
`cleanup_events.organization_id` is NOT NULL, so a community member cannot own a
campaign under the frozen schema. Rather than change the frozen table or invent a
proxy organisation row, the ruling is that **campaigns stay organisation-owned**:
`/cleanup/create` still renders and still validates against the real columns, and
then tells the member plainly that a verified organisation has to host it. The
screen, the controller and the flash all say the same thing, so nothing silently
vanishes. This replaces the open question the week 3 section left.

**Dropped, not faked:** the "Viewed" counter, exact location, minimum age,
visibility setting, registration deadline, minimum volunteers, required skills,
team leader, emergency hotline, nearest hospital, certificate template and
feedback-form choice. Each is still collected on the create form with a hint
saying why — and each is **absent from the read screens** rather than printed
blank. The browse card's "% Completed" was re-labelled **"Places filled"** and
derived from registrations against `volunteer_limit`: the Figma's percentage had
no column behind it, and calling places-filled "completed" would have been a claim
about work that had not happened.

### How M1 was signed off

Walked against the criterion role by role, in the browser, the same way the other
three were — not screen by screen as each was built.

- **Full lifecycle, end to end.** Create through the real two-page form → row
  written as `draft` → invisible to guests (`/cleanup/1` is 404, browse does not
  mention it) but visible to its organisation → admin approves → 200 to guests and
  listed on `/cleanup` → organiser edits → **back to `draft` and 404 again** →
  admin re-approves → organiser cancels → `cancelled` + `deleted_at` set, 404 to
  the public, still in the owner's list marked "You cancelled this campaign".
- **The reject arm too.** Rejecting with no reason is refused and leaves the status
  untouched; with a reason it writes `cancelled` + `cancel_reason` and leaves
  `deleted_at` NULL. The organiser sees the reason on both their list and their
  detail screen, edits the campaign, and it resubmits as `draft` with the reason
  cleared. Approving or rejecting twice is refused ("already been reviewed"), and
  cancelling twice is refused.
- **Every route, every role.** A 12-route × 5-caller matrix (guest, community,
  organisation, GN, admin). A community member gets 403 on all of `/org/cleanup*`;
  an organisation gets 403 on `/cleanup/create`, `/join` and `/feedback`; only the
  admin opens `/admin/cleanup`; guests are redirected to sign in from every
  authenticated route. An unapproved campaign is **404, not 403**, to everyone but
  its owner — a 403 would confirm it exists.
- **Forms, valid and invalid.** A missing required field bounces to the page it was
  submitted from with the error on the right field and the typed values still in
  the boxes. A tampered `category` and a non-existent `city_id` are **field errors,
  not foreign-key 500s**, because both validate against the real ENUM and the real
  id list. A missing CSRF token is refused on create, update, delete, approve and
  reject.
- **The edit form really prefills** — every text field, both times normalised from
  `HH:MM:SS` to `HH:MM`, and the stored city and category `selected`.
- **Nothing else was written.** After every form above, `event_registrations`,
  `attendance`, `work_areas`, `waste_records`, `event_photos`, `cleanup_impacts`,
  `certificates` and `event_feedback` are all still empty, and `users` still has
  exactly the five accounts.
- **No regressions.** Swept all 16 admin screens, the reuse / donation / blood
  modules, both other org workspaces, the shared screens and the guest pages
  afterwards — all 200, and no PHP notice on any public page.

**Not verified end to end:** the cross-organisation 403. `ownedOrFail()` compares
the campaign's `organization_id` against the signed-in account's own organisation
row, but only one organisation account exists and CLAUDE.md forbids creating more
seed users, so a second organisation could not be pointed at the first's campaign.
The role-middleware half of it *is* verified — every non-organisation role gets 403
on the whole `/org/cleanup` group.

**One thing checked and cleared, worth not re-investigating:** the MySQL console
prints `—` and Sinhala text as `?`, which looks like charset corruption. It is not.
Posting pre-percent-encoded UTF-8 through the real form and reading the column back
with `HEX()` returns the exact bytes (`E28094` for the em dash, `E0B6B1` for the
Sinhala letter). The connection charset is `utf8mb4` and the round trip is clean —
the `?` is the Windows console, not the database.

### Cleanup test data

One campaign, created through the real form by `hope@test.lk` and approved through
the real admin queue, so `/cleanup` is not empty for the demo. Nothing is seeded —
clear it freely:

```sql
DELETE FROM cleanup_events;   -- registrations, attendance and feedback cascade
```

---

## Week 6 — Navigation audit ✅

**Deliverable:** Every sidebar link opens a page. Zero 404s. End-to-end flow
works: guest landing → register → admin approves → user logs in → creates item
→ edits it → browses → views detail.

**Both halves done.** Rather than clicking the sidebars by hand, the audit was run
with a crawler that signs in as each of the six callers, then breadth-first
follows every in-app `<a href>` it finds and records where each one lands. It is
GET-only, so it never submits a form or writes a row.

**Coverage:** 328 page loads across guest / community / organisation / GN /
blood bank / admin, 276 of them answering 200 — every one reached by actually
following a link a real user can click, not from a list someone typed out.

### Three dead links it found, all now fixed

1. **`/blood/1`, `/blood/2`, `/blood/3` → 404** — the *My Registrations* tab on
   `/blood` draws sample registrations whose `campId` is hard-coded 1/2/3, and
   `blood_campaigns` is empty, so every "View Details" was a 404. The rest of that
   tab is already declared sample on the page, so the button is now `is-disabled`
   with a title saying the camp has no campaign row — the existing convention from
   `gn/disasters/show.php`, not a new one.
2. **`/cleanup/create` → 403 for a GN officer and a blood bank** — `/cleanup`
   picked the create URL with `role_is(ROLE_ORGANIZATION) ? … : '/cleanup/create'`,
   and that route is `role:community`, so the two roles that are neither fell
   through to a form they cannot open. The button now resolves per role and is
   **hidden** for the roles that can create nothing.
3. **`/org/cleanup/create` → 403 for a community member** — the footnote on the
   community proposal page linked the words "Create New Campaign" at an
   organisation-only route. Whoever reads that page is a community member by
   definition, so the link could only ever 403. It is plain text now; the sentence
   already tells them to sign in with the organisation account.

### What else the audit checked

- **Redirects.** Every 302 a link produces was followed to its target — 7 distinct
  destinations, none of them a 404 or a 500.
- **Form actions.** All 67 `<form action>` values found across the six callers were
  matched against the router's own compiled table by reflection, including the
  `_method` spoof for PUT/DELETE. Every one resolves to a declared route. Nothing
  was submitted, so nothing was written.
- **Leaked PHP diagnostics.** All 276 rendered pages were scanned for `Warning:`,
  `Notice:`, `Deprecated:`, `Undefined variable`, `Undefined array key` and
  `Fatal error`. Zero.
- **The 403s that remain are correct.** Every non-200 left in the crawl is a
  cross-role probe the crawler injected itself (a community member asking for
  `/admin/dashboard`), never a link the UI actually draws. No page offers a link
  its reader cannot open.

### The end-to-end flow, both arms

Run as scripted browser sessions against the real forms, uploads included:

- **Community arm** — guest landing → register (with the NIC scan the form
  requires) → account is **active immediately** → signs in → creates a reuse item
  (with its mandatory photo) → edits it → the edit shows on `/reuse` → opens the
  detail page. All eight steps pass.
- **Approval arm** — a community account never exercises "admin approves", because
  community sign-up is active on the spot. So it was run again as an
  organisation: registers → lands `pending` → **cannot reach its dashboard** →
  appears in the admin verification queue → admin approves → status `active` →
  now reaches `/org/dashboard`. All seven steps pass.

Both probe accounts, the item they created and the four files they uploaded were
deleted afterwards. `users` is back to exactly the five seed accounts, and
`storage/uploads` has no orphans — the audit left nothing behind.

**Re-running it:** the scripts are in the session scratchpad, not the repo. They
are ~100 lines of curl each; if the group wants them kept, they belong in a
`tools/` folder, which needs a group decision since no such folder exists yet.

---

## M4 · Reuse Items — The worked example ✅

**Complete backend CRUD + frontend + all closing gaps.** This is what "interim
done for one module" looks like. Dilsha, Janudi and Shenol should follow this
pattern for weeks 4–5: one entity, full CRUD, tests in the browser as four
callers (owner/donor, peer member, admin, guest).

For week 3, the reuse module's frontend patterns (CSS classes, form structure,
navigation wiring, sample data loops) can be reused in the other modules.

### What was built

| Session | Files |
|---|---|
| 1 · Model + Read (list) | `models/reuse/ReuseItem.php`, `ItemCategory.php`, `ItemImage.php`; `ItemListingController@index / manage`; `views/reuse/index.php`, `manage.php`, `_card.php`; `pages/reuse.css` |
| 2 · Create | `services/ReuseService.php`; `@create / store`; `views/reuse/create.php`, `_form.php`, `_guidelines.php` |
| 3 · Read (detail) | `@show`; `views/reuse/show.php`; `models/reuse/ItemRequest.php` |
| 4 · Update | `@edit / update`; `views/reuse/edit.php` (reuses `_form.php`) |
| 5 · Delete | `@destroy` — status `ITEM_REMOVED` + soft delete — plus `@relist` to undo it |
| Request flow | `ItemRequestController`, `services/ItemRequestService.php`, `models/reuse/GsVerification.php`, `DistributionCooldown.php`, `ReuseFeedback.php`; `views/reuse/requests.php`, `_request-modal.php`, `donor.php` |
| Closing the gaps | `MyActivityController` + `views/reuse/activities.php`; `@feedback` + `ItemRequestService::leaveFeedback / feedbackBlockedReason`; `GsLetterController` + `views/reuse/admin-letters.php` + `decideLetter()` |

The request flow is not extra credit — `routes/reuse.php` already declared
`ItemRequestController@store` and `@index`, and the detail page's primary
button posts to it. Left unbuilt, the main call to action on the module's
busiest screen would have answered 500.

### Verified

- Create with two photos → both stored, first becomes the cover; a listing
  with no photo, and one with a category id that does not exist, are both
  refused at field level with the typed values kept (an invalid `category_id`
  is a form error, not a foreign-key 500 — the rules validate against the real
  id lists)
- Edit changes every field and the detail page reflects it; stripping the last
  remaining photo is refused
- Withdraw soft deletes and marks the row removed: gone from browse, 404 to
  everyone else, still visible to its donor on My Listings, and relistable
- A second member's request, the donor selecting them, and the handover, each
  writing the other side's notification; the fair-distribution cooldown then
  blocks that member from asking for the same category again
- Ownership: another member gets 403 on edit, delete and relist, and cannot
  answer requests on someone else's listing; a GS letter is readable only by
  its uploader, the donor deciding on it, and an administrator
- A missing CSRF token is refused on every mutating route; guests see the
  browse and detail pages with the request button gated to sign-in, and never
  receive the request modal markup at all

### Decisions taken

- **Availability Period dropped.** It is on the Figma donate form; there is no
  column for it. Same ruling as the week-2 fields.
- **Quantity added to the form.** The reverse case — `reuse_items.quantity`
  exists and the detail frame prints it, but the donate frame has no input.
- **"Nearest to me" sort dropped.** The schema stores a city, not coordinates.
- **Withdraw ≠ erase.** Delete is a soft delete plus `ITEM_REMOVED`, so the
  donor's history survives; `@relist` is the visible counterpart. A *completed
  donation* cannot be withdrawn at all — that would overwrite the outcome.
- **No seed listings.** Everything above was created through the real forms,
  as agreed. `database/seeds/seed.sql` is untouched.

### Gotchas found — worth reading before your own module

- **`distribution_cooldowns` has no timestamp columns at all** — no
  `created_at`, `updated_at` or `deleted_at` — so `create()`, `update()` *and*
  `delete()` all break on it. Every write goes through `Database::run()`.
  `item_categories` has no `created_at` either (pass an explicit `$orderBy`,
  like `cities`), and `item_images`, `gs_verifications` and `reuse_feedback`
  have no `updated_at`, so `update()` fails on those three. **Check your own
  tables in `schema.sql` before assuming an inherited method works** — this is
  the same rule the top of this file states, and it caught four more tables.
- **Uploaded images are addressed by row id, not by path.** At the time
  `/file/{path}` could not match a two-segment stored path at all (fixed later —
  see Known gaps). Item photos use their `item_images` row id:
  `/reuse/image/{id}`, public unless the listing has been withdrawn. **Leave them
  that way even now that `/file` works**: item photos are meant to be public, and
  `/file` runs an owner-or-admin check that would hide them.
- **A public page cannot render in `layouts/app`.** That layout builds a
  sidebar and user chip from `Auth::user()`, so a guest would get an empty
  account menu and a Sign out button. Public module pages pick their shell:
  `is_logged_in() ? 'app' : 'guest'`.
- **`.guest-main` has no padding of its own** — the landing page wants
  full-bleed bands — so a module page rendered for a guest sits flush against
  the window edge until it pads itself. `pages/reuse.css` does this under a
  `.guest-body` selector rather than changing the shared layout file.
- **A soft-deleted row disappears from its owner too.** `find()` and the
  default `all()` filter `deleted_at IS NULL`, so after Withdraw the listing
  vanished from My Listings entirely and the owner-visibility branch on the
  detail page was unreachable. The donor's own queries have to opt back in.

### The three gaps above, now closed ✅

Signed off the same way — walked in the browser as requester, donor,
administrator and guest.

- **My Activities.** `/my/activities` exists. Both `sidebar-community.php` and
  `sidebar-org.php` had linked there since week 1 and the link 404'd;
  `ItemRequest::forRequesterList()` finally has its caller. The screen lists
  every request this member has made, what became of it, and — for a completed
  donation — the review form. It is deliberately *not* reuse-only in shape:
  whoever builds cleanup sign-ups or blood appointments should add a section to
  `MyActivityController` rather than a second screen.
- **Ratings.** A recipient rates the donor from that screen, on exactly one
  condition — the donor selected them *and* then confirmed the handover.
  Anything looser would let people rate donors they never met. Written once and
  never edited (`reuse_feedback` has no `updated_at`, so `Model::update()` could
  not change it anyway, and a rewritable review is worth less). The donor
  profile's rating, review list and star summary are now real rows instead of an
  empty state.
- **Administrator review of GS letters.** `/admin/reuse/letters`, linked from the
  admin sidebar with a live pending badge. Approve, reject, and open the letter
  itself. An approval carries a six-month expiry, because being disaster-affected
  is a circumstance and not a permanent status — a letter that never lapsed would
  let one household hold priority for ever.
  - This is what makes the priority badge true. The donor's queue now separates
    **Verified disaster-affected** from **Disaster-affected (claimed)**, and
    `ItemRequest::forItem()` sorts a verified claim above an asserted one. Before
    this, both the badge and the queue position were worth no more than the tick
    box that set them.

### Not built

- **Messaging.** Frame 3 shows a "Message donor" button once a request is
  accepted. There is no chat module yet, so the button is not drawn — the
  requester sees the request state instead. Wire it up when chat lands.

---

## Shared files that changed — everyone must pull

### `database/schema.sql` — the frozen file

`users` gained a nullable `nic_document_path VARCHAR(255)`, directly under `nic`.
It arrived as `database/migrations/2026_08_07_add_nic_document_to_users.sql` and
that line has now been **folded into `schema.sql` itself**, so schema + seed is the
whole setup for anyone cloning fresh.

- If your database predates 2026-08-11, run the migration once. If you imported
  from the current `schema.sql` you already have the column — running the migration
  then fails with `ERROR 1060: Duplicate column name`, which changes nothing but
  looks alarming. The migration file now carries a SUPERSEDED banner saying so.
- Anyone with a `schema.sql` edit of their own in flight will conflict on the
  `users` table. It is a three-line addition directly under `nic` — take both.

Verified by importing the edited `schema.sql` into a scratch database and diffing
`users` against the live migrated one: identical, and all 45 tables still build.

> **Careful:** `schema.sql` contains its own `CREATE DATABASE ... USE
> togetherthrives;`, so `mysql -u root some_other_db < schema.sql` silently targets
> `togetherthrives` regardless of the database you name. Today the file is all
> `CREATE TABLE` and re-running it merely errors out harmlessly on the first
> existing table — but if anyone ever adds `DROP TABLE IF EXISTS`, that same
> command becomes destructive. Strip the `USE` line if you need to import elsewhere.

### `app/core/` — three files

A stale copy of any of these behaves differently:

- **`Helpers.php`** — `old()`, `error_for()` and `has_error()` returned nothing at all,
  because they read `global $old` while `Controller::view()` only creates a
  method-local copy. Every form in the project would have failed to repopulate.
  They now read from a new `form_state()` store.
- **`Controller.php`** — one line in `view()` seeds `form_state()`.
- **`Auth.php`** — `attempt()` refused `STATUS_SUSPENDED` and `STATUS_PENDING` but
  said nothing about `STATUS_REJECTED`, so the moment Phase 3 could reject an account,
  that account could still sign in. Rewritten as an allow-list: only `STATUS_ACTIVE`
  gets in, and anything else is refused with a message. Written this way on purpose —
  a new status added to the ENUM later is now denied by default instead of silently
  admitted.

### `app/views/layouts/app.php` — sidebar routing

`ROLE_BLOOD_BANK` now resolves to its own sidebar instead of sharing the org one.

### `app/views/partials/sidebar-admin.php` — three module review queues

Each module that needs an approval step adds **one link and one count query**
here, and routes the screen from **its own route file**, so `routes/admin.php`
stays Member 3's week-2 account-verification queue and nobody else edits it.

| Link | Section | Route file it comes from |
|---|---|---|
| GS Letter Review | User & Organization Management | `routes/reuse.php` |
| Donation Request Review | Content Management | `routes/donation.php` |
| Blood Campaign Review | Content Management | `routes/blood.php` |
| Cleanup Campaign Review | Content Management | `routes/cleanup.php` |

Each badge is a live `pendingCount()` on that module's model. All four queues are
now in place. Expect a trivial conflict here if two of you push at once; take all
the lines.

### `public/assets/css/main.css` — two import lines

`@import 'pages/reuse.css';` and `@import 'pages/shared.css';` are now live; the
other three module lines are still commented out where the file invites each
member to add their own. Adding your line is a one-line edit to a shared file —
expect a trivial conflict if two of you push at once, and take both lines.

`pages/shared.css` is week 3's stylesheet for the dashboards, profile, settings,
notifications, messaging, organizations, dengue, support, checkout, search and
disaster reporting. It is tokens only — no new colours — and it is additive, so
it cannot change how an existing screen looks.

### Week 3 changes to shared files — pull before you start your module

- **`config/routes/admin.php`** — the eight placeholder targets under
  "still to be built" now point at real controllers, and a few POST/PUT routes
  joined them. Nothing above that block changed.
- **`config/routes/reuse.php`** — gained a labelled block of shared routes,
  because `web.php` may not be edited. If you need a shared route and it is not
  in `web.php`, put it in your own file the same way.
- **`app/views/partials/sidebar-{community,org,gn,blood-bank}.php`** — icons,
  a Report link, and the missing Dengue / My Activities entries. Items are now
  `[href, label, icon]`; the icon is optional.
- **`app/views/partials/sidebar-render.php`** and **`sidebar-admin.php`** — both
  call the new `sidebar_active()` instead of `nav_active()`.
- **`app/views/partials/nav-active.php`** — new, three lines of logic. See the
  navigation fixes in the week 3 section above.
- **`app/views/partials/topbar-user.php`** — real unread count, Report link, the
  account chip links to `/profile`.
- **`app/views/partials/topbar-admin.php`** — bell and inbox point at
  `/notifications` and `/messages`.
- **`app/controllers/HomeController.php`** — `dashboard()` now picks a view per
  role. The four routes that point at it are unchanged, including the two in
  Members 1 and 3's files.
- **Deleted:** `app/controllers/admin/PlaceholderController.php`,
  `app/views/admin/placeholder.php`, `app/views/home/dashboard.php`. Nothing
  else referenced them.

---

## Security fixes made in week 2 — read these

- **The whole project tree was being served by Apache.** Because the project sits in
  `htdocs`, `http://localhost/tt/.env`, `/database/schema.sql`, `/app/core/*.php` and
  every uploaded NIC scan under `/storage/uploads/` returned **200 to anyone**, with no
  login — bypassing all of the application's access control. Fixed by adding a root
  `.htaccess` that denies everything, with `public/.htaccess` granting itself back.
  On a real host the document root would point at `public/` instead.
  **When you build a feature that touches private files, test the direct URL too,
  not just the app route.**
- **Uploaded identity documents were tracked by git.** See the first open action
  at the top of this file — it is not finished.
- **Rejected accounts could sign in.** See the `Auth.php` note above.

## Known gaps

- "Remember me" on the sign-in form is cosmetic — nothing consumes the value yet.
- **No mail is sent.** Reset and confirmation links are written to
  `storage/logs/app.log` with `Logger::info()`. While `APP_DEBUG=true` the reset
  link is also shown on the forgot-password page in an amber development box, so
  the flow can be tested without opening the log — that box disappears when
  `APP_DEBUG=false`, and it must, because otherwise anyone could reset anyone's
  password by typing their address. Wiring in real SMTP is a later phase.
- There is no "resend confirmation link" screen yet. The verify-email failure page
  tells people to request one from their profile, which is week 6 work.
- ~~`/donations` still 500s~~ — built in week 3. **No sidebar entry 404s or 500s
  for any role now.** Whatever is still unbuilt is a *button* inside a screen,
  not a link in a sidebar, and every such button is `disabled` with a note
  saying which table it writes to in weeks 4–5.
- ~~The topbar "Report" link points at `/login`~~ — it opens `/report` for a
  signed-in account. Disaster reporting is at `/gn/disasters`, built in week 3.
- ~~`Router` compiles `{param}` to `([^/]+)`, so the `/file/{path}` route cannot
  match a stored path like `gn-documents/abc.pdf`.~~ **Fixed after the full system
  test** — the route is now `/file/{folder}/{name}`, which is the shape a stored
  path always has, and `upload_url()` works as CLAUDE.md always claimed. The
  router limitation itself is unchanged: a `{param}` still cannot hold a slash,
  so any future two-segment route needs two params. Admin review documents are
  still served by account id (`/admin/verification/{id}/document`), which is the
  right call — the id is the only thing the browser sends.
- ~~A missing CSRF token returns 500 rather than a friendly page.~~ **Fixed after
  the full system test** — it answers `419 Authentication Timeout` and renders
  `views/errors/419.php`. The 500 came from `http_response_code()` quietly
  normalising a code it has no reason phrase for, not from the middleware.

## Test accounts

Password `Passw0rd1`; the admin is `Admin@123`. Only the admin is seeded — the rest
were registered through the real forms, so delete them freely and re-register.

| Email | Role | Status | Why it is in this state |
|---|---|---|---|
| `admin@togetherthrives.lk` | admin | active | the one seeded credential |
| `hope@test.lk` | organization | active | approved through the queue |
| `sunil@test.lk` | grama_niladhari | **pending** | left pending so the queue is not empty |
| `bank@test.lk` | blood_bank | **rejected** | exercises the rejected path |
| `dilani@test.lk` | community | active | community accounts skip verification |

Remove with `DELETE FROM users WHERE email LIKE '%@test.lk';` — detail rows cascade.

### Reuse test data

Two listings exist, both created through the real forms by `dilani@test.lk`:
one **donated** to `hope@test.lk` (which left a live category cooldown on that
account until early September) and one **available**. Nothing is seeded, so
clear them freely and relist:

The donated one now also carries a **5-star review** from `hope@test.lk` (visible
on Dilani's public profile) and an **approved GS letter**, both created through
the real screens while signing the gap work off. They are what the demo needs to
show a non-empty rating panel and a decided letter, so keep them unless you are
resetting the module:

```sql
DELETE FROM reuse_feedback;
DELETE FROM distribution_cooldowns;
DELETE FROM item_requests;
DELETE FROM gs_verifications;
DELETE FROM reuse_items;   -- item_images cascade
```

The image files under `storage/uploads/items/` are not removed by that and can
be deleted by hand; nothing reads a file whose row is gone.
