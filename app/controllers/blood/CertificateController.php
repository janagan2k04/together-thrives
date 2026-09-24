<?php
namespace Controllers\Blood;

use Core\Controller;
use Models\Blood\BloodCampaign;
use Models\Blood\DonationRecord;
use Models\Certificate;

/**
 * The donor appreciation certificate.
 *
 * The route carries a camp id, not a certificate number: a certificate belongs
 * to a donation, and a donation is (donor, camp). Opening the sheet issues the
 * `certificates` row if it does not exist yet, which is what fixes the number -
 * print it today and again next year and both copies carry the same id.
 *
 * A certificate is a claim that this person donated blood at this camp, so the
 * guard is the `blood_donation_records` row the organiser wrote. Registering for
 * a camp is not enough; turning up and donating is what earns the sheet.
 *
 * Rendered on the `blank` layout - a document, not a screen.
 */
class CertificateController extends Controller
{
    public function show(string $id): void
    {
        $userId = (int) auth()['id'];
        $campId = (int) $id;

        $camp = (new BloodCampaign())->findDetail($campId, true);
        if (!$camp) {
            $this->abort(404, 'Blood donation camp not found.');
        }

        $records = (new DonationRecord())->forDonor($userId);
        $record  = null;
        foreach ($records as $r) {
            if ((int) $r['campaign_id'] === $campId) {
                $record = $r;
                break;
            }
        }

        if (!$record) {
            $this->abort(403, 'Certificates are issued once a donation has been recorded.');
        }

        $row = (new Certificate())->issue($userId, 'campaign_id', $campId, 'TT-BD');

        $this->view('blood.certificate', [
            'title'       => 'Certificate of Appreciation',
            'certificate' => [
                'id'        => $row['certificate_no'] ?? '',
                'donor'     => auth()['full_name'] ?? 'Donor',
                'camp'      => $camp['title'],
                'organiser' => $record['organiser'] ?? '',
                'date'      => format_date($record['donated_at']),
                'units'     => rtrim(rtrim(number_format((float) $record['units'], 2), '0'), '.')
                               . ((float) $record['units'] === 1.0 ? ' Unit' : ' Units'),
                'group'     => auth()['blood_group'] ?: '-',
                'issued'    => format_date($row['issued_at'] ?? date('Y-m-d')),
            ],
        ], 'blank');
    }
}
