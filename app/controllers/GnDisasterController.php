<?php
namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\District;
use Services\SampleData;

/**
 * Disaster reporting, the Grama Niladhari's own screens.
 *
 * `sidebar-gn.php` has linked at /gn/disasters since week 1 and the link 404'd.
 * The `disaster_reports` table is in the frozen schema and a verified report is
 * what unlocks a donation request or a cleanup campaign, so the flow is drawn
 * here in full — list, create form, and the detail page with its verification
 * timeline — with the writes left for whoever takes the module on.
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

    public function index(): void
    {
        $this->view('gn.disasters.index', [
            'title'   => 'Disaster Reports',
            'reports' => SampleData::disasterReports(),
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
        $this->validate([
            'disaster_type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'title'         => 'required|max:160',
            'occurred_at'   => 'required',
            'description'   => 'required|max:2000',
            'district_id'   => 'required|integer',
            'gn_division'   => 'required|max:120',
            'severity'      => 'required|in:' . implode(',', array_keys(self::SEVERITIES)),
            'contact_phone' => 'required|phone_lk',
        ]);

        Session::flash('info', 'Report validated. Submitting arrives with the disaster module — nothing was saved.');
        $this->redirect('/gn/disasters');
    }

    public function show(string $id): void
    {
        foreach (SampleData::disasterReports() as $r) {
            if ($r['id'] === $id) {
                $this->view('gn.disasters.show', ['title' => $r['title'], 'report' => $r]);
                return;
            }
        }
        $this->abort(404, 'Disaster report not found.');
    }
}
