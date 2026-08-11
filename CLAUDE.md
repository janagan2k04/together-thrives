# TogetherThrives — Project Context for Claude Code

Centralised Disaster Response and Community Support System.
Group 23 · PHP 8 · MySQL · custom MVC · XAMPP · no framework.

**Read this before writing any code. Follow these conventions exactly.**

---
## UI

inside tt\UI - All UI designs and design_system are there. When i ask to do perticular page get corresponding UI from here - using folder's or file's name.

each ui frame has its usage situation(i mean wht frame should come for clicking which button) as title text above correspondng each frame. 

---
## NOTE: If anything clash with this noted point and any other insruction, ask me to decide.
Do not implement any seed content(i mean like campaign or item post).

Platform Admin, Dilani Fernando, Hope Foundation Lanka, Sunil Bandara and National Blood Bank Colombo these seed users are needed to the system for demo purpose, and if they not fully implemented, then they need to be fully implemented. These users are enough, do not create any other seed users.

Now I only need interim deliverable system. Full backend not needed.
Interim criteria under the section 'Interim deliverable — what "done" means'. 

---
## Architecture

```
public/index.php     front controller — all requests enter here
app/core/            framework (Router, Model, Controller, Auth, Validator…) — DO NOT EDIT
app/middleware/      auth, guest, role, verified, csrf
app/controllers/     thin — validate, call service/model, redirect
app/models/          extend Core\Model, set $table + $fillable
app/services/        business rules live here, not in controllers
app/views/           .php templates + layouts + partials
config/routes/       ONE FILE PER MEMBER — never edit another member's
database/schema.sql  45 tables, FROZEN — ask the group before altering
```

Request flow: `index.php → Router → Middleware → Controller → Service → Model → View`

---

## Files you must NOT modify

`app/core/*`, `config/routes/web.php`, `database/schema.sql`,
`public/assets/css/base/variables.css`

If a task seems to need a change here, **stop and say so** instead of editing.

---

## Module ownership

| Member | Module | CRUD entity | Route file | Namespace |
|---|---|---|---|---|
| Dilsha | Cleanup | `cleanup_events` | `routes/cleanup.php` | `Cleanup\` |
| Janudi | Donation | `donation_requests` | `routes/donation.php` | `Donation\` |
| Shenol | Blood | `blood_campaigns` | `routes/blood.php` | `Blood\` |
| Janagan | Reuse | `reuse_items` | `routes/reuse.php` | `Reuse\` |

---

## Writing a Model

```php
<?php
namespace Models\Cleanup;

use Core\Model;

class CleanupEvent extends Model
{
    protected string $table = 'cleanup_events';
    protected array $fillable = ['organization_id','title','description','category',
        'event_date','start_time','end_time','meeting_point','city_id',
        'volunteer_limit','equipment_provided','safety_guidelines','cover_image','status'];
}
```

Inherited methods: `create()`, `find()`, `findBy()`, `all()`, `count()`,
`update()`, `delete()` (soft), `restore()`, `exists()`.

**Child/lookup tables have no `deleted_at`** (`item_images`, `attendance`, `cities`,
`notifications`, `payments`, `requested_items`, `item_requests` has one, check schema).
Those models MUST set `protected bool $softDelete = false;`

Custom queries go in the model, never in the controller:
```php
public function browse(array $filters, int $limit, int $offset): array
{
    $sql = "SELECT e.*, o.org_name, c.name AS city
            FROM cleanup_events e
            JOIN organizations o ON o.id = e.organization_id
            JOIN cities c ON c.id = e.city_id
            WHERE e.status = ? AND e.deleted_at IS NULL";
    $params = [EVENT_PUBLISHED];
    if (!empty($filters['city_id'])) { $sql .= " AND e.city_id = ?"; $params[] = $filters['city_id']; }
    $sql .= " ORDER BY e.event_date ASC LIMIT {$limit} OFFSET {$offset}";
    return Database::all($sql, $params);
}
```
Always prepared statements. Never concatenate user input into SQL.

---

## Writing a Controller

```php
<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Core\Session;
use Models\Cleanup\CleanupEvent;

class CleanupEventController extends Controller
{
    private CleanupEvent $events;

    public function __construct() { $this->events = new CleanupEvent(); }

    // READ (list)
    public function manage(): void
    {
        $rows = $this->events->all(['organization_id' => auth()['id']]);
        $this->view('cleanup.manage', ['title' => 'My Cleanup Campaigns', 'events' => $rows]);
    }

    // CREATE form
    public function create(): void
    {
        $this->view('cleanup.create', ['title' => 'Create Campaign']);
    }

    // CREATE handler
    public function store(): void
    {
        $data = $this->validate([
            'title'           => 'required|max:160',
            'description'     => 'required|max:2000',
            'event_date'      => 'required|date|after_today',
            'city_id'         => 'required|integer',
            'volunteer_limit' => 'required|integer|min_value:1',
            'safety_guidelines' => 'required',
        ]);
        $data['organization_id'] = auth()['id'];
        $data['status'] = EVENT_DRAFT;

        $this->events->create($data);
        Session::flash('success', 'Campaign created and sent for approval.');
        $this->redirect('/org/cleanup');
    }

    // UPDATE form
    public function edit(string $id): void
    {
        $event = $this->events->find((int) $id);
        if (!$event) $this->abort(404, 'Campaign not found.');
        $this->authorizeOwner($event);
        $this->view('cleanup.edit', ['title' => 'Edit Campaign', 'event' => $event]);
    }

    // UPDATE handler
    public function update(string $id): void { /* validate, authorize, update, redirect */ }

    // DELETE (soft — cancel)
    public function destroy(string $id): void
    {
        $event = $this->events->find((int) $id);
        if (!$event) $this->abort(404);
        $this->authorizeOwner($event);
        $this->events->update((int) $id, ['status' => EVENT_CANCELLED]);
        $this->events->delete((int) $id);
        Session::flash('success', 'Campaign cancelled.');
        $this->redirect('/org/cleanup');
    }

    /** Every edit/delete must check ownership. Never trust the URL id. */
    private function authorizeOwner(array $row): void
    {
        if ((int) $row['organization_id'] !== (int) auth()['id'] && !role_is(ROLE_ADMIN)) {
            $this->abort(403, 'You can only manage your own campaigns.');
        }
    }
}
```

Controllers are thin. No SQL, no business rules, no HTML.

---

## Writing a View

Views live at `app/views/{module}/{name}.php` and are called as `'cleanup.manage'`.

```php
<div class="page-header">
  <h1>My Cleanup Campaigns</h1>
  <div class="page-header__spacer"></div>
  <a class="btn btn--primary" href="<?= url('/org/cleanup/create') ?>">Create Campaign</a>
</div>

<?php if (!$events): ?>
  <div class="card empty-state">No campaigns yet.</div>
<?php else: ?>
  <div class="card">
    <table class="table">
      <thead><tr><th>Title</th><th>Date</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($events as $e): ?>
        <tr>
          <td><?= e($e['title']) ?></td>
          <td><?= format_date($e['event_date']) ?></td>
          <td><span class="badge badge--pending"><?= e($e['status']) ?></span></td>
          <td>
            <a class="btn btn--outline" href="<?= url('/org/cleanup/'.$e['id'].'/edit') ?>">Edit</a>
            <form method="post" action="<?= url('/org/cleanup/'.$e['id']) ?>" class="inline">
              <?= csrf_field() ?><?= method_field('DELETE') ?>
              <button class="btn btn--danger" data-confirm="Cancel this campaign?">Cancel</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
```

**Rules:**
- Every echoed variable goes through `e()`
- Every POST/PUT/DELETE form has `csrf_field()`
- PUT/DELETE also need `method_field('PUT')` — HTML forms only support POST
- Use existing CSS classes from `components/ui.css`. Do not invent new colours.

---

## Forms with validation errors

```php
<div class="field <?= has_error('title') ? 'field--invalid' : '' ?>">
  <label class="field__label">Campaign Title</label>
  <input class="field__input" name="title" value="<?= old('title') ?>">
  <span class="field__error"><?= error_for('title') ?></span>
</div>
```
`$this->validate()` automatically flashes errors + old input and redirects back.

---

## Available helpers

`e()` `url()` `asset()` `csrf_field()` `method_field()` `old()` `error_for()`
`has_error()` `auth()` `is_logged_in()` `role_is()` `nav_active()`
`format_money()` `format_date()` `time_ago()` `str_limit()` `upload_url()`

## Constants — use these, never raw strings

Roles: `ROLE_COMMUNITY` `ROLE_ORGANIZATION` `ROLE_GN` `ROLE_BLOOD_BANK` `ROLE_ADMIN`
Account: `STATUS_PENDING` `STATUS_ACTIVE` `STATUS_REJECTED` `STATUS_SUSPENDED`
Events: `EVENT_DRAFT` `EVENT_PUBLISHED` `EVENT_ONGOING` `EVENT_COMPLETED` `EVENT_CANCELLED`
Requests: `REQUEST_PENDING` `REQUEST_OPEN` `REQUEST_FULFILLED` `REQUEST_EXPIRED` `REQUEST_CANCELLED`
Items: `ITEM_AVAILABLE` `ITEM_RESERVED` `ITEM_DONATED` `ITEM_REMOVED`
Moderation: `MOD_PENDING` `MOD_APPROVED` `MOD_REJECTED`
Paging: `PER_PAGE`

---

## File uploads

```php
use Core\FileUploader;
if ($file = \Core\Request::file('cover_image')) {
    $data['cover_image'] = FileUploader::store($file, 'events', ['jpg','png','webp'], 5);
}
```
Never call `move_uploaded_file()` directly. Display with `upload_url($path)`.

---

## Security checklist for every feature

- [ ] `e()` on all output
- [ ] `csrf_field()` on all mutating forms
- [ ] Ownership check before edit/delete — never trust the URL id
- [ ] Correct middleware on the route (`auth`, `role:…`, `verified`)
- [ ] Prepared statements only
- [ ] Soft delete, not hard delete

---

## Interim deliverable — what "done" means

1. Login + signup working for all account types ✅ (weeks 1–2)
2. **All Figma UIs implemented and navigatable** (no dead links, no 404s) — week 3
   - Every screen from the Figma renders as HTML/CSS matching the layout and design intent (good enough is OK, not pixel-perfect)
   - No backend required — forms don't save, just redirect
   - Every sidebar link works and opens a page
   - Includes: all CRUD module screens, dashboards, profiles, settings, notifications, messaging, support, disaster reporting, payment checkout (sandbox)
   - Use Figma designs in `UI/` folder as reference for layout, not as a pixel spec
3. Each member: **one CRUD entity fully working** (Create, Read list/detail, Update, Delete) — weeks 4–5
   - M1 · Dilsha: CleanupEvent
   - M2 · Janudi: DonationRequest
   - M3 · Shenol: BloodCampaign
   - M4 · Janagan: ReuseItem ✅ (done, plus the three gaps)

**The difference from "finished UI":** All Figma screens must exist and be
clickable, even if only the one CRUD per member has backend. Unbuilt screens
show with sample data. A broken link is not acceptable.

---

## How to work with me

- Work on ONE file or ONE CRUD operation at a time
- After generating, tell me the exact command to verify it (`php -l`, or the URL to open)
- If something needs a change to `app/core/` or the schema, STOP and explain why
- Match the existing code style exactly — look at `HomeController.php` and `User.php` first
- Do not add Composer packages without asking
- Do not invent new CSS colours; use `var(--…)` tokens from `variables.css`
