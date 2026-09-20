<?php
namespace Controllers\Reuse;

use Core\Controller;
use Models\Blood\DonationRecord;
use Models\Blood\ScreeningResponse;
use Models\Cleanup\EventRegistration;
use Models\Donation\FundDonation;
use Models\Donation\ItemReservation;
use Models\Reuse\ItemRequest;

/**
 * MEMBER 4 - "My Activities", the screen behind the sidebar link of that name.
 *
 * It answers one question - "what have I taken part in, and what came of it?" -
 * across all four modules. Every other screen shows one module's flow; this is
 * the place a member looks when they cannot remember which module something was
 * in.
 *
 * Until 2026-09-04 it listed reuse requests and nothing else, because reuse was
 * the only module whose participation tables had a writer. The other three do
 * now, so the other three sections exist:
 *
 *   reuse         `item_requests`         - what I asked for
 *   cleanup       `event_registrations`   - campaigns I joined
 *   blood         `screening_responses`   - camps I registered for
 *   donation      `item_reservations` and `fund_donations` - what I gave
 *
 * The reuse half keeps its own detail - the counters and the review form - and
 * the other three are read-only summaries that link into their own module.
 * Whoever adds a fifth thing a member can take part in adds a section here
 * rather than building a second "my activities" screen.
 */
class MyActivityController extends Controller
{
    public function index(): void
    {
        $userId = (int) auth()['id'];

        $reservations = (new ItemReservation())->forDonor($userId);
        $funds        = (new FundDonation())->forDonor($userId);

        $this->view('reuse.activities', [
            'title'    => 'My Activities',
            'requests' => (new ItemRequest())->forRequesterList($userId),
            'cleanups' => (new EventRegistration())->forUser($userId),
            'camps'    => (new ScreeningResponse())->campsFor($userId),
            'donated'  => (new DonationRecord())->forDonor($userId),
            'giving'   => $this->giving($reservations, $funds),
        ]);
    }

    /**
     * Items promised and money given, in one list newest first.
     *
     * Same shape the donation hub's My Donations tab builds, for the same
     * reason: a donor thinks of the two as one thing they did, not as two
     * tables.
     */
    private function giving(array $reservations, array $funds): array
    {
        $rows = [];

        foreach ($reservations as $r) {
            $rows[] = [
                'kind'       => 'items',
                'at'         => $r['created_at'],
                'request_id' => (int) $r['request_id'],
                'title'      => $r['title'],
                'what'       => $r['quantity'] . ' ' . $r['unit'] . ' of ' . $r['item_name'],
                'status'     => ItemReservation::STATUSES[$r['status']] ?? $r['status'],
                'tone'       => ItemReservation::TONES[$r['status']] ?? 'neutral',
            ];
        }

        foreach ($funds as $f) {
            $rows[] = [
                'kind'       => 'funds',
                'at'         => $f['created_at'],
                'request_id' => (int) $f['request_id'],
                'title'      => $f['title'],
                'what'       => format_money((float) $f['amount']),
                'status'     => 'Paid',
                'tone'       => 'green',
            ];
        }

        usort($rows, fn($a, $b) => strcmp((string) $b['at'], (string) $a['at']));

        return $rows;
    }
}
