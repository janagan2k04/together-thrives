<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * A dengue risk alert raised against one district.
 *
 * The table has no `deleted_at` and no `updated_at`, so soft delete is off and
 * the inherited update() cannot be used. An alert is a statement made on a day;
 * a later reading is a new row, which is why `latestPerDistrict()` exists and
 * nothing edits an old one.
 *
 * The table used to record a district, a risk level, a case count, a date and
 * who raised it, and nothing else - so an alert could only say "Colombo, high
 * risk". `address` and `description` were added on 2026-09-18, along with the
 * `dengue_alert_images` child table, because a district is 25 rows wide and
 * does not tell a reader which lane to go and look at. See [[DengueAlertImage]].
 *
 * `source_file` is for a health-ministry spreadsheet an alert was read out of.
 * Nothing uploads one yet, so it stays NULL rather than being filled with a
 * placeholder.
 */
class DengueRiskDistrict extends Model
{
    protected string $table = 'dengue_risk_districts';
    protected array  $fillable = [
        'district_id', 'risk_level', 'address', 'description', 'case_count',
        'source_file', 'reported_on', 'added_by',
    ];
    protected bool $softDelete = false;

    /** `risk_level` ENUM, worded as the screen shows it. */
    public const LEVELS = [
        'high'   => 'High risk',
        'medium' => 'Medium risk',
        'low'    => 'Low risk',
    ];

    public const TONES = [
        'high'   => 'red',
        'medium' => 'amber',
        'low'    => 'green',
    ];

    /**
     * Standing public-health advice.
     *
     * Not data about the platform - it is the same guidance the health ministry
     * publishes, and it is true whether or not anybody has raised an alert. It
     * lives here so the public screen and the admin screen cannot show different
     * advice.
     */
    public const PREVENTION = [
        'Empty and scrub water containers weekly',
        'Clear blocked roof gutters and drains',
        'Cover water storage tanks tightly',
        'Dispose of tyres, tins and coconut shells',
        'Seek medical advice for fever lasting 2+ days',
    ];

    /** An alert counts as current for this many days after it was reported. */
    public const ACTIVE_DAYS = 30;

    /** Field caps, so the form and the validator cannot disagree. */
    public const MAX_ADDRESS     = 255;
    public const MAX_DESCRIPTION = 1000;

    /** Every alert, newest first, with its district and who raised it. */
    public function browse(int $limit = 50): array
    {
        return Database::all(
            "SELECT d.*, ds.name AS district, u.full_name AS author, u.role AS author_role
               FROM dengue_risk_districts d
               JOIN districts ds ON ds.id = d.district_id
               JOIN users u      ON u.id = d.added_by
           ORDER BY d.reported_on DESC, d.id DESC
              LIMIT " . (int) $limit
        );
    }

    /**
     * Case counts per district for the sidebar, highest first.
     *
     * The LATEST reading per district, not the sum: two readings a week apart
     * are two measurements of the same thing, and adding them would double-count
     * every case.
     */
    public function latestPerDistrict(): array
    {
        $rows = Database::all(
            "SELECT ds.name, d.case_count
               FROM dengue_risk_districts d
               JOIN districts ds ON ds.id = d.district_id
              WHERE d.case_count IS NOT NULL
                AND d.id = (SELECT MAX(d2.id) FROM dengue_risk_districts d2
                             WHERE d2.district_id = d.district_id)
           ORDER BY d.case_count DESC"
        );

        $out = [];
        foreach ($rows as $row) {
            $out[$row['name']] = (int) $row['case_count'];
        }
        return $out;
    }

    /**
     * The counters along the top.
     *
     * `$districtId` is the signed-in account's own district, or null for a guest
     * or an account with no district set - in which case "near you" has no
     * answer and the tile is left out by the caller.
     */
    public function stats(?int $districtId, int $userId): array
    {
        $since = date('Y-m-d', strtotime('-' . self::ACTIVE_DAYS . ' days'));

        return [
            'active' => (int) Database::value(
                'SELECT COUNT(*) FROM dengue_risk_districts WHERE reported_on >= ?',
                [$since]
            ),
            'near' => $districtId ? (int) Database::value(
                'SELECT COUNT(*) FROM dengue_risk_districts
                  WHERE reported_on >= ? AND district_id = ?',
                [$since, $districtId]
            ) : null,
            'mine' => (int) Database::value(
                'SELECT COUNT(*) FROM dengue_risk_districts WHERE added_by = ?',
                [$userId]
            ),
            'districts' => (int) Database::value(
                'SELECT COUNT(DISTINCT district_id) FROM dengue_risk_districts WHERE reported_on >= ?',
                [$since]
            ),
            'cases' => (int) Database::value(
                'SELECT COALESCE(SUM(case_count), 0) FROM dengue_risk_districts
                  WHERE reported_on >= ?',
                [$since]
            ),
        ];
    }

    /**
     * The district the signed-in account lives in.
     *
     * `users.city_id` points at `cities`, which since 2026-08-27 holds one row
     * per district - so this is a join, not a guess. Returns null when the
     * account has no district set.
     */
    public static function districtOf(int $userId): ?int
    {
        $id = Database::value(
            'SELECT c.district_id FROM users u JOIN cities c ON c.id = u.city_id WHERE u.id = ?',
            [$userId]
        );

        return $id ? (int) $id : null;
    }
}
