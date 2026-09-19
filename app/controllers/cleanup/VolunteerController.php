<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Cleanup\Attendance;
use Models\Cleanup\CleanupEvent;
use Models\Cleanup\EventRegistration;
use Models\Notification;

/**
 * Volunteer sign-up for a cleanup campaign - the "Join Campaign" frame.
 *
 * A campaign an administrator has not approved cannot be joined - the same 404
 * the public detail page gives, because a 403 would confirm the campaign is
 * there.
 *
 * The row this writes is `event_registrations`, created `pending`. The organiser
 * approves it on the attendance screen; only then can the volunteer be checked
 * in. A volunteer who has left and come back reuses their withdrawn row rather
 * than inserting a second one - see join(). Three things are refused before the
 * write, in this order, because each one needs a different sentence back to the
 * volunteer:
 *
 *   - the campaign has already finished;
 *   - the member is already signed up (the UNIQUE key would otherwise throw);
 *   - the campaign is full.
 *
 * Skills are one free-text line as of 2026-09-05, not a fixed checkbox list:
 * `event_registrations.skills` is a VARCHAR, and no five-item list covers what a
 * volunteer can actually offer.
 */
class VolunteerController extends Controller
{
    private CleanupEvent $events;
    private EventRegistration $registrations;

    public function __construct()
    {
        $this->events        = new CleanupEvent();
        $this->registrations = new EventRegistration();
    }

    /** The sign-up form. */
    public function form(string $id): void
    {
        $event = $this->joinableOrFail($id);

        if ($this->registrations->alreadyJoined((int) auth()['id'], (int) $event['id'])) {
            Session::flash('info', 'You have already signed up for this campaign.');
            $this->redirect('/cleanup/' . $id);
        }

        $this->view('cleanup.join', [
            'title' => 'Join ' . $event['title'],
            'event' => $event,
        ]);
    }

    /** Handles the sign-up. */
    public function join(string $id): void
    {
        $event  = $this->joinableOrFail($id);
        $userId = (int) auth()['id'];

        if ($this->registrations->alreadyJoined($userId, (int) $event['id'])) {
            Session::flash('info', 'You have already signed up for this campaign.');
            $this->redirect('/cleanup/' . $id);
        }

        $limit = (int) $event['volunteer_limit'];
        if ($limit > 0 && $this->registrations->countFor((int) $event['id']) >= $limit) {
            Session::flash('error', 'This campaign is full - all ' . $limit . ' places are taken.');
            $this->redirect('/cleanup/' . $id);
        }

        $data = $this->validate([
            'full_name'               => 'required|max:120',
            'nic'                     => 'required|nic_lk',
            'phone'                   => 'required|phone_lk',
            'email'                   => 'required|email',
            'emergency_contact_name'  => 'required|max:120',
            'emergency_contact_phone' => 'required|phone_lk',
            'skills'                  => 'max:255',
        ]);

        $skills = trim((string) ($data['skills'] ?? ''));

        $row = [
            'emergency_contact_name'  => $data['emergency_contact_name'],
            'emergency_contact_phone' => $data['emergency_contact_phone'],
            'skills'                  => $skills !== '' ? $skills : null,
            'status'                  => 'pending',
        ];

        /*
         * Signing up again after leaving REUSES the old row.
         *
         * `uq_event_user (event_id, user_id)` is a UNIQUE key and leaving sets
         * `cancelled` rather than deleting, so a second INSERT for the same pair
         * throws a duplicate-key error - which is what a member got the moment
         * Leave gave them a Join button back. The withdrawn row is brought back
         * to `pending` with whatever they typed this time, and the organiser
         * approves it as they would any other.
         */
        $existing = $this->registrations->forUserAndEvent($userId, (int) $event['id']);

        if ($existing) {
            $this->registrations->update((int) $existing['id'], $row + ['work_area_id' => null]);
        } else {
            $this->registrations->create($row + [
                'event_id' => (int) $event['id'],
                'user_id'  => $userId,
            ]);
        }

        $this->notifyOrganiser($event);

        Session::flash('success', 'You are signed up. The organiser confirms volunteers before '
            . 'the day, and you can see your place under My Activities.');
        $this->redirect('/cleanup/' . $id);
    }

    /**
     * Withdraw a sign-up - "Leave campaign" on the campaign page.
     *
     * The row is moved to `cancelled` rather than deleted: `uq_event_user` means
     * a deleted row would let the same member sign up again and again, and every
     * count in the module already reads `status <> 'cancelled'`, so the place is
     * handed straight back to the campaign.
     *
     * A volunteer who has already been checked in is refused. They turned up;
     * the attendance record and the certificate that reads it are the
     * organiser's to change, not theirs.
     */
    public function leave(string $id): void
    {
        $event  = $this->events->findDetail((int) $id);
        if (!$event || !in_array($event['status'], CleanupEvent::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Cleanup campaign not found.');
        }

        $registration = $this->registrations->forUserAndEvent((int) auth()['id'], (int) $event['id']);

        if (!$registration || $registration['status'] === 'cancelled') {
            Session::flash('info', 'You are not signed up for this campaign.');
            $this->redirect('/cleanup/' . $id);
        }

        $attendance = (new Attendance())->forRegistration((int) $registration['id']);
        if ($attendance && $attendance['checked_in_at'] !== null) {
            Session::flash('error', 'You have already been checked in at this campaign, so your '
                . 'place cannot be given up. Speak to the organiser.');
            $this->redirect('/cleanup/' . $id);
        }

        $this->registrations->update((int) $registration['id'], ['status' => 'cancelled']);

        $this->notifyOrganiserOfLeaving($event);

        Session::flash('success', 'You have left this campaign. Your place has been given back, '
            . 'and you can sign up again while there is still room.');

        /*
         * Leave is offered on two screens, so the form says which one it was
         * pressed on and the volunteer lands back there.
         *
         * A posted field rather than $this->back(): back() reads HTTP_REFERER,
         * which the browser may not send at all - a referrer policy, a privacy
         * extension or an HTTPS page linking to HTTP all drop it - and then the
         * fallback is the landing page, which is nowhere near either screen.
         * The value is matched against a fixed list, never redirected to.
         */
        $this->redirect(Request::post('from') === 'activities'
            ? '/my/activities'
            : '/cleanup/' . $id);
    }

    /**
     * Tell the organiser somebody signed up.
     *
     * `findDetail()` already carries the organisation's account id, so this
     * needs no extra query. A failure here must not lose the registration, which
     * is why it is not part of the same transaction.
     */
    private function notifyOrganiser(array $event): void
    {
        if (empty($event['organiser_user_id'])) {
            return;
        }

        (new Notification())->create([
            'user_id'  => (int) $event['organiser_user_id'],
            'title'    => 'New volunteer registration',
            'body'     => (auth()['full_name'] ?? 'A volunteer') . ' signed up for '
                          . $event['title'] . '. They are waiting for your approval.',
            'category' => 'volunteer',
            'link'     => '/org/cleanup/' . $event['id'] . '/volunteers',
        ]);
    }

    /** And tell them when somebody withdraws - the list they plan around changed. */
    private function notifyOrganiserOfLeaving(array $event): void
    {
        if (empty($event['organiser_user_id'])) {
            return;
        }

        (new Notification())->create([
            'user_id'  => (int) $event['organiser_user_id'],
            'title'    => 'A volunteer withdrew',
            'body'     => (auth()['full_name'] ?? 'A volunteer') . ' left ' . $event['title']
                          . '. Their place is open again.',
            'category' => 'volunteer',
            'link'     => '/org/cleanup/' . $event['id'] . '/volunteers',
        ]);
    }

    /** A campaign has to be approved and still upcoming to be joinable. */
    private function joinableOrFail(string $id): array
    {
        $event = $this->events->findDetail((int) $id);
        if (!$event || !in_array($event['status'], CleanupEvent::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Cleanup campaign not found.');
        }

        if ($event['status'] === EVENT_COMPLETED) {
            Session::flash('error', 'That campaign has already finished.');
            $this->redirect('/cleanup/' . $id);
        }

        return $event;
    }
}
