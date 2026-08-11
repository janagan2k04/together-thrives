<?php
namespace Services;

/**
 * Week 3 placeholder content.
 *
 * Every screen rendered for the interim needs to look like it has data in it,
 * but the modules that would produce that data are weeks 4–5 work and the group
 * agreed not to seed the database. So the rows live here instead of in
 * `database/seeds/seed.sql` — one file, plainly named, easy to delete when the
 * real queries land.
 *
 * Nothing in here is written to or read from the database. A controller that
 * has real rows available must prefer them and use these only as a fallback.
 */
class SampleData
{
    /** Notifications page. Grouped exactly as the Figma frames group them. */
    public static function notifications(string $role): array
    {
        if ($role === ROLE_ADMIN) {
            return [
                ['Requires action', [
                    ['icon' => 'shield-check', 'tone' => 'blue',  'unread' => true,
                     'title' => 'Verification requests pending',
                     'body'  => 'New community members, organizations and Grama Niladhari accounts await review.',
                     'when'  => '15 minutes ago', 'action' => 'View', 'link' => '/admin/verification'],
                    ['icon' => 'bug', 'tone' => 'red', 'unread' => true,
                     'title' => 'New disaster report submitted — Rathnapura',
                     'body'  => 'Flood report from Kelaniya GN with photo evidence. Needs verification.',
                     'when'  => '40 minutes ago', 'action' => 'View', 'link' => '/admin/complaints'],
                    ['icon' => 'droplet', 'tone' => 'red', 'unread' => true,
                     'title' => 'Critical blood request awaiting fact-check',
                     'body'  => 'O+ request from City General Hospital cannot publish until verified.',
                     'when'  => '1 hour ago', 'action' => 'View', 'link' => '/admin/verification'],
                ]],
                ['Today', [
                    ['icon' => 'message-circle', 'tone' => 'amber', 'unread' => false,
                     'title' => '7 new complaints filed',
                     'body'  => 'Users reported issues with donation requests and campaign conduct.',
                     'when'  => '2 hours ago', 'action' => 'View', 'link' => '/admin/complaints'],
                    ['icon' => 'ban', 'tone' => 'red', 'unread' => false,
                     'title' => 'Post flagged as false or inappropriate',
                     'body'  => 'A reuse item listing was reported by 4 separate users.',
                     'when'  => '3 hours ago', 'action' => 'View', 'link' => '/admin/suspended'],
                    ['icon' => 'building', 'tone' => 'blue', 'unread' => false,
                     'title' => 'Organization verification submitted',
                     'body'  => 'Green Lanka Trust uploaded registration documents for review.',
                     'when'  => '4 hours ago', 'action' => 'View', 'link' => '/admin/organizations'],
                ]],
                ['Earlier', [
                    ['icon' => 'file-text', 'tone' => 'amber', 'unread' => false,
                     'title' => 'Unusual login activity detected',
                     'body'  => 'Multiple failed admin login attempts recorded from one IP address.',
                     'when'  => 'Yesterday, 11:20 PM', 'action' => 'View', 'link' => '/admin/logs'],
                    ['icon' => 'bar-chart', 'tone' => 'green', 'unread' => false,
                     'title' => 'Weekly platform report is ready',
                     'body'  => 'Registrations up 18%. Donations fulfilled: 342. Campaigns closed: 26.',
                     'when'  => 'Yesterday, 8:00 AM', 'action' => 'View', 'link' => '/admin/analytics'],
                ]],
            ];
        }

        if ($role === ROLE_ORGANIZATION || $role === ROLE_BLOOD_BANK) {
            return [
                ['Today', [
                    ['icon' => 'check-circle', 'tone' => 'green', 'unread' => true,
                     'title' => 'Donation request approved by admin',
                     'body'  => 'Your request for flood relief items in Rathnapura is now published.',
                     'when'  => '25 minutes ago', 'action' => 'View', 'link' => '/my/donations'],
                    ['icon' => 'users', 'tone' => 'blue', 'unread' => true,
                     'title' => '12 new volunteers registered',
                     'body'  => 'Beach Cleanup — Galle now has 42 of 50 volunteer slots filled.',
                     'when'  => '1 hour ago', 'action' => 'View', 'link' => '/org/cleanup'],
                    ['icon' => 'clock', 'tone' => 'amber', 'unread' => true,
                     'title' => 'Volunteer limit almost reached',
                     'body'  => 'Only 8 slots remain for Beach Cleanup — Galle. Registration closes soon.',
                     'when'  => '1 hour ago', 'action' => 'View', 'link' => '/org/cleanup'],
                ]],
                ['Yesterday', [
                    ['icon' => 'gift', 'tone' => 'green', 'unread' => true,
                     'title' => 'Funds transferred to your account',
                     'body'  => 'LKR 145,000 from the Rathnapura flood relief campaign has been released.',
                     'when'  => 'Yesterday, 3:40 PM', 'action' => 'View', 'link' => '/my/donations'],
                    ['icon' => 'gift', 'tone' => 'blue', 'unread' => false,
                     'title' => 'Items delivered awaiting confirmation',
                     'body'  => '6 donors marked items as delivered. Confirm receipt to update counts.',
                     'when'  => 'Yesterday, 10:15 AM', 'action' => 'View', 'link' => '/my/donations'],
                ]],
                ['Earlier', [
                    ['icon' => 'check-circle', 'tone' => 'green', 'unread' => false,
                     'title' => 'Organization verification renewed',
                     'body'  => 'Your verified organization status is valid for another year.',
                     'when'  => '20 Jun 2026', 'action' => '', 'link' => ''],
                    ['icon' => 'droplet', 'tone' => 'red', 'unread' => false,
                     'title' => 'Blood camp published',
                     'body'  => 'Your Colombo blood donation camp is live and notifying nearby donors.',
                     'when'  => '18 Jun 2026', 'action' => 'View', 'link' => '/blood'],
                ]],
            ];
        }

        // community members and Grama Niladhari officers
        return [
            ['Today', [
                ['icon' => 'bug', 'tone' => 'red', 'unread' => true,
                 'title' => 'Flood alert in your district — Rathnapura',
                 'body'  => 'Heavy rainfall and flooding reported. Cleanup volunteers needed.',
                 'when'  => '10 minutes ago', 'action' => 'View', 'link' => '/dengue'],
                ['icon' => 'droplet', 'tone' => 'red', 'unread' => true,
                 'title' => 'Critical blood request near you — O+',
                 'body'  => 'City General Hospital needs O+ donors urgently. You are eligible.',
                 'when'  => '35 minutes ago', 'action' => 'Respond', 'link' => '/blood'],
                ['icon' => 'leaf', 'tone' => 'green', 'unread' => true,
                 'title' => 'Reminder: Wellawatte Beach Cleanup tomorrow',
                 'body'  => 'Meet at 7:00 AM. Bring gloves. Safety guidelines attached.',
                 'when'  => '2 hours ago', 'action' => 'View', 'link' => '/cleanup'],
            ]],
            ['Yesterday', [
                ['icon' => 'leaf', 'tone' => 'green', 'unread' => true,
                 'title' => 'Your item request was accepted',
                 'body'  => 'The donor accepted your request. You can now arrange collection.',
                 'when'  => 'Yesterday, 4:12 PM', 'action' => 'View', 'link' => '/my/activities'],
                ['icon' => 'gift', 'tone' => 'blue', 'unread' => false,
                 'title' => 'Donation received and confirmed',
                 'body'  => 'Kelaniya GN confirmed receipt of your 3 donated blankets.',
                 'when'  => 'Yesterday, 11:02 AM', 'action' => 'View', 'link' => '/my/activities'],
            ]],
            ['Earlier', [
                ['icon' => 'check-circle', 'tone' => 'green', 'unread' => false,
                 'title' => 'Blood donation eligibility confirmed',
                 'body'  => 'Your medical screening was approved for the Colombo camp.',
                 'when'  => '22 Jun 2026', 'action' => '', 'link' => ''],
                ['icon' => 'map-pin', 'tone' => 'amber', 'unread' => false,
                 'title' => 'Dengue high-risk area declared',
                 'body'  => 'Your district was listed. Consider organising a cleanup campaign.',
                 'when'  => '20 Jun 2026', 'action' => 'View map', 'link' => '/dengue'],
            ]],
        ];
    }

    /** Conversation list for the messaging screen. */
    public static function threads(string $role): array
    {
        if ($role === ROLE_ADMIN) {
            return [
                ['initials' => 'NP', 'name' => 'Nadeesha Perera', 'when' => '11:40', 'unread' => 2,
                 'preview' => 'The organization never collected the items I reserved.',
                 'tag' => 'Complaint #C-1042 · Item donation', 'state' => 'Open'],
                ['initials' => 'GL', 'name' => 'Green Lanka Trust', 'when' => '10:55', 'unread' => 0,
                 'preview' => 'Our verification has been pending for six days.',
                 'tag' => 'Dispute #D-318 · Verification', 'state' => 'In progress'],
                ['initials' => 'SK', 'name' => 'Saman Kumara', 'when' => '09:20', 'unread' => 0,
                 'preview' => 'My account was suspended without explanation.',
                 'tag' => 'Appeal #A-207 · Suspension', 'state' => 'Open'],
                ['initials' => 'GN', 'name' => 'Kelaniya GN Office', 'when' => 'Yesterday', 'unread' => 0,
                 'preview' => 'Requesting clarification on the disaster report rejection.',
                 'tag' => 'Query #Q-511 · Disaster report', 'state' => 'In progress'],
                ['initials' => 'OG', 'name' => 'Ocean Guardians', 'when' => '24 Jun', 'unread' => 0,
                 'preview' => 'Thank you, the issue has been sorted out.',
                 'tag' => 'Complaint #C-1029 · Cleanup', 'state' => 'Resolved'],
            ];
        }

        return [
            ['initials' => 'KP', 'name' => 'Kasun Perera', 'when' => '10:24', 'unread' => 2,
             'preview' => 'Sure, you can pick it up this weekend.',
             'tag' => 'Reuse · Dining Table with 4 Chairs', 'state' => ''],
            ['initials' => 'WB', 'name' => 'Wellawatte Beach Cleanup', 'when' => '09:12', 'unread' => 5,
             'preview' => 'Ocean Guardians: Please bring gloves tomorrow.',
             'tag' => 'Cleanup campaign group', 'state' => ''],
            ['initials' => 'HH', 'name' => 'Helping Hands Foundation', 'when' => 'Yesterday', 'unread' => 0,
             'preview' => 'Thank you for your donation of blankets.',
             'tag' => 'Item / Funds Donation', 'state' => ''],
            ['initials' => 'CB', 'name' => 'Colombo Blood Camp', 'when' => 'Yesterday', 'unread' => 0,
             'preview' => 'Camp starts at 8:00 AM at the town hall.',
             'tag' => 'Blood donation group', 'state' => ''],
            ['initials' => 'GN', 'name' => 'Kelaniya GN Office', 'when' => '24 Jun', 'unread' => 0,
             'preview' => 'Your GS letter has been verified.',
             'tag' => 'Reuse · Priority verification', 'state' => ''],
        ];
    }

    /** Bubbles inside the open conversation. `mine` sits on the right. */
    public static function conversation(string $role): array
    {
        if ($role === ROLE_ADMIN) {
            return [
                ['mine' => false, 'text' => 'Hello, I reserved three blankets for a flood-affected family two weeks ago. The organization never collected the items I reserved.', 'when' => '09:15'],
                ['mine' => true,  'text' => 'Thank you for reporting this. I can see your reservation under request #R-8841. I am reviewing the organization\'s activity now.', 'when' => '10:30'],
                ['mine' => false, 'text' => 'I appreciate that. The items are still at my home and the family still needs them.', 'when' => '11:02'],
                ['mine' => true,  'text' => 'Understood. I have contacted Helping Hands Foundation and reassigned your reservation to the Kelaniya GN collection point. They will confirm within 24 hours.', 'when' => '11:35'],
                ['mine' => false, 'text' => 'That works for me. Thank you for the quick response.', 'when' => '11:40'],
            ];
        }

        return [
            ['mine' => true,  'text' => 'Hi! I\'d really value this table for my family. I can pick it up this weekend.', 'when' => '10:02'],
            ['mine' => false, 'text' => 'Hello! Yes, the table is still available and your request has been accepted.', 'when' => '10:15'],
            ['mine' => true,  'text' => 'That\'s wonderful, thank you so much. Which time works best for you?', 'when' => '10:18'],
            ['mine' => false, 'text' => 'Sure, you can pick it up this weekend. Saturday morning around 10 AM works well. The address is in Nugegoda.', 'when' => '10:24'],
        ];
    }

    /** Public organization directory. */
    public static function organizations(): array
    {
        return [
            ['id' => 1, 'name' => 'Sri Lanka Red Cross Society', 'verified' => true,
             'blurb' => 'Humanitarian aid and emergency response across Sri Lanka.',
             'tags' => ['Disaster Relief', 'Health', 'Community Support'],
             'city' => 'Colombo 07', 'site' => 'www.redcross.lk',
             'volunteers' => '12,540', 'activities' => 156, 'type' => 'NGO / Non-Profit'],
            ['id' => 2, 'name' => 'Green Sri Lanka Foundation', 'verified' => true,
             'blurb' => 'Building a greener Sri Lanka through conservation and education.',
             'tags' => ['Environment', 'Sustainability'],
             'city' => 'Battaramulla', 'site' => 'www.greensrilanka.org',
             'volunteers' => '8,210', 'activities' => 98, 'type' => 'NGO / Non-Profit'],
            ['id' => 3, 'name' => 'National Blood Service', 'verified' => true,
             'blurb' => 'Ensuring a safe and adequate blood supply for all.',
             'tags' => ['Health', 'Blood Donation'],
             'city' => 'Colombo 05', 'site' => 'www.nbs.health.gov.lk',
             'volunteers' => '6,875', 'activities' => 210, 'type' => 'Government'],
            ['id' => 4, 'name' => 'Helping Hands Sri Lanka', 'verified' => true,
             'blurb' => 'Providing food, shelter and hope to those in need.',
             'tags' => ['Poverty Relief', 'Food Aid', 'Community Support'],
             'city' => 'Galle', 'site' => 'www.helpinghands.lk',
             'volunteers' => '3,890', 'activities' => 64, 'type' => 'NGO / Non-Profit'],
            ['id' => 5, 'name' => 'Women\'s Empowerment Network', 'verified' => true,
             'blurb' => 'Empowering women and youth for a better tomorrow.',
             'tags' => ['Women Empowerment', 'Youth'],
             'city' => 'Jaffna', 'site' => 'www.wen.lk',
             'volunteers' => '2,760', 'activities' => 51, 'type' => 'Community Group'],
        ];
    }

    /** Dengue risk alerts — the same list the admin and the public screens show. */
    public static function dengueAlerts(): array
    {
        return [
            ['area' => 'Kelaniya & Peliyagoda', 'district' => 'Gampaha District', 'mine' => false,
             'body' => 'Standing water after last week\'s flooding. Multiple confirmed cases near the canal area. Households advised to clear containers and drains.',
             'by' => 'Kelaniya GN Office', 'byRole' => 'Grama Niladhari', 'when' => '2 hours ago', 'initials' => 'GN'],
            ['area' => 'Nugegoda, Maharagama, Boralesgamuwa', 'district' => 'Colombo District', 'mine' => false,
             'body' => 'Sharp rise in reported cases across three adjacent areas. Construction sites with uncovered water tanks identified as breeding grounds.',
             'by' => 'Helping Hands Foundation', 'byRole' => 'Organization', 'when' => '5 hours ago', 'initials' => 'HH'],
            ['area' => 'Wellawatte', 'district' => 'Colombo District', 'mine' => false,
             'body' => 'Blocked roadside drains collecting water after rain. No confirmed cases yet, but conditions favourable for breeding.',
             'by' => 'Nirosha Fernando', 'byRole' => 'Community Member', 'when' => 'Yesterday', 'initials' => 'NF'],
            ['area' => 'Ratnapura Town & Kuruwita', 'district' => 'Ratnapura District', 'mine' => true,
             'body' => 'Post-monsoon standing water across low-lying wards. District health office has issued a public advisory.',
             'by' => 'Platform Admin', 'byRole' => 'Administrator', 'when' => 'Yesterday', 'initials' => 'AD'],
            ['area' => 'Negombo Beach Area', 'district' => 'Gampaha District', 'mine' => false,
             'body' => 'Routine seasonal advisory. Community cleanup completed last weekend; conditions improving.',
             'by' => 'Green Coast Volunteers', 'byRole' => 'Organization', 'when' => '2 days ago', 'initials' => 'GC'],
        ];
    }

    /** Right-hand column of the dengue screen. */
    public static function dengueDistricts(): array
    {
        return [
            'Colombo' => 42, 'Gampaha' => 31, 'Ratnapura' => 18,
            'Kalutara' => 12, 'Kandy' => 9, 'Galle' => 4,
        ];
    }

    public static function denguePrevention(): array
    {
        return [
            'Empty and scrub water containers weekly',
            'Clear blocked roof gutters and drains',
            'Cover water storage tanks tightly',
            'Dispose of tyres, tins and coconut shells',
            'Seek medical advice for fever lasting 2+ days',
        ];
    }

    /** Admin — suspended accounts and removed posts. */
    public static function suspended(): array
    {
        return [
            ['name' => 'Sanduni Fernando', 'sub' => 'sanduni.f@gmail.com', 'type' => 'User Account',
             'reason' => 'Repeated false requests', 'on' => '20 May 2025', 'status' => 'Suspended', 'action' => 'Reinstate'],
            ['name' => 'Ocean Guardians', 'sub' => 'oceanguardians.lk', 'type' => 'Organization',
             'reason' => 'Unverified campaign details', 'on' => '18 May 2025', 'status' => 'Suspended', 'action' => 'Reinstate'],
            ['name' => 'Free Laptops Giveaway', 'sub' => 'Reuse post · 45 reports', 'type' => 'Reuse Post',
             'reason' => 'Suspected fraud', 'on' => '17 May 2025', 'status' => 'Removed', 'action' => 'Restore'],
            ['name' => 'Urgent Blood Need', 'sub' => 'Blood request · unverified hospital', 'type' => 'Blood Post',
             'reason' => 'False information', 'on' => '15 May 2025', 'status' => 'Removed', 'action' => 'Restore'],
            ['name' => 'Ranjan Weerasinghe', 'sub' => 'ranjan.w@gmail.com', 'type' => 'User Account',
             'reason' => 'Abusive messages in group chat', 'on' => '12 May 2025', 'status' => 'Banned', 'action' => 'Review'],
        ];
    }

    /** Admin — complaints and feedback queue. */
    public static function complaints(): array
    {
        return [
            ['id' => 'C-1042', 'subject' => 'Donated items never collected', 'sub' => 'Reserved 3 days ago, no pickup',
             'module' => 'Item Donation', 'by' => 'Kamala Silva', 'byRole' => 'Community member', 'status' => 'Open'],
            ['id' => 'C-1041', 'subject' => 'Volunteer not marked present', 'sub' => 'Attended cleanup but no certificate',
             'module' => 'Cleanup', 'by' => 'Ruwan Jayasuriya', 'byRole' => 'Community member', 'status' => 'Open'],
            ['id' => 'C-1038', 'subject' => 'Duplicate blood request posted', 'sub' => 'Same request listed twice',
             'module' => 'Blood Donation', 'by' => 'Life Blood Bank', 'byRole' => 'Verified organisation', 'status' => 'In Review'],
            ['id' => 'C-1035', 'subject' => 'Reuse item already given away', 'sub' => 'Listing still shows as available',
             'module' => 'Reuse', 'by' => 'Ayesha Nazeer', 'byRole' => 'Community member', 'status' => 'Resolved'],
            ['id' => 'C-1031', 'subject' => 'Funds not transferred to GN', 'sub' => 'Campaign closed 5 days ago',
             'module' => 'Fund Donation', 'by' => 'K. Gunasekara', 'byRole' => 'Grama Niladhari', 'status' => 'Open'],
        ];
    }

    /** Admin — platform-wide announcements. */
    public static function announcements(): array
    {
        return [
            ['id' => 1, 'title' => 'Monsoon preparedness advisory', 'sub' => 'Stay alert for flood warnings',
             'audience' => 'All Users', 'on' => '24 May 2025', 'reach' => '12,480 viewed'],
            ['id' => 2, 'title' => 'Dengue cleanup drive — high risk districts', 'sub' => 'Organise campaigns in your area',
             'audience' => 'Users, Organizations', 'on' => '20 May 2025', 'reach' => '8,214 viewed'],
            ['id' => 3, 'title' => 'New verification process for organizations', 'sub' => 'Updated document requirements',
             'audience' => 'Organizations', 'on' => '16 May 2025', 'reach' => '642 viewed'],
            ['id' => 4, 'title' => 'Sinhala and Tamil language support', 'sub' => 'Platform now available in 3 languages',
             'audience' => 'All Users', 'on' => '10 May 2025', 'reach' => '15,903 viewed'],
        ];
    }

    /**
     * Admin — administrator accounts.
     *
     * The group decided there is exactly one real administrator, so the extra
     * rows below are drawn from the Figma frame and are clearly marked in the
     * view as design placeholders rather than accounts anybody can sign in as.
     */
    public static function admins(): array
    {
        return [
            ['name' => 'Dilani Rathnayake', 'email' => 'dilani.r@togetherthrives.lk',
             'role' => 'Verification Officer', 'perms' => 'Verify, reject requests',
             'active' => 'Today, 08:15 AM', 'status' => 'Active'],
            ['name' => 'Suresh Kumar', 'email' => 'suresh.k@togetherthrives.lk',
             'role' => 'Content Moderator', 'perms' => 'Posts, complaints',
             'active' => 'Yesterday, 04:30 PM', 'status' => 'Active'],
            ['name' => 'Nadeesha Perera', 'email' => 'nadeesha.p@togetherthrives.lk',
             'role' => 'Support Officer', 'perms' => 'Complaints, feedback',
             'active' => '22 May 2025', 'status' => 'Active'],
            ['name' => 'Chamara Silva', 'email' => 'chamara.s@togetherthrives.lk',
             'role' => 'Analytics Viewer', 'perms' => 'Read-only reports',
             'active' => '14 May 2025', 'status' => 'Inactive'],
        ];
    }

    /** GN — disaster reports raised by this officer. */
    public static function disasterReports(): array
    {
        return [
            ['id' => 'DR-2025-0148', 'title' => 'Flood in Kelaniya Area', 'type' => 'Flood',
             'body' => 'Heavy rainfall since Friday night caused the Kelani river to overflow. Low-lying areas of the division are flooded up to waist height and several families have moved to the temple hall.',
             'place' => 'Kelaniya', 'district' => 'Gampaha District', 'landmark' => 'Kelaniya Temple Road',
             'severity' => 'High', 'status' => 'Pending Verification',
             'on' => '25 May 2025', 'at' => '10:30 AM', 'families' => 46, 'displaced' => 178],
            ['id' => 'DR-2025-0139', 'title' => 'Fire Incident in Factory', 'type' => 'Urban Fire',
             'body' => 'Fire broke out in a furniture factory on the Weliweriya main road. No casualties reported; three neighbouring houses were evacuated as a precaution.',
             'place' => 'Weliweriya', 'district' => 'Gampaha District', 'landmark' => 'Weliweriya Junction',
             'severity' => 'High', 'status' => 'Verified',
             'on' => '18 May 2025', 'at' => '09:45 AM', 'families' => 3, 'displaced' => 11],
            ['id' => 'DR-2025-0127', 'title' => 'Landslide Warning — Hill Road', 'type' => 'Landslide',
             'body' => 'Cracks observed on the slope above Hill Road after continuous rain. NBRO has been informed and two households moved out voluntarily.',
             'place' => 'Kelaniya', 'district' => 'Gampaha District', 'landmark' => 'Hill Road, upper ward',
             'severity' => 'Medium', 'status' => 'Verified',
             'on' => '02 May 2025', 'at' => '04:10 PM', 'families' => 2, 'displaced' => 7],
            ['id' => 'DR-2025-0118', 'title' => 'Storm Damage to Roofs', 'type' => 'Cyclone',
             'body' => 'High winds removed roofing sheets from twelve houses in the eastern ward. Temporary sheeting distributed the same evening.',
             'place' => 'Peliyagoda', 'district' => 'Gampaha District', 'landmark' => 'Eastern ward',
             'severity' => 'Medium', 'status' => 'Verified',
             'on' => '21 Apr 2025', 'at' => '07:55 PM', 'families' => 12, 'displaced' => 0],
            ['id' => 'DR-2025-0104', 'title' => 'Road Accident — Kandy Road', 'type' => 'Accident',
             'body' => 'Bus and lorry collision blocked the Kandy Road for four hours. Emergency services attended; report filed for records.',
             'place' => 'Kelaniya', 'district' => 'Gampaha District', 'landmark' => 'Kandy Road, 8th mile post',
             'severity' => 'Low', 'status' => 'Rejected',
             'on' => '30 Mar 2025', 'at' => '06:20 AM', 'families' => 0, 'displaced' => 0],
        ];
    }
}
