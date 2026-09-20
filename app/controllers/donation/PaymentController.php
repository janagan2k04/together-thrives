<?php
namespace Controllers\Donation;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Donation\DonationRequest;
use Models\Donation\FundDonation;
use Models\Donation\Payment;
use Services\PaymentService;

/**
 * The sandbox fund checkout and its receipt - frames 4 and 5 of
 * Donation-User.svg.
 *
 * `payments.gateway` is `payhere_sandbox`: no card details are collected - the
 * four card inputs on the checkout form deliberately have no `name` attribute -
 * and no money moves. Everything else is real. Paying writes `fund_donations`,
 * writes `payments`, moves `donation_requests.collected_amount` and notifies the
 * requester, all in one transaction; see Services\PaymentService.
 *
 * The receipt is addressed by the donation, not the request: a donor who gives
 * twice has two receipts, and the second must not overwrite the first. Each is
 * scoped to its donor, so a receipt cannot be read by guessing an id.
 */
class PaymentController extends Controller
{
    private PaymentService $payments;

    public function __construct()
    {
        $this->payments = new PaymentService();
    }

    /** The checkout form. `?amount=` preselects one of the amount pills. */
    public function checkout(string $id): void
    {
        $request = $this->fundRequestOrFail($id);

        $amount = (int) Request::get('amount', 2500);
        if ($amount < 0) {
            $amount = 0;
        }

        $this->view('donation.checkout', [
            'title'   => 'Secure Payment',
            'request' => $request,
            'amount'  => $amount,
        ]);
    }

    /**
     * The Pay button. Validates the amount and the donor's own details; the card
     * fields are not posted and so are not validated.
     */
    public function pay(string $id): void
    {
        $request = $this->fundRequestOrFail($id);

        $data = $this->validate([
            'amount'    => 'required|integer|min_value:100',
            'full_name' => 'required|max:120',
            'email'     => 'required|email',
            'phone'     => 'required|phone_lk',
            'agree'     => 'accepted',
        ]);

        $donationId = $this->payments->charge(
            $request,
            (int) auth()['id'],
            (float) $data['amount'],
            (bool) Request::post('is_anonymous')
        );

        $this->payments->notifyRequester(
            $request,
            (float) $data['amount'],
            (bool) Request::post('is_anonymous')
        );

        Session::flash('success', 'Thank you - your donation has been recorded.');
        $this->redirect('/donations/receipt/' . $donationId);
    }

    /** The receipt for one donation. */
    public function receipt(string $id): void
    {
        $row = (new FundDonation())->receiptFor((int) $id, (int) auth()['id']);
        if (!$row) {
            $this->abort(404, 'Receipt not found.');
        }

        $this->view('donation.receipt', [
            'title'   => 'Payment Successful',
            'receipt' => [
                'receipt_no'      => $row['receipt_no'] ?: Payment::receiptNo((int) $row['id']),
                'transaction_ref' => $row['transaction_ref'] ?: '-',
                'paid_at'         => format_date($row['paid_at'] ?? $row['created_at']),
                'method'          => 'Sandbox gateway - no card was charged',
                'donor'           => trim(($row['donor_name'] ?? '') . ' - ' . ($row['donor_phone'] ?? ''), ' -'),
                'donor_email'     => $row['donor_email'] ?? '',
                'donor_first'     => explode(' ', trim((string) $row['donor_name']))[0] ?? '',
                'recipient'       => $row['requester'],
                'request'         => [
                    'id'            => (int) $row['request_id'],
                    'ref'           => 'DR-' . str_pad((string) $row['request_id'], 5, '0', STR_PAD_LEFT),
                    'title'         => $row['title'],
                    'affected_area' => $row['affected_area'],
                ],
                'amount' => (float) $row['amount'],
                'fee'    => (float) $row['fee'],
                'total'  => (float) $row['amount'] + (float) $row['fee'],
            ],
        ]);
    }

    /**
     * Only a fund request has a checkout. Asking to pay money into an item
     * request is a 404 rather than a redirect, because the URL is wrong rather
     * than the state.
     *
     * Only an approved request takes money - one still waiting on an
     * administrator has no business accepting a payment.
     */
    private function fundRequestOrFail(string $id): array
    {
        $request = (new DonationRequest())->findDetail((int) $id);
        if (!$request || !in_array($request['status'], DonationRequest::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Donation request not found.');
        }

        if ($request['request_type'] !== 'funds') {
            $this->abort(404, 'This request does not take monetary donations.');
        }

        return $request;
    }
}
