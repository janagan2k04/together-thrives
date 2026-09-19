<?php
namespace Controllers\Blood;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Blood\BloodCampaign;
use Services\ContentVerificationService;

/**
 * MEMBER 3 - administrator review of blood donation campaigns.
 *
 * A new campaign is created `pending` and is invisible to donors: the create
 * form, the organiser's detail sidebar and the public hub all promise that an
 * administrator fact-checks a camp before anyone is invited to it. This is the
 * screen that keeps the promise - without it `published` would be unreachable
 * and the public hub would stay empty forever.
 *
 * It is an admin screen, but blood-module work, so it lives under
 * Controllers\Blood and routes from routes/blood.php - the same call Member 4
 * made for the GS letter queue, and it means no other member's file changes.
 */
class CampaignReviewController extends Controller
{
    private BloodCampaign $campaigns;

    public function __construct()
    {
        $this->campaigns = new BloodCampaign();
    }

    public function index(): void
    {
        $status = (string) Request::get('status', MOD_PENDING);
        if (!in_array($status, [MOD_PENDING, EVENT_PUBLISHED, MOD_REJECTED], true)) {
            $status = MOD_PENDING;
        }

        $this->view('blood.admin-campaigns', [
            'title'     => 'Blood Campaign Review',
            'campaigns' => $this->campaigns->queue($status),
            'status'    => $status,
            'pending'   => $this->campaigns->pendingCount(),
            'statuses'  => BloodCampaign::STATUSES,
        ]);
    }

    public function approve(string $id): void
    {
        $this->decide($id, true, 'Campaign approved. It is live on the blood donation hub and the organiser has been notified.');
    }

    public function reject(string $id): void
    {
        $this->decide($id, false, 'Campaign rejected. The organiser has been notified and can edit it and resubmit.');
    }

    /**
     * Both decisions differ only in which way they go and what they say, and
     * both must refuse a campaign that has already been decided - a second
     * decision would silently overwrite the first and re-notify the organiser.
     *
     * The decision itself is ContentVerificationService's, not this class's.
     * Added 2026-08-18: writing a status here and stopping meant the organiser
     * was never told, and the unified queue at /admin/verification would have
     * been a second implementation of the same rule. One service, two doors.
     */
    private function decide(string $id, bool $approve, string $message): void
    {
        $campaign = $this->campaigns->find((int) $id);
        if (!$campaign) {
            $this->abort(404, 'Campaign not found.');
        }

        if ($campaign['status'] !== MOD_PENDING) {
            Session::flash('error', 'That campaign has already been reviewed.');
            $this->redirect('/admin/blood/campaigns');
        }

        $content = new ContentVerificationService();
        $adminId = (int) auth()['id'];

        $approve
            ? $content->approve('blood', (int) $campaign['id'], $adminId)
            : $content->reject('blood', (int) $campaign['id'], $adminId, (string) Request::post('reason', ''));

        Session::flash('success', $message);
        $this->redirect('/admin/blood/campaigns');
    }
}
