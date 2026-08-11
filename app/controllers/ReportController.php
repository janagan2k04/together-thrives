<?php
namespace Controllers;

use Core\Controller;
use Core\Session;

/**
 * "Report a Problem" — the topbar's Report link.
 *
 * The `reports` table in the schema is for reporting *content* (a listing, a
 * campaign, a message) and belongs to whoever builds moderation. This screen is
 * the general bug/problem form from the Figma, so week 3 renders it and the
 * submit button flashes rather than writing a row.
 */
class ReportController extends Controller
{
    public function problem(): void
    {
        $this->view('report.problem', ['title' => 'Report a Problem']);
    }

    public function submitProblem(): void
    {
        $this->validate([
            'subject'     => 'required|max:160',
            'description' => 'required|max:1000',
        ]);

        Session::flash('success', 'Thank you — your report has been noted. Storing reports arrives with the moderation backend.');
        $this->redirect('/report');
    }
}
