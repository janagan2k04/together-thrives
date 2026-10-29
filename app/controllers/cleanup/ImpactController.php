<?php
namespace Controllers\Cleanup;

use Controllers\FileController;
use Core\Controller;
use Core\Database;
use Core\FileUploader;
use Core\Request;
use Core\Session;
use Models\Cleanup\CleanupEvent;
use Models\Cleanup\CleanupImpact;
use Models\Cleanup\EventPhoto;
use Models\Notification;
use Models\Organization;
use Services\Audit;

/**
 * A campaign's outcome - its photographs and its published impact report
 * (FR-doc cleanup: "upload impact report", "view cleanup outcomes / impact
 * reports"; FULL-SYSTEM-PLAN Phase 2).
 *
 * Publishing the report is what completes a campaign. Nothing moved a cleanup
 * campaign to `completed` before 2026-10, so the Give Feedback button that
 * waits for that status could never be reached from the campaign page. A
 * report can be published once the campaign's last day has arrived, and
 * republishing edits the summary without re-notifying anyone.
 */
class ImpactController extends Controller
{
    private CleanupEvent $events;
    private EventPhoto $photos;
    private CleanupImpact $impacts;

    public function __construct()
    {
        $this->events  = new CleanupEvent();
        $this->photos  = new EventPhoto();
        $this->impacts = new CleanupImpact();
    }

    public function storePhoto(string $id): void
    {
        $event = $this->ownedOrFail($id);
        if ($event['status'] === EVENT_CANCELLED) {
            Session::flash('error', 'A cancelled campaign cannot take new photos.');
            $this->redirect('/org/cleanup/' . $event['id']);
        }

        $data = $this->validate(['photo_type' => 'required|in:' . implode(',', array_keys(EventPhoto::TYPES))]);

        if ($this->photos->countFor((int) $event['id']) >= EventPhoto::MAX_PHOTOS) {
            Session::flash('error', 'A campaign can hold at most ' . EventPhoto::MAX_PHOTOS . ' photos. Remove one first.');
            $this->redirect('/org/cleanup/' . $event['id'] . '#photos');
        }

        $file = Request::file('photo');
        if (!$file) {
            Session::flashInput($data, ['photo' => ['Choose a photo to upload.']]);
            $this->redirect('/org/cleanup/' . $event['id'] . '#photos');
        }

        try {
            $path = FileUploader::store($file, 'events', ['jpg', 'png'], 5);
        } catch (\RuntimeException $e) {
            Session::flashInput($data, ['photo' => [$e->getMessage()]]);
            $this->redirect('/org/cleanup/' . $event['id'] . '#photos');
        }

        $photoId = (int) $this->photos->create([
            'event_id'   => (int) $event['id'],
            'file_path'  => $path,
            'photo_type' => $data['photo_type'],
        ]);
        Audit::record("Photo {$photoId} added to cleanup campaign {$event['id']}", 'event_photos', $photoId);

        Session::flash('success', 'Photo added.');
        $this->redirect('/org/cleanup/' . $event['id'] . '#photos');
    }

    public function destroyPhoto(string $id, string $photoId): void
    {
        $event = $this->ownedOrFail($id);
        $photo = $this->photos->find((int) $photoId);

        // The campaign id is checked against the photo's own, so another
        // campaign's photo cannot be removed through this one's URL.
        if (!$photo || (int) $photo['event_id'] !== (int) $event['id']) {
            $this->abort(404);
        }

        $this->photos->remove((int) $photo['id']);
        FileUploader::delete($photo['file_path']);

        Session::flash('success', 'Photo removed.');
        $this->redirect('/org/cleanup/' . $event['id'] . '#photos');
    }

    /** One photo, by row id - public once the campaign is, otherwise the organiser's and admins'. */
    public function photo(string $photoId): void
    {
        $photo = $this->photos->find((int) $photoId);
        $event = $photo ? $this->events->findAny((int) $photo['event_id']) : null;
        if (!$photo || !$event) {
            $this->abort(404);
        }

        $public = $event['deleted_at'] === null
            && in_array($event['status'], CleanupEvent::PUBLIC_STATUSES, true);
        if (!$public && !$this->owns($event)) {
            $this->abort(404);
        }

        (new FileController())->stream($photo['file_path']);
    }

    public function publish(string $id): void
    {
        $event = $this->ownedOrFail($id);

        if (!in_array($event['status'], [EVENT_PUBLISHED, EVENT_ONGOING, EVENT_COMPLETED], true)) {
            Session::flash('error', 'Only an approved campaign can publish an outcome.');
            $this->redirect('/org/cleanup/' . $event['id']);
        }
        if (self::lastDay($event) > date('Y-m-d')) {
            Session::flash('error', 'You can publish the outcome once the campaign has taken place.');
            $this->redirect('/org/cleanup/' . $event['id']);
        }

        $data  = $this->validate(['summary' => 'required|max:' . CleanupImpact::MAX_SUMMARY]);
        $first = $this->impacts->forEvent((int) $event['id']) === null;

        Database::transaction(function () use ($event, $data, $first) {
            $this->impacts->publish((int) $event['id'], trim($data['summary']));
            $this->events->update((int) $event['id'], ['status' => EVENT_COMPLETED]);

            if ($first) {
                // Everyone still signed up hears once that the outcome is out -
                // and that feedback is open, which is what completion unlocks.
                Database::run(
                    "INSERT INTO notifications (user_id, title, body, category, link)
                     SELECT r.user_id, ?, ?, 'volunteer', ?
                       FROM event_registrations r
                      WHERE r.event_id = ? AND r.status <> 'cancelled'",
                    [
                        'Campaign outcome published',
                        mb_substr('“' . $event['title'] . '” is complete. See what was achieved and share your feedback.', 0, 500),
                        '/cleanup/' . $event['id'],
                        (int) $event['id'],
                    ]
                );
            }
        });

        Audit::record("Impact report published for cleanup campaign {$event['id']}", 'cleanup_impacts', (int) $event['id']);
        Session::flash('success', $first
            ? 'Outcome published. The campaign is complete and volunteers have been told.'
            : 'Outcome updated.');
        $this->redirect('/org/cleanup/' . $event['id']);
    }

    /** The campaign's final day - its end date when it runs over several. */
    public static function lastDay(array $event): string
    {
        return (string) ($event['event_end_date'] ?: $event['event_date']);
    }

    private function ownedOrFail(string $id): array
    {
        $event = $this->events->findDetail((int) $id, true);
        if (!$event) {
            $this->abort(404, 'Cleanup campaign not found.');
        }
        if (!$this->owns($event)) {
            $this->abort(403, 'You can only manage your own campaigns.');
        }
        return $event;
    }

    /** The organiser's own campaign, or any campaign for an administrator. */
    private function owns(array $event): bool
    {
        if (!is_logged_in()) {
            return false;
        }
        if (role_is(ROLE_ADMIN)) {
            return true;
        }
        $org = (new Organization())->findBy('user_id', (int) auth()['id']);
        return $org !== null && (int) $org['id'] === (int) $event['organization_id'];
    }
}
