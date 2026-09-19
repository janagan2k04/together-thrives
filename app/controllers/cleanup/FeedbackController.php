<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Cleanup\CleanupEvent;
use Models\Cleanup\EventFeedback;
use Models\Cleanup\EventRegistration;

/**
 * Volunteer feedback after a completed campaign - the "Volunteer Feedback Form"
 * frame, reached from My Activities.
 *
 * `event_feedback` stores one overall `rating`, one `safety_rating`, a `comment`
 * and an `is_anonymous` flag, with a unique key on (event_id, user_id). The form
 * asks for exactly those two scores; the five per-topic stars the Figma drew have
 * no column and are not asked for at all.
 *
 * Who may rate a campaign: a volunteer who signed up for it. The form is reached
 * from My Activities, which only lists campaigns the member joined, so the guard
 * is the `event_registrations` row - and it is a 404 rather than a 403, because
 * whether somebody joined a campaign is not public.
 *
 * A second submission edits the first rather than being refused: the unique key
 * on (event_id, user_id) would otherwise turn a double-click into a database
 * error.
 */
class FeedbackController extends Controller
{
    private CleanupEvent $events;
    private EventFeedback $feedback;
    private EventRegistration $registrations;

    public function __construct()
    {
        $this->events        = new CleanupEvent();
        $this->feedback      = new EventFeedback();
        $this->registrations = new EventRegistration();
    }

    public function form(string $id): void
    {
        $event = $this->attendedOrFail($id);

        $this->view('cleanup.feedback', [
            'title'    => 'Volunteer Feedback',
            'event'    => $event,
            'existing' => $this->feedback->forUserAndEvent((int) auth()['id'], (int) $event['id']),
        ]);
    }

    public function store(string $id): void
    {
        $event  = $this->attendedOrFail($id);
        $userId = (int) auth()['id'];

        $data = $this->validate([
            'rating'        => 'required|integer|min_value:1',
            'safety_rating' => 'integer|min_value:1',
            'comment'       => 'max:500',
        ]);

        $row = [
            'rating'        => (int) $data['rating'],
            'safety_rating' => $data['safety_rating'] !== '' ? (int) $data['safety_rating'] : null,
            'comment'       => $data['comment'] !== '' ? $data['comment'] : null,
            'is_anonymous'  => Request::post('is_anonymous') ? 1 : 0,
        ];

        $existing = $this->feedback->forUserAndEvent($userId, (int) $event['id']);
        if ($existing) {
            $this->feedback->updateRating((int) $existing['id'], $row);
            Session::flash('success', 'Your feedback has been updated.');
        } else {
            $this->feedback->create($row + [
                'event_id' => (int) $event['id'],
                'user_id'  => $userId,
            ]);
            Session::flash('success', 'Thank you - your feedback has been sent to the organiser.');
        }

        $this->redirect('/my/activities');
    }

    /**
     * The campaign has to be one this member turned up to.
     *
     * A 404 rather than a 403: the campaign is public, but whether somebody
     * attended it is not, and a 403 would answer that question for anyone who
     * guessed the URL.
     */
    private function attendedOrFail(string $id): array
    {
        $event = $this->events->findDetail((int) $id);
        if (!$event || !in_array($event['status'], CleanupEvent::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Cleanup campaign not found.');
        }

        $registration = $this->registrations->forUserAndEvent((int) auth()['id'], (int) $event['id']);
        if (!$registration) {
            $this->abort(404, 'Cleanup campaign not found.');
        }

        return $event;
    }
}
