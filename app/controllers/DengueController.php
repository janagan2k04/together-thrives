<?php
namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\District;
use Services\SampleData;

/**
 * Dengue Risk Alerts, as seen by community members, organizations and Grama
 * Niladhari officers. The administrator sees the same list through
 * `Admin\DengueAlertController`, which adds the moderation controls.
 *
 * `dengue_risk_districts` exists in the schema but nothing writes to it yet,
 * so the alerts come from the sample set for the interim.
 */
class DengueController extends Controller
{
    public function index(): void
    {
        $this->view('dengue.index', [
            'title'      => 'Dengue Risk Map',
            'alerts'     => SampleData::dengueAlerts(),
            'districts'  => (new District())->listAll(),
            'reported'   => SampleData::dengueDistricts(),
            'prevention' => SampleData::denguePrevention(),
            'stats'      => [
                ['18',  'Active alerts',   'red'],
                ['6',   'Alerts near you', 'ink'],
                ['3',   'Alerts you raised', 'green'],
                ['24',  'Districts covered', 'ink'],
                ['142', 'Total this month',  'ink'],
            ],
            'isAdmin' => false,
        ]);
    }

    public function store(): void
    {
        $this->validate([
            'area'        => 'required|max:160',
            'district_id' => 'required|integer',
            'details'     => 'required|max:1000',
        ]);

        Session::flash('success', 'Alert noted. Publishing dengue alerts arrives with the dengue backend.');
        $this->redirect('/dengue');
    }
}
