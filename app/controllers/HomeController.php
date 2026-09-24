<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\Database;

class HomeController extends Controller
{
    public function landing(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::homeFor());
        }
        $this->view('home.landing', [
            'title' => 'TogetherThrives',
            'stats' => $this->publicStats(),
        ], 'guest');
    }

    /**
     * The five figures across the top of the homepage.
     *
     * They were hard-coded ("2,480 volunteers", "LKR 4.2M", "186 cleanup
     * campaigns"). On a public page those are not placeholders, they are claims
     * about the platform, so each one is a live count now. With no seed content
     * four of them read 0 until the modules start storing rows, which is the
     * correct answer to "how many, so far".
     *
     * Only money and organizations count something other than plain rows:
     * a donation counts once the payment succeeded, and an organization counts
     * once an administrator has verified it - the label says "verified".
     */
    private function publicStats(): array
    {
        $count = static fn(string $sql, array $p = []): int => (int) Database::value($sql, $p);

        $funds = (float) Database::value(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success'"
        );

        return [
            [number_format($count(
                "SELECT COUNT(*) FROM users WHERE role = ? AND status = ? AND deleted_at IS NULL",
                [ROLE_COMMUNITY, STATUS_ACTIVE]
             )), 'Volunteers registered'],

            [format_money($funds), 'Donated through platform'],

            [number_format($count(
                "SELECT COUNT(*) FROM cleanup_events WHERE deleted_at IS NULL"
             )), 'Cleanup campaigns'],

            [number_format($count(
                "SELECT COUNT(*) FROM blood_donation_records WHERE deleted_at IS NULL"
             )), 'Blood donations'],

            [number_format($count(
                "SELECT COUNT(*) FROM organizations WHERE verified_at IS NOT NULL AND deleted_at IS NULL"
             )), 'Verified organizations'],
        ];
    }

    /**
     * The landing screen for every signed-in role except the administrator.
     *
     * Four routes point here - `/dashboard` (web.php), `/org/dashboard`
     * (cleanup.php), `/gn/dashboard` (reuse.php) and `/blood-bank/dashboard`
     * (blood.php) - because `Auth::homeFor()` sends each role somewhere
     * different. Rather than ask three other members to repoint their route
     * files, this method picks the view. The role middleware on those routes
     * already guarantees the caller matches the path they arrived on.
     */
    public function dashboard(): void
    {
        $view = match (Auth::role()) {
            ROLE_ORGANIZATION => 'home.dashboard-org',
            ROLE_GN           => 'home.dashboard-gn',
            ROLE_BLOOD_BANK   => 'home.dashboard-blood-bank',
            default           => 'home.dashboard-community',
        };

        $this->view($view, ['title' => 'Dashboard'] + $this->dashboardData());
    }

    /**
     * Per-role figures for the dashboard, read from the database.
     *
     * Every role now goes through Services\DashboardService. Four of the five
     * screens used to draw hard-coded arrays - Keerthy Ganesh's homepage
     * claimed 14 cleanup camps joined and 23 items donated on an account that
     * had done neither - and a number on a dashboard is a claim about the
     * signed-in account, not a placeholder. With no seed content most of them
     * read 0, which is the true answer.
     */
    private function dashboardData(): array
    {
        $dash = new \Services\DashboardService();
        $user = Auth::user() ?? [];
        $id   = (int) ($user['id'] ?? 0);
        $city = isset($user['city_id']) ? (int) $user['city_id'] : null;

        if (Auth::role() === ROLE_BLOOD_BANK) {
            $campaigns = new \Models\Blood\BloodCampaign();

            return [
                'camps'  => $campaigns->forOrganiser($id),
                'counts' => $campaigns->statusCountsFor($id),
                'stats'  => $campaigns->organiserStats($id),
            ];
        }

        if (Auth::role() === ROLE_ORGANIZATION) {
            return $dash->organiser($id, $this->organisationId($id));
        }

        if (Auth::role() === ROLE_GN) {
            return $dash->gn($id, $this->organisationId($id));
        }

        return $dash->community($id, $city);
    }

    /**
     * The `organizations` row behind this account, or null.
     *
     * `cleanup_events.organization_id` points at that row rather than at the
     * account, so a campaign count needs the profile id, not the user id. An
     * organization that has not been given a profile row - and a GS officer who
     * has not hosted a campaign yet - simply has none, and every campaign
     * figure reads 0.
     */
    private function organisationId(int $userId): ?int
    {
        $org = (new \Models\Organization())->findBy('user_id', $userId);
        return $org ? (int) $org['id'] : null;
    }

    public function about(): void
    {
        // Same template, so it needs the same counts or $stats is undefined.
        $this->view('home.landing', [
            'title' => 'About',
            'stats' => $this->publicStats(),
        ], 'guest');
    }
}
