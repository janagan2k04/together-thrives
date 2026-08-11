<?php
namespace Controllers\Blood;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\District;
use Services\BloodSampleData;

/**
 * The medical screening form every donor completes before joining a camp or
 * answering a critical request.
 *
 * The frozen schema splits this in two: `screening_questions` holds the
 * fourteen default questions with the answer that disqualifies, and
 * `screening_responses` stores the donor's answers as JSON plus a result of
 * eligible / ineligible / deferred. Week 3 renders the form, validates every
 * answer, and scores it — but stores nothing.
 */
class ScreeningController extends Controller
{
    public function show(string $id): void
    {
        $camp = BloodSampleData::camp($id);
        if (!$camp) {
            $this->abort(404, 'Blood donation camp not found.');
        }

        $this->render('camp', $camp['title'], '/blood/' . $id . '/screening', '/blood/' . $id);
    }

    public function submit(string $id): void
    {
        if (!BloodSampleData::camp($id)) {
            $this->abort(404, 'Blood donation camp not found.');
        }

        $this->handle();
    }

    /** The same form, reached from a critical request rather than a camp. */
    public function showForRequest(string $id): void
    {
        $request = BloodSampleData::criticalRequest($id);
        if (!$request) {
            $this->abort(404, 'Blood request not found.');
        }

        $this->render(
            'critical',
            'Critical Blood Request - ' . $request['group'],
            '/blood/critical/' . $id . '/screening',
            '/blood/critical/' . $id
        );
    }

    public function submitForRequest(string $id): void
    {
        if (!BloodSampleData::criticalRequest($id)) {
            $this->abort(404, 'Blood request not found.');
        }

        $this->handle();
    }

    public function submitted(): void
    {
        $this->view('blood.screening-done', ['title' => 'Screening Form Submitted']);
    }

    // ---------------------------------------------------------------

    private function render(string $context, string $forWhat, string $action, string $back): void
    {
        $this->view('blood.screening', [
            'title'     => 'Blood Donation Screening Form',
            'context'   => $context,
            'forWhat'   => $forWhat,
            'action'    => $action,
            'back'      => $back,
            'questions' => BloodSampleData::screeningQuestions(),
            'groups'    => BloodSampleData::GROUPS,
            'districts' => (new District())->listAll(),
        ]);
    }

    /**
     * Validate the personal section, then require an answer to all fourteen
     * questions — a half-filled medical form is worse than none, so a missing
     * answer is an error rather than an assumed "no".
     */
    private function handle(): void
    {
        $rules = [
            'full_name'     => 'required|max:120',
            'nic'           => 'required|nic_lk',
            'date_of_birth' => 'required|date',
            'gender'        => 'required|in:male,female,other',
            'phone'         => 'required|phone_lk',
            'address'       => 'required|max:255',
            'district_id'   => 'required|integer',
            'donation_type' => 'required|in:whole_blood,apheresis',
        ];

        foreach (BloodSampleData::screeningQuestions() as [$key]) {
            $rules[$key] = 'required|in:yes,no';
        }

        $data = $this->validate($rules);

        Session::flash('success', $this->verdict($data));
        $this->redirect('/blood/screening/submitted');
    }

    /**
     * Score the answers the way `screening_responses.result` will.
     *
     * Each question carries the answer that disqualifies; any match defers the
     * donor. The message is flashed rather than stored, but the scoring is the
     * real rule, so weeks 4–5 keeps it and writes the result to the table.
     */
    private function verdict(array $data): string
    {
        $failed = 0;
        foreach (BloodSampleData::screeningQuestions() as [$key, , $disqualifying]) {
            if (($data[$key] ?? '') === $disqualifying) {
                $failed++;
            }
        }

        return $failed === 0
            ? 'Screening complete — your answers indicate you are eligible to donate.'
            : "Screening complete — {$failed} answer" . ($failed === 1 ? '' : 's')
              . ' would defer you. Nothing was stored; scoring becomes real in weeks 4–5.';
    }
}
