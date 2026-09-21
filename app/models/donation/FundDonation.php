<?php
namespace Models\Donation;

use Core\Database;
use Core\Model;

/**
 * Money given to one fund request.
 *
 * The row is created `pending` and moves to `paid` when the gateway comes back,
 * so a donation that never completes leaves a trace rather than vanishing. Only
 * `paid` rows are counted anywhere - the donor wall, the progress bar and the
 * donor's own total all filter on it.
 *
 * `donor_id` is nullable because the schema allows an unauthenticated gift; the
 * checkout requires a signed-in donor, so it is always set today. `is_anonymous`
 * hides the name on the public wall without losing who gave, which is what the
 * requester's receipt still needs.
 */
class FundDonation extends Model
{
    protected string $table = 'fund_donations';
    protected array  $fillable = [
        'request_id', 'donor_id', 'amount', 'fee', 'is_anonymous', 'status',
    ];

    /**
     * The donor wall on a fund request - paid gifts only, newest first.
     *
     * An anonymous gift keeps its amount and loses its name. That is the whole
     * promise the checkbox makes, so it is kept here rather than left to the
     * view to remember.
     */
    public function wallFor(int $requestId, int $limit = 20): array
    {
        return Database::all(
            "SELECT f.id, f.amount, f.is_anonymous, f.created_at,
                    CASE WHEN f.is_anonymous = 1 THEN NULL ELSE u.full_name END AS donor
               FROM fund_donations f
          LEFT JOIN users u ON u.id = f.donor_id
              WHERE f.request_id = ? AND f.status = 'paid' AND f.deleted_at IS NULL
           ORDER BY f.created_at DESC
              LIMIT " . (int) $limit,
            [$requestId]
        );
    }

    /** Everything this donor has given, newest first, with the request. */
    public function forDonor(int $donorId): array
    {
        return Database::all(
            "SELECT f.id, f.amount, f.fee, f.status, f.created_at,
                    r.id AS request_id, r.title, r.request_type, r.affected_area,
                    r.target_amount, r.collected_amount, r.status AS request_status,
                    u.full_name AS requester
               FROM fund_donations f
               JOIN donation_requests r ON r.id = f.request_id
               JOIN users u             ON u.id = r.requester_id
              WHERE f.donor_id = ? AND f.status = 'paid' AND f.deleted_at IS NULL
           ORDER BY f.created_at DESC",
            [$donorId]
        );
    }

    /** Total this donor has actually paid, across every request. */
    public function totalFor(int $donorId): float
    {
        return (float) Database::value(
            "SELECT COALESCE(SUM(amount), 0) FROM fund_donations
              WHERE donor_id = ? AND status = 'paid' AND deleted_at IS NULL",
            [$donorId]
        );
    }

    /** Paid gifts to one request - the "N donors" figure beside the bar. */
    public function countFor(int $requestId): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM fund_donations
              WHERE request_id = ? AND status = 'paid' AND deleted_at IS NULL",
            [$requestId]
        );
    }

    /**
     * The donation behind one receipt, with everything the sheet prints.
     *
     * Scoped to the donor, so a receipt cannot be read by guessing an id.
     */
    public function receiptFor(int $id, int $donorId): ?array
    {
        return Database::one(
            "SELECT f.*, p.receipt_no, p.transaction_ref, p.gateway, p.created_at AS paid_at,
                    r.title, r.affected_area, r.request_type,
                    u.full_name AS requester,
                    d.full_name AS donor_name, d.email AS donor_email, d.phone AS donor_phone
               FROM fund_donations f
          LEFT JOIN payments p          ON p.fund_donation_id = f.id
               JOIN donation_requests r ON r.id = f.request_id
               JOIN users u             ON u.id = r.requester_id
          LEFT JOIN users d             ON d.id = f.donor_id
              WHERE f.id = ? AND f.donor_id = ? AND f.deleted_at IS NULL
              LIMIT 1",
            [$id, $donorId]
        );
    }
}
