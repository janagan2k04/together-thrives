<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Core\FileUploader;
use Core\Request;
use Core\Session;
use Models\BloodBank;
use Models\City;
use Models\GramaNiladhari;
use Models\Organization;
use Models\User;

/**
 * My Profile and Settings - the two shared account screens.
 *
 * Both used to validate the input, flash "saving arrives with the account
 * backend", and throw it away. They write to `users` now: a form that says
 * Save Changes and changes nothing is worse than one that is not there, and the
 * flash explaining why was one of the implementation notes the group asked to
 * have taken off every page.
 *
 * `ds_division` and `gn_division` arrived with migration 2026_08_27 and are
 * asked of a community member only - the other three roles hold a division
 * rather than living in one, and their own profile table already records it.
 *
 * NIC, role and status are deliberately not editable here. An administrator
 * verified them; letting the account rewrite its own NIC after approval would
 * undo the check.
 *
 * The profile photo writes `users.avatar_path`, a column that had been in the
 * frozen schema since week 1 with nothing writing it. It is served by user id
 * at /avatar/{id} rather than through upload_url(): FileController streams a
 * file only to the account that uploaded it or to an admin, which for an avatar
 * would hide it from precisely the people it is drawn for. The same call the
 * reuse module made for item photos.
 */
class ProfileController extends Controller
{
    /** Values allowed by the blood_group ENUM in schema.sql. */
    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

    /** Values allowed by the locale ENUM. */
    private const LOCALES = ['en', 'si', 'ta'];

    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function show(): void
    {
        $this->view('profile.show', [
            'title'  => 'My Profile',
            'detail' => $this->roleDetail(),
            'cities' => (new City())->listAll(),
        ]);
    }

    public function update(): void
    {
        $id      = (int) auth()['id'];
        $cityIds = implode(',', array_column((new City())->listAll(), 'id'));

        $data = $this->validate([
            'full_name'   => 'required|max:120',
            'email'       => 'required|email',
            'phone'       => 'phone_lk',
            'city_id'     => 'integer|in:' . $cityIds,
            'blood_group' => 'in:' . implode(',', self::BLOOD_GROUPS),
            'locale'      => 'in:' . implode(',', self::LOCALES),
            'ds_division' => 'max:120',
            'gn_division' => 'max:120',
        ]);

        // `users.email` is UNIQUE, so a duplicate would surface as a database
        // error rather than a field error. Ask first, excluding this account.
        if ($this->users->emailTaken($data['email'], $id)) {
            Session::flashInput($data, ['email' => ['That email address is already registered.']]);
            $this->back();
        }

        // An empty select is "not stated", not the string "". city_id is a
        // foreign key and blood_group an ENUM; neither accepts one.
        $fields = [
            'full_name'   => $data['full_name'],
            'email'       => $data['email'],
            'phone'       => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
            'city_id'     => ($data['city_id'] ?? '') !== '' ? (int) $data['city_id'] : null,
            'blood_group' => ($data['blood_group'] ?? '') !== '' ? $data['blood_group'] : null,
        ];

        if (($data['locale'] ?? '') !== '') {
            $fields['locale'] = $data['locale'];
        }

        // Only a community member is shown the two division fields, so only a
        // community member may write them. Never trust the posted form.
        if (role_is(ROLE_COMMUNITY)) {
            $fields['ds_division'] = ($data['ds_division'] ?? '') !== '' ? $data['ds_division'] : null;
            $fields['gn_division'] = ($data['gn_division'] ?? '') !== '' ? $data['gn_division'] : null;
        }

        // Changing the address un-confirms it: the old confirmation was of an
        // address this account no longer uses.
        if ($data['email'] !== auth()['email']) {
            $fields['email_verified_at'] = null;
        }

        $this->users->update($id, $fields);

        Session::flash('success', $data['email'] !== auth()['email']
            ? 'Profile updated. Confirm your new email address to finish.'
            : 'Profile updated.');
        $this->redirect('/profile');
    }

    /**
     * Replace the profile photo.
     *
     * The old file is deleted only after the new path is stored: a failed write
     * leaving the account with a path to nothing is worse than one orphan file.
     */
    public function updateAvatar(): void
    {
        $id   = (int) auth()['id'];
        $file = Request::file('avatar');

        if (!$file) {
            Session::flash('error', 'Choose an image first.');
            $this->redirect('/profile');
        }

        try {
            $path = FileUploader::store($file, 'avatars', ['jpg', 'png', 'webp'], 5);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/profile');
        }

        $previous = auth()['avatar_path'] ?? null;
        $this->users->update($id, ['avatar_path' => $path]);
        FileUploader::delete($previous);

        Session::flash('success', 'Profile photo updated.');
        $this->redirect('/profile');
    }

    /** Go back to the initial in a circle. */
    public function removeAvatar(): void
    {
        $previous = auth()['avatar_path'] ?? null;

        $this->users->update((int) auth()['id'], ['avatar_path' => null]);
        FileUploader::delete($previous);

        Session::flash('success', 'Profile photo removed.');
        $this->redirect('/profile');
    }

    /**
     * Stream one account's photo, by account id.
     *
     * Open to guests, because the pages that draw an avatar - the reuse donor
     * card, the organisation directory - are open to guests. Only the avatar is
     * reachable this way; every other upload stays behind FileController's
     * owner check.
     */
    public function avatar(string $id): void
    {
        $user = $this->users->find((int) $id);
        if (!$user || empty($user['avatar_path'])) {
            $this->abort(404);
        }

        (new FileController())->stream($user['avatar_path']);
    }

    public function settings(): void
    {
        $this->view('profile.settings', ['title' => 'Settings']);
    }

    /**
     * `locale` is the only preference the frozen schema stores, so it is the
     * only one this screen offers as of 2026-09-04. The four notification and
     * two privacy switches were removed rather than left collecting input that
     * was dropped: a switch that resets itself on the next page load tells the
     * member something is configured when it is not. Giving them a home needs a
     * table and a group decision.
     */
    public function saveSettings(): void
    {
        $data = $this->validate(['locale' => 'in:' . implode(',', self::LOCALES)]);

        if (($data['locale'] ?? '') !== '') {
            $this->users->update((int) auth()['id'], ['locale' => $data['locale']]);
        }

        Session::flash('success', 'Settings saved.');
        $this->redirect('/settings');
    }

    /**
     * The role-specific row that sits beside the users row: organizations,
     * grama_niladharis or blood_banks. Community members have none, and that is
     * not an error - the profile simply shows fewer panels.
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
