<?php
namespace Controllers\Blood;

use Core\Controller;
use Core\Session;
use Models\City;
use Services\BloodSampleData;

/**
 * Critical blood requests — the urgent side of the module.
 *
 * A community member posts one for a patient; an administrator fact-checks it;
 * matching donors nearby are notified. `critical_blood_requests` is the frozen
 * table and the form below validates against its real columns and ENUMs.
 */
class CriticalRequestController extends Controller
{
    /** `critical_blood_requests.blood_group` — the same eight the schema lists. */
    private const PRIORITIES = ['urgent' => 'Urgent', 'high' => 'High Priority', 'normal' => 'Normal'];

    public function create(): void
    {
        $this->view('blood.critical-form', [
            'title'      => 'Post a Critical Blood Request',
            'groups'     => BloodSampleData::GROUPS,
            'priorities' => self::PRIORITIES,
            'cities'     => (new City())->listWithDistrict(),
        ]);
    }

    public function store(): void
    {
        $this->validate([
            'patient_name'  => 'required|max:120',
            'contact_phone' => 'required|phone_lk',
            'hospital'      => 'required|max:160',
            'city_id'       => 'required|integer',
            'blood_group'   => 'required|in:' . implode(',', BloodSampleData::GROUPS),
            'units_needed'  => 'required|integer|min_value:1',
            'priority'      => 'required|in:' . implode(',', array_keys(self::PRIORITIES)),
            'needed_by'     => 'required',
            'notes'         => 'required|max:1000',
        ]);

        Session::flash('info', 'Request validated. Posting arrives with the blood backend in weeks 4–5 — nothing was stored.');
        $this->redirect('/blood?tab=critical');
    }

    public function show(string $id): void
    {
        $request = BloodSampleData::criticalRequest($id);
        if (!$request) {
            $this->abort(404, 'Blood request not found.');
        }

        $this->view('blood.critical-show', [
            'title'   => 'Critical Blood Request - ' . $request['group'],
            'request' => $request,
        ]);
    }

    /**
     * "I Can Donate" — like joining a camp, this goes to the screening form
     * first. Contact details are only shared with the requester once the donor
     * comes back eligible, which is the promise the detail screen makes.
     */
    public function respond(string $id): void
    {
        if (!BloodSampleData::criticalRequest($id)) {
            $this->abort(404, 'Blood request not found.');
        }

        $this->redirect('/blood/critical/' . $id . '/screening');
    }
}
