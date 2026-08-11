<?php
namespace Controllers\Blood;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\City;
use Services\BloodSampleData;

/**
 * MEMBER 3 — Blood Donation. Week 3: every screen renders from
 * `Services\BloodSampleData`; nothing is written.
 *
 * `blood_campaigns` is the frozen table this module's weeks 4–5 CRUD will use.
 * The create and edit forms below already validate against its real columns and
 * ENUMs, so wiring the model in later is a matter of replacing the flash with
 * `$this->campaigns->create($data)`.
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

    /** `blood_campaigns.organizer_type`. */
    private const ORGANIZER_TYPES = ['organization' => 'Organization', 'blood_bank' => 'Blood Bank'];

    // ---------------------------------------------------------------
    // Community — the hub at /blood
    // ---------------------------------------------------------------

    /**
     * One screen with five tabs, matching the Figma: the tab is a query string
     * so every tab is a shareable URL and the back button behaves.
     */
    public function index(): void
    {
        $tab = (string) (Request::get('tab') ?? 'all');
        if (!isset(self::TABS[$tab])) {
            $tab = 'all';
        }

        $this->view('blood.index', [
            'title'         => 'Blood Donation',
            'tab'           => $tab,
            'tabs'          => self::TABS,
            'impact'        => BloodSampleData::impact($tab),
            'camps'         => BloodSampleData::camps(),
            'requests'      => BloodSampleData::criticalRequests(),
            'donations'     => BloodSampleData::donations(),
            'registrations' => BloodSampleData::registrations(),
            'responses'     => BloodSampleData::responses(),
        ]);
    }

    /** Camp detail — the screen behind "View Details". */
    public function show(string $id): void
    {
        $camp = BloodSampleData::camp($id);
        if (!$camp) {
            $this->abort(404, 'Blood donation camp not found.');
        }

        $this->view('blood.show', ['title' => $camp['title'], 'camp' => $camp]);
    }

    /**
     * "Join This Camp" — hands off to the screening form rather than
     * registering directly, which is what the Figma's copy promises.
     */
    public function join(string $id): void
    {
        if (!BloodSampleData::camp($id)) {
            $this->abort(404, 'Blood donation camp not found.');
        }

        $this->redirect('/blood/' . $id . '/screening');
    }

    // ---------------------------------------------------------------
    // Organiser — /my/blood
    // ---------------------------------------------------------------

    public function manage(): void
    {
        $this->view('blood.manage', [
            'title' => 'My Blood Campaigns',
            'rows'  => BloodSampleData::organiserRows(),
        ]);
    }

    /** Organiser's view of one campaign: stats, details, registered donors. */
    public function detail(string $id): void
    {
        $row = BloodSampleData::organiserRow($id);
        if (!$row) {
            $this->abort(404, 'Campaign not found.');
        }

        $this->view('blood.detail', [
            'title'  => $row['title'],
            'row'    => $row,
            'donors' => BloodSampleData::registeredDonors(),
        ]);
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function store(): void
    {
        $this->validateCampaign();

        Session::flash('info', 'Campaign validated. Saving arrives with the blood backend in weeks 4–5 — nothing was stored.');
        $this->redirect('/my/blood');
    }

    public function edit(string $id): void
    {
        $row = BloodSampleData::organiserRow($id);
        if (!$row) {
            $this->abort(404, 'Campaign not found.');
        }

        $this->form($row);
    }

    public function update(string $id): void
    {
        if (!BloodSampleData::organiserRow($id)) {
            $this->abort(404, 'Campaign not found.');
        }

        $this->validateCampaign();

        Session::flash('info', 'Changes validated. Saving arrives with the blood backend in weeks 4–5 — nothing was changed.');
        $this->redirect('/my/blood');
    }

    public function destroy(string $id): void
    {
        if (!BloodSampleData::organiserRow($id)) {
            $this->abort(404, 'Campaign not found.');
        }

        Session::flash('info', 'Cancelling a campaign arrives with the blood backend in weeks 4–5.');
        $this->redirect('/my/blood');
    }

    // ---------------------------------------------------------------

    /** Create and edit render the same form; `$row` is null when creating. */
    private function form(?array $row): void
    {
        $this->view('blood.form', [
            'title'   => $row ? 'Edit Blood Campaign' : 'Create New Blood Campaign',
            'row'     => $row,
            'groups'  => BloodSampleData::GROUPS,
            'cities'  => (new City())->listWithDistrict(),
            'types'   => self::ORGANIZER_TYPES,
            'action'  => $row ? '/my/blood/' . $row['id'] : '/my/blood',
            'method'  => $row ? 'PUT' : 'POST',
        ]);
    }

    /**
     * Validated against the real `blood_campaigns` columns, so weeks 4–5 keeps
     * this method as-is. `blood_types` is the one field with no column — the
     * Figma asks which groups are needed and the table has nowhere to put it,
     * so it is collected and dropped, the same call the group made on the
     * week 2 registration forms.
     */
    private function validateCampaign(): void
    {
        $this->validate([
            'title'         => 'required|max:160',
            'description'   => 'required|max:2000',
            'venue'         => 'required|max:255',
            'city_id'       => 'required|integer',
            'campaign_date' => 'required|date',
            'end_date'      => 'required|date',
            'start_time'    => 'required',
            'end_time'      => 'required',
            'target_donors' => 'required|integer|min_value:1',
            'contact_phone' => 'required|phone_lk',
        ]);
    }
}
