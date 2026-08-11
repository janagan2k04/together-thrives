<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;

class HomeController extends Controller
{
    public function landing(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::homeFor());
        }
        $this->view('home.landing', ['title' => 'TogetherThrives'], 'guest');
    }

    /**
     * The landing screen for every signed-in role except the administrator.
     *
     * Four routes point here — `/dashboard` (web.php), `/org/dashboard`
     * (cleanup.php), `/gn/dashboard` (reuse.php) and `/blood-bank/dashboard`
     * (blood.php) — because `Auth::homeFor()` sends each role somewhere
     * different. Rather than ask three other members to repoint their route
     * files, this method picks the view. The role middleware on those routes
     * already guarantees the caller matches the path they arrived on.
     */
    public function dashboard(): void
    {
        $view = match (Auth::role()) {
            ROLE_ORGANIZATION => 'home.dashboard-org',
            ROLE_GN           => 'home.dashboard-gn',
            ROLE_BLOOD_BANK   => 'home.dashboard-blood-bank',
            default           => 'home.dashboard-community',
        };

        $this->view($view, ['title' => 'Dashboard']);
    }

    public function about(): void
    {
        $this->view('home.landing', ['title' => 'About'], 'guest');
    }
}
