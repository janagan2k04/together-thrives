<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\User;
use Services\VerificationService;

/**
 * Every account on the platform, whatever its status. Verify and reject act on
 * pending rows; suspend and reinstate act on the rest.
 */
class UserManagementController extends Controller
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
        $filters = array_filter([
            'role'   => (string) Request::get('role', ''),
            'status' => (string) Request::get('status', ''),
        ]);

        $this->view('admin.users.index', [
            'title'   => 'Users',
            'users'   => $this->users->browseForAdmin($filters),
            'filters' => $filters,
        ]);
    }

    public function suspend(string $id): void
    {
        $reason = trim((string) Request::post('reason', ''));

        if ($this->verification->suspend((int) $id, (int) auth()['id'], $reason)) {
            Session::flash('success', 'Account suspended.');
        } else {
            Session::flash('error', 'That account cannot be suspended.');
        }

        $this->back();
    }

    public function reinstate(string $id): void
    {
        if ($this->verification->reinstate((int) $id, (int) auth()['id'])) {
            Session::flash('success', 'Account reinstated.');
        } else {
            Session::flash('error', 'That account cannot be reinstated.');
        }

        $this->back();
    }
}
