# Setup, Authentication & Modules — weeks 1–5

Scaffold and authentication are **done**. Module work (weeks 3–5) has started:
**M4 · Reuse is complete**; the other three CRUDs are still to come.

Read the two sections directly below before you pull or start building — they are
the only things that will bite you.

---

## Open actions — do these before week 3

- [ ] **`git rm --cached -r storage/uploads`** before the first push. Six proof
      documents from registration testing are still in git's index — one NIC scan,
      three GN appointment letters, one organization certificate, one blood bank
      licence. `.gitignore` now excludes the folder, but that does not untrack what
      is already staged, and real identity scans must never reach GitHub. The files
      stay on disk; this only stops git carrying them.
- [ ] **Tell the group `schema.sql` changed** — see *Shared files* below. It is the
      frozen file; anyone with their own edit in flight will conflict on `users`.
- [ ] Create the GitHub repo, push this scaffold
- [ ] Everyone clones, imports schema + seed, confirms the landing page loads
- [ ] Agree branch naming: `feature/m1-cleanup-crud`
- [ ] Agree: never push to `main` directly, always pull before you start
- [ ] Decide who builds `/support`. Rejection and suspension notifications link
      there and it 500s, so this gap is now visible to real users.

## Two rules the module weeks depend on

Both were learned the hard way in week 2. They will cost someone a day each if
they are rediscovered instead of read.

**A role must never borrow another role's sidebar.** `layouts/app.php` mapped
blood banks to the organization sidebar — sensible-looking, since both run
campaigns. But `/org/*` is gated `role:organization`, so blood banks were shown
three links that answered **403**, and none led back to their own dashboard. That
role had no working navigation at all and nobody noticed for two phases. If two
roles genuinely need one screen, widen that route's `role:` list deliberately.

**Child and lookup tables need `$softDelete = false` — and check `updated_at`
too.** `Model::update()` unconditionally appends `updated_at = NOW()`, so calling
it on a table without that column is a SQL error, not a silent no-op. Tables hit
so far: `notifications`, `password_resets`, `email_verifications`. Their writes go
through `Database::run()` instead. Check your own tables in `schema.sql` before
assuming `update()` works.

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

## Weeks 3–5 — M4 · Reuse Items ✅

**Done means:** full Create + Read + Update + Delete on `reuse_items`, every
screen reachable by clicking, and no dead links out of it.

Signed off by walking the sentence end to end, in the browser, as four
different callers — donor, another member, administrator and signed-out guest.

### What was built

| Session | Files |
|---|---|
| 1 · Model + Read (list) | `models/reuse/ReuseItem.php`, `ItemCategory.php`, `ItemImage.php`; `ItemListingController@index / manage`; `views/reuse/index.php`, `manage.php`, `_card.php`; `pages/reuse.css` |
| 2 · Create | `services/ReuseService.php`; `@create / store`; `views/reuse/create.php`, `_form.php`, `_guidelines.php` |
| 3 · Read (detail) | `@show`; `views/reuse/show.php`; `models/reuse/ItemRequest.php` |
| 4 · Update | `@edit / update`; `views/reuse/edit.php` (reuses `_form.php`) |
| 5 · Delete | `@destroy` — status `ITEM_REMOVED` + soft delete — plus `@relist` to undo it |
| Request flow | `ItemRequestController`, `services/ItemRequestService.php`, `models/reuse/GsVerification.php`, `DistributionCooldown.php`, `ReuseFeedback.php`; `views/reuse/requests.php`, `_request-modal.php`, `donor.php` |

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

### Not built

- **Messaging.** Frame 3 shows a "Message donor" button once a request is
  accepted. There is no chat module yet, so the button is not drawn — the
  requester sees the request state instead. Wire it up when chat lands.
- **Ratings.** The donor profile frame shows a star rating, review list and
  handover rate. `reuse_feedback` has no rows and nothing writes to it yet, so
  those panels carry a real empty state rather than invented numbers. The
  stats that *are* shown (items donated, currently listed, requests received,
  disaster-affected helped) are counted from real rows.
- **Administrator review of GS letters.** A letter is stored `pending` and the
  donor can open it, but no admin screen approves one, so "verified
  disaster-affected" currently means "claimed, with a letter attached".

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

### `public/assets/css/main.css` — one import line

`@import 'pages/reuse.css';` is now live; the other three module lines are
still commented out where the file invites each member to add their own. Adding
your line is a one-line edit to a shared file — expect a trivial conflict if
two of you push at once, and take both lines.

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
- These still 500 until their phase lands: `/support` (week 6),
  `/cleanup`, `/blood`, `/donations` (weeks 3–5). `/reuse` is live.
- The sidebar's **My Activities** link does not yet show the items a member has
  requested. `ItemRequest::forRequesterList()` returns exactly that and has no
  caller — whoever builds that screen can use it.
- The topbar "Report" link points at `/login`; disaster reporting is M1's week 3–5 work.
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

```sql
DELETE FROM distribution_cooldowns;
DELETE FROM item_requests;
DELETE FROM gs_verifications;
DELETE FROM reuse_items;   -- item_images cascade
```

The image files under `storage/uploads/items/` are not removed by that and can
be deleted by hand; nothing reads a file whose row is gone.
