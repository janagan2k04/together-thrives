<?php
namespace Controllers\Reuse;

use Controllers\FileController;
use Core\Controller;
use Core\Request;
use Core\Session;
use Models\City;
use Models\Reuse\ItemCategory;
use Models\Reuse\ItemImage;
use Models\Reuse\ItemRequest;
use Models\Reuse\ReuseFeedback;
use Models\Reuse\ReuseItem;
use Models\User;
use Services\ReuseService;

/**
 * MEMBER 4 - Reuse Item Listings. Full CRUD on `reuse_items`.
 *
 * /reuse and /reuse/{id} are open to guests; everything under /my/reuse is the
 * donor's own workspace and every write checks ownership before it runs.
 */
class ItemListingController extends Controller
{
    private ReuseItem    $items;
    private ItemCategory $categories;
    private ItemImage    $images;
    private City         $cities;
    private ReuseService $service;

    public function __construct()
    {
        $this->items      = new ReuseItem();
        $this->categories = new ItemCategory();
        $this->images     = new ItemImage();
        $this->cities     = new City();
        $this->service    = new ReuseService();
    }

    // ---------- READ (public list) ----------

    public function index(): void
    {
        $filters = array_filter([
            'category_id'    => (string) Request::get('category_id', ''),
            'item_condition' => (string) Request::get('item_condition', ''),
            'city_id'        => (string) Request::get('city_id', ''),
            'q'              => trim((string) Request::get('q', '')),
            'sort'           => (string) Request::get('sort', ''),
        ], fn($v) => $v !== '');

        $page   = max(1, (int) Request::get('page', 1));
        $offset = ($page - 1) * PER_PAGE;
        $total  = $this->items->browseCount($filters);

        $this->view('reuse.index', [
            'title'      => 'Browse Reuse Items',
            'items'      => $this->items->browse($filters, PER_PAGE, $offset),
            'categories' => $this->categories->listWithCounts(),
            'cities'     => $this->cities->listAll(),
            'conditions' => ReuseItem::CONDITIONS,
            'filters'    => $filters,
            'page'       => $page,
            'pages'      => (int) ceil($total / PER_PAGE),
            'total'      => $total,
        ], $this->publicLayout());
    }

    // ---------- READ (donor's own list) ----------

    public function manage(): void
    {
        $donorId = (int) auth()['id'];

        $this->view('reuse.manage', [
            'title'  => 'My Listings',
            'items'  => $this->items->forDonor($donorId),
            'counts' => $this->items->statusCountsFor($donorId),
        ]);
    }

    // ---------- READ (public detail) ----------

    public function show(string $id): void
    {
        // Loaded including withdrawn rows so the donor can still open their own
        // listing from My Listings; everyone else is turned away just below.
        $item = $this->items->findDetail((int) $id, true);
        if (!$item) {
            $this->abort(404, 'That listing is no longer available.');
        }

        $private = $item['deleted_at'] !== null
            || !in_array($item['status'], ReuseItem::PUBLIC_STATUSES, true);

        if ($private && !$this->ownsItem((int) $item['donor_id'])) {
            // A listing awaiting approval, turned down, or withdrawn is the
            // donor's own business. 404 rather than 403 - a stranger should not
            // learn that the id exists at all.
            $this->abort(404, 'That listing is no longer available.');
        }

        $this->view('reuse.show', [
            'title'    => $item['title'],
            'item'     => $item,
            'images'   => $this->images->forItem((int) $item['id']),
            'similar'  => $this->items->similar((int) $item['category_id'], (int) $item['id']),
            'myRequest' => is_logged_in()
                ? (new ItemRequest())->forRequester((int) $item['id'], (int) auth()['id'])
                : null,
            'isOwner'  => $this->ownsItem((int) $item['donor_id']),
        ], $this->publicLayout());
    }

    // ---------- READ (public donor profile) ----------

    /** "Reuse - Donor Public Profile" frame. Open to guests. */
    public function donor(string $id): void
    {
        $donor = (new User())->find((int) $id);
        if (!$donor || $donor['status'] !== STATUS_ACTIVE) {
            $this->abort(404, 'That member profile is not available.');
        }

        $feedback = new ReuseFeedback();

        $this->view('reuse.donor', [
            'title'    => $donor['full_name'],
            'donor'    => $donor,
            'stats'    => $this->items->donorStats((int) $donor['id']),
            'listings' => $this->items->browse(['donor_id' => $donor['id']], 8),
            'reviews'  => $feedback->forDonor((int) $donor['id']),
            'rating'   => $feedback->summaryFor((int) $donor['id']),
        ], $this->publicLayout());
    }

    // ---------- CREATE ----------

    public function create(): void
    {
        $this->view('reuse.create', [
            'title' => 'Donate an Item',
            'item'  => null,
        ] + $this->formOptions());
    }

    public function store(): void
    {
        $data   = $this->validate($this->rules());
        $photos = ReuseService::normalizeFiles($_FILES['photos'] ?? null);

        if (!$photos) {
            $this->bounce(['photos' => ['Add at least one photo of the item.']], $data);
        }

        try {
            $id = $this->service->create((int) auth()['id'], $data, $photos);
        } catch (\RuntimeException $e) {
            // Upload problems are the donor's to fix - show the message rather
            // than a 500, and keep everything they typed.
            $this->bounce(['photos' => [$e->getMessage()]], $data);
        }

        Session::flash('success',
            'Listing submitted. An administrator reviews it before it appears in the browse grid.');
        $this->redirect('/reuse/' . $id);
    }

    // ---------- UPDATE ----------

    public function edit(string $id): void
    {
        $item = $this->ownedOrFail((int) $id, true);

        $this->view('reuse.edit', [
            'title'  => 'Edit Listing',
            'item'   => $item,
            'images' => $this->images->forItem((int) $item['id']),
        ] + $this->formOptions());
    }

    public function update(string $id): void
    {
        $item = $this->ownedOrFail((int) $id, true);
        $data = $this->validate($this->rules());

        $photos = ReuseService::normalizeFiles($_FILES['photos'] ?? null);
        $remove = array_map('intval', (array) Request::post('remove_images', []));

        // Refusing to strip the last photo keeps every listing showing a card
        // image, which is what the browse grid in the design assumes.
        $keeping = count($this->images->forItem((int) $item['id'])) - count($remove) + count($photos);
        if ($keeping < 1) {
            $this->bounce(['photos' => ['A listing needs at least one photo.']], $data);
        }

        try {
            $this->service->update((int) $item['id'], $data, $photos, $remove);
        } catch (\RuntimeException $e) {
            $this->bounce(['photos' => [$e->getMessage()]], $data);
        }

        Session::flash('success', in_array($item['status'], [ITEM_AVAILABLE, ITEM_REJECTED], true)
            ? 'Listing updated. It goes back to the administrator for review before the public sees the changes.'
            : 'Listing updated.');
        $this->redirect('/my/reuse');
    }

    // ---------- DELETE ----------

    /**
     * Withdraw, not erase. The row is soft deleted and marked removed so the
     * donor's history and any requests attached to it survive.
     */
    public function destroy(string $id): void
    {
        $item = $this->ownedOrFail((int) $id);

        // The button is hidden for a completed donation; refuse it here too,
        // or a replayed form would overwrite that outcome with "removed".
        if ($item['status'] === ITEM_DONATED) {
            Session::flash('error', 'A completed donation cannot be withdrawn.');
            $this->redirect('/my/reuse');
        }

        $this->service->withdraw((int) $item['id']);

        Session::flash('success', 'Listing withdrawn.');
        $this->redirect('/my/reuse');
    }

    /**
     * Put a withdrawn listing back. The counterpart of a soft delete - without
     * it, "withdraw" would be indistinguishable from erasing the row.
     */
    public function relist(string $id): void
    {
        $item = $this->ownedOrFail((int) $id, true);

        if ($item['deleted_at'] === null) {
            Session::flash('error', 'That listing is already live.');
            $this->redirect('/my/reuse');
        }

        $this->service->relist((int) $item['id']);

        Session::flash('success',
            'Listing restored. It goes back to the administrator for review before the public sees it.');
        $this->redirect('/my/reuse');
    }

    // ---------- item photos ----------

    /**
     * Uploads live outside the web root, and the generic /file/{path} route
     * cannot match a two-segment stored path (the router compiles {param} to
     * [^/]+). Photos are therefore addressed by their row id.
     *
     * A listing is public, so its photos are public - except once it has been
     * withdrawn, when only the donor and an administrator may still see them.
     */
    public function image(string $id): void
    {
        $image = $this->images->withItem((int) $id);
        if (!$image) {
            $this->abort(404);
        }

        $hidden = $image['deleted_at'] !== null || $image['status'] === ITEM_REMOVED;
        if ($hidden && !$this->ownsItem((int) $image['donor_id'])) {
            $this->abort(404);
        }

        (new FileController())->stream($image['file_path']);
    }

    // ---------- form plumbing ----------

    /**
     * Category and city are foreign keys, so an id that does not exist would
     * come back as a database error. Validating against the real id lists turns
     * that into an ordinary field error instead.
     *
     * "Availability Period" is on the Figma form but has no column in the
     * frozen schema, so it is not collected - see WEEK1-CHECKLIST.md for the
     * other fields dropped the same way.
     */
    private function rules(): array
    {
        $categoryIds = implode(',', array_column($this->categories->listAll(), 'id'));
        $cityIds     = implode(',', array_column($this->cities->listAll(), 'id'));

        return [
            'title'          => 'required|max:160',
            'category_id'    => 'required|integer|in:' . $categoryIds,
            'item_condition' => 'required|in:' . implode(',', array_keys(ReuseItem::CONDITIONS)),
            'description'    => 'required|max:2000',
            'quantity'       => 'required|integer|min_value:1',
            'city_id'        => 'required|integer|in:' . $cityIds,
            'pickup_note'    => 'max:255',
        ];
    }

    private function formOptions(): array
    {
        return [
            'categories' => $this->categories->listAll(),
            'cities'     => $this->cities->listAll(),
            'conditions' => ReuseItem::CONDITIONS,
        ];
    }

    /**
     * Send the donor back to the form with an error the Validator cannot
     * produce - a rejected upload, mostly - without losing what they typed.
     */
    private function bounce(array $errors, array $data): never
    {
        Session::flashInput($data, $errors);
        Session::flash('error', 'Please correct the highlighted fields.');
        $this->back();
    }

    // ---------- guards ----------

    /**
     * Load a listing the caller is allowed to change. Never trust the id in
     * the URL: it is the only thing standing between one donor and another
     * donor's listings.
     */
    private function ownedOrFail(int $id, bool $includeWithdrawn = false): array
    {
        $item = $includeWithdrawn ? $this->items->findAny($id) : $this->items->find($id);
        if (!$item) {
            $this->abort(404, 'Listing not found.');
        }
        if (!$this->ownsItem((int) $item['donor_id'])) {
            $this->abort(403, 'You can only manage your own listings.');
        }
        return $item;
    }

    /** True when the signed-in account is the donor, or an administrator. */
    private function ownsItem(int $donorId): bool
    {
        return is_logged_in()
            && ((int) auth()['id'] === $donorId || role_is(ROLE_ADMIN));
    }

    /**
     * Guests get the public shell. `layouts/app` builds a sidebar and a user
     * chip from Auth::user(), so rendering it signed-out would print an empty
     * account menu and a Sign out button.
     */
    private function publicLayout(): string
    {
        return is_logged_in() ? 'app' : 'guest';
    }
}
