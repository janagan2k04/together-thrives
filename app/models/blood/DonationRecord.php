<?php
namespace Models\Blood;

use Core\Database;
use Core\Model;

/**
 * A completed blood donation - the My Donations tab and the certificate behind
 * it.
 *
 * Written by the organiser on the camp's donor list once somebody has actually
 * given blood. Registering is `screening_responses`; donating is this. Keeping
 * them apart is the whole reason the "Donations Made" tile can differ from
 * "Camps Joined" without either being wrong.
 *
 * `campaign_id` is nullable because a donation can answer a critical request
 * rather than happen at a camp.
 */
class DonationRecord extends Model
{
    protected string $table = 'blood_donation_records';
    protected array  $fillable = ['donor_id', 'campaign_id', 'donated_at', 'units'];

    /** Whole-blood donors wait four months between donations. */
    public const MONTHS_BETWEEN_DONATIONS = 4;

    /** This donor's history, newest first, with the camp it happened at. */
    public function forDonor(int $donorId): array
    {
        return Database::all(
            "SELECT d.id, d.donated_at, d.units, d.campaign_id,
                    c.title AS camp,
                    ct.name AS city,
                    u.full_name AS organiser
               FROM blood_donation_records d
          LEFT JOIN blood_campaigns c ON c.id = d.campaign_id
          LEFT JOIN cities ct         ON ct.id = c.city_id
          LEFT JOIN users u           ON u.id = c.organizer_id
              WHERE d.donor_id = ? AND d.deleted_at IS NULL
           ORDER BY d.donated_at DESC, d.id DESC",
            [$donorId]
        );
    }

    /** Everyone recorded as having donated at one camp. */
    public function forCampaign(int $campaignId): array
    {
        return Database::all(
            'SELECT d.*, u.full_name, u.blood_group
               FROM blood_donation_records d
               JOIN users u ON u.id = d.donor_id
              WHERE d.campaign_id = ? AND d.deleted_at IS NULL
           ORDER BY d.donated_at DESC',
            [$campaignId]
        );
    }

    public function hasDonatedAt(int $donorId, int $campaignId): bool
    {
        return (bool) Database::value(
            'SELECT COUNT(*) FROM blood_donation_records
              WHERE donor_id = ? AND campaign_id = ? AND deleted_at IS NULL',
            [$donorId, $campaignId]
        );
    }

    public function countFor(int $donorId): int
    {
        return (int) Database::value(
            'SELECT COUNT(*) FROM blood_donation_records
              WHERE donor_id = ? AND deleted_at IS NULL',
            [$donorId]
        );
    }

    /**
     * Distinct camps this donor has given blood at.
     *
     * Not the same as countFor(): `campaign_id` is nullable, because a donation
     * can answer a critical request instead of happening at a camp, and two
     * donations at the same camp are still one camp. The All tab's "Camps
     * Donated At" tile is exactly this figure, and countFor() answered it with
     * the number of donations.
     */
    public function campCountFor(int $donorId): int
    {
        return (int) Database::value(
            'SELECT COUNT(DISTINCT campaign_id) FROM blood_donation_records
              WHERE donor_id = ? AND campaign_id IS NOT NULL AND deleted_at IS NULL',
            [$donorId]
        );
    }

    /** Total units given, which is not the same as the number of donations. */
    public function unitsFor(int $donorId): float
    {
        return (float) Database::value(
            'SELECT COALESCE(SUM(units), 0) FROM blood_donation_records
              WHERE donor_id = ? AND deleted_at IS NULL',
            [$donorId]
        );
    }

    /** The date of the last donation, or null for a first-time donor. */
    public function lastDonationDate(int $donorId): ?string
    {
        $date = Database::value(
            'SELECT MAX(donated_at) FROM blood_donation_records
              WHERE donor_id = ? AND deleted_at IS NULL',
            [$donorId]
        );

        return $date ?: null;
    }

    /**
     * The date this donor may give blood again, or null if they never have.
     *
     * Four months, the interval the screening questionnaire asks about, so the
     * banner on My Donations and question 3 on the form cannot disagree.
     */
    public function eligibleAgainOn(int $donorId): ?string
    {
        $last = $this->lastDonationDate($donorId);
        if (!$last) {
            return null;
        }

        return date('Y-m-d', strtotime($last . ' +' . self::MONTHS_BETWEEN_DONATIONS . ' months'));
    }
}
