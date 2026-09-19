<?php
namespace Models\Blood;

use Core\Database;
use Core\Model;

/**
 * One donor's completed screening form.
 *
 * This row is the registration. There is no separate "I am coming to this camp"
 * table in the frozen schema, and there does not need to be one: a donor joins a
 * camp by answering the medical questionnaire for it, so the response IS the
 * sign-up. `campaign_id` names the camp, `critical_request_id` the urgent
 * request, and exactly one of the two is set.
 *
 * `result` is scored from the answers against each question's
 * `disqualifying_answer` - see Services\ScreeningService. `reason` carries the
 * short sentence the donor and the organiser both read, so the verdict never has
 * to be re-derived from the JSON.
 *
 * The table has no `deleted_at` and no `updated_at`, so soft delete is off and
 * the inherited update() cannot be used. A screening is a statement made on a
 * day; answering again writes a new row rather than editing the old one, and
 * `latestFor*()` is what every read uses.
 */
class ScreeningResponse extends Model
{
    protected string $table = 'screening_responses';
    protected array  $fillable = [
        'campaign_id', 'critical_request_id', 'donor_id', 'answers_json', 'result', 'reason',
    ];
    protected bool $softDelete = false;

    /** `result` ENUM, worded as the donor's screens show it. */
    public const RESULTS = [
        'eligible'   => 'Eligible',
        'ineligible' => 'Not Eligible',
        'deferred'   => 'Deferred',
    ];

    /** Badge tone per result, so no view hardcodes a colour. */
    public const TONES = [
        'eligible'   => 'green',
        'ineligible' => 'red',
        'deferred'   => 'amber',
    ];

    public function latestForCampaign(int $donorId, int $campaignId): ?array
    {
        return Database::one(
            'SELECT * FROM screening_responses
              WHERE donor_id = ? AND campaign_id = ?
           ORDER BY id DESC LIMIT 1',
            [$donorId, $campaignId]
        );
    }

    public function latestForRequest(int $donorId, int $requestId): ?array
    {
        return Database::one(
            'SELECT * FROM screening_responses
              WHERE donor_id = ? AND critical_request_id = ?
           ORDER BY id DESC LIMIT 1',
            [$donorId, $requestId]
        );
    }

    /**
     * The camps this donor has registered for - the My Registrations tab.
     *
     * Only the latest response per camp: answering the form twice is a
     * correction, not a second registration.
     */
    public function campsFor(int $donorId): array
    {
        return Database::all(
            "SELECT s.id, s.result, s.reason, s.created_at,
                    c.id AS campaign_id, c.title, c.venue, c.address,
                    c.campaign_date, c.start_time, c.end_time, c.status AS campaign_status,
                    ct.name AS city,
                    u.full_name AS organiser
               FROM screening_responses s
               JOIN blood_campaigns c ON c.id = s.campaign_id
               JOIN cities ct         ON ct.id = c.city_id
               JOIN users u           ON u.id = c.organizer_id
              WHERE s.donor_id = ?
                AND s.campaign_id IS NOT NULL
                AND c.deleted_at IS NULL
                AND s.id = (SELECT MAX(s2.id) FROM screening_responses s2
                             WHERE s2.donor_id = s.donor_id AND s2.campaign_id = s.campaign_id)
           ORDER BY c.campaign_date DESC, s.id DESC",
            [$donorId]
        );
    }

    /**
     * The critical requests this donor has answered.
     *
     * "Contact shared" is not a stored flag: an eligible donor's phone number is
     * what the requester is given, an ineligible one's is not. Deriving it from
     * the result is the only way the table cannot disagree with itself.
     */
    public function requestsFor(int $donorId): array
    {
        return Database::all(
            "SELECT s.id, s.result, s.reason, s.created_at,
                    r.id AS request_id, r.blood_group, r.hospital, r.status AS request_status,
                    ct.name AS city
               FROM screening_responses s
               JOIN critical_blood_requests r ON r.id = s.critical_request_id
               JOIN cities ct                 ON ct.id = r.city_id
              WHERE s.donor_id = ?
                AND s.critical_request_id IS NOT NULL
                AND r.deleted_at IS NULL
                AND s.id = (SELECT MAX(s2.id) FROM screening_responses s2
                             WHERE s2.donor_id = s.donor_id
                               AND s2.critical_request_id = s.critical_request_id)
           ORDER BY s.id DESC",
            [$donorId]
        );
    }

    /**
     * Everyone registered for one camp, for the organiser's detail screen.
     *
     * The phone number is only selected for a donor the screening cleared. An
     * organiser has no reason to call somebody the form deferred, and the donor
     * did not agree to be called by failing a medical questionnaire.
     */
    public function donorsFor(int $campaignId): array
    {
        return Database::all(
            "SELECT s.id, s.result, s.reason, s.created_at,
                    u.id AS donor_id, u.full_name, u.blood_group,
                    CASE WHEN s.result = 'eligible' THEN u.phone ELSE NULL END AS phone,
                    ct.name AS city
               FROM screening_responses s
               JOIN users u    ON u.id = s.donor_id
          LEFT JOIN cities ct  ON ct.id = u.city_id
              WHERE s.campaign_id = ?
                AND s.id = (SELECT MAX(s2.id) FROM screening_responses s2
                             WHERE s2.donor_id = s.donor_id AND s2.campaign_id = s.campaign_id)
           ORDER BY s.created_at ASC",
            [$campaignId]
        );
    }

    /** Donors who answered one critical request, for the requester's screen. */
    public function respondersFor(int $requestId): array
    {
        return Database::all(
            "SELECT s.id, s.result, s.reason, s.created_at,
                    u.id AS donor_id, u.full_name, u.blood_group,
                    CASE WHEN s.result = 'eligible' THEN u.phone ELSE NULL END AS phone,
                    ct.name AS city
               FROM screening_responses s
               JOIN users u    ON u.id = s.donor_id
          LEFT JOIN cities ct  ON ct.id = u.city_id
              WHERE s.critical_request_id = ?
                AND s.id = (SELECT MAX(s2.id) FROM screening_responses s2
                             WHERE s2.donor_id = s.donor_id
                               AND s2.critical_request_id = s.critical_request_id)
           ORDER BY s.created_at ASC",
            [$requestId]
        );
    }

    /** Registrations on one camp, counted the way the organiser's tiles count. */
    public function countForCampaign(int $campaignId): int
    {
        return (int) Database::value(
            'SELECT COUNT(DISTINCT donor_id) FROM screening_responses WHERE campaign_id = ?',
            [$campaignId]
        );
    }
}
