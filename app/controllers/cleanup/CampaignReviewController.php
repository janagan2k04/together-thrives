<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Cleanup\CleanupEvent;
use Services\ContentVerificationService;

/**
 * MEMBER 1 - administrator review of cleanup campaigns.
 *
 * A new campaign is created `draft` and is invisible to volunteers: the create
 * form, the organiser's detail screen and the public list all promise that an
 * administrator checks a campaign - its date, meeting point, volunteer limit and
 * safety guidelines - before anyone is asked to turn up to it. This is the screen
 * that keeps the promise. Without it `published` would be unreachable and
 * `/cleanup` would stay empty for ever.
 *
 * `cleanup_events.status` has no `rejected` and the table has no `reject_reason`,
 * so a rejection is `cancelled` with the note in `cancel_reason` - the row stays
 * live, the organiser sees the reason on their own screens, and editing it
 * resubmits as `draft`. An organiser's own withdrawal is the same status but soft
 * deleted, which is what tells the two apart.
 *
 * It is an admin screen but cleanup-module work, so it lives under
 * Controllers\Cleanup and routes from routes/cleanup.php - the same call Member 4
 * made for the GS letter queue, Member 3 for blood campaigns and Member 2 for
 * donation requests. No other member's route file changes.
 */
class CampaignReviewController extends Controller
{
    /** The three tabs, and the statuses behind them. */
    private const TABS = [EVENT_DRAFT, EVENT_PUBLISHED, EVENT_CANCELLED];

    private CleanupEvent $events;

    public function __construct()
    {
        $this->events = new CleanupEvent();
    }

    public function index(): void
    {
        $status = (string) Request::get('status', EVENT_DRAFT);
        if (!in_array($status, self::TABS, true)) {
            $status = EVENT_DRAFT;
        }

        $this->view('cleanup.admin-events', [
            'title'    => 'Cleanup Campaign Review',
            'events'   => $this->events->queue($status),
            'status'   => $status,
            'pending'  => $this->events->pendingCount(),
            'tabs'     => self::TABS,
        ]);
    }

    /*
     * Both decisions go through ContentVerificationService, not through the
     * model directly. Added 2026-08-18: writing a status here and stopping meant
     * the author was never told their post had been decided on, and the unified
     * queue at /admin/verification would have been a second implementation of
     * the same rule. One service, two doors into it.
     */
    public function approve(string $id): void
    {
        $event = $this->pendingOrFail($id);

        (new ContentVerificationService())->approve('cleanup', (int) $event['id'], (int) auth()['id']);

        Session::flash('success', 'Campaign approved. Volunteers can now see it, and the organiser has been notified.');
        $this->redirect('/admin/cleanup');
    }

    public function reject(string $id): void
    {
        $event = $this->pendingOrFail($id);
        $data  = $this->validate(['cancel_reason' => 'required|max:255']);

        (new ContentVerificationService())
            ->reject('cleanup', (int) $event['id'], (int) auth()['id'], $data['cancel_reason']);

        Session::flash('success', 'Campaign rejected. The organiser has been notified and can correct it and resubmit.');
        $this->redirect('/admin/cleanup');
    }

    /**
     * Both decisions must refuse a campaign that has already been decided - a
     * second decision would silently overwrite the first and contradict whatever
     * the organiser was already told.
     */
    private function pendingOrFail(string $id): array
    {
        $event = $this->events->find((int) $id);
        if (!$event) {
            $this->abort(404, 'Cleanup campaign not found.');
        }

        if ($event['status'] !== EVENT_DRAFT) {
            Session::flash('error', 'That campaign has already been reviewed.');
            $this->redirect('/admin/cleanup');
        }

        return $event;
    }
}
