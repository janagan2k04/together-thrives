<?php
namespace Services;

use Core\Auth;
use Core\Database;

/**
 * Every figure on every role's dashboard, counted from real rows.
 *
 * Until 2026-08-27 four of the five dashboards drew hard-coded arrays: Keerthy
 * Fernando's homepage claimed 14 cleanup camps joined, 23 items donated and 8
 * volunteer hours on an account that had done none of it, and the organisation
 * screen listed three campaigns that were not in the database. A number on a
 * dashboard is not a placeholder, it is a claim about the signed-in account, so
 * every one of them is a query now. With no seed content most of them read 0,
 * which is the correct answer to "how many, so far".
 *
 * One method per role, each returning exactly the keys that role's view reads.
 * Counts come straight from SQL rather than through the module models, because
 * a dashboard wants totals and the models return rows.
 */
class DashboardService
{
    /** Figures and panels for a community member. */
    public function community(int $userId, ?int $cityId): array
    {
        $one = static fn(string $sql, array $p = []): int => (int) Database::value($sql, $p);

        return [
            'impact' => [
                ['Cleanup Camps Joined', $one(
                    "SELECT COUNT(*) FROM event_registrations
                      WHERE user_id = ? AND status <> 'cancelled' AND deleted_at IS NULL",
                    [$userId]), 'green'],
                ['Items Donated', $one(
                    "SELECT COUNT(*) FROM reuse_items
                      WHERE donor_id = ? AND status = ? AND deleted_at IS NULL",
                    [$userId, ITEM_DONATED]), 'amber'],
                ['Blood Donations', $one(
                    "SELECT COUNT(*) FROM blood_donation_records
                      WHERE donor_id = ? AND deleted_at IS NULL",
                    [$userId]), 'red'],
                ['Items Listed', $one(
                    "SELECT COUNT(*) FROM reuse_items
                      WHERE donor_id = ? AND deleted_at IS NULL",
                    [$userId]), 'blue'],
            ],

            // Cleanup camps this member has actually signed up for.
            'joined' => Database::all(
                "SELECT e.id, e.title, e.event_date AS on_date, e.status,
                        o.org_name AS by_whom
                   FROM event_registrations r
                   JOIN cleanup_events e ON e.id = r.event_id
                   JOIN organizations o  ON o.id = e.organization_id
                  WHERE r.user_id = ? AND r.status <> 'cancelled'
                    AND r.deleted_at IS NULL AND e.deleted_at IS NULL
               ORDER BY e.event_date DESC
                  LIMIT 6",
                [$userId]
            ),

            // The reuse requests this member has made, which is the only thing
            // "Your Requests" can honestly show today.
            'requests' => Database::all(
                "SELECT r.id, r.status, i.title, c.name AS category, r.created_at
                   FROM item_requests r
                   JOIN reuse_items i     ON i.id = r.item_id
                   JOIN item_categories c ON c.id = i.category_id
                  WHERE r.requester_id = ? AND r.deleted_at IS NULL
               ORDER BY r.created_at DESC
                  LIMIT 4",
                [$userId]
            ),

            'alerts' => $this->alerts($cityId),
            'nearby' => $this->nearby($cityId),
        ];
    }

    /**
     * Figures and panels for whoever hosts campaigns - an organization, and
     * since 2026-08-27 a Grama Niladhari officer, who owns campaigns through the
     * same `organizations` profile row.
     */
    public function organiser(int $userId, ?int $organizationId): array
    {
        $one   = static fn(string $sql, array $p = []): int => (int) Database::value($sql, $p);
        $orgId = $organizationId ?? 0;

        return [
            'counts' => [
                'campaigns' => $one(
                    "SELECT COUNT(*) FROM cleanup_events
                      WHERE organization_id = ? AND status = ? AND deleted_at IS NULL",
                    [$orgId, EVENT_PUBLISHED]),
                'campaignsPending' => $one(
                    "SELECT COUNT(*) FROM cleanup_events
                      WHERE organization_id = ? AND status = ? AND deleted_at IS NULL",
                    [$orgId, EVENT_DRAFT]),
                'volunteers' => $one(
                    "SELECT COUNT(*) FROM event_registrations r
                       JOIN cleanup_events e ON e.id = r.event_id
                      WHERE e.organization_id = ? AND r.status <> 'cancelled'
                        AND r.deleted_at IS NULL AND e.deleted_at IS NULL",
                    [$orgId]),
                'requests' => $one(
                    "SELECT COUNT(*) FROM donation_requests
                      WHERE requester_id = ? AND deleted_at IS NULL",
                    [$userId]),
                'requestsPending' => $one(
                    "SELECT COUNT(*) FROM donation_requests
                      WHERE requester_id = ? AND status = ? AND deleted_at IS NULL",
                    [$userId, REQUEST_PENDING]),
                'camps' => $one(
                    "SELECT COUNT(*) FROM blood_campaigns
                      WHERE organizer_id = ? AND deleted_at IS NULL",
                    [$userId]),
                'campsPending' => $one(
                    "SELECT COUNT(*) FROM blood_campaigns
                      WHERE organizer_id = ? AND status = ? AND deleted_at IS NULL",
                    [$userId, MOD_PENDING]),
            ],

            'campaigns' => Database::all(
                "SELECT e.id, e.title, e.status, e.event_date, e.volunteer_limit,
                        (SELECT COUNT(*) FROM event_registrations r
                          WHERE r.event_id = e.id AND r.status <> 'cancelled'
                            AND r.deleted_at IS NULL) AS volunteers
                   FROM cleanup_events e
                  WHERE e.organization_id = ? AND e.deleted_at IS NULL
               ORDER BY e.event_date DESC
                  LIMIT 6",
                [$orgId]
            ),

            'requests' => Database::all(
                "SELECT id, title, status, request_type, target_amount, collected_amount
                   FROM donation_requests
                  WHERE requester_id = ? AND deleted_at IS NULL
               ORDER BY created_at DESC
                  LIMIT 6",
                [$userId]
            ),
        ];
    }

    /**
     * A Grama Niladhari officer. Everything organiser() returns, plus this
     * role's own disaster reports - the officer now hosts cleanup and blood
     * campaigns too, so the campaign figures are the same query.
     */
    public function gn(int $userId, ?int $organizationId): array
    {
        $base = $this->organiser($userId, $organizationId);
        $one  = static fn(string $sql, array $p = []): int => (int) Database::value($sql, $p);

        $base['counts']['reports'] = $one(
            "SELECT COUNT(*) FROM disaster_reports
              WHERE reporter_id = ? AND deleted_at IS NULL",
            [$userId]);
        $base['counts']['reportsPending'] = $one(
            "SELECT COUNT(*) FROM disaster_reports
              WHERE reporter_id = ? AND status = ? AND deleted_at IS NULL",
            [$userId, MOD_PENDING]);

        $base['reports'] = Database::all(
            "SELECT d.id, d.title, d.disaster_type, d.severity, d.status, d.created_at,
                    ct.name AS district
               FROM disaster_reports d
               JOIN cities ct ON ct.id = d.city_id
              WHERE d.reporter_id = ? AND d.deleted_at IS NULL
           ORDER BY d.created_at DESC
              LIMIT 6",
            [$userId]
        );

        return $base;
    }

    // ---------- shared panels ----------

    /**
     * Live alerts for the member's own district: dengue risk first, then any
     * announcement an administrator has published to this role.
     */
    private function alerts(?int $cityId): array
    {
        $rows = [];

        if ($cityId) {
            $risks = Database::all(
                "SELECT d.name AS district, r.risk_level, r.case_count, r.reported_on
                   FROM dengue_risk_districts r
                   JOIN districts d ON d.id = r.district_id
                   JOIN cities c    ON c.district_id = d.id
                  WHERE c.id = ? AND r.risk_level IN ('high', 'medium')
               ORDER BY r.reported_on DESC
                  LIMIT 3",
                [$cityId]
            );

            foreach ($risks as $r) {
                $rows[] = [
                    ucfirst($r['risk_level']),
                    'Dengue risk in ' . $r['district'],
                    $r['case_count']
                        ? $r['case_count'] . ' cases reported.'
                        : 'Take precautions in your area.',
                    format_date($r['reported_on']),
                ];
            }
        }

        $notices = Database::all(
            "SELECT title, body, published_at
               FROM announcements
              WHERE published_at IS NOT NULL AND deleted_at IS NULL
                AND audience IN ('all', ?)
           ORDER BY published_at DESC
              LIMIT 3",
            [Auth::role() ?? ROLE_COMMUNITY]
        );

        foreach ($notices as $a) {
            $rows[] = ['Notice', $a['title'], str_limit($a['body'], 120), time_ago($a['published_at'])];
        }

        return array_slice($rows, 0, 4);
    }

    /** Approved cleanup campaigns and blood camps still to come in this district. */
    private function nearby(?int $cityId): array
    {
        if (!$cityId) {
            return [];
        }

        return Database::all(
            "SELECT 'Cleanup Campaign' AS kind, e.title, e.event_date AS on_date,
                    CONCAT('/cleanup/', e.id) AS link
               FROM cleanup_events e
              WHERE e.city_id = ? AND e.status = ? AND e.event_date >= CURDATE()
                AND e.deleted_at IS NULL
              UNION ALL
             SELECT 'Blood Camp' AS kind, c.title, c.campaign_date AS on_date,
                    CONCAT('/blood/', c.id) AS link
               FROM blood_campaigns c
              WHERE c.city_id = ? AND c.status = ? AND c.campaign_date >= CURDATE()
                AND c.deleted_at IS NULL
           ORDER BY on_date ASC
              LIMIT 5",
            [$cityId, EVENT_PUBLISHED, $cityId, EVENT_PUBLISHED]
        );
    }
}
