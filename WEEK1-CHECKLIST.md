# Week 1–2 Checklist

Tick these off before anyone starts module work.

## Done in this scaffold
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
- [x] Layouts: guest, app, auth + 4 role sidebars + error pages
- [x] CSS design tokens ported from Figma + component library
- [x] Full schema: 45 tables, all FKs resolve
- [x] Seed data: 25 districts, 37 cities, 11 categories, 8 screening questions, admin user
- [x] Split route files — one per member, no merge conflicts

## Environment (do this on every machine)
- [x] Copy `.env.example` to `.env` and set `BASE_URL` to your own path
      (this project runs at `http://localhost/tt/public`)
- [x] Import `database/schema.sql`, **then** `database/seeds/seed.sql`
      — the seed is easy to forget and the app looks broken without it
- [x] Run every file in `database/migrations/` in date order — currently one:
      `2026_08_07_add_nic_document_to_users.sql`. Community registration breaks
      without it.
- [ ] Confirm `http://localhost/tt/public/` loads the homepage
- [ ] Confirm `admin@togetherthrives.lk` / `Admin@123` signs in

## Group must still do this week
- [ ] Create the GitHub repo, push this scaffold
- [ ] Everyone clones and confirms the landing page loads
- [ ] Agree branch naming: `feature/m1-cleanup-crud`
- [ ] Agree: never push to `main` directly, always pull before you start
- [x] ~~Export icons from Figma~~ — replaced by inline SVG in
      `app/views/partials/icons.php`, used as `<?= icon('mail') ?>`. No image files needed.
- [x] Assign the four CRUD entities formally — recorded in the CLAUDE.md ownership table

---

## Week 2 — Authentication

**Done means:** all four account types register, admin approves, they log in,
and each lands on the correct dashboard with the correct sidebar.

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

### Phase 3 — M3 · Admin verification queue *(next)*
- [ ] `Admin\OrgVerificationController@index / approve / reject`
- [ ] `Notification` model (`notifications` has no `deleted_at`)
- [ ] Verified: the pending org from Phase 2 can sign in after approval

### Phase 4 — M2 · Password reset + email verification
- [ ] `PasswordController@showForgot / sendLink / showReset / reset`
- [ ] `PasswordReset` + `EmailVerification` models (no `deleted_at` on either)
- [ ] Token + expiry + single use; log the link with `Logger::info()` instead of SMTP
- [ ] `AuthController@verifyEmail`

---

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
- **Community NIC is a file upload**, matching the Figma frame. This needed the one
  migration above — `users` had nowhere to record a document path (`nic` is
  `VARCHAR(15)`, and a stored path is ~50 characters).
- **Blood Bank signup adapted from the Organization form** — no design frame exists,
  but the route and `blood_banks` table do.
- **`/register` is a standalone page** reusing the four role cards from the homepage.

Rendered PNGs of every auth screen are in `docs/ui-reference/` — the source SVGs are
too large to open comfortably.

## Schema change — everyone must run this

`database/migrations/2026_08_07_add_nic_document_to_users.sql` adds a nullable
`users.nic_document_path VARCHAR(255)`. Additive, so nothing else breaks — but
community registration fails until you run it, and **someone still needs to add the
same line to the `users` table in `schema.sql`** so a fresh import matches. That file
is shared: agree it in the group first.

## Core changes — everyone must pull before working

Two files under `app/core/` were changed. A stale copy of either behaves differently:

- **`Helpers.php`** — `old()`, `error_for()` and `has_error()` returned nothing at all,
  because they read `global $old` while `Controller::view()` only creates a
  method-local copy. Every form in the project would have failed to repopulate.
  They now read from a new `form_state()` store.
- **`Controller.php`** — one line in `view()` seeds `form_state()`.

## Known gaps

- "Remember me" on the sign-in form is cosmetic — nothing consumes the value yet.
- These still 500 until their phase lands: `/forgot-password`, `/verify-email/{token}`
  (phase 4), `/support` (week 6), `/cleanup`, `/blood`, `/reuse`, `/donations` (weeks 3–5).
- The topbar "Report" link points at `/login`; disaster reporting is M1's week 3–5 work.
- Pending accounts cannot be approved yet — that is phase 3. Until then, flip one by
  hand: `UPDATE users SET status='active' WHERE email='…';`
- Test accounts left in the database from phase 2 testing (password `Passw0rd1`):
  `nimal@test.lk` (community, active), `hope@test.lk` (organization, pending),
  `sunil@test.lk` and `kamala@test.lk` (GN, pending), `bank@test.lk` (blood bank, pending).
  Useful for testing phase 3. Remove with
  `DELETE FROM users WHERE email LIKE '%@test.lk';` — detail rows cascade.
