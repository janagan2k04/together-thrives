<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\User;
use Services\VerificationService;

/**
 * The verification queue — every account that registered and is waiting to be
 * let in. Approving one is what turns a STATUS_PENDING signup into a working
 * login, so this is the other half of registration.
 */
class OrgVerificationController extends Controller
{
    private User $users;
    private VerificationService $verification;

    public function __construct()
    {
        $this->users        = new User();
        $this->verification = new VerificationService();
    }

    public function index(): void
    {
        $role = (string) Request::get('role', '');
        $rows = $this->users->pendingVerifications($role !== '' ? ['role' => $role] : []);

        $this->view('admin.verification.index', [
            'title'    => 'Verification Requests',
            'requests' => $rows,
            'role'     => $role,
        ]);
    }

    /** Full submission for one applicant, including the uploaded document. */
    public function show(string $id): void
    {
        $row = $this->users->pendingVerification((int) $id);
        if (!$row) {
            $this->abort(404, 'That request is no longer pending.');
        }

        $this->view('admin.verification.show', [
            'title'   => 'Verification Request',
            'request' => $row,
        ]);
    }

    public function approve(string $id): void
    {
        if ($this->verification->approve((int) $id, (int) auth()['id'])) {
            Session::flash('success', 'Account approved. They can sign in now.');
        } else {
            Session::flash('error', 'That account is no longer awaiting verification.');
        }

        $this->redirect('/admin/verification');
    }

    public function reject(string $id): void
    {
        $reason = trim((string) Request::post('reason', ''));

        if ($this->verification->reject((int) $id, (int) auth()['id'], $reason)) {
            Session::flash('success', 'Registration rejected. The applicant has been notified.');
        } else {
            Session::flash('error', 'That account is no longer awaiting verification.');
        }

        $this->redirect('/admin/verification');
    }
}
