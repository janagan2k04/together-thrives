<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Session;
use Services\SampleData;

/**
 * The four System Management screens from admin.svg: Admin Management, System
 * Logs, Analytics & Stats, and Settings.
 *
 * System Logs is the one that is fully real — `Logger::audit()` has been
 * writing `storage/logs/audit.log` since week 2, so this reads that file back
 * rather than inventing rows. The `audit_logs` table exists too, but nothing
 * writes to it; whoever moves the logger onto the table swaps `readAuditLog()`
 * for a query and the screen is unchanged.
 */
class SystemController extends Controller
{
    // ---------- Admin Management ----------

    public function admins(): void
    {
        $real = Database::all(
            "SELECT id, full_name, email, last_login_at, status, created_at
             FROM users WHERE role = ? AND deleted_at IS NULL
             ORDER BY created_at ASC",
            [ROLE_ADMIN]
        );

        $this->view('admin.admins', [
            'title'   => 'Admin Management',
            'real'    => $real,
            'samples' => SampleData::admins(),
        ]);
    }

    public function storeAdmin(): void
    {
        $this->validate([
            'full_name' => 'required|max:120',
            'email'     => 'required|email',
            'admin_role'=> 'required',
        ]);

        Session::flash('info', 'The group agreed on a single administrator account — adding more is a later decision.');
        $this->redirect('/admin/admins');
    }

    // ---------- System Logs ----------

    public function logs(): void
    {
        $source = in_array(Request::get('source'), ['audit', 'error', 'app'], true)
            ? (string) Request::get('source')
            : 'audit';

        $this->view('admin.logs', [
            'title'  => 'System Logs',
            'lines'  => $this->readLog($source, 120),
            'source' => $source,
        ]);
    }

    /**
     * Tail of one of the three log files, newest first.
     *
     * Lines look like `[2026-08-12 01:36:02] AUDIT: GS letter 1 approved by
     * admin 1`. Anything that does not parse is still shown, with an empty
     * timestamp, rather than silently dropped — a log viewer that hides lines
     * it does not understand is worse than useless.
     */
    private function readLog(string $file, int $limit): array
    {
        $path = STORAGE_PATH . "/logs/{$file}.log";
        if (!is_file($path)) {
            return [];
        }

        $lines = array_slice(array_filter(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)), -$limit);
        $out   = [];

        foreach (array_reverse($lines) as $line) {
            if (preg_match('/^\[([^\]]+)\]\s+([A-Z]+):\s*(.*)$/', $line, $m)) {
                $out[] = ['at' => $m[1], 'level' => $m[2], 'message' => $m[3]];
            } else {
                $out[] = ['at' => '', 'level' => strtoupper($file), 'message' => $line];
            }
        }
        return $out;
    }

    // ---------- Analytics ----------

    public function analytics(): void
    {
        $this->view('admin.analytics', [
            'title'   => 'Analytics & Reports',
            'headline' => [
                ['Total accounts',   (string) $this->scalar("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")],
                ['Verified accounts',(string) $this->scalar("SELECT COUNT(*) FROM users WHERE status = ? AND deleted_at IS NULL", [STATUS_ACTIVE])],
                ['Organizations',    (string) $this->scalar("SELECT COUNT(*) FROM organizations WHERE deleted_at IS NULL")],
                ['Reuse listings',   (string) $this->scalar("SELECT COUNT(*) FROM reuse_items WHERE deleted_at IS NULL")],
            ],
            'modules' => [
                ['Reuse Items',        'live',    (int) $this->scalar("SELECT COUNT(*) FROM reuse_items WHERE deleted_at IS NULL"),      'Listings created'],
                ['Cleanup Events',     'pending', (int) $this->scalar("SELECT COUNT(*) FROM cleanup_events WHERE deleted_at IS NULL"),   'Campaigns created'],
                ['Donation Requests',  'pending', (int) $this->scalar("SELECT COUNT(*) FROM donation_requests WHERE deleted_at IS NULL"),'Requests raised'],
                ['Blood Campaigns',    'pending', (int) $this->scalar("SELECT COUNT(*) FROM blood_campaigns WHERE deleted_at IS NULL"),  'Camps scheduled'],
            ],
            'registrations' => Database::all(
                "SELECT DATE(created_at) AS day, COUNT(*) AS total
                 FROM users WHERE deleted_at IS NULL
                 GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 10"
            ),
        ]);
    }

    private function scalar(string $sql, array $params = []): int
    {
        return (int) Database::value($sql, $params);
    }

    // ---------- Settings ----------

    public function settings(): void
    {
        $this->view('admin.settings', ['title' => 'Settings']);
    }

    public function saveSettings(): void
    {
        $this->validate([
            'support_email' => 'required|email',
            'contact_phone' => 'required|phone_lk',
        ]);

        Session::flash('info', 'Platform settings are not persisted yet — this screen is the week 3 render.');
        $this->redirect('/admin/settings');
    }
}
