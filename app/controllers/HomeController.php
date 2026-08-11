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

    public function dashboard(): void
    {
        $this->view('home.dashboard', ['title' => 'Dashboard']);
    }

    public function about(): void
    {
        $this->view('home.landing', ['title' => 'About'], 'guest');
    }
}
