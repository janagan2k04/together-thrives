<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Database;
use Core\Session;
use Services\SampleData;

/**
 * The two moderation screens from admin.svg: Suspended Users and Posts, and
 * Complaints & Feedback.
 *
 * Suspended *accounts* are real — week 2's verification queue can suspend one,
 * and `Admin\UserManagementController` reinstates it. Suspended *posts* need
 * the modules that own them, so the removed-content rows are samples until
 * weeks 4–5 land. Complaints are entirely sample: the `reports` table exists
 * but nothing writes to it yet.
 */
class ModerationController extends Controller
{
    public function suspended(): void
    {
        $accounts = Database::all(
            "SELECT u.id, u.full_name, u.email, u.role, u.status, u.updated_at
             FROM users u
             WHERE u.status = ? AND u.deleted_at IS NULL
             ORDER BY u.updated_at DESC, u.id DESC",
            [STATUS_SUSPENDED]
        );

        $real = array_map(fn($u) => [
            'id'     => (int) $u['id'],
            'name'   => $u['full_name'],
            'sub'    => $u['email'],
            'type'   => $u['role'] === ROLE_ORGANIZATION ? 'Organization' : 'User Account',
            'reason' => 'Suspended by an administrator',
            'on'     => format_date($u['updated_at']),
            'status' => 'Suspended',
            'action' => 'Reinstate',
            'sample' => false,
        ], $accounts);

        $samples = array_map(function ($row) {
            $row['id']     = 0;
            $row['sample'] = true;
            return $row;
        }, SampleData::suspended());

        $this->view('admin.suspended', [
            'title'     => 'Suspended Users and Posts',
            'rows'      => array_merge($real, $samples),
            'realCount' => count($real),
        ]);
    }

    public function complaints(): void
    {
        $this->view('admin.complaints', [
            'title'      => 'Complaints & Feedback',
            'complaints' => SampleData::complaints(),
        ]);
    }

    public function complaint(string $id): void
    {
        foreach (SampleData::complaints() as $c) {
            if ($c['id'] === $id) {
                $this->view('admin.complaint', ['title' => $c['subject'], 'complaint' => $c]);
                return;
            }
        }
        $this->abort(404, 'Complaint not found.');
    }

    public function resolve(string $id): void
    {
        Session::flash('info', 'Resolving complaints arrives with the moderation backend.');
        $this->redirect('/admin/complaints');
    }
}
