<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Models\Certificate;
use Models\Cleanup\Attendance;
use Models\Cleanup\CleanupEvent;
use Models\Cleanup\EventRegistration;

/**
 * The volunteer appreciation certificate issued after a campaign.
 *
 * Rendered on its own layout - no sidebar, no topbar - because the frame is an
 * A4 landscape sheet meant to be printed or saved.
 *
 * A certificate is a claim that this person volunteered at this campaign, so it
 * is not handed to anyone who reaches the URL: the member must have been
 * checked in by the organiser. Opening the sheet issues the `certificates` row if it does not
 * exist yet, which is what fixes the number - print it today and again next year
 * and both copies carry the same id.
 *
 * Every figure on the sheet is counted: hours from the campaign's own start and
 * end times, waste from `waste_records`, volunteers from `attendance`. Waste
 * that nobody recorded prints as "Not recorded" rather than as a zero, because
 * those are different answers.
 */
class CertificateController extends Controller
{
    public function show(string $id): void
    {
        $event = (new CleanupEvent())->findDetail((int) $id);
        if (!$event || !in_array($event['status'], CleanupEvent::PUBLIC_STATUSES, true)) {
            $this->abort(404, 'Cleanup campaign not found.');
        }

        $userId = (int) auth()['id'];

        // FR3.10: issued from a recorded attendance. Signing up is not taking
        // part - until 2026-10 any live registration opened the sheet, which
        // certified people the organiser never checked in.
        $registration = (new EventRegistration())->forUserAndEvent($userId, (int) $event['id']);
        if (!$registration || $registration['status'] === 'cancelled'
            || !(new Attendance())->isCheckedIn((int) $registration['id'])) {
            $this->abort(403, 'Certificates are issued to volunteers the organiser checked in.');
        }

        $row = (new Certificate())->issue($userId, 'event_id', (int) $event['id'], 'TT-CL');

        $this->view('cleanup.certificate', [
            'title'       => 'Certificate of Appreciation',
            'certificate' => [
                'id'         => $row['certificate_no'] ?? '',
                'volunteer'  => auth()['full_name'] ?? 'Volunteer',
                'event'      => $event['title'],
                'organiser'  => $event['organiser'],
                'where'      => $event['district'],
                'date'       => format_date($event['event_date']),
                'issued'     => format_date($row['issued_at'] ?? date('Y-m-d')),
                'hours'      => rtrim(rtrim((string) $event['duration_hours'], '0'), '.') . ' hours',
                'volunteers' => (int) $event['checked_in'],
            ],
        ], 'blank');
    }
}
