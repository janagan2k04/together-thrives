<?php
namespace Controllers;

use Core\Config;
use Core\Controller;
use Core\Session;
use Services\PasswordResetService;

/**
 * The forgotten-password flow: ask for an address, click the link, choose a new
 * password. All four actions are guest-only - see the route group in web.php.
 */
class PasswordController extends Controller
{
    /**
     * Shown whether or not the address was recognised. Anything more specific
     * would turn this form into a way of testing which addresses are registered.
     */
    private const SENT_MESSAGE =
        'If that email address has an account with us, a reset link is on its way. '
        . 'The link is valid for one hour.';

    private PasswordResetService $resets;

    public function __construct()
    {
        $this->resets = new PasswordResetService();
    }

    public function showForgot(): void
    {
        $this->view('auth.forgot-password', [
            'title' => 'Forgot Password',
            // Development only - see sendLink() below.
            'devLink' => $this->pullDevLink(),
        ], 'auth-centre');
    }

    public function sendLink(): void
    {
        $data = $this->validate(['email' => 'required|email']);

        $link = $this->resets->issue($data['email']);

        Session::flash('success', self::SENT_MESSAGE);

        // Nothing sends mail in this deliverable, so without this the link only
        // exists in storage/logs/app.log - which nobody testing the form is
        // going to read. Strictly debug-only: on a real deployment APP_DEBUG is
        // false and handing the link straight to the browser would mean anyone
        // could reset anyone's password by typing their address.
        if ($link !== null && Config::get('debug')) {
            Session::set('_dev_reset_link', $link);
        }

        $this->redirect('/forgot-password');
    }

    public function showReset(string $token): void
    {
        // Check before rendering so an expired link says so immediately, rather
        // than after the visitor has typed a new password twice.
        if (!$this->resets->isRedeemable($token)) {
            Session::flash('error', 'That reset link is invalid or has expired. Please request a new one.');
            $this->redirect('/forgot-password');
        }

        $this->view('auth.reset-password', [
            'title' => 'Choose a New Password',
            'token' => $token,
        ], 'auth-centre');
    }

    public function reset(): void
    {
        $data = $this->validate([
            'token'            => 'required',
            'password'         => 'required|password',
            'password_confirm' => 'required|matches:password',
        ]);

        // Re-checked here, not just in showReset - the token could have expired
        // or been spent in another tab while this form sat open.
        if (!$this->resets->complete($data['token'], $data['password'])) {
            Session::flash('error', 'That reset link is invalid or has already been used. Please request a new one.');
            $this->redirect('/forgot-password');
        }

        Session::flash('success', 'Your password has been updated. Please sign in.');
        $this->redirect('/login');
    }

    /** Read and clear the one-shot development link set by sendLink(). */
    private function pullDevLink(): ?string
    {
        $link = Session::get('_dev_reset_link');
        Session::forget('_dev_reset_link');

        return Config::get('debug') ? $link : null;
    }
}
