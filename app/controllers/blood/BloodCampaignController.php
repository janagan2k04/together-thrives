<?php
namespace Controllers\Blood;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Blood\BloodCampaign;
use Models\Blood\CriticalRequest;
use Models\Blood\DonationRecord;
use Models\Blood\ScreeningResponse;
use Models\City;
use Models\Notification;

/**
 * MEMBER 3 - Blood Donation. Full CRUD on `blood_campaigns`.
 *
 * Weeks 4-5: the campaign is real. An organiser creates, reads, updates and
 * cancels its own rows, and donors read the approved ones on the public hub.
 *
 * Every tab reads a real table. A donor registers for a camp by completing its
 * screening form, so `screening_responses` is what My Registrations lists and
 * what the organiser's donor table shows; `blood_donation_records` is what My
 * Donations lists, written by the organiser once somebody has actually given
 * blood. Registering and donating are deliberately two different rows.
 */
class BloodCampaignController extends Controller
{
    /** The tabs across the community hub. */
    private const TABS = [
        'all'           => 'All',
        'camps'         => 'Upcoming Camps',
        'critical'      => 'Critical Requests',
        'donations'     => 'My Donations',
        'registrations' => 'My Registrations',
    ];

    private BloodCampaign     $campaigns;
    private CriticalRequest   $critical;
    private City              $cities;
    private ScreeningResponse $screenings;
    private DonationRecord    $donations;

    public function __construct()
    {
        $this->campaigns  = new BloodCampaign();
        $this->critical   = new CriticalRequest();
        $this->cities     = new City();
        $this->screenings = new ScreeningResponse();
        $this->donations  = new DonationRecord();
    }

    // ---------------------------------------------------------------
    // Community - the hub at /blood
    // ---------------------------------------------------------------

    /**
     * One screen with five tabs, matching the Figma: the tab is a query string
     * so every tab is a shareable URL and the back button behaves.
     *
     * The two personal tabs are only queried for a signed-in donor. A guest can
     * read the camps and the critical requests; "My Donations" is not a question
     * that has an answer for them, and the view leaves the tab out.
     */
    public function index(): void
    {
        $tab = (string) (Request::get('tab') ?? 'all');
        if (!isset(self::TABS[$tab])) {
            $tab = 'all';
        }

        // The hub shows a short list; "View All" switches to the camps tab.
        $limit   = $tab === 'camps' ? PER_PAGE : 3;
        $donorId = is_logged_in() ? (int) auth()['id'] : 0;

        $this->view('blood.index', [
            'title'         => 'Blood Donation',
            'tab'           => $tab,
            'tabs'          => self::TABS,
            'impact'        => $this->impact($tab),
            'camps'         => $this->campaigns->browse([], $limit),
            'campTotal'     => $this->campaigns->browseCount(),
            // Real `critical_blood_requests` rows since 2026-08-18, approved
            // ones only - the section header promises exactly that.
            'requests'      => $this->critical->browse($tab === 'critical' ? PER_PAGE : 3),
            'donations'     => $donorId ? $this->donations->forDonor($donorId) : [],
            'registrations' => $donorId ? $this->screenings->campsFor($donorId) : [],
            'responses'     => $donorId ? $this->screenings->requestsFor($donorId) : [],
            'nextDonation'  => $donorId ? $this->donations->eligibleAgainOn($donorId) : null,
        ], $this->publicLayout());
    }

    /**
     * The three headline tiles above the tabs, per tab.
     *
     * Every figure is a count against the signed-in account. A guest gets no
     * tiles at all rather than zeros - "0 donations made" is an answer to a
     * question nobody asked.
     *
     * "Lives supported" is three per unit, the figure the health ministry uses
     * for a whole-blood donation split into its components. It is a stated
     * multiple of something counted, not a number picked to look good.
     */
    private function impact(string $tab): array
    {
        if (!is_logged_in()) {
            return [];
        }

        $donorId = (int) auth()['id'];

        if ($tab === 'donations') {
            $units = $this->donations->unitsFor($donorId);
            return [
                [(string) $this->donations->countFor($donorId), 'Donations Made',  'red'],
                [rtrim(rtrim(number_format($units, 2), '0'), '.'), 'Units Donated', 'green'],
                [(string) (int) round($units * 3), 'Lives Supported', 'blue'],
            ];
        }

        if ($tab === 'registrations') {
            $camps = $this->screenings->campsFor($donorId);

            // A cancelled camp is not something the donor is still going to,
            // so it counts under Camps Joined but not under Upcoming.
            $upcoming = array_filter($camps, fn($c) => $c['campaign_date'] >= date('Y-m-d')
                                                   && $c['campaign_status'] !== EVENT_CANCELLED);
            $pending  = array_filter($camps, fn($c) => $c['result'] === 'deferred');

            return [
                [(string) count($upcoming), 'Upcoming Registrations', 'red'],
                [(string) count($pending),  'Deferred',               'amber'],
                [(string) count($camps),    'Camps Joined',           'green'],
            ];
        }

        $last = $this->donations->lastDonationDate($donorId);

        return [
            // Camps, not donations: a donation can answer a critical request,
            // where there is no camp, and two at one camp are still one camp.
            [(string) $this->donations->campCountFor($donorId), 'Camps Donated At', 'green'],
            [$last ? format_date($last) : 'Not yet',           'Last Donated',     'blue'],
        ];
    }

    /** Camp detail - the screen behind "View Details". */
    public function show(string $id): void
    {
        $camp = $this->campaigns->findDetail((int) $id);
        if (!$camp || !in_array($camp['status'], BloodCampaign::PUBLIC_STATUSES, true)) {
            // A camp still awaiting approval is not public, but its organiser
            // reads it on their own screen at /my/blood/{id}.
            $this->abort(404, 'Blood donation camp not found.');
        }

        $this->view('blood.show', [
            'title'     => $camp['title'],
            'camp'      => $camp,
            'organiser' => $this->campaigns->organiserStats((int) $camp['organizer_id']),
        ], $this->publicLayout());
    }

    /**
     * "Join This Camp" - hands off to the screening form rather than
     * registering directly, which is what the Figma's copy promises.
     */
    public function join(string $id): void
    {
        $camp = $this->campaigns->findDetail((int) $id);
        if (!$camp || !in_array($camp['status'], BloodCampaign::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Blood donation camp not found.');
        }

        $this->redirect('/blood/' . $id . '/screening');
    }

    // ---------------------------------------------------------------
    // Organiser - /my/blood
    // ---------------------------------------------------------------

    /** READ (list) - every campaign this organiser has posted. */
    public function manage(): void
    {
        $organizerId = (int) auth()['id'];

        $status = (string) Request::get('status', '');
        if (!isset(BloodCampaign::STATUSES[$status])) {
            $status = '';
        }

        $this->view('blood.manage', [
            'title'    => 'My Blood Campaigns',
            'rows'     => $this->campaigns->forOrganiser($organizerId, ['status' => $status]),
            'counts'   => $this->campaigns->statusCountsFor($organizerId),
            'statuses' => BloodCampaign::STATUSES,
            'tones'    => BloodCampaign::TONES,
            'status'   => $status,
        ]);
    }

    /** READ (one) - the organiser's view: counters, details, registered donors. */
    public function detail(string $id): void
    {
        $row = $this->ownedOrFail((int) $id);

        $this->view('blood.detail', [
            'title'    => $row['title'],
            'row'      => $row,
            'statuses' => BloodCampaign::STATUSES,
            'tones'    => BloodCampaign::TONES,
            // Everyone who completed this camp's screening form. An eligible
            // donor's phone number comes back with the row and a deferred one's
            // does not - see ScreeningResponse::donorsFor().
            'donors'   => $this->screenings->donorsFor((int) $row['id']),
            'donated'  => $this->donations->forCampaign((int) $row['id']),
        ]);
    }

    // ---------- CREATE ----------

    public function create(): void
    {
        $this->form(null);
    }

    public function store(): void
    {
        $data = $this->validateCampaign();

        $data['organizer_id']   = (int) auth()['id'];
        $data['organizer_type'] = role_is(ROLE_BLOOD_BANK) ? ROLE_BLOOD_BANK : ROLE_ORGANIZATION;
        $data['auto_group_chat'] = Request::post('auto_group_chat') ? 1 : 0;

        // Every new campaign waits for an administrator. The create form and the
        // detail sidebar both promise donors that, so the default is not the
        // organiser's to skip.
        $data['status'] = MOD_PENDING;

        $id = $this->campaigns->create($data);

        Session::flash('success', 'Campaign submitted. An administrator reviews it before donors are notified.');
        $this->redirect('/my/blood/' . $id);
    }

    // ---------- UPDATE ----------

    public function edit(string $id): void
    {
        $this->form($this->ownedOrFail((int) $id));
    }

    public function update(string $id): void
    {
        $row  = $this->ownedOrFail((int) $id);
        $data = $this->validateCampaign();

        $data['auto_group_chat'] = Request::post('auto_group_chat') ? 1 : 0;

        /*
         * Editing an approved campaign sends it back to the queue. Venue, date
         * and contact number are exactly the facts an administrator checked, so
         * letting an edit through silently would publish unreviewed details.
         * organizer_id, organizer_type and the review columns are never taken
         * from the form.
         */
        if ($row['status'] === EVENT_PUBLISHED) {
            $data['status'] = MOD_PENDING;
        }

        $this->campaigns->update((int) $row['id'], $data);

        Session::flash('success', 'Campaign updated.');
        $this->redirect('/my/blood/' . $row['id']);
    }

    // ---------- DELETE ----------

    /**
     * Cancel, not erase. The row is soft deleted and marked cancelled so the
     * organiser's history - and any screening already done against it - survives.
     */
    public function destroy(string $id): void
    {
        $row = $this->ownedOrFail((int) $id);

        if ($row['deleted_at'] !== null) {
            Session::flash('error', 'That campaign is already cancelled.');
            $this->redirect('/my/blood');
        }

        $this->campaigns->update((int) $row['id'], ['status' => EVENT_CANCELLED]);
        $this->campaigns->delete((int) $row['id']);

        Session::flash('success', 'Campaign cancelled.');
        $this->redirect('/my/blood');
    }

    // ---------------------------------------------------------------

    /** Create and edit render the same form; `$row` is null when creating. */
    private function form(?array $row): void
    {
        $this->view('blood.form', [
            'title'  => $row ? 'Edit Blood Campaign' : 'Create New Blood Campaign',
            'row'    => $row,
            'groups' => BloodCampaign::GROUPS,
            'cities' => $this->cities->listWithDistrict(),
            'action' => $row ? '/my/blood/' . $row['id'] : '/my/blood',
            'method' => $row ? 'PUT' : 'POST',
        ]);
    }

    /**
     * Validated against the real `blood_campaigns` columns.
     *
     * `city_id` is a foreign key, so an id that does not exist would surface as
     * a database error; checking it against the real id list turns that into an
     * ordinary field error instead.
     *
     * `campaign_end_date` and `address` were added by migration 2026_08_18. The
     * End Date used to be collected and dropped because `campaign_date` is a
     * single DATE; a camp is routinely run over several days, so it has a column
     * of its own now. `address` sits alongside `venue`, which holds a place name
     * rather than a street a donor can navigate to.
     *
     * One field the Figma draws is still deliberately absent: `blood_types[]`.
     * It has no column in the frozen schema, so the form collects it and drops
     * it rather than faking it - the same call the group made on the week 2
     * registration forms. Adding it needs a schema change and a group decision.
     */
    private function validateCampaign(): array
    {
        $cityIds = implode(',', array_column($this->cities->listAll(), 'id'));

        $data = $this->validate([
            'title'         => 'required|max:160',
            'description'   => 'required|max:2000',
            'venue'         => 'required|max:255',
            'address'       => 'required|max:255',
            'city_id'       => 'required|integer|in:' . $cityIds,
            'campaign_date' => 'required|date',
            'campaign_end_date' => 'required|date',
            'start_time'    => 'required',
            'end_time'      => 'required',
            'target_donors' => 'required|integer|min_value:1',
            'contact_phone' => 'required|phone_lk',
        ]);

        // The Validator has no rule that compares two fields, so the two ranges
        // are checked here. A camp that ends before it starts would render a
        // nonsense Period on every screen that shows one.
        //
        // A single-day camp is normal, so the end DATE may equal the start;
        // the end TIME may not, or the camp lasts no time at all.
        if ($data['campaign_end_date'] < $data['campaign_date']) {
            Session::flashInput($data, ['campaign_end_date' => ['The end date cannot be before the start date.']]);
            Session::flash('error', 'Please correct the highlighted fields.');
            $this->back();
        }

        if ($data['end_time'] <= $data['start_time']) {
            Session::flashInput($data, ['end_time' => ['The closing time must be after the opening time.']]);
            Session::flash('error', 'Please correct the highlighted fields.');
            $this->back();
        }

        return $data;
    }

    /**
     * Record that a registered donor actually gave blood at this camp.
     *
     * The organiser's own screen, and the only place `blood_donation_records` is
     * written. Two guards beyond ownership: the donor must have registered - the
     * screening response is the registration - and must have cleared it. An
     * organiser cannot record a donation for somebody the medical form deferred.
     *
     * `donated_at` is the campaign date rather than today, because that is when
     * the donation happened; an organiser catching up on paperwork a week later
     * should not move it.
     */
    public function storeDonation(string $id): void
    {
        $camp    = $this->ownedOrFail((int) $id);
        $donorId = (int) Request::post('donor_id');

        $screening = $this->screenings->latestForCampaign($donorId, (int) $camp['id']);
        if (!$screening) {
            $this->abort(404, 'That donor is not registered for this camp.');
        }
        if ($screening['result'] !== 'eligible') {
            Session::flash('error', 'That donor did not clear the medical screening.');
            $this->redirect('/my/blood/' . $camp['id']);
        }

        if ($this->donations->hasDonatedAt($donorId, (int) $camp['id'])) {
            Session::flash('info', 'That donation is already recorded.');
            $this->redirect('/my/blood/' . $camp['id']);
        }

        $this->donations->create([
            'donor_id'    => $donorId,
            'campaign_id' => (int) $camp['id'],
            'donated_at'  => $camp['campaign_date'],
            'units'       => 1,
        ]);

        (new Notification())->create([
            'user_id'  => $donorId,
            'title'    => 'Thank you for donating',
            'body'     => 'Your donation at ' . $camp['title'] . ' has been recorded. '
                          . 'Your certificate is ready under My Donations.',
            'category' => 'blood',
            'link'     => '/blood?tab=donations',
        ]);

        Session::flash('success', 'Donation recorded.');
        $this->redirect('/my/blood/' . $camp['id']);
    }

    // ---------------------------------------------------------------
    // Guards
    // ---------------------------------------------------------------

    /**
     * Load a campaign the caller is allowed to read and change. Never trust the
     * id in the URL: it is the only thing standing between one organiser and
     * another organiser's campaigns.
     *
     * Cancelled campaigns are included - the delete is soft, and the organiser
     * still opens them from their list.
     */
    private function ownedOrFail(int $id): array
    {
        $row = $this->campaigns->findDetail($id, true);
        if (!$row) {
            $this->abort(404, 'Campaign not found.');
        }
        if ((int) $row['organizer_id'] !== (int) auth()['id'] && !role_is(ROLE_ADMIN)) {
            $this->abort(403, 'You can only manage your own campaigns.');
        }
        return $row;
    }

    /**
     * Guests get the public shell; signed-in users get their sidebar.
     *
     * `layouts/app` builds its sidebar and account chip from Auth::user(), so
     * rendering it signed-out printed a sidebar for no role and raised a PHP
     * warning in `partials/topbar.php`. Same fix the other three modules make.
     */
    private function publicLayout(): string
    {
        return is_logged_in() ? 'app' : 'guest';
    }
}
