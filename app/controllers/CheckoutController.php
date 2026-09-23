<?php
namespace Controllers;

use Core\Controller;
use Core\Request;
use Core\Session;

/**
 * Sandbox payment checkout.
 *
 * One screen serving every module that takes money - supporting the platform,
 * a fund donation to a relief request, a campaign contribution - because they
 * all collect the same card fields. The gateway is not wired: `pay()` validates
 * the form, writes nothing, and shows the receipt screen.
 *
 * `payments` and `fund_donations` are Member 2's tables. Nothing here touches
 * them, so the real integration can replace `pay()` without unpicking anything.
 */
class CheckoutController extends Controller
{
    private const PURPOSES = [
        'support'  => 'Support the TogetherThrives platform',
        'donation' => 'Contribution to a relief request',
        'campaign' => 'Contribution to a campaign',
    ];

    public function show(): void
    {
        $purpose = (string) Request::get('purpose', 'support');
        $amount  = (int) Request::get('amount', 2500);

        $this->view('checkout.show', [
            'title'   => 'Checkout',
            'purpose' => self::PURPOSES[$purpose] ?? self::PURPOSES['support'],
            'key'     => array_key_exists($purpose, self::PURPOSES) ? $purpose : 'support',
            'amount'  => max(100, $amount),
        ]);
    }

    public function pay(): void
    {
        $data = $this->validate([
            'cardholder'  => 'required|max:120',
            'card_number' => 'required|min:12|max:24',
            'expiry'      => 'required|max:7',
            'cvv'         => 'required|numeric|min:3|max:4',
            'amount'      => 'required|integer|min_value:100',
            'billing'     => 'required|max:255',
        ]);

        Session::flash('success', 'Sandbox payment accepted. No card was charged and nothing was stored.');
        $this->redirect('/checkout/receipt?amount=' . (int) $data['amount']);
    }

    public function receipt(): void
    {
        $this->view('checkout.receipt', [
            'title'  => 'Payment receipt',
            'amount' => max(100, (int) Request::get('amount', 2500)),
            'ref'    => 'TT-SBX-' . strtoupper(substr(md5((string) time()), 0, 8)),
        ]);
    }
}
