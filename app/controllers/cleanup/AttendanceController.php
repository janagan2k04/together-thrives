<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Cleanup\Attendance;
use Models\Cleanup\CleanupEvent;
use Models\Cleanup\EventRegistration;
use Models\Cleanup\WorkArea;
use Models\Notification;
use Models\Organization;

/**
 * "Registered Volunteers & Attendance" - the organiser's check-in screen.
 *
 * Every row is `event_registrations` joined with `attendance` and `work_areas`.
 * The campaign is ownership-checked the same way every other organiser screen
 * is: this lists who is coming to your event, so the id in the URL is never
 * trusted - and neither is the registration id in the form, which is checked
 * back to this campaign before any write.
 *
 * Four writes live here:
 *
 *   approve      pending -> confirmed. Only a confirmed volunteer can be
 *                checked in, which is why they are two buttons.
 *   checkIn      writes the `attendance` row. Accepts several ids at once, so
 *                one button serves the row action and "Mark Selected".
 *   storeArea    creates a zone on this campaign.
 *   assignArea   puts confirmed volunteers in a zone.
 *
 * Nothing writes `checked_out_at` - see Models\Cleanup\Attendance.
 */
class AttendanceController extends Controller
{
    private CleanupEvent $events;
    private EventRegistration $registrations;
    private Attendance $attendance;
    private WorkArea $areas;

    public function __construct()
    {
        $this->events        = new CleanupEvent();
        $this->registrations = new EventRegistration();
        $this->attendance    = new Attendance();
        $this->areas         = new WorkArea();
    }

    public function index(string $id): void
    {
        $event = $this->ownedOrFail($id);

        $filters = [
            'q'         => trim((string) Request::get('q', '')),
            'state'     => (string) Request::get('state', ''),
            'work_area' => (string) Request::get('work_area', ''),
        ];

        $this->view('cleanup.volunteers', [
            'title'      => 'Registered Volunteers & Attendance',
            'event'      => $event,
            'volunteers' => $this->registrations->forEvent((int) $event['id'], $filters),
            'workAreas'  => $this->areas->forEvent((int) $event['id']),
            'filters'    => $filters,
        ]);
    }

    // ---------- writes ----------

    /** pending -> confirmed, and tell the volunteer they are in. */
    public function approve(string $id, string $registrationId): void
    {
        $event = $this->ownedOrFail($id);
        $row   = $this->registrationOrFail((int) $registrationId, (int) $event['id']);

        if ($row['status'] !== 'pending') {
            Session::flash('error', 'That volunteer has already been decided on.');
            $this->redirect('/org/cleanup/' . $event['id'] . '/volunteers');
        }

        $this->registrations->update((int) $row['id'], ['status' => 'confirmed']);

        (new Notification())->create([
            'user_id'  => (int) $row['user_id'],
            'title'    => 'Your place is confirmed',
            'body'     => 'The organiser confirmed your place at ' . $event['title'] . '.',
            'category' => 'volunteer',
            'link'     => '/cleanup/' . $event['id'],
        ]);

        Session::flash('success', 'Volunteer confirmed.');
        $this->redirect('/org/cleanup/' . $event['id'] . '/volunteers');
    }

    /**
     * Check one or many volunteers in.
     *
     * The row button posts a single id and "Mark Selected as Checked In" posts
     * the ticked ones, so both use this. A registration that is not confirmed is
     * skipped rather than refused - selecting a whole page and pressing the
     * button should not fail because one row was still pending.
     */
    public function checkIn(string $id): void
    {
        $event = $this->ownedOrFail($id);
        $ids   = array_filter(array_map('intval', (array) (Request::post('registration_id') ?? [])));

        if (!$ids) {
            Session::flash('error', 'Select at least one volunteer first.');
            $this->redirect('/org/cleanup/' . $event['id'] . '/volunteers');
        }

        $done    = 0;
        $skipped = 0;
        foreach ($ids as $registrationId) {
            $row = $this->registrations->findWithEvent($registrationId);
            if (!$row || (int) $row['event_id'] !== (int) $event['id']) {
                continue;
            }
            if ($row['status'] !== 'confirmed') {
                $skipped++;
                continue;
            }
            $this->attendance->checkIn((int) $row['id'], (int) auth()['id']);
            $done++;
        }

        $message = $done === 1 ? 'Volunteer checked in.' : $done . ' volunteers checked in.';
        if ($skipped) {
            $message .= ' ' . $skipped . ' skipped - confirm them first.';
        }

        Session::flash($done ? 'success' : 'error', $done ? $message : 'Confirm a volunteer before checking them in.');
        $this->redirect('/org/cleanup/' . $event['id'] . '/volunteers');
    }

    /** Create a zone on this campaign. */
    public function storeArea(string $id): void
    {
        $event = $this->ownedOrFail($id);

        $data = $this->validate(['name' => 'required|max:120']);

        $this->areas->create([
            'event_id' => (int) $event['id'],
            'name'     => $data['name'],
        ]);

        Session::flash('success', 'Work area added.');
        $this->redirect('/org/cleanup/' . $event['id'] . '/volunteers');
    }

    /**
     * Put the selected volunteers in a zone.
     *
     * An empty `work_area_id` clears the assignment, which is the only way back
     * to "Not assigned" once a volunteer has been placed.
     */
    public function assignArea(string $id): void
    {
        $event  = $this->ownedOrFail($id);
        $ids    = array_filter(array_map('intval', (array) (Request::post('registration_id') ?? [])));
        $areaId = (int) Request::post('work_area_id');

        if (!$ids) {
            Session::flash('error', 'Select at least one volunteer first.');
            $this->redirect('/org/cleanup/' . $event['id'] . '/volunteers');
        }

        if ($areaId && !$this->areas->belongsToEvent($areaId, (int) $event['id'])) {
            $this->abort(403, 'That work area belongs to a different campaign.');
        }

        $done = 0;
        foreach ($ids as $registrationId) {
            $row = $this->registrations->findWithEvent($registrationId);
            if (!$row || (int) $row['event_id'] !== (int) $event['id']) {
                continue;
            }
            $this->registrations->update((int) $row['id'], ['work_area_id' => $areaId ?: null]);
            $done++;
        }

        Session::flash('success', $areaId
            ? $done . ' volunteer' . ($done === 1 ? '' : 's') . ' assigned.'
            : $done . ' volunteer' . ($done === 1 ? '' : 's') . ' unassigned.');
        $this->redirect('/org/cleanup/' . $event['id'] . '/volunteers');
    }

    // ---------- guards ----------

    /** Loads cancelled campaigns too - the organiser keeps their own history. */
    private function ownedOrFail(string $id): array
    {
        $event = $this->events->findDetail((int) $id, true);
        if (!$event) {
            $this->abort(404, 'Cleanup campaign not found.');
        }

        if (!role_is(ROLE_ADMIN)) {
            $org = (new Organization())->findBy('user_id', (int) auth()['id']);
            if (!$org || (int) $event['organization_id'] !== (int) $org['id']) {
                $this->abort(403, 'You can only see volunteers for your own campaigns.');
            }
        }

        return $event;
    }

    /** The id in the form is never trusted either - it must be on this campaign. */
    private function registrationOrFail(int $registrationId, int $eventId): array
    {
        $row = $this->registrations->findWithEvent($registrationId);
        if (!$row || (int) $row['event_id'] !== $eventId) {
            $this->abort(404, 'Registration not found.');
        }
        return $row;
    }
}
