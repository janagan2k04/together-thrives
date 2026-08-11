# Finishing TogetherThrives to Interim Level with Claude Code

Week 1 scaffold is done. This is how to get from there to the interim demo.

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

## Weeks 3–5 — The four CRUDs (fully parallel)

Each member, same five sessions. Example is M1 / Cleanup Event; substitute your own.

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

---

## Week 6 — Navigation (this is the requirement people fail)

Requirement 2 says the system must be *navigable*. Split the sweep four ways.

> Read CLAUDE.md. Go through `app/views/partials/sidebar-community.php` and list
> every `href`. For each, check whether a matching route exists in `config/routes/`.
> Report which links currently 404. Do not fix anything yet — just give me the list.

Then, for each dead link, either wire it or build a static placeholder page:

> Create a static placeholder view for the Impact Report screen matching the
> Figma layout, using hardcoded sample data. Add the route and a controller
> method that just renders it. Label nothing as "coming soon" — it should look
> like a finished screen.

**Also this week:**
- Guest browse pages for all four modules with gated buttons
- The "Sign in required" modal
- The long landing page content
- Verify 404 and 403 pages render properly

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

## Prompts that work vs prompts that don't

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
2. Each demos their CRUD in the browser
3. Anything broken gets fixed before new work starts

A broken `main` on Monday costs the whole group a week.
