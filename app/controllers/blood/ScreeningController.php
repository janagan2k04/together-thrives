<?php
namespace Controllers\Blood;

use Core\Controller;
use Core\Session;
use Models\Blood\BloodCampaign;
use Models\Blood\CriticalRequest;
use Models\Blood\ScreeningQuestion;
use Models\District;
use Models\Notification;
use Services\ScreeningService;

/**
 * The medical screening form every donor completes before joining a camp or
 * answering a critical request.
 *
 * The frozen schema splits this in two: `screening_questions` holds the
 * questionnaire with the answer that disqualifies, and `screening_responses`
 * stores the donor's answers as JSON plus a result of eligible / ineligible /
 * deferred.
 *
 * **The response row is the registration.** There is no separate join table, and
 * none is needed: a donor signs up for a camp by answering its questionnaire, so
 * submitting this form is what puts the camp on their My Registrations tab and
 * the donor on the organiser's list.
 *
 * The questions come from the database, not from a list in PHP, so a camp that
 * has added its own is asked - and scored on - all of them.
 */
class ScreeningController extends Controller
{
    private ScreeningService $screening;

    public function __construct()
    {
        $this->screening = new ScreeningService();
    }

    // ---------- a camp ----------

    public function show(string $id): void
    {
        $camp = $this->camp($id);

        $this->render(
            'camp',
            $camp['title'],
            '/blood/' . $id . '/screening',
            '/blood/' . $id,
            (int) $camp['id']
        );
    }

    public function submit(string $id): void
    {
        $camp = $this->camp($id);

        $verdict = $this->handle(
            ['campaign_id' => (int) $camp['id']],
            (int) $camp['id']
        );

        $this->notifyOrganiser((int) $camp['organizer_id'], $camp['title'], (int) $camp['id']);

        Session::flash('success', 'Screening complete - ' . lcfirst(
            \Models\Blood\ScreeningResponse::RESULTS[$verdict['result']]
        ) . '. ' . $verdict['reason']);
        $this->redirect('/blood/screening/submitted');
    }

    /**
     * The camp being screened for - and only an approved one, so a donor cannot
     * register for a camp that is still waiting on an administrator.
     */
    private function camp(string $id): array
    {
        $camp = (new BloodCampaign())->findDetail((int) $id);
        if (!$camp || !in_array($camp['status'], BloodCampaign::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Blood donation camp not found.');
        }
        return $camp;
    }

    // ---------- a critical request ----------

    public function showForRequest(string $id): void
    {
        $request = $this->publishedRequestOrFail($id);

        $this->render(
            'critical',
            'Critical Blood Request - ' . $request['blood_group'],
            '/blood/critical/' . $id . '/screening',
            '/blood/critical/' . $id
        );
    }

    public function submitForRequest(string $id): void
    {
        $request = $this->publishedRequestOrFail($id);

        $verdict = $this->handle(['critical_request_id' => (int) $request['id']]);

        $this->notifyRequester($request, $verdict['result']);

        Session::flash('success', 'Screening complete - ' . lcfirst(
            \Models\Blood\ScreeningResponse::RESULTS[$verdict['result']]
        ) . '. ' . $verdict['reason']);
        $this->redirect('/blood/screening/submitted');
    }

    /**
     * A donor may only answer the screening form for a request the public can
     * see. One still in the administrator's queue is not a call for donors yet.
     */
    private function publishedRequestOrFail(string $id): array
    {
        $request = (new CriticalRequest())->findDetail((int) $id);

        if (!$request || !in_array($request['status'], CriticalRequest::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Blood request not found.');
        }

        return $request;
    }

    public function submitted(): void
    {
        $this->view('blood.screening-done', ['title' => 'Screening Form Submitted']);
    }

    // ---------------------------------------------------------------

    private function render(string $context, string $forWhat, string $action, string $back, ?int $campaignId = null): void
    {
        $this->view('blood.screening', [
            'title'     => 'Blood Donation Screening Form',
            'context'   => $context,
            'forWhat'   => $forWhat,
            'action'    => $action,
            'back'      => $back,
            'questions' => $this->screening->form($campaignId),
            'groups'    => BloodCampaign::GROUPS,
            'districts' => (new District())->listAll(),
        ]);
    }

    /**
     * Validate the personal section, then require an answer to every question -
     * a half-filled medical form is worse than none, so a missing answer is an
     * error rather than an assumed "no".
     *
     * The rules are built from the questionnaire the database holds, so adding a
     * question makes it required without anything here changing.
     */
    private function handle(array $link, ?int $campaignId = null): array
    {
        $questions = $this->screening->form($campaignId);

        $rules = [
            'full_name'     => 'required|max:120',
            'nic'           => 'required|nic_lk',
            'date_of_birth' => 'required|date',
            'gender'        => 'required|in:male,female,other',
            'phone'         => 'required|phone_lk',
            'address'       => 'required|max:255',
            'district_id'   => 'required|integer',
        ];

        foreach ($questions as $question) {
            $rules[ScreeningQuestion::fieldName($question)] = 'required|in:yes,no';
        }

        $data = $this->validate($rules);

        return $this->screening->record((int) auth()['id'], $link, $data, $campaignId);
    }

    /** Tell the organiser a donor registered for their camp. */
    private function notifyOrganiser(int $organizerId, string $title, int $campaignId): void
    {
        (new Notification())->create([
            'user_id'  => $organizerId,
            'title'    => 'New donor registration',
            'body'     => (auth()['full_name'] ?? 'A donor') . ' completed the screening form for ' . $title . '.',
            'category' => 'blood',
            'link'     => '/my/blood/' . $campaignId,
        ]);
    }

    /**
     * Tell the requester somebody answered - but only when the donor cleared the
     * screening. An urgent appeal does not need a notification per deferral.
     */
    private function notifyRequester(array $request, string $result): void
    {
        if ($result !== 'eligible') {
            return;
        }

        (new Notification())->create([
            'user_id'  => (int) $request['requester_id'],
            'title'    => 'A donor answered your request',
            'body'     => (auth()['full_name'] ?? 'A donor') . ' cleared screening for your '
                          . $request['blood_group'] . ' request at ' . $request['hospital'] . '.',
            'category' => 'blood',
            'link'     => '/blood/critical/' . $request['id'],
        ]);
    }
}
