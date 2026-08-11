<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Core\Session;
use Models\BloodBank;
use Models\City;
use Models\GramaNiladhari;
use Models\Organization;

/**
 * My Profile and Settings — the two shared account screens.
 *
 * Week 3 renders them; nothing is written yet. `update()` and `saveSettings()`
 * validate the input so the forms behave, then flash and bounce back without
 * touching the row, which is what the interim brief asks for.
 */
class ProfileController extends Controller
{
    public function show(): void
    {
        $this->view('profile.show', [
            'title'  => 'My Profile',
            'detail' => $this->roleDetail(),
            'cities' => (new City())->listWithDistrict(),
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'full_name' => 'required|max:120',
            'email'     => 'required|email',
            'phone'     => 'phone_lk',
        ]);

        Session::flash('info', 'Profile saving arrives with the account backend — nothing was changed.');
        $this->redirect('/profile');
    }

    public function settings(): void
    {
        $this->view('profile.settings', ['title' => 'Settings']);
    }

    public function saveSettings(): void
    {
        Session::flash('info', 'Preferences are not stored yet — this screen is the week 3 render.');
        $this->redirect('/settings');
    }

    /**
     * The role-specific row that sits beside the users row: organizations,
     * grama_niladharis or blood_banks. Community members have none, and that is
     * not an error — the profile simply shows fewer panels.
     */
    private function roleDetail(): ?array
    {
        $id = (int) (auth()['id'] ?? 0);

        return match (Auth::role()) {
            ROLE_ORGANIZATION => (new Organization())->findBy('user_id', $id),
            ROLE_GN           => (new GramaNiladhari())->findBy('user_id', $id),
            ROLE_BLOOD_BANK   => (new BloodBank())->findBy('user_id', $id),
            default           => null,
        };
    }
}
