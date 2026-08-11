# Setup, Authentication & Modules — weeks 1–6

**Weeks 1–2: Done** ✅  
Scaffold and authentication complete. All four roles register, admin approves,
each lands on its dashboard with the right sidebar.

**Week 3: M4's half is done** ✅ — all 12 admin screens and every shared screen
render, with one sample-data file behind them. **M3 · Blood is part-built** ⏳ —
scaffolding, routes and the two browse screens are live, eight views remain.
M1 · Cleanup and M2 · Donation have not been started. Details in the two week 3
sections below.

**Weeks 3–6: To come**  
- **Week 3:** Render all Figma UIs as static pages with sample data, no backend
- **Weeks 4–5:** Add backend CRUD for one entity per member (M1 · Dilsha, M2 ·
  Janudi, M3 · Shenol each implement one CRUD; M4 · Janagan is done ✅)
- **Week 6:** Navigation audit — verify every sidebar link opens a page, fix 404s

Read the two sections directly below before you pull or start building — they are
the only things that will bite you during week 3.

---

## Open actions — blocking week 3

- [ ] **`git rm --cached -r storage/uploads`** (if not done). Six proof documents
      from registration testing are still in git's index. Real identity scans must
      never reach GitHub. `.gitignore` excludes the folder going forward.
- [ ] **Push to GitHub and everyone pulls.** The scaffold and auth are stable
      enough to share. One person pushes; everyone else clones and confirms the
      landing page loads.
- [ ] **Agree on branch naming:** `feature/m1-cleanup-frontend` (week 3),
      `feature/m1-cleanup-backend` (weeks 4–5). One branch per person per week.
- [ ] **Agree: never push to `main` directly.** Always make a PR. Always pull
      `main` before you start a session.
- [x] ~~**Assign who builds `/support`.**~~ Built in week 3 by M4 — it is the
      "Support the System" frame, with the sandbox checkout behind it.

## Three rules weeks 3–6 depend on

Learned the hard way. They will cost someone a day each if rediscovered.

**Week 3 (frontend): A role must never borrow another role's sidebar.**
`layouts/app.php` mapped blood banks to the org sidebar — both run campaigns.
But `/org/*` is gated `role:organization`, so blood banks saw links that
answered **403** and had no working navigation. When you add a new screen in
week 3, check which sidebars link to it. If two roles need it, widen that
route's `role:` list deliberately — do not reuse another role's sidebar.

**Weeks 4–5 (backend): Child and lookup tables need `$softDelete = false` —
and check `updated_at` too.** `Model::update()` writes `updated_at = NOW()`
unconditionally. If your table lacks that column, you get a SQL error. Tables
without `updated_at`: `item_images`, `item_requests`, `gs_verifications`,
`distribution_cooldowns`, `reuse_feedback`, plus the ones from week 2 
(`notifications`, `password_resets`, `email_verifications`). Their writes go
through `Database::run()` instead. **Check your own tables in `schema.sql`
before assuming `update()` works.**

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
      controller, so every uploaded document 500'd
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

## Week 3 — M3 · Blood (part-built) ⏳

Started on request and **paused part-way**. Everything below is committed and
lints clean; the module is in a working state, not a broken one — the two
screens that exist render, and the rest fail the same way they did before
(a 500 from a missing piece), so nothing regressed.

**Ownership note:** this is Shenol's module. `config/routes/blood.php`, the
`Blood\` namespace and `app/views/blood/` are M3's under CLAUDE.md's one-file-
per-member rule. It was touched at the project owner's explicit request. Whoever
picks it up should read this section first and is free to change any of it.

### Done

| Piece | File |
|---|---|
| Sample data (self-contained, delete in weeks 4–5) | `app/services/BloodSampleData.php` |
| Campaign controller — hub, camp detail, organiser CRUD | `app/controllers/blood/BloodCampaignController.php` |
| Critical request controller | `app/controllers/blood/CriticalRequestController.php` |
| Screening controller (incl. eligibility scoring) | `app/controllers/blood/ScreeningController.php` |
| Certificate controller | `app/controllers/blood/CertificateController.php` |
| Routes — original six CRUD routes unchanged, rest added | `config/routes/blood.php` |
| Stylesheet + its `main.css` import line | `public/assets/css/pages/blood.css` |
| Bare layout for printable documents | `app/views/layouts/blank.php` |
| Community hub, five tabs | `app/views/blood/index.php` |
| Camp detail | `app/views/blood/show.php` |

Verified live: `/blood`, `/blood?tab=camps`, `?tab=critical`, `?tab=donations`,
`?tab=registrations`, and `/blood/1`.

### To finish — eight views

Each one's controller, route and sample data already exist, so this is view work
only. The Figma frame each comes from is in `UI/Blood/`:

| View file to write | Route | Frame |
|---|---|---|
| `blood/manage.php` | `/my/blood` | Blood-Org, frame 1 — "Your Posted Blood Donation Campaigns" |
| `blood/form.php` | `/my/blood/create`, `/{id}/edit` | Blood-Org, frame 2 — "Create New Blood Campaign" |
| `blood/detail.php` | `/my/blood/{id}` | Blood-Org, frame 3 — campaign detail + registered donors |
| `blood/critical-form.php` | `/blood/critical/create` | Blood-User — "Post a Critical Blood Request" |
| `blood/critical-show.php` | `/blood/critical/{id}` | Blood-User — "Critical Request Details" |
| `blood/screening.php` | `/blood/{id}/screening` | Blood-User — "Blood donation Form" |
| `blood/screening-done.php` | `/blood/screening/submitted` | Blood-User — "Form Submitted" |
| `blood/certificate.php` | `/blood/certificate/{id}` | Blood-User — A4 landscape certificate (uses the `blank` layout) |

`pages/blood.css` already carries the classes all eight need — `.camp-table`,
`.count-grid`, `.donor-table`, `.type-pick`, `.group-pick`, `.q-row`,
`.done-card`, `.cert__*` and so on. Read it before inventing anything.

### Rendering the Figma frames

Both blood SVGs are single wide exports with the text as paths, and the frames
are **not** evenly spaced — guessing a stride gives you half-frames. Detect the
frame edges instead: render the whole SVG small, then scan for runs of non-black
columns. `Blood-User.svg` has 9 frames at x = 0, 2868, 4468, 6068, 8220, 9960,
12120, 13684, 15284 (all 1440 wide except the certificate at 1124).
`Blood-Org.svg` has 3 frames at x = 0, 1540, 3080.

Note that `storage/` is `Deny from all` in Apache, so a wrapper page there cannot
load anything over http — render with `file://` URLs.

### Decisions taken

- **No model yet.** `Models\Blood\BloodCampaign` is deliberately not written —
  the model plus real queries *is* M3's weeks 4–5 CRUD deliverable, and week 3
  needs no database reads. The controllers read `BloodSampleData` instead.
- **The forms validate against the real columns.** `validateCampaign()` and the
  critical-request rules use the actual `blood_campaigns` and
  `critical_blood_requests` columns and ENUMs, so weeks 4–5 keeps them and
  replaces only the flash with a `create()` call.
- **Blood types needed is collected and dropped.** The Figma create form asks
  which groups a campaign needs; `blood_campaigns` has no column for it. Dropped
  rather than faked — the same ruling as the week 2 registration forms.
- **Screening is scored for real.** `ScreeningController::verdict()` applies each
  question's disqualifying answer, the way `screening_responses.result` will.
  The result is flashed, not stored.
- **Joining goes through screening.** "Join This Camp" and "I Can Donate" both
  redirect to the screening form rather than registering directly, because both
  frames promise that contact details are shared only after an eligibility check.
- **Certificate gets its own layout.** It is an A4 document, not an app screen,
  so `layouts/blank.php` renders it with no chrome and a `@media print` block.

### Shared files touched

- `public/assets/css/main.css` — `@import 'pages/blood.css';` moved out of the
  commented block. M1 and M2's lines are still commented where they were.
- `app/views/layouts/blank.php` — new, and general-purpose: any printable
  receipt or letter can use it.

---

## Weeks 4–5 — Backend CRUD (each member, their own module)

**Deliverable:** One CRUD entity per member, fully working (Create, Read list,
Read detail, Update, Delete). Real data in the database, real forms that save.

- **M1 · Dilsha:** CleanupEvent CRUD
- **M2 · Janudi:** DonationRequest CRUD
- **M3 · Shenol:** BloodCampaign CRUD
- **M4 · Janagan:** Done ✅

**Pattern:** Follow the reuse module's worked example (sessions 1–5 in the
section below). One session per day, one commit per session.

---

## Week 6 — Navigation audit

**Deliverable:** Every sidebar link opens a page. Zero 404s. End-to-end flow
works: guest landing → register → admin approves → user logs in → creates item
→ edits it → browses → views detail.

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
- **Uploaded images could not use `/file/{path}`.** The router limitation
  already recorded under Known gaps applies to any two-segment stored path.
  Item photos are addressed by their `item_images` row id instead:
  `/reuse/image/{id}`, public unless the listing has been withdrawn.
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

### `app/views/partials/sidebar-admin.php` — one link

**GS Letter Review** was added under User & Organization Management, with a live
pending badge alongside the verification one. Two lines. Its screen routes from
`routes/reuse.php`, not `routes/admin.php`, so Member 3's route file is
untouched — the week-2 queue reviews *accounts*, this reviews evidence attached
to a reuse request.

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
- These still 500 until their phase lands: `/cleanup` and `/donations` — M1 and
  M2's week 3 screens. `/reuse`, `/support` and `/blood` are live; the rest of
  M3's blood module is part-built (see the week 3 · M3 section below).
- ~~The topbar "Report" link points at `/login`~~ — it opens `/report` for a
  signed-in account. Disaster reporting is at `/gn/disasters`, built in week 3.
- `Router` compiles `{param}` to `([^/]+)`, so the `/file/{path}` route declared in
  `web.php` cannot match a stored path like `gn-documents/abc.pdf`. Admin review
  documents are served by account id instead (`/admin/verification/{id}/document`).
  Anyone needing general file serving will have to raise the router limitation with
  the group — both files are on the do-not-edit list.
- A missing CSRF token returns 500 rather than a friendly page. It does block the
  request, so this is cosmetic; the CSRF middleware is core, so it was left alone.

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
