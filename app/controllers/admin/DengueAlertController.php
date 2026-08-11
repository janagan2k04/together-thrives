<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Session;
use Models\District;
use Services\SampleData;

/**
 * Dengue Risk Alerts as the administrator sees them.
 *
 * The same list the public screen shows — `Controllers\DengueController` —
 * rendered through the admin shell, plus the counters the frame draws along
 * the top. Both screens read the same sample set so they cannot drift.
 */
class DengueAlertController extends Controller
{
    public function index(): void
    {
        $this->view('dengue.index', [
            'title'      => 'Dengue Alert',
            'alerts'     => SampleData::dengueAlerts(),
            'districts'  => (new District())->listAll(),
            'reported'   => SampleData::dengueDistricts(),
            'prevention' => SampleData::denguePrevention(),
            'stats'      => [
                ['18',  'Active alerts',     'red'],
                ['6',   'Alerts near you',   'ink'],
                ['3',   'Alerts you raised', 'green'],
                ['24',  'Districts covered', 'ink'],
                ['142', 'Total this month',  'ink'],
            ],
            'isAdmin' => true,
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
        $this->redirect('/admin/dengue');
    }
}
