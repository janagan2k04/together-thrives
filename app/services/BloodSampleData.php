<?php
namespace Services;

/**
 * Week 3 placeholder content for the Blood Donation module (Member 3).
 *
 * The blood tables are in the frozen schema and nothing writes to them yet, so
 * every screen in `app/views/blood/` reads from here. Deliberately one
 * self-contained file: when the real BloodCampaign CRUD lands in weeks 4–5,
 * delete this class and swap each call for a model query — the views take the
 * same array shapes either way.
 *
 * Kept separate from `SampleData` so the two modules can be finished and
 * cleaned up independently.
 */
final class BloodSampleData
{
    /** The eight groups, in the order `blood_campaigns` and the Figma use. */
    public const GROUPS = ['O-', 'O+', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

    /** ---------------------------------------------------------------
     *  Donation camps — the "Upcoming Blood Donation Camps" list and
     *  the camp detail screen.
     *  --------------------------------------------------------------- */
    public static function camps(): array
    {
        return [
            [
                'id'          => 1,
                'title'       => 'Blood Donation Camp – National Hospital',
                'organiser'   => 'National Hospital of Sri Lanka',
                'organiserSub'=> 'Government hospital · Verified',
                'initials'    => 'NH',
                'venue'       => 'National Hospital of Sri Lanka, Blood Bank Wing',
                'city'        => 'Colombo',
                'date'        => '21 Jun 2026',
                'time'        => '8:30 AM – 1:00 PM',
                'countdown'   => 'Tomorrow',
                'tone'        => 'green',
                'registered'  => 86,
                'target'      => 120,
                'campType'    => 'Walk-in + Registered',
                'contact'     => '011 269 1111 · bloodbank@nhsl.lk',
                'groups'      => ['O-', 'O+', 'A+'],
                'organised'   => 22,
                'reached'     => 1940,
                'description' => 'Join our monthly blood donation camp at the National Hospital. '
                               . 'Every donation can save up to three lives. Refreshments and a '
                               . 'post-donation rest area are provided. Registration takes 5 minutes '
                               . 'and screening is done on site.',
            ],
            [
                'id'          => 2,
                'title'       => 'Community Blood Drive – Kandy',
                'organiser'   => 'Kandy General Hospital',
                'organiserSub'=> 'Government hospital · Verified',
                'initials'    => 'KG',
                'venue'       => 'Kandy General Hospital, OPD Block',
                'city'        => 'Kandy',
                'date'        => '24 Jun 2026',
                'time'        => '9:00 AM – 2:00 PM',
                'countdown'   => '3 Days Left',
                'tone'        => 'amber',
                'registered'  => 54,
                'target'      => 100,
                'campType'    => 'Registered only',
                'contact'     => '081 222 2261 · donors@kgh.lk',
                'groups'      => ['A+', 'B+', 'AB+'],
                'organised'   => 14,
                'reached'     => 1120,
                'description' => 'A community drive run with the Kandy Municipal Council. Please '
                               . 'register in advance so we can plan stock and staffing. Bring your '
                               . 'NIC and eat a proper meal before you arrive.',
            ],
            [
                'id'          => 3,
                'title'       => 'Blood Donation Camp – Galle',
                'organiser'   => 'Teaching Hospital, Karapitiya',
                'organiserSub'=> 'Teaching hospital · Verified',
                'initials'    => 'TK',
                'venue'       => 'Teaching Hospital Karapitiya, Auditorium',
                'city'        => 'Galle',
                'date'        => '26 Jun 2026',
                'time'        => '8:30 AM – 1:00 PM',
                'countdown'   => '5 Days Left',
                'tone'        => 'blue',
                'registered'  => 63,
                'target'      => 100,
                'campType'    => 'Walk-in + Registered',
                'contact'     => '091 223 2560 · blood@karapitiya.lk',
                'groups'      => ['O-', 'O+', 'B-'],
                'organised'   => 9,
                'reached'     => 730,
                'description' => 'Southern Province donors are especially needed for O- stock. '
                               . 'Screening is on site and the whole visit takes about 45 minutes.',
            ],
        ];
    }

    public static function camp(string $id): ?array
    {
        foreach (self::camps() as $c) {
            if ((string) $c['id'] === $id) {
                return $c;
            }
        }
        return null;
    }

    /** ---------------------------------------------------------------
     *  Critical blood requests — the urgent cards and their detail page.
     *  --------------------------------------------------------------- */
    public static function criticalRequests(): array
    {
        return [
            [
                'id'        => 1,
                'group'     => 'O+',
                'units'     => 2,
                'fulfilled' => 0,
                'hospital'  => 'Nawaloka Hospital, Colombo 02',
                'hospitalShort' => 'Nawaloka Hospital, Colombo',
                'city'      => 'Colombo',
                'ward'      => 'ICU - Ward 3',
                'neededBy'  => 'Today, 5:00 PM',
                'remaining' => '3 h 42 m left',
                'priority'  => 'Urgent',
                'tone'      => 'red',
                'requester' => 'Nimal Perera (Family member)',
                'posted'    => '40 minutes ago',
                'reason'    => 'Emergency surgery after road accident',
                'note'      => 'Patient underwent emergency surgery following a road accident this '
                             . 'morning and requires 2 units of O+ blood before 5:00 PM today. Any '
                             . 'help is deeply appreciated.',
            ],
            [
                'id'        => 2,
                'group'     => 'A-',
                'units'     => 3,
                'fulfilled' => 1,
                'hospital'  => 'Kandy General Hospital',
                'hospitalShort' => 'Kandy General Hospital',
                'city'      => 'Kandy',
                'ward'      => 'Ward 8, Bed 12',
                'neededBy'  => 'Tomorrow, 10:00 AM',
                'remaining' => '21 h 12 m left',
                'priority'  => 'High Priority',
                'tone'      => 'amber',
                'requester' => 'Kandy General Hospital (Blood Bank)',
                'posted'    => '3 hours ago',
                'reason'    => 'Scheduled surgery — rare group, low stock',
                'note'      => 'A- is our lowest stock group this week and a scheduled procedure '
                             . 'needs 3 units by tomorrow morning. Donors from Kandy and Matale are '
                             . 'especially welcome.',
            ],
            [
                'id'        => 3,
                'group'     => 'B+',
                'units'     => 1,
                'fulfilled' => 0,
                'hospital'  => 'District Hospital, Ratnapura',
                'hospitalShort' => 'District Hospital, Ratnapura',
                'city'      => 'Ratnapura',
                'ward'      => 'Ward 4, Bed 7',
                'neededBy'  => 'Today, 8:00 PM',
                'remaining' => '6 h 42 m left',
                'priority'  => 'Urgent',
                'tone'      => 'red',
                'requester' => 'Saman Wickramasinghe (Family member)',
                'posted'    => '1 hour ago',
                'reason'    => 'Post-partum haemorrhage',
                'note'      => 'One unit of B+ is needed this evening. The hospital blood bank has '
                             . 'confirmed the requirement.',
            ],
        ];
    }

    public static function criticalRequest(string $id): ?array
    {
        foreach (self::criticalRequests() as $r) {
            if ((string) $r['id'] === $id) {
                return $r;
            }
        }
        return null;
    }

    /** ---------------------------------------------------------------
     *  Community tabs — My Donations and My Registrations.
     *  --------------------------------------------------------------- */
    public static function donations(): array
    {
        return [
            ['cert' => 'TT-BD-1-1-21062025', 'camp' => 'World Blood Donor Day Drive',
             'organiser' => 'Sri Lanka Red Cross Society · Colombo 07',
             'date' => '14 Jun 2025', 'group' => 'A+', 'units' => '1 Unit', 'status' => 'Completed'],
            ['cert' => 'TT-BD-2-1-05042026', 'camp' => 'Blood Donation Camp – National Hospital',
             'organiser' => 'National Hospital of Sri Lanka',
             'date' => '02 Apr 2026', 'group' => 'A+', 'units' => '1 Unit', 'status' => 'Completed'],
            ['cert' => 'TT-BD-3-1-20122025', 'camp' => 'Community Blood Drive – Kandy',
             'organiser' => 'Kandy General Hospital',
             'date' => '18 Dec 2025', 'group' => 'A+', 'units' => '1 Unit', 'status' => 'Completed'],
            ['cert' => 'TT-BD-4-1-05082025', 'camp' => 'Emergency Response Drive – Matara',
             'organiser' => 'Matara General Hospital',
             'date' => '05 Aug 2025', 'group' => 'A+', 'units' => '1 Unit', 'status' => 'Completed'],
        ];
    }

    public static function registrations(): array
    {
        return [
            ['camp' => 'Blood Donation Camp – National Hospital', 'campId' => 1,
             'where' => 'National Hospital of Sri Lanka · Colombo 10',
             'when' => '21 Jun 2026 · 8:30 AM – 1:00 PM', 'screening' => 'Eligible', 'tone' => 'green',
             'on' => 'Registered 12 Jun 2026',
             'note' => 'You are cleared to donate. Bring your NIC to the venue.'],
            ['camp' => 'Community Blood Drive – Kandy', 'campId' => 2,
             'where' => 'Kandy General Hospital · Kandy',
             'when' => '24 Jun 2026 · 9:00 AM – 2:00 PM', 'screening' => 'Screening Pending', 'tone' => 'amber',
             'on' => 'Registered 15 Jun 2026',
             'note' => 'Your medical screening form is awaiting automatic evaluation.'],
            ['camp' => 'Blood Donation Camp – Galle', 'campId' => 3,
             'where' => 'Teaching Hospital, Karapitiya · Galle',
             'when' => '26 Jun 2026 · 8:30 AM – 1:00 PM', 'screening' => 'Not Eligible', 'tone' => 'red',
             'on' => 'Registered 16 Jun 2026',
             'note' => 'Deferred — four months have not passed since your last donation.'],
        ];
    }

    /** Critical requests this donor answered, on the My Registrations tab. */
    public static function responses(): array
    {
        return [
            ['request' => 'Urgent O+ Requirement', 'hospital' => 'Nawaloka Hospital, Colombo',
             'group' => 'O+', 'on' => 'Today, 9:12 AM', 'screening' => 'Eligible',   'shared' => 'Shared'],
            ['request' => 'Emergency A− Requirement', 'hospital' => 'Kandy General Hospital',
             'group' => 'A-', 'on' => '18 Jun 2026', 'screening' => 'Not Eligible', 'shared' => 'Not shared'],
        ];
    }

    /** ---------------------------------------------------------------
     *  Organiser side — the manage list, and the donors on one campaign.
     *  --------------------------------------------------------------- */
    public static function organiserRows(): array
    {
        return [
            [
                'id' => 1, 'kind' => 'critical', 'group' => 'O-',
                'title' => 'Urgent Need: O- Blood Donors', 'critical' => true,
                'where' => 'National Hospital Colombo',
                'blurb' => 'Urgently need O- blood for a patient in ICU. Your donation can save a life!',
                'type'  => 'Urgent Request', 'status' => 'Visible', 'statusTone' => 'active',
                'period' => [['Posted', '14 Jun 2025'], ['Needed By', '16 Jun 2025'], ['Time', 'Before 6:00 PM']],
                'viewed' => '1,485', 'registered' => '82', 'rejected' => '11',
            ],
            [
                'id' => 2, 'kind' => 'campaign', 'group' => 'A+',
                'title' => 'World Blood Donor Day Drive', 'critical' => false,
                'where' => 'Sri Lanka Red Cross Society',
                'blurb' => 'Be a hero. Donate blood and give the gift of life.',
                'type'  => 'Blood Donation', 'status' => 'Visible', 'statusTone' => 'active',
                'period' => [['Start', '14 Jun 2025'], ['End', '20 Jun 2025'], ['Time', '8:00 AM - 2:00 PM']],
                'viewed' => '2,340', 'registered' => '312', 'rejected' => '18',
            ],
            [
                'id' => 3, 'kind' => 'critical', 'group' => 'B+',
                'title' => 'Emergency B+ Blood Needed', 'critical' => true,
                'where' => 'Lady Ridgeway Hospital',
                'blurb' => 'B+ blood urgently required for multiple patients.',
                'type'  => 'Urgent Request', 'status' => 'Pending Approval', 'statusTone' => 'pending',
                'period' => [['Posted', '13 Jun 2025'], ['Needed By', '15 Jun 2025'], ['Time', 'Before 5:00 PM']],
                'viewed' => '856', 'registered' => '54', 'rejected' => '7',
            ],
            [
                'id' => 4, 'kind' => 'campaign', 'group' => 'O+',
                'title' => 'Community Blood Donation Camp', 'critical' => false,
                'where' => 'Sri Lanka Red Cross Society – Kandy Branch',
                'blurb' => 'Join us for a community blood donation camp.',
                'type'  => 'Blood Donation', 'status' => 'Visible', 'statusTone' => 'active',
                'period' => [['Start', '25 Jun 2025'], ['End', '26 Jun 2025'], ['Time', '9:00 AM - 3:00 PM']],
                'viewed' => '645', 'registered' => '68', 'rejected' => '4',
            ],
            [
                'id' => 5, 'kind' => 'campaign', 'group' => 'AB-',
                'title' => 'Thalassemia Support Drive', 'critical' => false,
                'where' => 'National Thalassemia Center',
                'blurb' => 'Regular blood donations can bring hope to thalassemia patients.',
                'type'  => 'Blood Donation', 'status' => 'Visible', 'statusTone' => 'active',
                'period' => [['Start', '02 Jun 2025'], ['End', '08 Jun 2025'], ['Time', '8:00 AM - 1:00 PM']],
                'viewed' => '1,102', 'registered' => '143', 'rejected' => '9',
            ],
        ];
    }

    public static function organiserRow(string $id): ?array
    {
        foreach (self::organiserRows() as $r) {
            if ((string) $r['id'] === $id) {
                return $r;
            }
        }
        return null;
    }

    /** Donors registered on one campaign, for the organiser's detail screen. */
    public static function registeredDonors(): array
    {
        return [
            ['initials' => 'NF', 'name' => 'Nimal Fernando',  'city' => 'Colombo 05',
             'group' => 'A+', 'result' => 'Eligible',     'contact' => '+94 77 412 8830', 'on' => '12 Jun 2025'],
            ['initials' => 'SP', 'name' => 'Sanduni Perera', 'city' => 'Dehiwala',
             'group' => 'O-', 'result' => 'Eligible',     'contact' => '+94 71 903 2214', 'on' => '12 Jun 2025'],
            ['initials' => 'RJ', 'name' => 'Ruwan Jayasuriya', 'city' => 'Nugegoda',
             'group' => 'B+', 'result' => 'Not eligible', 'contact' => '',                'on' => '13 Jun 2025'],
            ['initials' => 'MD', 'name' => 'Malsha De Silva', 'city' => 'Maharagama',
             'group' => 'A+', 'result' => 'Eligible',     'contact' => '+94 76 220 5471', 'on' => '13 Jun 2025'],
            ['initials' => 'TB', 'name' => 'Tharindu Bandara', 'city' => 'Kotte',
             'group' => 'O-', 'result' => 'Eligible',     'contact' => '+94 70 118 6642', 'on' => '14 Jun 2025'],
        ];
    }

    /** ---------------------------------------------------------------
     *  The standard screening form.
     *
     *  `screening_questions` seeds 14 default rows in the schema; these are
     *  those questions. `disqualifying` is the answer that defers a donor,
     *  which is what `answer_type`/`disqualifying_answer` encode on the table.
     *  --------------------------------------------------------------- */
    public static function screeningQuestions(): array
    {
        return [
            ['q1',  'Are you feeling well today?',                                          'no'],
            ['q2',  'Are you between 18 and 60 years old?',                                 'no'],
            ['q3',  'Do you weigh more than 50 kg?',                                        'no'],
            ['q4',  'Have you eaten within the last 4 hours?',                              'no'],
            ['q5',  'Have you donated blood in the last 4 months?',                         'yes'],
            ['q6',  'Are you currently taking antibiotics or any medication?',              'yes'],
            ['q7',  'Have you had a fever, cold or infection in the last 2 weeks?',         'yes'],
            ['q8',  'Have you had dental treatment in the last 7 days?',                    'yes'],
            ['q9',  'Have you had surgery or a blood transfusion in the last 12 months?',   'yes'],
            ['q10', 'Have you been tattooed or pierced in the last 12 months?',             'yes'],
            ['q11', 'Do you have diabetes, heart disease, or high blood pressure?',         'yes'],
            ['q12', 'Have you travelled to a malaria-risk area in the last 12 months?',     'yes'],
            ['q13', 'Are you pregnant or have you given birth in the last 12 months?',      'yes'],
            ['q14', 'Have you consumed alcohol in the last 24 hours?',                      'yes'],
        ];
    }

    /** ---------------------------------------------------------------
     *  Appreciation certificate.
     *  --------------------------------------------------------------- */
    public static function certificate(string $id, string $donor): array
    {
        foreach (self::donations() as $d) {
            if ($d['cert'] === $id) {
                return [
                    'id'        => $d['cert'],
                    'donor'     => $donor,
                    'camp'      => $d['camp'],
                    'organiser' => $d['organiser'],
                    'date'      => $d['date'],
                    'units'     => $d['units'],
                    'group'     => $d['group'],
                    'issued'    => $d['date'],
                ];
            }
        }

        // Unknown reference — still render a certificate rather than 404, so the
        // week 3 walkthrough never dead-ends. Weeks 4–5 looks this up for real.
        return [
            'id'        => $id,
            'donor'     => $donor,
            'camp'      => 'World Blood Donor Day Blood Donation Camp',
            'organiser' => 'Sri Lanka Red Cross Society · Colombo 07',
            'date'      => '14 June 2025',
            'units'     => '1 Unit',
            'group'     => 'A+',
            'issued'    => '21 June 2025',
        ];
    }

    /** Headline tiles on the community hub, per tab. */
    public static function impact(string $tab): array
    {
        return match ($tab) {
            'donations'     => [
                ['4',  'Donations Made',   'red'],
                ['4',  'Units Donated',    'green'],
                ['12', 'Lives Supported',  'blue'],
            ],
            'registrations' => [
                ['2', 'Active Registrations', 'red'],
                ['1', 'Pending Screening',    'amber'],
                ['5', 'Camps Joined',         'green'],
            ],
            default         => [
                ['2',          'Donated Camps',     'green'],
                ['2026/04/02', 'Last Donated Date', 'blue'],
            ],
        };
    }
}
