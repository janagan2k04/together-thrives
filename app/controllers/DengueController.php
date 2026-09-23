<?php
namespace Controllers;

use Core\Controller;
use Core\Session;
use Models\DengueAlertImage;
use Services\DengueAlertService;

/**
 * Dengue Risk Alerts, as seen by community members, organizations and Grama
 * Niladhari officers. The administrator sees the same list through
 * `Admin\DengueAlertController`, which adds the moderation controls.
 *
 * Real `dengue_risk_districts` rows. Anyone signed in may raise an alert for a
 * district - dengue is reported by the people who live there - and the table
 * records who raised each one, so an administrator can see where a reading came
 * from.
 *
 * Both controllers build their data through Services\DengueAlertService, so the
 * public screen and the admin screen cannot drift apart. They used to keep
 * separate copies of the same counters beside the same sample array.
 */
class DengueController extends Controller
{
    private DengueAlertService $service;

    public function __construct()
    {
        $this->service = new DengueAlertService();
    }

    public function index(): void
    {
        $this->view('dengue.index', $this->service->forView(false) + [
            'title' => 'Dengue Alert',
        ]);
    }

    public function store(): void
    {
        $data = $this->validate(DengueAlertService::rules());

        try {
            $this->service->record(
                $data,
                (int) auth()['id'],
                DengueAlertService::normalizeFiles($_FILES['photos'] ?? null)
            );
        } catch (\RuntimeException $e) {
            // A rejected upload is the reporter's to fix - show what was wrong
            // and keep everything they typed, rather than a 500.
            $this->bounce(['photos' => [$e->getMessage()]], $data);
        }

        Session::flash('success', 'Alert published. Everyone can see it on the dengue screen.');
        $this->redirect('/dengue');
    }

    /**
     * Stream one alert photo by row id.
     *
     * Not through upload_url(): FileController serves a stored path only to the
     * account that uploaded it or to an admin, which for a photo drawn on a
     * public-health screen would hide it from everyone it is drawn for. Same
     * call the reuse module made at /reuse/image/{id}.
     */
    public function image(string $id): void
    {
        $photo = (new DengueAlertImage())->find((int) $id);
        if (!$photo) {
            $this->abort(404);
        }

        (new \Controllers\FileController())->stream($photo['file_path']);
    }

    /**
     * Send the reporter back to the form with an error the Validator cannot
     * produce - a rejected upload - without losing what they typed.
     *
     * Redirects to the screen itself rather than through Controller::back():
     * the form posts to the page it is drawn on, so the destination is known,
     * and back() falls through to the landing page when the browser sends no
     * referrer.
     */
    private function bounce(array $errors, array $data): never
    {
        Session::flashInput($data, $errors);
        Session::flash('error', 'Please correct the highlighted fields.');
        $this->redirect('/dengue');
    }
}
