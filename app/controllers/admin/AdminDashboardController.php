<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Database;
use Models\Reuse\GsVerification;
use Models\User;

/**
 * Landing page for administrators.
 *
 * There is no dashboard frame in admin.svg — the design drops an administrator
 * straight onto Users — but every sidebar in this project starts with one and
 * `Auth::homeFor()` sends admins to /admin/dashboard, so this is the one admin
 * screen built to the house pattern rather than to a frame.
 *
 * The counts are real. They come from tables week 2 already fills, which is
 * what makes this page worth having: it is the only screen that tells an
 * administrator whether there is anything waiting for them.
 */
class AdminDashboardController extends Controller
{
    public function index(): void
    {
        $users = new User();

        $this->view('admin.dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => [
                ['label' => 'Pending verifications', 'value' => $users->pendingCount(),
                 'hint' => 'Accounts awaiting review', 'link' => '/admin/verification', 'tone' => 'amber'],
                ['label' => 'GS letters to review', 'value' => (new GsVerification())->pendingCount(),
                 'hint' => 'Evidence attached to reuse requests', 'link' => '/admin/reuse/letters', 'tone' => 'blue'],
                ['label' => 'Active accounts', 'value' => $this->countUsers(STATUS_ACTIVE),
                 'hint' => 'Signed off and able to sign in', 'link' => '/admin/users', 'tone' => 'green'],
                ['label' => 'Suspended accounts', 'value' => $this->countUsers(STATUS_SUSPENDED),
                 'hint' => 'Blocked pending review', 'link' => '/admin/suspended', 'tone' => 'red'],
            ],
            'roles'  => $this->roleBreakdown(),
            'recent' => $this->recentAccounts(),
        ]);
    }

    private function countUsers(string $status): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM users WHERE status = ? AND deleted_at IS NULL",
            [$status]
        );
    }

    /** How the platform's accounts split across the five roles. */
    private function roleBreakdown(): array
    {
        $rows = Database::all(
            "SELECT role, COUNT(*) AS total FROM users WHERE deleted_at IS NULL GROUP BY role"
        );
        $labels = [
            ROLE_COMMUNITY    => 'Community members',
            ROLE_ORGANIZATION => 'Organizations',
            ROLE_GN           => 'Grama Niladhari officers',
            ROLE_BLOOD_BANK   => 'Blood banks',
            ROLE_ADMIN        => 'Administrators',
        ];

        $out = [];
        foreach ($labels as $role => $label) {
            $out[$label] = 0;
        }
        foreach ($rows as $r) {
            $out[$labels[$r['role']] ?? $r['role']] = (int) $r['total'];
        }
        return $out;
    }

    private function recentAccounts(): array
    {
        return Database::all(
            "SELECT id, full_name, email, role, status, created_at
             FROM users WHERE deleted_at IS NULL
             ORDER BY created_at DESC LIMIT 6"
        );
    }
}
