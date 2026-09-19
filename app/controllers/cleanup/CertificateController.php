<?php
namespace Controllers\Cleanup;

use Core\Controller;
use Models\Certificate;
use Models\Cleanup\CleanupEvent;
use Models\Cleanup\EventRegistration;
use Models\Cleanup\WasteRecord;

/**
 * The volunteer appreciation certificate issued after a campaign.
 *
 * Rendered on its own layout - no sidebar, no topbar - because the frame is an
 * A4 landscape sheet meant to be printed or saved.
 *
 * A certificate is a claim that this person volunteered at this campaign, so it
 * is not handed to anyone who reaches the URL: the member must have a live
 * registration. Opening the sheet issues the `certificates` row if it does not
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

        if (!(new EventRegistration())->forUserAndEvent($userId, (int) $event['id'])) {
            $this->abort(403, 'Certificates are issued to volunteers who joined the campaign.');
        }

        $row   = (new Certificate())->issue($userId, 'event_id', (int) $event['id'], 'TT-CL');
        $waste = (new WasteRecord())->totalFor((int) $event['id']);

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
                'waste'      => $waste > 0
                    ? rtrim(rtrim(number_format($waste, 2), '0'), '.') . ' kg'
                    : 'Not recorded',
                'volunteers' => (int) $event['checked_in'],
            ],
        ], 'blank');
    }
}
