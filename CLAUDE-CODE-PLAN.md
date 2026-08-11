# Finishing TogetherThrives to Interim Level with Claude Code

Week 1 scaffold is done. This is how to get from there to the interim demo.

> **Status: weeks 1 and 2 are complete.** All four account types register, the
> admin approves them, and each lands on its own dashboard with its own sidebar.
> Password reset and email verification are built. Week 3 will render all Figma
> screens as static pages with sample data. Weeks 4–5 will add backend to one
> CRUD per member in parallel. Of the four CRUDs, **M4 · Reuse is already
> complete** with full backend including the gaps (ratings, admin GS letter
> review, and My Activities). **M1 · Cleanup, M2 · Donation and M3 · Blood need
> both frontend (week 3) and backend CRUD (weeks 4–5).** The outcome, the
> decisions taken, the gaps, and the open actions for Reuse are all in
> `WEEK1-CHECKLIST.md`.
>
> Read the *Gotchas* list in that file before starting your own module. Four more
> tables turned out to be missing timestamp columns the base `Model` assumes, and
> that is the kind of thing that costs an afternoon each time it is rediscovered.

---

## 0. One-time setup (30 minutes, do this together)

**1. Push the scaffold to GitHub.** One member does this; everyone else clones.

**2. Drop `CLAUDE.md` in the project root.** This is the highest-value 10 minutes
of the whole project. Without it, every Claude Code session re-guesses your
conventions and you get four different coding styles. With it, output matches
your scaffold on the first try.

**3. Open the project folder in VS Code**, install the Claude Code extension,
and run it from the integrated terminal so it sees the whole repo.

**4. Agree the git rules** and write them in the group chat:
- Branch per feature: `feature/m1-cleanup-create`
- Never push to `main`, always open a PR
- `git pull origin main` before you start any session
- Never commit `.env`

---

## The core working loop

Do **not** ask Claude Code to "build the cleanup module." You will get 15 files
you don't understand and cannot debug in front of an examiner.

Use one operation per session:

```
1. Pull latest        git pull origin main
2. Give context       "Read CLAUDE.md and app/controllers/HomeController.php"
3. One task           "Now create the CleanupEvent model only."
4. Verify             php -l on the file, then open the URL
5. Commit             git commit -m "feat(cleanup): add CleanupEvent model"
```

Small commits mean that when something breaks, you can find it.

---

## Week 2 — Authentication (all four members, parallel)

Build in this order because of dependencies. Do not start 2B before 2A works.

### 2A · M4 — Login, logout, session *(build first, everyone waits on this)*

> Read CLAUDE.md. Create `app/controllers/AuthController.php` with only
> `showLogin()` and `login()`. Use `Core\Auth::attempt()`. On success redirect to
> `Auth::homeFor()`. On failure flash an error and go back. Then create
> `app/views/auth/login.php` using the `auth` layout and the existing `.field`
> classes. Do not build registration yet.

**Verify:** log in as `admin@togetherthrives.lk` / `Admin@123`, land on the admin
dashboard, see the admin sidebar. Then log out.

### 2B · M1 — Registration for four account types

> Read CLAUDE.md. Build the Choose Account Type page at `/register` — four cards
> linking to the four registration routes. Then build community registration only:
> `showCommunityForm()` and `registerCommunity()`. Validate with `required|email|
> unique:users,email`, `password`, `matches:password`, `phone_lk`, `nic_lk`.
> Insert with `status = STATUS_ACTIVE` for community, hash via `Auth::hash()`.

Then repeat per role. Organization / GN / Blood Bank insert `STATUS_PENDING`
plus a row in their detail table, wrapped in `Database::transaction()`.

**Verify:** register a community user, log in immediately. Register an org, get
"awaiting verification" on login.

### 2C · M3 — Admin verification queue

> Build `Admin\OrgVerificationController` with `index()` listing users where
> `status = STATUS_PENDING`, plus `approve()` and `reject()` that set the status
> and write a `notifications` row. Add the ownership/role guard.

**Verify:** the org from 2B now logs in successfully after approval.

### 2D · M2 — Password reset + email verification

Use `password_resets` and `email_verifications` tables. Token, expiry, single-use.

> During development, instead of sending mail, `Logger::info()` the reset link
> and read it from `storage/logs/app.log`. Wire real SMTP later if time allows.

**Week 2 is done when:** all four account types register, admin approves, all
log in, each lands on the right dashboard with the right sidebar.

---

## Week 3 — Frontend render-out: **ALL Figma UIs** (all four members, parallel)

Build every Figma UI as static HTML/CSS pages. No backend yet — just layout,
styling, forms that don't save, and sample data. The goal: every screen is
clickable from the navigation, no dead links, the interim UI is complete.

**Follow the Figma designs — "good enough" is OK.**

Figma designs are the reference. Build HTML/CSS that matches the layout and design intent — exact pixel measurements and micro-spacing don't matter.

What must match:
- Overall layout and structure
- Content organization (where things go)
- Component types (use `.card`, `.table`, `.field` classes, not custom divs)
- Color scheme and visual hierarchy (use CSS tokens, not custom colors)

What doesn't need to match exactly:
- Pixel-perfect spacing (within ~5px is fine)
- Font sizes (close is good enough)
- Shadows and subtle effects - not needed.

Figma files are in `UI/` folder — use them as reference, not as a spec to measure against:
- `UI/Cleanup/Cleanup.svg` — all cleanup screens
- `UI/Donation/Donation.svg` — all donation screens
- `UI/Blood/Blood.svg` — all blood screens
- `UI/Reuse/Reuse.svg` — reference (already implemented)
- `UI/Admin/admin.svg` — all 12 admin screens
- `UI/Landing/` — landing page designs
- `UI/Auth/` — login, register, password reset
- `UI/Shared/` — dashboards, profiles, settings, notifications, etc.

**Before coding:** Look at the Figma frame to understand the layout, then build it with existing CSS classes.

**Scope:** Every Figma frame, every user type, every feature. Not just the four
CRUD modules — also shared screens, admin, profiles, settings, notifications,
messaging, support, disaster reporting, etc.

### Each member's week 3 scope:

> **M1 · Dilsha (Cleanup):** All cleanup Figma screens
> - Browse/list, create form, detail, edit, volunteer sign-up, feedback, activities
> - Sample data: 5 cleanup events, volunteers, feedback
> - ✓ Focus only on cleanup module — no shared screens
>
> **M2 · Janudi (Donation):** All donation Figma screens
> - Browse requests, create form, detail, edit, pledge flow, status flow, activities
> - Sample data: 5 donation requests, pledges
> - ✓ Focus only on donation module — no shared screens
>
> **M3 · Shenol (Blood):** All blood Figma screens
> - Campaign browse, detail, screening form, eligibility result, appointment booking, activities
> - Sample data: 5 campaigns, screening results, appointments
> - ✓ Focus only on blood module — no shared screens
>
> **M4 · Janagan (You):** Heavy lift — Admin screens (12) + Shared screens (11)
> - **Reuse module:** Already done ✅ — use as pattern/reference for others
> - **Admin (12 screens):** Dashboard, Verification, Users, Organizations, Suspended, Complaints, Announcements, Dengue, Admin Mgmt, Logs, Analytics, Settings
> - **Shared (11 screens):** Dashboards (5 roles), sidebars + nav, profile + settings, notifications, messaging, organizations, my activities, support, disaster report, payment/checkout, error pages + system pages (registration/verification/reset)

### Week 3 Workload Division — DECIDED

**M4 · Janagan (You) — Heavy lift: Admin + Shared screens** (because Reuse CRUD is done ✅)

All 12 admin screens:
- Dashboard, Verification, Users, Organizations, Suspended, Complaints, Announcements, Dengue, Admin Mgmt, Logs, Analytics, Settings

All shared screens (across all roles):
- Dashboards (5 roles), sidebars + top nav, profiles + settings, notifications, messaging, organizations list, my activities, support, disaster report, payment/checkout, search, error pages (404/403/500), registration/verification/reset pages

**M1 · Dilsha — Cleanup module screens only**
- Browse, create, detail, edit, volunteer sign-up, feedback, activities

**M2 · Janudi — Donation module screens only**
- Browse requests, create form, detail, edit, pledge flow, status flow, activities

**M3 · Shenol — Blood module screens only** ⏳ *part-built — see below*
- Campaign browse, detail, screening form, eligibility, appointment, activities

**Workload split:** M4 ~50%, M1 ~15%, M2 ~15%, M3 ~15%, landing page ~5%**

---

### M3 · Blood — part-built, paused mid-way

The scaffolding, routes, sample data, stylesheet and the two community browse
screens are done. **Eight views are still to write.** Full hand-over notes,
including the exact file list and the frame each view comes from, are in
`WEEK1-CHECKLIST.md` under "Week 3 — M3 · Blood (part-built)".

Live now (verified by URL):

- [x] `/blood` — hub, all five tabs (All · Upcoming Camps · Critical Requests · My Donations · My Registrations)
- [x] `/blood/{id}` — camp detail

Still 500, view file missing:

- [ ] `/my/blood` — organiser campaign list
- [ ] `/my/blood/create` and `/my/blood/{id}/edit` — campaign form
- [ ] `/my/blood/{id}` — organiser campaign detail with registered donors
- [ ] `/blood/critical/create` — post a critical blood request
- [ ] `/blood/critical/{id}` — critical request detail
- [ ] `/blood/{id}/screening` — medical screening form
- [ ] `/blood/screening/submitted` — screening confirmation
- [ ] `/blood/certificate/{id}` — appreciation certificate


**System pages:** ✅ (verified by URL — see `WEEK1-CHECKLIST.md`)
- [x] Landing page
- [x] 404 error page
- [x] 403 forbidden page
- [x] 500 error page
- [x] Registration success page
- [x] Email verification page
- [x] Password reset page

**M4 · Janagan — Admin sidebar (ALL 12 screens, assigned to you):** ✅
- [x] Admin Dashboard (main landing for admin)
- [x] Verification Requests (built week 2, unchanged)
- [x] Users list (built week 2, unchanged)
- [x] Organizations list (built week 2, unchanged)
- [x] Suspended Users and Posts (moderation list)
- [x] Complaints & Feedback (report management)
- [x] Announcements (create/edit/list announcements)
- [x] Dengue Alert (dengue risk management)
- [x] Admin Management (manage other admins)
- [x] System Logs (audit log viewer — reads the real `storage/logs/*.log`)
- [x] Analytics & Stats (live counts, per module)
- [x] Settings (system configuration)

**M4 · Janagan — Shared screens:** ✅
- [x] Dashboards for all five roles
- [x] Sidebars + top navigation (icons, one active link per page)
- [x] Profile + settings
- [x] Notifications (real rows when the account has any, sample otherwise)
- [x] Messaging (two-pane layout, replying switched off)
- [x] Organizations directory + profile
- [x] My Activities (delivered with the reuse module)
- [x] Support (fund the platform) + Report a Problem
- [x] Disaster reporting — list, create form, detail with verification timeline
- [x] Payment / checkout (sandbox) + receipt
- [x] Search

**Still open at the end of M4's week 3:** `/cleanup` and `/donations` answer 500
— their controllers do not exist. They are M1 and M2's week 3 screens, and they
are linked from sidebars that are now otherwise complete. `/blood` is live; the
rest of M3's module is part-built and listed above.

**Approach:** Copy the layout pattern from Reuse module (already complete).
Reuse its CSS classes (`.card`, `.table`, `.field`, `.badge`, `.btn--*`),
component patterns, and form structure. Hardcode sample data with `count()` loops
to show what a list of 5 items looks like. Wire everything into the sidebars and
top navigation.

**Verify — week 3 is done when:**
- Every sidebar link (community, org, GN, blood-bank, admin) opens a page → zero 404s
- Every role sees only their own screens (other roles get 403 or redirect to their dashboard)
- Every form renders without errors (clicking submit just redirects back, no save)
- Guest can browse: landing page, register, module browse pages (cleanup, donation, blood, reuse)
- Each logged-in role can access: their dashboard, profile, settings, notifications, my activities
- Admin can navigate all admin screens (no dead links in admin sidebar)
- Sample data shows properly: lists with 5+ items, detail pages with content, forms with all fields
- Notifications panel shows sample notifications (unread, read)
- Chat/messaging shows structure or placeholder (not required to be built if not in Figma)
- Support/help page is static but looks complete
- Disaster reporting form renders (static, no backend)

---

## Weeks 4–5 — Backend CRUD, one per member (fully parallel)

Each member implements **one CRUD entity** with full backend (Read list, Create,
Update, Delete). Example is M1 / Cleanup Event; substitute your own.
M4 · Reuse has been through all five — `app/controllers/reuse/`,
`app/models/reuse/` and `app/views/reuse/` are a worked example of the shape
these sessions produce, if you would rather read one than start cold.

### Session 1 — Model + Read (list)
> Read CLAUDE.md and `database/schema.sql` (cleanup_events table).
> Create `app/models/cleanup/CleanupEvent.php` extending `Core\Model` with the
> correct `$table` and `$fillable`. Add a `browse()` method joining organizations
> and cities, filtered by status and city, with LIMIT/OFFSET.
> Then create `CleanupEventController@manage` and `views/cleanup/manage.php`
> showing a table with title, date, status and Edit/Cancel actions.

Do Read first. You need to see rows before you can tell whether Create worked.
Insert 3 test rows manually in phpMyAdmin to have something to display.

### Session 2 — Create
> Add `create()` and `store()`. The form matches the Figma "Create Campaign"
> screen: title, description, category, date, start/end time, meeting point,
> city dropdown, volunteer limit, equipment, safety guidelines, cover image.
> Validate everything. Handle the image with `FileUploader::store()`.
> Set `status = EVENT_DRAFT` and `organization_id = auth()['id']`.

### Session 3 — Read (detail)
> Add `show()` for the public detail page plus `views/cleanup/show.php`,
> matching the Figma layout. Guests can view; the join button is gated.

### Session 4 — Update
> Add `edit()` and `update()`. Reuse the create form via a shared partial
> `views/cleanup/_form.php` that takes an optional `$event`. Include the
> ownership check. Form needs `method_field('PUT')`.

### Session 5 — Delete
> Add `destroy()` — set status to `EVENT_CANCELLED` and soft delete.
> Ownership check. Button needs `data-confirm`.

**After each session:** commit, push, and tell your group it's merged.

**Interim is done when:** all four modules have at least one CRUD working
end-to-end (working example: Reuse Item from Janagan is complete; Cleanup Event
from Dilsha, Donation Request from Janudi, Blood Campaign from Shenol must each
have at least Create, Read list, Update and Delete). The other screens in each
module are static with sample data, but navigatable and not dead.

---

## Week 6 — Navigation verification (this is the requirement people fail)

Week 3 should have wired every Figma screen into the navigation. Week 6 is the
audit: verify no dead links exist. Split the sweep four ways, one member per
sidebar.

> Read CLAUDE.md. Go through every sidebar (`sidebar-community.php`,
> `sidebar-org.php`, `sidebar-gn.php`, `sidebar-blood-bank.php`,
> `sidebar-admin.php`) and list every `href`. For each, check whether a matching
> route exists in `config/routes/` and renders a page. Report any 404s or broken
> links. Do not fix anything yet — just give the list.

Then, fix the remaining issues:

> For any 404: either the route is missing, the controller method is missing, or
> the view is missing. Add what's needed so the link renders a page (it was built
> in week 3, so you are wiring it in, not building it). Add the route,
> controller method, and view if needed — they should already exist as static
> pages with sample data.

**Final checks:**
- Every sidebar link opens a live page (no 404)
- Every form renders (create, edit, filters) without errors
- Guest and signed-in user can both navigate their respective screens
- Error pages (404, 403, 500) render without PHP errors
- Rehearse end-to-end: Guest landing → Register → Admin approves → User logs in
  → Creates item → Edits item → Browses own items → Details page → (if built)
  rating/feedback flow

---


**Rehearse one path end-to-end:**
```
Guest landing → Register org → Admin approves → Org logs in →
Creates campaign → Edits it → Community user browses → Views detail →
Org cancels it
```
That single path shows auth, navigation, and a full CRUD. Each member then
repeats the CRUD portion for their own module. Time it. Practise it twice.

---

## Week 3 — Prompts for frontend render-out

No backend yet. The goal is to show the Figma layout with sample data, wired
into the navigation.

| Weak | Strong |
|---|---|
| "Build all cleanup screens" | "Render the cleanup browse, create, detail, and edit screens matching cleanup.svg, with sample data and placeholder forms" |
| "Make the form work" | "Build the HTML form for cleanup create matching the Figma, with title, date, category dropdown (5 sample options), city dropdown (5 cities), and submit button. No backend — the button just redirects back" |
| "Add the pages to navigation" | "Wire `/org/cleanup`, `/org/cleanup/create`, `/cleanup/{id}` into routes as static views. Add them to sidebar-org.php so they appear in the link list" |

---

## Weeks 4–5 — Prompts for backend CRUD implementation

| Weak | Strong |
|---|---|
| "Build the cleanup module" | "Create only the CleanupEvent model with $table and $fillable from schema.sql" |
| "Fix the bug" | "Clicking Edit gives 404. Here's the route and the controller method. What's wrong?" |
| "Make it look nice" | "Style this table using the .table and .badge classes in components/ui.css" |
| "Add validation" | "Add validation: title required max 160, event_date required and after today" |

**Always end a session with:** "Give me the exact command or URL to verify this works."

---

## Rules to protect the group

**Never let Claude Code edit `app/core/` or `schema.sql`.** If it proposes that,
stop and raise it with the group. One silent schema change breaks three people.

**Read what it writes.** You have to defend this code at the interim. If you
can't explain a line, ask Claude Code to explain it before you commit it.

**Verify before committing.** `php -l` catches syntax errors; opening the URL
catches everything else. A commit that doesn't run blocks whoever pulls next.

**One person, one module, one branch.** Two people in the same file is where
group projects die.

---

## Weekly checkpoint

Every Sunday, 15 minutes, all four:
1. Everyone pulls `main` and confirms the app still loads
2. **Week 3:** Each member opens their module's screens and clicks through the
   navigation — does every link work? Do all forms render? Any 404s?
3. **Weeks 4–5:** Each member demos their CRUD in the browser (create, read list,
   update, delete) with real data
4. Anything broken gets fixed before new work starts

A broken `main` on Monday costs the whole group a week.
