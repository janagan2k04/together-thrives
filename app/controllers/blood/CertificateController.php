<?php
namespace Controllers\Blood;

use Core\Controller;
use Services\BloodSampleData;

/**
 * The appreciation certificate issued after a completed donation.
 *
 * Rendered on its own layout — no sidebar, no topbar — because the frame is an
 * A4 landscape sheet meant to be printed or saved. `blood_donation_records` is
 * the table that will supply the real donation in weeks 4–5.
 */
class CertificateController extends Controller
{
    public function show(string $id): void
    {
        $this->view('blood.certificate', [
            'title'       => 'Certificate of Appreciation',
            'certificate' => BloodSampleData::certificate($id, auth()['full_name'] ?? 'Donor'),
        ], 'blank');
    }
}
