<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\City;
use Models\Cleanup\CleanupPriority;
use Models\DengueRiskDistrict;
use Models\GramaNiladhari;
use Services\Audit;

/**
 * Cleanup priorities (FULL-SYSTEM-PLAN Phase 4).
 *
 * A Grama Niladhari officer names an area in their district that needs
 * cleaning, and why, at /gn/priorities. Organisers read every open one - with
 * the administrator's dengue cleanup recommendations beside them - at
 * /org/cleanup/priorities, before deciding where to run a campaign.
 */
class PriorityController extends Controller
{
    private CleanupPriority $priorities;

    public function __construct()
    {
        $this->priorities = new CleanupPriority();
    }

    // ---------- the officer ----------

    public function mine(): void
    {
        $this->view('cleanup.priorities-gn', [
            'title'      => 'Cleanup Priorities',
            'priorities' => $this->priorities->forOfficer((int) auth()['id']),
            'district'   => $this->officerDistrict()['name'] ?? '',
            'urgency'    => CleanupPriority::URGENCY,
            'statuses'   => CleanupPriority::STATUSES,
        ]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'area'    => 'required|max:160',
            'reason'  => 'required|max:2000',
            'urgency' => 'required|in:' . implode(',', array_keys(CleanupPriority::URGENCY)),
        ]);

        $district = $this->officerDistrict();
        if (!$district) {
            Session::flash('error', 'Your profile has no district, so a priority cannot be placed.');
            $this->redirect('/gn/priorities');
        }

        $id = (int) $this->priorities->create([
            'gn_user_id' => (int) auth()['id'],
            'city_id'    => $district['city_id'],
            'area'       => $data['area'],
            'reason'     => $data['reason'],
            'urgency'    => $data['urgency'],
            'status'     => 'open',
        ]);
        Audit::record("Cleanup priority {$id} raised by user " . (int) auth()['id'], 'cleanup_priorities', $id);

        Session::flash('success', 'Priority posted. Organisers see it when planning a campaign.');
        $this->redirect('/gn/priorities');
    }

    /** Mark addressed, or withdraw. Only the officer's own, and only while open. */
    public function setStatus(string $id): void
    {
        $row = $this->priorities->find((int) $id);
        if (!$row || (int) $row['gn_user_id'] !== (int) auth()['id']) {
            $this->abort(404, 'Priority not found.');
        }

        $status = (string) Request::post('status', '');
        if (!in_array($status, ['addressed', 'withdrawn'], true) || $row['status'] !== 'open') {
            Session::flash('error', 'That priority is no longer open.');
            $this->redirect('/gn/priorities');
        }

        $this->priorities->update((int) $row['id'], ['status' => $status]);
        Session::flash('success', $status === 'addressed' ? 'Marked as addressed.' : 'Priority withdrawn.');
        $this->redirect('/gn/priorities');
    }

    // ---------- organisers ----------

    public function board(): void
    {
        $cityId = (int) Request::get('city', 0) ?: null;

        $this->view('cleanup.priorities-board', [
            'title'           => 'Cleanup Priorities',
            'priorities'      => $this->priorities->open($cityId),
            'recommendations' => (new DengueRiskDistrict())->withRecommendation(),
            'cities'          => (new City())->listAll(),
            'city'            => $cityId,
            'urgency'         => CleanupPriority::URGENCY,
        ]);
    }

    /** The officer's district, as a `cities` row id and name. */
    private function officerDistrict(): ?array
    {
        $gn = (new GramaNiladhari())->findBy('user_id', (int) auth()['id']);
        if (!$gn) {
            return null;
        }
        $cityId = (new City())->forDistrict((int) $gn['district_id']);
        $city   = $cityId ? (new City())->find($cityId) : null;
        return $city ? ['city_id' => $cityId, 'name' => $city['name']] : null;
    }
}
