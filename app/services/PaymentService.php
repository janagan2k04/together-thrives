<?php
namespace Services;

use Core\Database;
use Models\Donation\FundDonation;
use Models\Donation\Payment;
use Models\Notification;

/**
 * The sandbox fund checkout.
 *
 * No card details are collected, no money moves and nothing is sent anywhere -
 * `payments.gateway` is `payhere_sandbox` and the transaction reference is
 * generated here. What is real is everything the platform then does with it: a
 * `fund_donations` row, a `payments` row, the request's `collected_amount`, the
 * requester's notification, and the receipt the donor can print.
 *
 * The three writes go in one transaction. A `fund_donations` row without its
 * payment, or a payment that never reached `collected_amount`, would leave the
 * progress bar disagreeing with the donor wall - and the first place anyone
 * looks at a fundraiser is the bar.
 */
final class PaymentService
{
    /** What the gateway keeps, as a percentage of the gift. */
    private const FEE_PERCENT = 2.5;

    private FundDonation $donations;
    private Payment $payments;

    public function __construct()
    {
        $this->donations = new FundDonation();
        $this->payments  = new Payment();
    }

    public static function feeOn(float $amount): float
    {
        return round($amount * self::FEE_PERCENT / 100, 2);
    }

    /**
     * Take one sandbox payment and return the stored `fund_donations` id.
     *
     * `collected_amount` moves by the gift, not by the gift plus the fee: the
     * fee is what the gateway takes, and the request never receives it.
     */
    public function charge(array $request, int $donorId, float $amount, bool $anonymous): int
    {
        $fee = self::feeOn($amount);

        return Database::transaction(function () use ($request, $donorId, $amount, $fee, $anonymous) {
            $donationId = $this->donations->create([
                'request_id'   => (int) $request['id'],
                'donor_id'     => $donorId,
                'amount'       => $amount,
                'fee'          => $fee,
                'is_anonymous' => $anonymous ? 1 : 0,
                'status'       => 'paid',
            ]);

            $this->payments->create([
                'fund_donation_id' => $donationId,
                'gateway'          => 'payhere_sandbox',
                'transaction_ref'  => $this->reference(),
                'receipt_no'       => Payment::receiptNo($donationId),
                'amount'           => $amount,
                'status'           => 'success',
                'raw_response'     => json_encode([
                    'sandbox' => true,
                    'note'    => 'No card was collected and no money moved.',
                ], JSON_UNESCAPED_UNICODE),
            ]);

            Database::run(
                'UPDATE donation_requests
                    SET collected_amount = collected_amount + ?, updated_at = NOW()
                  WHERE id = ?',
                [$amount, (int) $request['id']]
            );

            return $donationId;
        });
    }

    /**
     * Tell the requester money arrived.
     *
     * Outside the transaction on purpose: a notification that fails must not
     * roll back a payment the donor has already been told succeeded.
     */
    public function notifyRequester(array $request, float $amount, bool $anonymous): void
    {
        $who = $anonymous ? 'An anonymous donor' : (auth()['full_name'] ?? 'A donor');

        (new Notification())->create([
            'user_id'  => (int) $request['requester_id'],
            'title'    => 'A donation arrived',
            'body'     => $who . ' gave ' . format_money($amount) . ' to ' . $request['title'] . '.',
            'category' => 'donation',
            'link'     => '/my/donations/' . $request['id'],
        ]);
    }

    /** A sandbox reference in the shape a real gateway returns. */
    private function reference(): string
    {
        return 'PH-SBX-' . strtoupper(bin2hex(random_bytes(5)));
    }
}
