<?php
namespace Controllers\Blood;

use Core\Controller;
use Core\Session;
use Models\Blood\BloodCampaign;
use Models\Blood\CriticalRequest;
use Models\Blood\ScreeningResponse;
use Models\City;

/**
 * Critical blood requests - the urgent side of the module.
 *
 * A community member posts one for a patient; an administrator fact-checks it;
 * matching donors nearby are notified. `critical_blood_requests` is the frozen
 * table and the form below validates against its real columns and ENUMs.
 *
 * `address` was added by migration 2026_08_18: `hospital` is a name and
 * `city_id` a city, and a donor driving to give blood in a hurry needs the
 * street too. `priority` and `ward` came with the same day's second migration,
 * when this screen stopped throwing its input away.
 *
 * Until 2026-08-18 `store()` flashed a message and discarded everything. It
 * writes a real row now, created `pending`: no post on this platform reaches the
 * public before an administrator has approved it, and an urgent request - a
 * named patient, a hospital, a phone number - is the one that most needs
 * checking before it is broadcast.
 */
class CriticalRequestController extends Controller
{
    private CriticalRequest $requests;

    public function __construct()
    {
        $this->requests = new CriticalRequest();
    }

    public function create(): void
    {
        // The form is reached from two places: the donor hub for a community
        // member, and /my/blood/create for an organiser. Both organiser roles
        // are blocked from the hub, so back and Cancel have to lead to their own
        // list instead of dropping them on a 403.
        $bank = role_is(ROLE_BLOOD_BANK, ROLE_ORGANIZATION);

        $this->view('blood.critical-form', [
            'title'      => 'Post a Critical Blood Request',
            'groups'     => BloodCampaign::GROUPS,
            'priorities' => CriticalRequest::PRIORITIES,
            'cities'     => (new City())->listWithDistrict(),
            'backUrl'    => $bank ? '/my/blood' : '/blood',
            'backLabel'  => $bank ? 'Back to Blood Campaigns' : 'Back to Blood Donation',
        ]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'patient_name'  => 'required|max:120',
            'contact_phone' => 'required|phone_lk',
            'hospital'      => 'required|max:160',
            'address'       => 'required|max:255',
            'ward'          => 'max:60',
            'city_id'       => 'required|integer',
            'blood_group'   => 'required|in:' . implode(',', BloodCampaign::GROUPS),
            'units_needed'  => 'required|integer|min_value:1',
            'priority'      => 'required|in:' . implode(',', array_keys(CriticalRequest::PRIORITIES)),
            'needed_by'     => 'required',
            'notes'         => 'required|max:1000',
        ]);

        // `needed_by` is a DATETIME and the field is a date input, so a bare
        // date arrives without a time. End of that day is the reading a person
        // making the request would expect.
        $data['needed_by']    = $this->asDeadline($data['needed_by']);
        $data['ward']         = ($data['ward'] ?? '') ?: null;
        $data['requester_id'] = (int) auth()['id'];
        $data['status']       = MOD_PENDING;

        $this->requests->create($data);

        Session::flash('success',
            'Request submitted. An administrator verifies it before donors are notified.');

        // An organiser may post an urgent need but is blocked from the donor
        // hub, so it goes back to its own campaign list rather than to /blood.
        $this->redirect(role_is(ROLE_BLOOD_BANK, ROLE_ORGANIZATION) ? '/my/blood' : '/blood?tab=critical');
    }

    public function show(string $id): void
    {
        $request = $this->requests->findDetail((int) $id);
        if (!$request) {
            $this->abort(404, 'Blood request not found.');
        }

        // A request still in the queue, or turned down, is visible to the person
        // who raised it and to nobody else. 404 rather than 403: a stranger
        // should not learn that the id exists.
        $mine = is_logged_in() && (int) $request['requester_id'] === (int) auth()['id'];
        if (!in_array($request['status'], CriticalRequest::PUBLIC_STATUSES, true) && !$mine) {
            $this->abort(404, 'Blood request not found.');
        }

        $this->view('blood.critical-show', [
            'title'    => 'Critical Blood Request - ' . $request['group'],
            'request'  => $request,
            'statuses' => CriticalRequest::STATUSES,
            'tones'    => CriticalRequest::TONES,
            'mine'     => $mine,
            // Who has answered. Only the requester is shown this: the whole
            // point of the screening step is that a donor's number reaches the
            // person who needs blood and nobody else.
            'responders' => $mine
                ? (new ScreeningResponse())->respondersFor((int) $request['id'])
                : [],
        ]);
    }

    /**
     * "I Can Donate" - like joining a camp, this goes to the screening form
     * first. Contact details are only shared with the requester once the donor
     * comes back eligible, which is the promise the detail screen makes.
     */
    public function respond(string $id): void
    {
        $request = $this->requests->findDetail((int) $id);
        if (!$request || !in_array($request['status'], CriticalRequest::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Blood request not found.');
        }

        $this->redirect('/blood/critical/' . $id . '/screening');
    }

    /** 'YYYY-MM-DD' -> end of that day; anything already carrying a time is kept. */
    private function asDeadline(string $value): string
    {
        $ts = strtotime($value);
        if ($ts === false) {
            return date('Y-m-d H:i:s');
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))
            ? date('Y-m-d 23:59:59', $ts)
            : date('Y-m-d H:i:s', $ts);
    }
}
