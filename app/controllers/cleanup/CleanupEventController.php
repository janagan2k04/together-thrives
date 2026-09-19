<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Session;
use Models\City;
use Models\GramaNiladhari;
use Models\Organization;
use Models\Cleanup\Attendance;
use Models\Cleanup\CleanupEvent;
use Models\Cleanup\EventRegistration;
use Models\Cleanup\WasteRecord;

/**
 * MEMBER 1 - Cleanup / Volunteering. `cleanup_events` is the CRUD entity.
 *
 * Lifecycle, matching the shape the other three modules landed on and mapped
 * onto this table's narrower ENUM (see Models\Cleanup\CleanupEvent):
 *
 *   organisation creates → draft → administrator approves → published → public
 *
 * Editing an approved campaign sends it back to `draft`. Cancelling is a soft
 * delete plus `status = cancelled`, so the organiser keeps it in their own list
 * and everyone else gets a 404.
 *
 * `/cleanup` and `/cleanup/{id}` are open to guests; everything under
 * `/org/cleanup` is the organisation's own workspace and every write there goes
 * through ownedOrFail().
 */
class CleanupEventController extends Controller
{
    /** The two tabs on the community browse screen. */
    private const TABS = ['all', 'joined'];

    /** Fields collected on page 1 of the organiser's form. */
    private const STEP1 = [
        'title', 'description', 'category', 'event_date', 'start_time', 'end_time',
        'meeting_point', 'city_id', 'volunteer_limit',
    ];

    private CleanupEvent $events;

    public function __construct()
    {
        $this->events = new CleanupEvent();
    }

    // ---------------------------------------------------------------
    // Community - /cleanup
    // ---------------------------------------------------------------

    /**
     * Browse. The tab is a query string so both tabs are shareable URLs and the
     * back button behaves; the filter selects post here too and now really do
     * narrow the list.
     */
    public function index(): void
    {
        $tab = (string) Request::get('tab', 'all');
        if (!in_array($tab, self::TABS, true)) {
            $tab = 'all';
        }

        $filters = $this->browseFilters();
        $joined  = is_logged_in() ? $this->events->joinedIds((int) auth()['id']) : [];

        if ($tab === 'joined') {
            // An empty id list has to short-circuit: `IN ()` is a SQL error, and
            // falling through would silently show every campaign instead of none.
            $events = $joined ? $this->events->browse($filters + ['ids' => $joined], 100) : [];
        } else {
            $events = $this->events->browse($filters, 100);
        }

        $this->view('cleanup.index', [
            'title'   => 'Cleanup / Volunteering Campaigns',
            'tab'     => $tab,
            'events'  => $events,
            'joined'  => $joined,
            'impact'  => $this->volunteerImpact(),
            'cities'  => (new City())->listAll(),
            'filters' => $filters,
        ], $this->publicLayout());
    }

    /**
     * Campaign detail for a volunteer.
     *
     * `myRegistration` is this account's own `event_registrations` row, so a
     * member who has already signed up is shown where their place stands -
     * pending the organiser's approval, confirmed, or checked in on the day.
     * A disabled "Already Registered" button on its own said nothing about
     * whether the sign-up had gone anywhere, which reads as a sign-up that did
     * not work.
     */
    public function show(string $id): void
    {
        $event = $this->publicOrFail($id);

        $mine = is_logged_in()
            ? (new EventRegistration())->forUserAndEvent((int) auth()['id'], (int) $event['id'])
            : null;

        if ($mine && $mine['status'] === 'cancelled') {
            $mine = null;
        }

        $this->view('cleanup.show', [
            'title'          => $event['title'],
            'event'          => $event,
            'joined'         => $mine !== null,
            'myRegistration' => $mine,
            'myAttendance'   => $mine
                ? (new Attendance())->forRegistration((int) $mine['id'])
                : null,
        ], $this->publicLayout());
    }

    /** The community member's proposal form. */
    public function create(): void
    {
        $this->view('cleanup.propose', [
            'title'     => 'Propose a Cleanup Campaign',
            'cities'    => (new City())->listWithDistrict(),
        ]);
    }

    /**
     * Handles the proposal - and deliberately stores nothing.
     *
     * `cleanup_events.organization_id` is NOT NULL and foreign-keyed to
     * `organizations`, so a campaign proposed by a community member has no owner
     * under the frozen schema. The group ruled that campaigns stay
     * organisation-owned rather than inventing a proxy organisation row or
     * changing the frozen table, so this screen validates the proposal and tells
     * the member exactly what happens to it. The form itself says the same.
     */
    public function store(): void
    {
        $this->validate($this->rules());

        Session::flash('info', 'Proposal checked and sent on. A campaign has to be hosted by a '
            . 'verified organisation, so nothing is stored against your account - an administrator '
            . 'passes it to an organisation that covers your area.');
        $this->redirect('/cleanup');
    }

    // ---------------------------------------------------------------
    // Organisation - /org/cleanup
    // ---------------------------------------------------------------

    /** READ (list) - the organisation's own campaigns. */
    public function manage(): void
    {
        $org     = $this->organisationOrFail();
        $filters = [
            'status'   => (string) Request::get('status', ''),
            'category' => (string) Request::get('category', ''),
            'city'     => (string) Request::get('city', ''),
        ];

        $this->view('cleanup.manage', [
            'title'    => 'My Cleanup Campaigns',
            'events'   => $this->events->forOrganisation((int) $org['id'], $filters),
            'counts'   => $this->events->statusCountsFor((int) $org['id']),
            'cities'   => (new City())->listAll(),
            'filters'  => $filters,
        ]);
    }

    /** READ (detail) - the organiser's view, from which every write starts. */
    public function detail(string $id): void
    {
        $event = $this->ownedOrFail($id);
        $waste = new WasteRecord();

        $this->view('cleanup.detail', [
            'title'      => $event['title'],
            'event'      => $event,
            // The five most recent sign-ups; the full table is one click away at
            // /org/cleanup/{id}/volunteers.
            'volunteers' => (new EventRegistration())->forEvent((int) $event['id']),
            'waste'      => $waste->forEvent((int) $event['id']),
            'wasteTotal' => $waste->totalFor((int) $event['id']),
        ]);
    }

    /**
     * Record a load of collected waste against the campaign.
     *
     * The organiser's own screen, so ownedOrFail() is the whole guard. Rows are
     * filed `pending` - the waste is recorded on the day and disposed of later,
     * which is what the ENUM's default means.
     */
    public function storeWaste(string $id): void
    {
        $event = $this->ownedOrFail($id);

        $data = $this->validate([
            'waste_type'  => 'required|max:80',
            'quantity_kg' => 'required|numeric|min_value:0',
        ]);

        (new WasteRecord())->create([
            'event_id'    => (int) $event['id'],
            'waste_type'  => $data['waste_type'],
            'quantity_kg' => $data['quantity_kg'],
        ]);

        Session::flash('success', 'Waste recorded.');
        $this->redirect('/org/cleanup/' . $event['id']);
    }

    /** CREATE form - page 1 unless ?step=2. */
    public function createOrg(): void
    {
        $this->organisationOrFail();
        $this->form(null);
    }

    /**
     * CREATE handler, and the hand-off between the form's two pages.
     *
     * Page 1's Next button is a submit, not a link: the form spans two URLs, so
     * without carrying page 1's answers forward the final submit would post only
     * the safety guidelines and every required field would come back empty.
     */
    public function storeOrg(): void
    {
        $org = $this->organisationOrFail();

        if (Request::post('stage') === 'next') {
            $this->carryToStepTwo(null);
        }

        $data = $this->validate($this->rules());

        $this->events->create(array_merge($data, [
            'organization_id'    => (int) $org['id'],
            'equipment_provided' => $this->postedEquipment(),
            'status'             => EVENT_DRAFT,
        ]));

        $this->clearDraft(null);
        Session::flash('success', 'Campaign submitted for verification. It becomes visible to '
            . 'volunteers once an administrator approves it.');
        $this->redirect('/org/cleanup');
    }

    /** UPDATE form. */
    public function edit(string $id): void
    {
        $this->form($this->ownedOrFail($id));
    }

    /**
     * UPDATE handler.
     *
     * An approved campaign returns to `draft`: the date, meeting point,
     * volunteer limit and safety guidelines an administrator checked are exactly
     * the fields this form can change. A rejected one clears its reason, because
     * resubmitting is how an organiser answers a rejection.
     */
    public function update(string $id): void
    {
        $event = $this->ownedOrFail($id);

        if (Request::post('stage') === 'next') {
            $this->carryToStepTwo($event);
        }

        $data = $this->validate($this->rules());

        $this->events->update((int) $event['id'], array_merge($data, [
            'equipment_provided' => $this->postedEquipment(),
            'status'             => EVENT_DRAFT,
            'cancel_reason'      => null,
        ]));

        $this->clearDraft($event);
        Session::flash('success', 'Changes saved. The campaign goes back to the administrator for '
            . 'verification before volunteers see the new details.');
        $this->redirect('/org/cleanup/' . $event['id']);
    }

    /**
     * DELETE - cancelling a campaign.
     *
     * A soft delete plus `status = cancelled`, the shape CLAUDE.md sets out: the
     * organiser keeps it in their own list and its volunteers keep their history,
     * while everyone else gets a 404. A campaign that has already run is not
     * cancellable - that would overwrite the outcome.
     */
    public function destroy(string $id): void
    {
        $event = $this->ownedOrFail($id);

        if (in_array($event['status'], [EVENT_COMPLETED, EVENT_CANCELLED], true)) {
            Session::flash('error', 'A campaign that is already ' . $event['status'] . ' cannot be cancelled.');
            $this->redirect('/org/cleanup/' . $event['id']);
        }

        $this->events->update((int) $event['id'], ['status' => EVENT_CANCELLED]);
        $this->events->delete((int) $event['id']);

        Session::flash('success', 'Campaign cancelled. It stays in your list and is no longer public.');
        $this->redirect('/org/cleanup');
    }

    // ---------------------------------------------------------------
    // The two-page form
    // ---------------------------------------------------------------

    /**
     * Create and edit render the same two-page form; `$event` is null when
     * creating.
     *
     * Opening the form with no `?step` in the URL - from the sidebar, or from
     * the Create button - starts clean. `?step=1` and `?step=2` keep whatever
     * has been typed so far, so Next and Back both round-trip.
     */
    private function form(?array $event): void
    {
        $stepped = Request::get('step') !== null;
        $step    = (int) Request::get('step', 1) === 2 ? 2 : 1;

        if (!$stepped) {
            $this->clearDraft($event);
        }

        $base = $event ? '/org/cleanup/' . $event['id'] . '/edit' : '/org/cleanup/create';

        $this->view('cleanup.form', [
            'title'     => $event ? 'Edit Cleanup/ Volunteering Campaign'
                                  : 'Create New Cleanup/ Volunteering Campaign',
            'event'     => $event,
            // Values typed on page 1 and carried into page 2, so the hidden
            // inputs there post the whole campaign in one go.
            'draft'     => $this->draft($event),
            'step'      => $step,
            'stepUrl'   => $base,
            'action'    => $event ? '/org/cleanup/' . $event['id'] : '/org/cleanup',
            'method'    => $event ? 'PUT' : 'POST',
            'cities'    => (new City())->listWithDistrict(),
            'step1'     => self::STEP1,
        ]);
    }

    /**
     * Page 1's Next button. Validates only what page 1 asks for, remembers it,
     * and sends the organiser to page 2. Never returns - it either bounces back
     * with errors or redirects.
     */
    private function carryToStepTwo(?array $event): never
    {
        $rules = array_intersect_key($this->rules(), array_flip(self::STEP1));
        $this->validate($rules);

        Session::set($this->draftKey($event), Request::all());
        $this->redirect(($event ? '/org/cleanup/' . $event['id'] . '/edit' : '/org/cleanup/create') . '?step=2');
    }

    private function draftKey(?array $event): string
    {
        return 'cleanup_form_' . ($event ? (int) $event['id'] : 'new');
    }

    private function draft(?array $event): array
    {
        $draft = Session::get($this->draftKey($event), []);
        return is_array($draft) ? $draft : [];
    }

    private function clearDraft(?array $event): void
    {
        Session::forget($this->draftKey($event));
    }

    // ---------------------------------------------------------------

    /**
     * The ten fields that map to `cleanup_events` columns.
     *
     * `category` and `city_id` are validated against the real ENUM and the real
     * id list, so a tampered select is a field error rather than a foreign-key
     * 500 - the lesson the reuse module recorded.
     *
     * The Figma draws a great deal more than nine fields. Everything else is
     * either filled in after the event runs (`waste_records`, `event_photos`,
     * `cleanup_impacts`) or has no column at all, and is not drawn.
     *
     * `disaster_report_id` was dropped from the form on 2026-09-05 by group
     * decision. The column stays - a report can still be linked by whatever
     * writes one later - but an organiser is no longer asked to pick one.
     */
    private function rules(): array
    {
        return [
            'title'              => 'required|max:160',
            'description'        => 'required|max:2000',
            'category'           => 'required|in:' . implode(',', array_keys(CleanupEvent::CATEGORIES)),
            'event_date'         => 'required|date',
            'start_time'         => 'required',
            'end_time'           => 'required',
            'meeting_point'      => 'required|max:255',
            'city_id'            => 'required|in:' . implode(',', $this->cityIds()),
            'volunteer_limit'    => 'required|integer|min_value:1',
            'safety_guidelines'  => 'required|max:2000',
        ];
    }

    /** The ticked equipment boxes, joined into the `equipment_provided` TEXT column. */
    private function postedEquipment(): ?string
    {
        $ticked = Request::post('equipment');
        if (!is_array($ticked) || !$ticked) {
            return null;
        }

        $clean = array_filter(array_map(fn($item) => trim((string) $item), $ticked));
        return $clean ? implode(', ', $clean) : null;
    }

    private function cityIds(): array
    {
        return array_map('strval', array_column((new City())->listAll(), 'id'));
    }

    // ---------------------------------------------------------------

    /**
     * The signed-in account's organisation row. `cleanup_events.organization_id`
     * points at `organizations(id)`, not at the account, so every write needs
     * this - and an organisation account with no profile row cannot own one.
     *
     * A Grama Niladhari officer hosts cleanup campaigns as of 2026-08-27, and a
     * GS account registers into `grama_niladharis`, not into `organizations`, so
     * it has no row to point at. The frozen schema cannot be repointed without
     * breaking every cleanup query that joins `organizations`, so the officer is
     * given a profile row of their own the first time they post - named after
     * their GN division, typed `community_group`, and verified because the
     * account behind it already is.
     *
     * That row is a campaign owner and not an institution, which is why
     * Organization::browseForAdmin() and the public directory both filter on
     * `users.role = 'organization'`. Hiding it there is not cosmetic: without
     * the filter an administrator would be asked to approve the same officer
     * twice, once on /admin/gs and once on /admin/organizations.
     */
    private function organisationOrFail(): array
    {
        $organizations = new Organization();
        $userId        = (int) auth()['id'];

        $org = $organizations->findBy('user_id', $userId);
        if ($org) {
            return $org;
        }

        if (role_is(ROLE_GN)) {
            return $organizations->find($this->provisionGsOrganisation($userId));
        }

        $this->abort(403, 'Your account has no organisation profile, so it cannot post campaigns.');
    }

    /**
     * Create the campaign-owner row behind a GS officer, once.
     *
     * `reg_number` is NOT NULL and an officer has no registration number, so it
     * carries the division code the administrator fills in at verification, or
     * the account id when that is still blank. `verified_at` is set here because
     * the officer's own account was verified on /admin/gs before they could
     * reach this screen at all - leaving it null would mark the owner of an
     * approved campaign as unverified.
     */
    private function provisionGsOrganisation(int $userId): int
    {
        $gn   = (new GramaNiladhari())->findBy('user_id', $userId) ?: [];
        $user = auth();

        $division = trim((string) ($gn['gn_division'] ?? ''));
        $name     = $division !== ''
            ? $division . ' GN Division Office'
            : ($user['full_name'] ?? 'Grama Niladhari') . ' - GN Division Office';

        return (new Organization())->create([
            'user_id'     => $userId,
            'org_name'    => $name,
            'reg_number'  => (string) ($gn['division_code'] ?? '') ?: 'GN-' . $userId,
            'org_type'    => 'community_group',
            'city_id'     => $user['city_id'] ?? null,
            'description' => 'Cleanup and volunteering campaigns hosted by this Grama Niladhari division.',
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Load a campaign for a write or for the organiser's own screens.
     *
     * Loads cancelled rows too - a soft delete would otherwise hide a campaign
     * from the organisation that cancelled it. Never trusts the id in the URL.
     */
    private function ownedOrFail(string $id): array
    {
        $event = $this->events->findDetail((int) $id, true);
        if (!$event) {
            $this->abort(404, 'Cleanup campaign not found.');
        }

        if (!role_is(ROLE_ADMIN)) {
            $org = $this->organisationOrFail();
            if ((int) $event['organization_id'] !== (int) $org['id']) {
                $this->abort(403, 'You can only manage your own campaigns.');
            }
        }

        return $event;
    }

    /**
     * Load a campaign for a volunteer or a guest. A campaign an administrator
     * has not approved does not exist as far as the public is concerned - a 403
     * would confirm that it is there.
     */
    private function publicOrFail(string $id): array
    {
        $event = $this->events->findDetail((int) $id);
        if (!$event || !in_array($event['status'], CleanupEvent::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Cleanup campaign not found.');
        }
        return $event;
    }

    /**
     * The "Your Impact" strip. Both figures are counted from this member's own
     * rows, so they read 0 until they sign up for something - an invented number
     * here would be a claim about what someone has done.
     *
     * The two count different things on purpose. The first is REGISTRATIONS: a
     * member has put their name down, which is all a sign-up says. Volunteer
     * hours are not earned by signing up - they are earned by turning up, and
     * the only record of that is the organiser marking attendance on
     * /org/cleanup/{id}/volunteers. So the second figure counts only
     * registrations that have an `attendance` row, and a member who registers
     * for ten campaigns and attends none still reads 0 hours.
     *
     * Nothing writes `checked_out_at` yet, so where it is NULL the campaign's
     * own scheduled start-to-end is the session length. The CASE is there for
     * the day a Check Out button exists: an actual span beats a scheduled one.
     */
    private function volunteerImpact(): array
    {
        if (!is_logged_in()) {
            return [];
        }

        $me = (int) auth()['id'];

        $row = Database::one(
            "SELECT
                (SELECT COUNT(*)
                   FROM event_registrations r
                  WHERE r.user_id = ? AND r.status <> 'cancelled'
                    AND r.deleted_at IS NULL) AS registered,
                (SELECT COALESCE(SUM(
                          CASE WHEN a.checked_out_at IS NOT NULL
                               THEN TIMESTAMPDIFF(SECOND, a.checked_in_at, a.checked_out_at) / 3600
                               ELSE TIME_TO_SEC(TIMEDIFF(e.end_time, e.start_time)) / 3600
                          END), 0)
                   FROM event_registrations r
                   JOIN attendance a     ON a.registration_id = r.id
                   JOIN cleanup_events e ON e.id = r.event_id
                  WHERE r.user_id = ? AND r.deleted_at IS NULL
                    AND a.checked_in_at IS NOT NULL) AS hours",
            [$me, $me]
        ) ?: [];

        return [
            [(string) (int) ($row['registered'] ?? 0), 'Campaigns Registered', 'green'],
            [(string) (int) round((float) ($row['hours'] ?? 0)), 'Volunteer Hours', 'blue'],
        ];
    }

    private function browseFilters(): array
    {
        return [
            'city'     => (string) Request::get('city', ''),
            'category' => (string) Request::get('category', ''),
            'status'   => (string) Request::get('status', ''),
        ];
    }

    /** Guests get the public shell; signed-in users get their sidebar. */
    private function publicLayout(): string
    {
        return is_logged_in() ? 'app' : 'guest';
    }
}
