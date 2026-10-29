<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Core\Database;
use Core\Session;
use Models\City;
use Models\Cleanup\CleanupEvent;
use Models\GramaNiladhari;
use Models\Notification;
use Services\Audit;

/**
 * A Grama Niladhari officer confirms cleanup campaigns held in their district
 * (FR-doc cleanup GN: "confirm cleanup campaigns"; FULL-SYSTEM-PLAN Phase 4).
 *
 * A campaign stores a district, not a GN division, so the district is what is
 * matched. Confirming is the officer vouching for the campaign on the ground;
 * the campaign page shows it and the organiser is told. The officer's own
 * campaigns are not listed - vouching for yourself says nothing.
 *
 * GN officers are blocked from /cleanup (2026-08-27), so they need a list of
 * their own; this is it.
 */
class ConfirmationController extends Controller
{
    public function index(): void
    {
        $cityId = $this->officerCityId();

        $this->view('cleanup.confirmations', [
            'title'     => 'Confirm Campaigns',
            'campaigns' => $cityId ? $this->campaignsIn($cityId) : [],
            'hasCity'   => $cityId !== null,
        ]);
    }

    public function confirm(string $id): void
    {
        $cityId = $this->officerCityId();
        $event  = (new CleanupEvent())->findAny((int) $id);
        $me     = (int) auth()['id'];

        if (!$event || $event['deleted_at'] !== null || !$cityId || (int) $event['city_id'] !== $cityId
            || !in_array($event['status'], [EVENT_PUBLISHED, EVENT_ONGOING, EVENT_COMPLETED], true)) {
            $this->abort(404, 'Cleanup campaign not found in your district.');
        }
        if ($this->organiserUserId((int) $event['organization_id']) === $me) {
            Session::flash('error', 'You cannot confirm your own campaign.');
            $this->redirect('/gn/cleanup');
        }

        // The guard is on the UPDATE: a second confirmation does nothing.
        $changed = Database::run(
            'UPDATE cleanup_events SET gn_confirmed_by = ?, gn_confirmed_at = NOW()
              WHERE id = ? AND gn_confirmed_by IS NULL',
            [$me, (int) $event['id']]
        )->rowCount();

        if ($changed) {
            $gn = (new GramaNiladhari())->findBy('user_id', $me);
            (new Notification())->create([
                'user_id'  => (int) $this->organiserUserId((int) $event['organization_id']),
                'title'    => 'Campaign confirmed by the Grama Niladhari',
                'body'     => mb_substr('The Grama Niladhari of ' . ($gn['gn_division'] ?? 'your area')
                                . ' confirmed “' . $event['title'] . '”.', 0, 500),
                'category' => 'volunteer',
                'link'     => '/org/cleanup/' . $event['id'],
            ]);
            Audit::record("Cleanup campaign {$event['id']} confirmed by user {$me}", 'cleanup_events', (int) $event['id']);
            Session::flash('success', 'Campaign confirmed. The organiser has been told.');
        } else {
            Session::flash('info', 'That campaign has already been confirmed.');
        }
        $this->redirect('/gn/cleanup');
    }

    /** Approved campaigns in the district, other officers' and organisations' only. */
    private function campaignsIn(int $cityId): array
    {
        return Database::all(
            "SELECT e.id, e.title, e.event_date, e.event_end_date, e.meeting_point, e.status,
                    e.gn_confirmed_by, e.gn_confirmed_at, o.org_name, cu.full_name AS confirmed_by_name
               FROM cleanup_events e
               JOIN organizations o ON o.id = e.organization_id
          LEFT JOIN users cu ON cu.id = e.gn_confirmed_by
              WHERE e.city_id = ? AND e.deleted_at IS NULL AND e.status IN (?, ?, ?)
                AND o.user_id <> ?
           ORDER BY e.event_date DESC, e.id DESC",
            [$cityId, EVENT_PUBLISHED, EVENT_ONGOING, EVENT_COMPLETED, (int) auth()['id']]
        );
    }

    private function organiserUserId(int $organizationId): int
    {
        return (int) Database::value('SELECT user_id FROM organizations WHERE id = ?', [$organizationId]);
    }

    private function officerCityId(): ?int
    {
        $gn = (new GramaNiladhari())->findBy('user_id', (int) auth()['id']);
        return $gn ? (new City())->forDistrict((int) $gn['district_id']) : null;
    }
}
