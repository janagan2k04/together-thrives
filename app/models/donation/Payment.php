<?php
namespace Models\Donation;

use Core\Database;
use Core\Model;

/**
 * The gateway transaction behind one fund donation.
 *
 * `gateway` defaults to `payhere_sandbox` in the frozen schema and this stays a
 * sandbox flow: no card field is ever posted, let alone stored - the four card
 * inputs on the checkout form deliberately have no `name` attribute - and no
 * money moves. What is real is the record: a `payments` row with a receipt
 * number and a transaction reference, which is what the receipt reads back.
 *
 * The table has a `created_at` and nothing else - no `deleted_at`, no
 * `updated_at` - so soft delete is off and the inherited update() cannot be
 * used. A payment is a record of an attempt and is never edited.
 */
class Payment extends Model
{
    protected string $table = 'payments';
    protected array  $fillable = [
        'fund_donation_id', 'gateway', 'transaction_ref', 'receipt_no',
        'amount', 'status', 'raw_response',
    ];
    protected bool $softDelete = false;

    public function forDonation(int $fundDonationId): ?array
    {
        return Database::one(
            'SELECT * FROM payments WHERE fund_donation_id = ? ORDER BY id DESC LIMIT 1',
            [$fundDonationId]
        );
    }

    /**
     * A receipt number that reads sensibly off a printed sheet and cannot
     * collide: the year, then the donation's own id zero-padded.
     *
     * Derived from the id rather than random, so re-issuing a receipt for the
     * same donation always produces the same number.
     */
    public static function receiptNo(int $fundDonationId): string
    {
        return sprintf('TT-RCPT-%s-%06d', date('Y'), $fundDonationId);
    }
}
