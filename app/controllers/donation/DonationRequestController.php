<?php
namespace Controllers\Donation;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\City;
use Models\District;
use Models\Donation\DonationRequest;
use Models\Donation\FundDonation;
use Models\Donation\ItemReservation;
use Models\Donation\RequestedItem;
use Services\DonationService;

/**
 * MEMBER 2 - Item & Fund Donation. Full CRUD on `donation_requests`.
 *
 * Weeks 4-5: the request is real. A Grama Niladhari or an organisation creates,
 * reads, updates and cancels its own rows - together with the `requested_items`
 * lines an item request owns - and donors read the approved ones.
 *
 * The surrounding flow is real too: reserving an item writes
 * `item_reservations` and moves `requested_items.qty_reserved`, paying into a
 * fund writes `fund_donations` and `payments` and moves `collected_amount`, and
 * the donor wall, the receipt and the My Donations tab all read those rows back.
 * See ItemReservationController and PaymentController.
 *
 * /donations and /donations/{id} are open to guests. Everything under
 * /my/donations is the requester's own workspace and every write checks
 * ownership before it runs.
 */
class DonationRequestController extends Controller
{
    /** Browse tabs. `mine` needs an account; the router does not enforce that, so index() does. */
    private const TABS = ['all', 'items', 'funds', 'mine'];

    /** The requester's own tabs - no `mine`, because every row here is already theirs. */
    private const OWN_TABS = ['all', 'items', 'funds'];

    private DonationRequest $requests;
    private RequestedItem   $items;
    private DonationService $service;
    private City            $cities;
    private FundDonation    $funds;
    private ItemReservation $reservations;

    public function __construct()
    {
        $this->requests     = new DonationRequest();
        $this->items        = new RequestedItem();
        $this->service      = new DonationService();
        $this->cities       = new City();
        $this->funds        = new FundDonation();
        $this->reservations = new ItemReservation();
    }

    // ---------------------------------------------------------------
    // Community - /donations
    // ---------------------------------------------------------------

    /**
     * Browse. The tab is a query string so every tab is a shareable URL and the
     * back button behaves.
     *
     * "My Donations" is the donor's own giving history - `item_reservations` for
     * things promised and `fund_donations` for money given, merged into one list
     * newest first, because a donor thinks of them as one thing they did.
     */
    public function index(): void
    {
        $tab = (string) Request::get('tab', 'all');
        if (!in_array($tab, self::TABS, true) || ($tab === 'mine' && !is_logged_in())) {
            $tab = 'all';
        }

        $filters = array_filter([
            'city_id'      => (string) Request::get('city', ''),
            'status'       => (string) Request::get('status', ''),
            'request_type' => in_array($tab, ['items', 'funds'], true) ? $tab : '',
        ], fn($v) => $v !== '');

        if (!isset(DonationRequest::STATUSES[$filters['status'] ?? ''])) {
            unset($filters['status']);
        }

        $page  = max(1, (int) Request::get('page', 1));
        $total = $this->requests->browseCount($filters);
        $rows  = $tab === 'mine'
            ? []
            : $this->requests->browse($filters, PER_PAGE, ($page - 1) * PER_PAGE);

        $this->view('donation.index', [
            'title'    => 'Donation Requests',
            'tab'      => $tab,
            'requests' => $rows,
            'itemsBy'  => $this->items->forRequests(array_column($rows, 'id')),
            'myGiving' => $tab === 'mine' ? $this->myGiving() : [],
            'total'    => $total,
            'impact'   => $this->impact(),
            'cities'   => $this->cities->listAll(),
            'statuses' => DonationRequest::STATUSES,
            'tones'    => DonationRequest::TONES,
            'filters'  => [
                'city'   => (string) Request::get('city', ''),
                'status' => (string) Request::get('status', ''),
            ],
        ], $this->publicLayout());
    }

    /**
     * Detail. The two request types have genuinely different designs - one
     * lists items to reserve, the other takes money - so they are two views
     * rather than one template full of branches.
     */
    public function show(string $id): void
    {
        $request = $this->requests->findDetail((int) $id);
        if (!$request || !in_array($request['status'], DonationRequest::PUBLIC_STATUSES, true)) {
            // A request still awaiting approval is not public, but its requester
            // reads it on their own screen at /my/donations/{id}.
            $this->abort(404, 'Donation request not found.');
        }

        if ($request['request_type'] === 'items') {
            $this->view('donation.show-items', [
                'title'   => $request['title'],
                'request' => $request,
                'items'   => $this->items->forRequest((int) $request['id']),
                // What this donor has already promised here, so they can take
                // any of it back. Empty for a guest and for anyone who has not
                // reserved anything.
                'mine'    => is_logged_in()
                    ? $this->reservations->forDonorAndRequest(
                        (int) auth()['id'], (int) $request['id'])
                    : [],
            ] + $this->labels(), $this->publicLayout());
            return;
        }

        $this->view('donation.show-funds', [
            'title'   => $request['title'],
            'request' => $request,
            'donors'  => $this->funds->wallFor((int) $request['id']),
        ] + $this->labels(), $this->publicLayout());
    }

    // ---------------------------------------------------------------
    // Requester - /my/donations  (organisation and Grama Niladhari)
    // ---------------------------------------------------------------

    /** READ (list) - the requester's own donation requests. */
    public function manage(): void
    {
        $requesterId = (int) auth()['id'];

        $tab = (string) Request::get('tab', 'all');
        if (!in_array($tab, self::OWN_TABS, true)) {
            $tab = 'all';
        }

        $status = (string) Request::get('status', '');
        if (!isset(DonationRequest::STATUSES[$status])) {
            $status = '';
        }

        $rows = $this->requests->forRequester($requesterId, [
            'request_type' => in_array($tab, ['items', 'funds'], true) ? $tab : '',
            'status'       => $status,
        ]);

        $this->view('donation.manage', [
            'title'      => 'My Donation Requests',
            'tab'        => $tab,
            'requests'   => $rows,
            'itemsBy'    => $this->items->forRequests(array_column($rows, 'id')),
            'typeCounts' => $this->requests->typeCountsFor($requesterId),
            'counts'     => $this->requests->statusCountsFor($requesterId),
            'status'     => $status,
        ] + $this->labels());
    }

    /** READ (detail) - the requester's view, from which every write starts. */
    public function detail(string $id): void
    {
        $request = $this->ownedOrFail((int) $id);

        if ($request['request_type'] === 'items') {
            $this->view('donation.detail-items', [
                'title'        => $request['title'],
                'request'      => $request,
                'items'        => $this->items->forRequest((int) $request['id']),
                // Who promised what, so the requester can confirm each delivery.
                'reservations' => $this->reservations->forRequest((int) $request['id']),
            ] + $this->labels());
            return;
        }

        $this->view('donation.detail-funds', [
            'title'   => $request['title'],
            'request' => $request,
            'donors'  => $this->funds->wallFor((int) $request['id']),
        ] + $this->labels());
    }

    // ---------- CREATE ----------

    /** CREATE form - relief items unless ?type=funds. */
    public function create(): void
    {
        $this->form(null);
    }

    public function store(): void
    {
        [$data, $lines] = $this->validated();

        $id = $this->service->create(
            (int) auth()['id'],
            self::requesterType(),
            $data,
            $lines
        );

        Session::flash('success', 'Request submitted. An administrator reviews it before donors see it.');
        $this->redirect('/my/donations/' . $id);
    }

    // ---------- UPDATE ----------

    public function edit(string $id): void
    {
        $this->form($this->ownedOrFail((int) $id));
    }

    public function update(string $id): void
    {
        $request = $this->ownedOrFail((int) $id);
        [$data, $lines] = $this->validated();

        $this->service->update($request, $data, $lines);

        Session::flash('success', 'Request updated.');
        $this->redirect('/my/donations/' . $request['id']);
    }

    // ---------- DELETE ----------

    public function destroy(string $id): void
    {
        $request = $this->ownedOrFail((int) $id);

        if ($request['deleted_at'] !== null) {
            Session::flash('error', 'That request is already cancelled.');
            $this->redirect('/my/donations');
        }

        $this->service->cancel((int) $request['id']);

        Session::flash('success', 'Request cancelled.');
        $this->redirect('/my/donations');
    }

    // ---------------------------------------------------------------

    /**
     * Create and edit render the same form; `$request` is null when creating.
     *
     * `?type=` still chooses which panel opens, but it is no longer how the
     * requester switches - both panels are in the page and a radio shows one.
     * See app/views/donation/form.php.
     */
    private function form(?array $request): void
    {
        $type = (string) Request::get('type', $request['request_type'] ?? 'items');
        if (!in_array($type, ['items', 'funds'], true)) {
            $type = 'items';
        }

        // The lines an item request already has. The form opens its item table
        // empty otherwise - three blank rows the requester names themselves, and
        // no category list to pick a wrong answer from.
        $rows = $request && $request['request_type'] === 'items'
            ? $this->items->forRequest((int) $request['id'])
            : [];

        $this->view('donation.form', [
            'title'     => $request ? 'Edit Donation Request' : 'Create Donation Request',
            'request'   => $request,
            'type'      => $type,
            'action'    => $request ? '/my/donations/' . $request['id'] : '/my/donations',
            'method'    => $request ? 'PUT' : 'POST',
            'cities'    => $this->cities->listWithDistrict(),
            'districts' => (new District())->listAll(),
            'rows'      => $rows,
        ]);
    }

    /**
     * Validate the request and its item lines together, so a bad item row sends
     * the whole form back with everything the requester typed still in it.
     *
     * Validated against the real `donation_requests` columns. The Figma draws a
     * good deal more: number of affected people, urgency (per request and per
     * item), disaster type, minimum donation, contact number, pickup address and
     * hours, additional instructions, payout bank details, and the "How the
     * Funds Will Be Used" breakdown have **no column anywhere in the frozen
     * schema**. They are collected by the form and dropped rather than faked -
     * the same call the group made on the week 2 registration forms and on M1's
     * and M3's. Adding any of them needs a schema change and a group decision.
     *
     * @return array{0: array, 1: array} validated data, and the item lines
     */
    private function validated(): array
    {
        $cityIds = implode(',', array_column($this->cities->listAll(), 'id'));

        $type = (string) Request::post('request_type', 'items');
        $type = in_array($type, ['items', 'funds'], true) ? $type : 'items';

        $rules = [
            'title'            => 'required|max:160',
            'description'      => 'required|max:500',
            'disaster_details' => 'max:2000',
            'affected_area'    => 'required|max:255',
            'city_id'          => 'required|integer|in:' . $cityIds,
            'starts_on'        => 'required|date',
            'ends_on'          => 'required|date',
            'request_type'     => 'required|in:items,funds',
        ];

        $rules += $type === 'items'
            ? ['pickup_point'  => 'required|max:255']
            : ['target_amount' => 'required|integer|min_value:1000'];

        $data = $this->validate($rules);

        // The Validator has no rule that compares two fields, and a window that
        // closes before it opens would render a negative "days left".
        if ($data['ends_on'] < $data['starts_on']) {
            $this->bounce(['ends_on' => ['The closing date must be on or after the opening date.']], $data);
        }

        $lines = [];
        if ($type === 'items') {
            [$lines, $error] = DonationService::normaliseLines(
                (array) Request::post('item_name', []),
                (array) Request::post('unit', []),
                (array) Request::post('qty_required', [])
            );

            if ($error !== null) {
                $this->bounce(['items' => [$error]], $data);
            }
        }

        return [$data, $lines];
    }

    /**
     * Send the requester back to the form with an error the Validator cannot
     * produce - a bad item table, mostly - without losing what they typed.
     *
     * The whole request is flashed rather than just the validated fields: the
     * item table posts `item_name[]`, `unit[]` and `qty_required[]`, and
     * `validate()` returns only the scalar rules it was given, so flashing that
     * would blank every item row the requester had filled in.
     */
    private function bounce(array $errors, array $data): never
    {
        Session::flashInput($data + Request::all(), $errors);
        Session::flash('error', 'Please correct the highlighted fields.');
        $this->back();
    }

    /**
     * The donor's own giving, items and money in one list, newest first.
     *
     * The two tables have nothing in common but a date and a request, so each is
     * mapped to the same shape before they are merged - the view then draws one
     * card and not two.
     */
    private function myGiving(): array
    {
        if (!is_logged_in()) {
            return [];
        }

        $donorId = (int) auth()['id'];
        $rows    = [];

        foreach ($this->reservations->forDonor($donorId) as $r) {
            $rows[] = [
                'kind'       => 'items',
                // The reservation's own id and state, so the card can offer Undo.
                'id'         => (int) $r['id'],
                'raw_status' => $r['status'],
                'at'         => $r['created_at'],
                'request_id' => (int) $r['request_id'],
                'title'      => $r['title'],
                'requester'  => $r['requester'],
                'what'       => $r['quantity'] . ' ' . $r['unit'] . ' of ' . $r['item_name'],
                'status'     => ItemReservation::STATUSES[$r['status']] ?? $r['status'],
                'tone'       => ItemReservation::TONES[$r['status']] ?? 'neutral',
            ];
        }

        foreach ($this->funds->forDonor($donorId) as $f) {
            $rows[] = [
                'kind'       => 'funds',
                'id'         => (int) $f['id'],
                'raw_status' => 'paid',
                'at'         => $f['created_at'],
                'request_id' => (int) $f['request_id'],
                'title'      => $f['title'],
                'requester'  => $f['requester'],
                'what'       => format_money((float) $f['amount']),
                'status'     => 'Paid',
                'tone'       => 'green',
            ];
        }

        usort($rows, fn($a, $b) => strcmp((string) $b['at'], (string) $a['at']));

        return $rows;
    }

    /**
     * The three tiles above the tabs, counted against the signed-in account.
     *
     * A guest gets none: "0 items donated" answers a question they did not ask.
     * "Requests supported" replaced the Figma's "Lives Impacted" - the number of
     * people a gift reaches is not something this platform can count, and the
     * number of appeals somebody gave to is.
     */
    private function impact(): array
    {
        if (!is_logged_in()) {
            return [];
        }

        $donorId = (int) auth()['id'];

        return [
            // "Pledged", not "Donated": `item_reservations` records what a donor
            // promised, and the requester confirms the delivery separately. The
            // tile read "Items Donated" over a figure that counted goods still
            // sitting in the donor's house.
            [(string) $this->reservations->itemCountFor($donorId), 'Items Pledged', 'green'],
            [format_money($this->funds->totalFor($donorId)),       'Funds Donated', 'blue'],
            [(string) $this->reservations->requestCountFor($donorId), 'Requests Supported', 'amber'],
        ];
    }

    /**
     * The `requester_type` value for the signed-in account.
     *
     * The column is an ENUM of the three roles that may post a request, and an
     * administrator was added to it on 2026-09-13 - see
     * `database/migrations/2026_09_13_admin_can_request_donations.sql`. The role
     * middleware on /my/donations lets nobody else this far.
     */
    private static function requesterType(): string
    {
        return match (true) {
            role_is(ROLE_GN)    => ROLE_GN,
            role_is(ROLE_ADMIN) => ROLE_ADMIN,
            default             => ROLE_ORGANIZATION,
        };
    }

    /** Labels and tones every donation view prints. */
    private function labels(): array
    {
        return [
            'statuses' => DonationRequest::STATUSES,
            'tones'    => DonationRequest::TONES,
            'types'    => DonationRequest::TYPES,
        ];
    }

    // ---------------------------------------------------------------
    // Guards
    // ---------------------------------------------------------------

    /**
     * Load a request the caller is allowed to read and change. Never trust the
     * id in the URL: it is the only thing standing between one requester and
     * another requester's appeals.
     *
     * Cancelled requests are included - the delete is soft, and the requester
     * still opens them from their list.
     */
    private function ownedOrFail(int $id): array
    {
        $request = $this->requests->findDetail($id, true);
        if (!$request) {
            $this->abort(404, 'Donation request not found.');
        }
        if ((int) $request['requester_id'] !== (int) auth()['id'] && !role_is(ROLE_ADMIN)) {
            $this->abort(403, 'You can only manage your own donation requests.');
        }
        return $request;
    }

    /** Guests get the public shell; signed-in users get their sidebar. */
    private function publicLayout(): string
    {
        return is_logged_in() ? 'app' : 'guest';
    }
}
