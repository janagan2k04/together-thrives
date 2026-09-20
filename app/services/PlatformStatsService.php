<?php
namespace Services;

use Core\Database;

/**
 * The platform-wide figures that used to live only on "Analytics & Stats".
 *
 * They are drawn on the admin dashboard now - an administrator should not have
 * to open a second screen to see how the platform is doing - and the analytics
 * screen still reads the same numbers. Assembling them here is what stops the
 * two from drifting apart, the same call `Services\DengueAlertService` made for
 * the public and admin dengue screens.
 *
 * Every figure is a live count. Nothing here is a placeholder.
 */
final class PlatformStatsService
{
    /**
     * Each module spells the same three ideas differently, so the states are
     * mapped rather than assumed:
     *
     *   active    live to the public and still running
     *   finished  ran to completion
     *   blocked   kept off the platform by an administrator. Cleanup has no
     *             `rejected` state - a rejection there is `cancelled` plus a
     *             `cancel_reason` - which is why it is the odd one out.
     */
    private const BUCKETS = [
        'active' => [
            'cleanup_events'    => ['published', 'ongoing'],
            'donation_requests' => ['open'],
            'blood_campaigns'   => ['published', 'ongoing'],
            'reuse_items'       => ['available', 'reserved'],
        ],
        'finished' => [
            'cleanup_events'    => ['completed'],
            'donation_requests' => ['fulfilled'],
            'blood_campaigns'   => ['completed'],
            'reuse_items'       => ['donated'],
        ],
        'blocked' => [
            'cleanup_events'    => ['cancelled'],
            'donation_requests' => ['rejected'],
            'blood_campaigns'   => ['rejected'],
            'reuse_items'       => ['removed'],
        ],
    ];

    /**
     * The eight boxes, already formatted for printing.
     *
     * @return array<int, array{0:string, 1:string}> [label, value]
     */
    public function figures(): array
    {
        $funds = (float) Database::value(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success'"
        );

        return [
            ['Community users', number_format($this->usersWith(ROLE_COMMUNITY, STATUS_ACTIVE))],
            ['Organizations',   number_format($this->usersWith(ROLE_ORGANIZATION, STATUS_ACTIVE))],
            ['Active campaigns',   number_format($this->campaignCount('active'))],
            ['Finished campaigns', number_format($this->campaignCount('finished'))],

            ['Blocked community users', number_format($this->usersWith(ROLE_COMMUNITY, STATUS_SUSPENDED))],
            ['Blocked organizations',   number_format($this->usersWith(ROLE_ORGANIZATION, STATUS_SUSPENDED))],
            ['Blocked content', number_format($this->campaignCount('blocked'))],
            ['Funds through the platform', format_money($funds)],
        ];
    }

    private function usersWith(string $role, string $status): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM users WHERE role = ? AND status = ? AND deleted_at IS NULL",
            [$role, $status]
        );
    }

    /** One figure summed across all four module tables. */
    private function campaignCount(string $bucket): int
    {
        $total = 0;
        foreach (self::BUCKETS[$bucket] as $table => $states) {
            $marks  = implode(',', array_fill(0, count($states), '?'));
            $total += (int) Database::value(
                "SELECT COUNT(*) FROM {$table} WHERE status IN ({$marks}) AND deleted_at IS NULL",
                $states
            );
        }
        return $total;
    }
}
