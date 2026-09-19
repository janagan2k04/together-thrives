<?php
namespace Controllers;

use Core\Controller;
use Core\Request;
use Core\Session;

/**
 * "Support the System" - the fund-donation-to-the-platform screen linked from
 * every sidebar. Until this page existed the link answered 500 on every role,
 * which is the open action the week 1 checklist asked someone to pick up.
 *
 * Donating hands off to the sandbox checkout; nothing is charged.
 */
class SupportController extends Controller
{
    /** Preset amounts drawn on the Figma frame, in LKR. */
    public const AMOUNTS = [500, 1000, 2500, 5000];

    public function index(): void
    {
        $this->view('support.index', [
            'title'   => 'Support',
            'amounts' => self::AMOUNTS,
        ], is_logged_in() ? 'app' : 'guest');
    }

    public function donate(): void
    {
        // The last radio is "Other Amount", which reads its value from the box
        // beside it. Resolve that before validating so the error, if there is
        // one, lands on the field the visitor actually typed in.
        $chosen = Request::post('amount');
        $amount = $chosen === 'other' ? Request::post('other_amount') : $chosen;

        $data = $this->validate(
            ['amount' => 'required|integer|min_value:100'],
            ['amount' => $amount]
        );

        Session::flash('info', 'Sandbox checkout - no money moves and nothing is stored.');
        $this->redirect('/checkout?purpose=support&amount=' . (int) $data['amount']);
    }
}
