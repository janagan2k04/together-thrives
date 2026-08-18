<?php
namespace Controllers\Blood;

use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Blood\BloodCampaign;
use Models\City;
use Services\BloodSampleData;

/**
 * MEMBER 3 — Blood Donation.
 *
 * The organiser side (manage/detail/create/store/edit/update/destroy) now
 * reads and writes `blood_campaigns` for real through the BloodCampaign
 * model. The community side (index/show/join) still renders from
 * `BloodSampleData` — browse/screening/critical-request wiring is a
 * separate piece of work, not part of this CRUD entity.
 */
class BloodCampaignController extends Controller
{
    private BloodCampaign $campaigns;

    public function __construct()
    {
        $this->campaigns = new BloodCampaign();
    }

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
        $rows = $this->campaigns->forOrganizer(Auth::id());

        $status = (string) (Request::get('status') ?? '');
        if ($status !== '') {
            $rows = array_values(array_filter($rows, fn ($r) => $r['status'] === $status));
        }

        $this->view('blood.manage', [
            'title' => 'My Blood Campaigns',
            'rows'  => $rows,
        ]);
    }

    /** Organiser's view of one campaign: stats, details, registered donors. */
    public function detail(string $id): void
    {
        $row = $this->campaigns->findForOrganizer((int) $id, Auth::id());
        if (!$row) {
            $this->abort(404, 'Campaign not found.');
        }

        $this->view('blood.detail', [
            'title' => $row['title'],
            'row'   => $row,
        ]);
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function store(): void
    {
        $data = $this->validateCampaign();

        $data['organizer_id']    = Auth::id();
        $data['organizer_type']  = Auth::role(); // 'organization' or 'blood_bank' — matches organizer_type ENUM
        $data['auto_group_chat'] = Request::post('auto_group_chat') ? 1 : 0;
        $data['status']          = 'pending';

        $this->campaigns->create($data);

        Session::flash('success', 'Campaign submitted for admin verification.');
        $this->redirect('/my/blood');
    }

    public function edit(string $id): void
    {
        $row = $this->campaigns->findForOrganizer((int) $id, Auth::id());
        if (!$row) {
            $this->abort(404, 'Campaign not found.');
        }

        $this->form($row);
    }

    public function update(string $id): void
    {
        $row = $this->campaigns->findForOrganizer((int) $id, Auth::id());
        if (!$row) {
            $this->abort(404, 'Campaign not found.');
        }

        $data = $this->validateCampaign();
        $data['auto_group_chat'] = Request::post('auto_group_chat') ? 1 : 0;
        // Editing sends it back for re-approval, same rule the other CRUD
        // modules use (create -> pending -> admin approves -> public).
        $data['status'] = 'pending';

        $this->campaigns->update((int) $id, $data);

        Session::flash('success', 'Campaign updated and resubmitted for verification.');
        $this->redirect('/my/blood');
    }

    public function destroy(string $id): void
    {
        $row = $this->campaigns->findForOrganizer((int) $id, Auth::id());
        if (!$row) {
            $this->abort(404, 'Campaign not found.');
        }

        // Soft delete plus a cancelled status, the same shape every CRUD
        // module in this project uses.
        $this->campaigns->update((int) $id, ['status' => 'cancelled']);
        $this->campaigns->delete((int) $id);

        Session::flash('success', 'Campaign cancelled.');
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
     * Validated against the real `blood_campaigns` columns. `blood_types` is
     * collected on the form but has no column, so it is not in these rules —
     * same treatment as the week 2 registration forms gave it. `end_date` was
     * dropped for the same reason: no column, and the form no longer asks
     * for it.
     */
    private function validateCampaign(): array
    {
        return $this->validate([
            'title'         => 'required|max:160',
            'description'   => 'required|max:2000',
            'venue'         => 'required|max:255',
            'city_id'       => 'required|integer',
            'campaign_date' => 'required|date',
            'start_time'    => 'required',
            'end_time'      => 'required',
            'target_donors' => 'required|integer|min_value:1',
            'contact_phone' => 'required|phone_lk',
        ]);
    }
}