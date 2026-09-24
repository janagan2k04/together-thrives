<?php
namespace Controllers;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\City;
use Models\DisasterReport;
use Models\District;

/**
 * Disaster reporting - the Grama Niladhari's screens, and the administrator's.
 *
 * Both roles file a report here as of 2026-09-13. The officer's list shows what
 * their own division filed; the administrator's shows every report with the
 * officer who filed it.
 *
 * `disaster_reports` is in the frozen schema and a verified report is what
 * unlocks a donation request or a cleanup campaign. Until 2026-08-27 all three
 * screens drew Services\SampleData and the create form ended with "Report
 * validated ... nothing was saved", which left the GN dashboard counting a
 * table nobody wrote. Reports are real rows now: filed pending, listed back to
 * the officer who filed them, and read on the detail screen.
 *
 * `city_id` is the district - `cities` has held one row per district since the
 * same date - so the district the form asks for is translated by
 * City::forDistrict() before it is stored.
 */
class GnDisasterController extends Controller
{
    /** Values allowed by the disaster_type ENUM, labelled as the Figma labels them. */
    public const TYPES = [
        'flood'     => 'Flood',
        'landslide' => 'Landslide',
        'cyclone'   => 'Cyclone',
        'fire'      => 'Urban Fire',
        'accident'  => 'Accident',
        'other'     => 'Other',
    ];

    public const SEVERITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'];

    private DisasterReport $reports;

    public function __construct()
    {
        $this->reports = new DisasterReport();
    }

    public function index(): void
    {
        $filters = array_filter([
            'status'   => (string) Request::get('status', ''),
            'severity' => (string) Request::get('severity', ''),
        ], fn($v) => $v !== '');

        // An administrator reads every officer's report; an officer reads their
        // own. Same screen, one extra column.
        $mine = !role_is(ROLE_ADMIN);

        $this->view('gn.disasters.index', [
            'title'      => 'Disaster Reports',
            'mine'       => $mine,
            'reports'    => $mine
                ? $this->reports->forReporter((int) auth()['id'], $filters)
                : $this->reports->browseAll($filters),
            'types'      => self::TYPES,
            'severities' => self::SEVERITIES,
            'statuses'   => DisasterReport::STATUSES,
            'filters'    => $filters,
        ]);
    }

    public function create(): void
    {
        $this->view('gn.disasters.create', [
            'title'      => 'Report a Disaster',
            'types'      => self::TYPES,
            'severities' => self::SEVERITIES,
            'districts'  => (new District())->listAll(),
        ]);
    }

    public function store(): void
    {
        $districtIds = implode(',', array_column((new District())->listAll(), 'id'));

        $data = $this->validate([
            'disaster_type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'title'         => 'required|max:160',
            'occurred_at'   => 'required',
            'description'   => 'required|max:2000',
            'district_id'   => 'required|integer|in:' . $districtIds,
            'gn_division'   => 'required|max:120',
            'severity'      => 'required|in:' . implode(',', array_keys(self::SEVERITIES)),
            'contact_phone' => 'required|phone_lk',
        ]);

        // The district is validated against the real id list above, so this
        // cannot come back null - but a missing city row would be a foreign key
        // error rather than a field error, so it is checked all the same.
        $cityId = (new City())->forDistrict((int) $data['district_id']);
        if (!$cityId) {
            Session::flashInput($data, ['district_id' => ['That district is not available.']]);
            $this->back();
        }

        // `location` is the one free-text place the table stores. The form
        // collects a GN division and an optional landmark; both belong in it.
        $landmark = trim((string) Request::post('landmark', ''));
        $location = $landmark !== ''
            ? $data['gn_division'] . ' - ' . $landmark
            : $data['gn_division'];

        $id = $this->reports->create([
            'reporter_id'   => (int) auth()['id'],
            'title'         => $data['title'],
            'disaster_type' => $data['disaster_type'],
            'description'   => $data['description'],
            'location'      => $location,
            'city_id'       => $cityId,
            'severity'      => $data['severity'],
            'status'        => MOD_PENDING,
        ]);

        Session::flash('success', 'Disaster report filed. An administrator verifies it before it is acted on.');
        $this->redirect('/gn/disasters/' . $id);
    }

    public function show(string $id): void
    {
        $report = $this->reports->findDetail((int) $id);
        if (!$report) {
            $this->abort(404, 'Disaster report not found.');
        }

        // A report is the filing officer's own record. Never trust the URL id.
        if ((int) $report['reporter_id'] !== (int) auth()['id'] && !role_is(ROLE_ADMIN)) {
            $this->abort(403, 'You can only open your own reports.');
        }

        $this->view('gn.disasters.show', [
            'title'      => $report['title'],
            'report'     => $report,
            'types'      => self::TYPES,
            'severities' => self::SEVERITIES,
            'statuses'   => DisasterReport::STATUSES,
        ]);
    }
}
