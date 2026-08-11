# TogetherThrives — Group 23

Centralised Disaster Response and Community Support System.
PHP 8 · MySQL · MVC · XAMPP

---

## Setup (every member does this once)

1. **Clone into XAMPP**
   ```
   cd C:/xampp/htdocs
   git clone <repo-url> togetherthrives
   ```

2. **Create the database**
   - Open http://localhost/phpmyadmin
   - Import `database/schema.sql`
   - Import `database/seeds/seed.sql`

3. **Configure**
   ```
   copy .env.example .env
   ```
   Edit `.env` if your MySQL user/password differs from `root` / empty.

4. **Install packages** (needed from week 2 for email)
   ```
   composer install
   ```

5. **Open** http://localhost/togetherthrives/public

   Admin login: `admin@togetherthrives.lk` / `Admin@123`

---

## How a request flows

```
public/index.php
  → Core\App          boots session + database, loads config/routes/*.php
  → Core\Router       matches URI, runs middleware
  → Controller        validates input, calls a Service
  → Service           business rules (eligibility, fair distribution, quantities)
  → Model             prepared-statement queries
  → view + layout     rendered HTML
```

---

## Rules for the group

**Do not edit these without telling everyone:**
`app/core/*`, `config/routes/web.php`, `database/schema.sql`, `assets/css/base/variables.css`

**Your own files:**
| Member | Routes file | Controllers | Models | Views | CSS |
|---|---|---|---|---|---|
| Dilsha Cleanup  | `routes/cleanup.php`  | `controllers/cleanup/`  | `models/cleanup/`  | `views/cleanup/`  | `css/pages/cleanup.css` |
| Janudi Donation | `routes/donation.php` | `controllers/donation/` | `models/donation/` | `views/donation/` | `css/pages/donation.css` |
| Shenol Blood    | `routes/blood.php`    | `controllers/blood/`    | `models/blood/`    | `views/blood/`    | `css/pages/blood.css` |
| Janagan Reuse    | `routes/reuse.php`    | `controllers/reuse/`    | `models/reuse/`    | `views/reuse/`    | `css/pages/reuse.css` |

Each member adds one `@import` line to `assets/css/main.css`. That is the only shared CSS edit.

---

## Writing a model

```php
namespace Models\Cleanup;
use Core\Model;

class CleanupEvent extends Model
{
    protected string $table = 'cleanup_events';
    protected array $fillable = ['organization_id','title','description','event_date', /* ... */];
}
```

**Child and lookup tables have no `deleted_at`** (`item_images`, `attendance`, `cities`,
`notifications`, `payments`, …). Their models must set:
```php
protected bool $softDelete = false;
```

## Writing a controller

```php
namespace Controllers\Cleanup;
use Core\Controller;
use Models\Cleanup\CleanupEvent;

class CleanupEventController extends Controller
{
    public function store(): void
    {
        $data = $this->validate([
            'title'       => 'required|max:160',
            'event_date'  => 'required|date|after_today',
            'volunteer_limit' => 'required|integer|min_value:1',
        ]);
        $data['organization_id'] = auth()['id'];
        (new CleanupEvent())->create($data);

        \Core\Session::flash('success', 'Campaign created.');
        $this->redirect('/org/cleanup');
    }
}
```

## Writing a form

```php
<form method="post" action="<?= url('/org/cleanup') ?>">
  <?= csrf_field() ?>
  <div class="field <?= has_error('title') ? 'field--invalid' : '' ?>">
    <label class="field__label">Title</label>
    <input class="field__input" name="title" value="<?= old('title') ?>">
    <span class="field__error"><?= error_for('title') ?></span>
  </div>
  <button class="btn btn--primary">Save</button>
</form>
```

For update and delete, HTML forms can only POST — use the method spoof:
```php
<form method="post" action="<?= url('/org/cleanup/'.$id) ?>">
  <?= csrf_field() ?><?= method_field('PUT') ?>
```

---

## Security rules — non-negotiable

- Every `echo` of a variable goes through `e()`
- Every POST/PUT/DELETE form includes `csrf_field()`
- Never concatenate user input into SQL — use `Database::run($sql, $params)`
- Uploads go through `FileUploader::store()`, never `move_uploaded_file()` directly
- `storage/` is outside the web root; serve files through `FileController`
