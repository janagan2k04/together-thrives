<?php
namespace Controllers;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Report;

/**
 * "Report a Problem" - the sidebar's Report link.
 *
 * Writes a `reports` row, which is what the administrator reads at
 * /admin/complaints. The two used to be unconnected: this form flashed a thank
 * you and stored nothing, and the admin screen listed invented complaints, so a
 * report a user actually filed went nowhere and an administrator saw four that
 * nobody had sent.
 *
 * The area picker is folded into `reason` rather than into `content_type`: the
 * ENUM there names post types, and "my account or sign-in" is not one. See
 * Models\Report for how a report with no specific post to point at is filed.
 */
class ReportController extends Controller
{
    /** The areas the form offers, and how each is worded back to an admin. */
    public const AREAS = [
        'general'  => 'General / not sure',
        'account'  => 'My account or sign-in',
        'reuse'    => 'Reuse items',
        'cleanup'  => 'Cleanup / volunteering',
        'donation' => 'Item or fund donation',
        'blood'    => 'Blood donation',
        'content'  => 'Inappropriate or false content',
    ];

    private Report $reports;

    public function __construct()
    {
        $this->reports = new Report();
    }

    public function problem(): void
    {
        $this->view('report.problem', [
            'title' => 'Report a Problem',
            'areas' => self::AREAS,
            // What this member has reported before, and what came of it.
            'mine'  => $this->reports->forReporter((int) auth()['id']),
        ]);
    }

    public function submitProblem(): void
    {
        $data = $this->validate([
            'subject'     => 'required|max:120',
            'description' => 'required|max:1000',
        ]);

        $area = (string) Request::post('area');
        if (!isset(self::AREAS[$area])) {
            $area = 'general';
        }

        $this->reports->create([
            'reporter_id'  => (int) auth()['id'],
            'content_type' => 'user',
            'content_id'   => (int) auth()['id'],
            'reason'       => $data['subject'],
            'details'      => self::AREAS[$area] . "\n\n" . $data['description'],
            'status'       => 'open',
        ]);

        Session::flash('success', 'Thank you - your report has been sent to the administrators. '
            . 'You can see it below.');
        $this->redirect('/report');
    }
}
