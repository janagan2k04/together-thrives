<?php
namespace Controllers\Reuse;

use Controllers\FileController;
use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Reuse\GsVerification;
use Services\ItemRequestService;

/**
 * MEMBER 4 - administrator review of Grama Niladhari letters.
 *
 * A requester claiming to be disaster-affected uploads a GS letter, which gave
 * their request priority in the donor's queue. Nothing ever checked it, so the
 * badge said "verified" on the strength of a file having been attached. This is
 * the screen that decides.
 *
 * It is an admin screen, but it belongs to the reuse module rather than to the
 * verification queue - that one reviews *accounts*, this reviews evidence
 * attached to a reuse request. It lives under Controllers\Reuse and routes from
 * routes/reuse.php so that no other member's file has to change.
 */
class GsLetterController extends Controller
{
    private GsVerification     $letters;
    private ItemRequestService $service;

    public function __construct()
    {
        $this->letters = new GsVerification();
        $this->service = new ItemRequestService();
    }

    public function index(): void
    {
        $status = (string) Request::get('status', MOD_PENDING);
        if (!in_array($status, [MOD_PENDING, MOD_APPROVED, MOD_REJECTED], true)) {
            $status = MOD_PENDING;
        }

        $this->view('reuse.admin-letters', [
            'title'   => 'GS Letter Review',
            'letters' => $this->letters->queue($status),
            'status'  => $status,
            'pending' => $this->letters->pendingCount(),
        ]);
    }

    /** The letter itself. Route is already gated `role:admin`. */
    public function file(string $id): void
    {
        $letter = $this->letters->find((int) $id);
        if (!$letter) {
            $this->abort(404, 'Letter not found.');
        }

        (new FileController())->stream($letter['letter_path']);
    }

    public function approve(string $id): void
    {
        $this->decide($id, MOD_APPROVED, 'Letter verified. The requester now shows as verified disaster-affected.');
    }

    public function reject(string $id): void
    {
        $this->decide($id, MOD_REJECTED, 'Letter rejected. The requester has been told.');
    }

    /**
     * Both decisions differ only in the status and the message, and both must
     * refuse a letter that has already been reviewed - deciding twice would
     * send the applicant a second, contradictory notification.
     */
    private function decide(string $id, string $status, string $message): void
    {
        $letter = $this->letters->find((int) $id);
        if (!$letter) {
            $this->abort(404, 'Letter not found.');
        }

        if ($letter['status'] !== MOD_PENDING) {
            Session::flash('error', 'That letter has already been reviewed.');
            $this->redirect('/admin/reuse/letters');
        }

        $this->service->decideLetter($letter, $status, (int) auth()['id']);

        Session::flash('success', $message);
        $this->redirect('/admin/reuse/letters');
    }
}
