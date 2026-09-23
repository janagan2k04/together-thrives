<?php
namespace Controllers;

use Core\Controller;
use Core\Request;
use Core\Session;
use Models\Notification;

/**
 * The notifications screen, shared by every signed-in role.
 *
 * Every row is real. The verification queue writes one on each decision, and so
 * now does every flow that involves two people: a volunteer joining a campaign
 * and the organiser confirming them, a donor reserving an item and the requester
 * confirming it arrived, a donation paid, a screening form submitted, a reuse
 * item requested and handed over.
 *
 * A new account sees an empty screen, which is the true answer to "what has
 * happened on your account". It used to be filled with the Figma's content
 * instead, which meant a brand new account was shown four things that had never
 * happened to it.
 */
class NotificationController extends Controller
{
    private Notification $notifications;

    public function __construct() { $this->notifications = new Notification(); }

    public function index(): void
    {
        $id       = (int) auth()['id'];
        $category = (string) Request::get('category', '');
        if (!isset(self::ICONS[$category])) {
            $category = '';
        }

        $rows = $this->notifications->forUser($id, 40, $category ?: null);

        $this->view('notifications.index', [
            'title'    => 'Notifications',
            'groups'   => $this->group($rows),
            'unread'   => $this->notifications->unreadCount($id),
            'category' => $category,
            // The filter chips: one per `notifications.category` ENUM value, so
            // the list on screen cannot drift from what the column accepts.
            'filters'  => self::FILTERS,
        ]);
    }

    public function markAllRead(): void
    {
        $this->notifications->markAllRead((int) auth()['id']);
        Session::flash('success', 'All notifications marked as read.');
        $this->redirect('/notifications');
    }

    /** Rows grouped by day for the view: [[heading, [item, …]], …]. */
    private function group(array $rows): array
    {
        $buckets = ['Today' => [], 'Yesterday' => [], 'Earlier' => []];
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        foreach ($rows as $r) {
            $day = date('Y-m-d', strtotime($r['created_at']));
            $key = $day === $today ? 'Today' : ($day === $yesterday ? 'Yesterday' : 'Earlier');

            $buckets[$key][] = [
                'icon'   => self::ICONS[$r['category']] ?? 'bell',
                'tone'   => self::TONES[$r['category']] ?? 'blue',
                'unread' => $r['read_at'] === null,
                'title'  => $r['title'],
                'body'   => (string) $r['body'],
                'when'   => time_ago($r['created_at']),
                'action' => $r['link'] ? 'View' : '',
                'link'   => (string) $r['link'],
            ];
        }

        return array_map(
            fn($k) => [$k, $buckets[$k]],
            array_values(array_filter(array_keys($buckets), fn($k) => $buckets[$k] !== []))
        );
    }

    /** The filter chips, labelled as a person would say them. */
    private const FILTERS = [
        ''             => 'All',
        'campaign'     => 'Campaigns',
        'donation'     => 'Donations',
        'volunteer'    => 'Volunteering',
        'blood'        => 'Blood',
        'verification' => 'Verification',
        'system'       => 'System',
    ];

    /** Maps the notifications.category ENUM onto the icon set. */
    private const ICONS = [
        'campaign'     => 'megaphone',
        'donation'     => 'gift',
        'volunteer'    => 'users',
        'verification' => 'shield-check',
        'blood'        => 'droplet',
        'system'       => 'bell',
    ];

    private const TONES = [
        'campaign'     => 'green',
        'donation'     => 'blue',
        'volunteer'    => 'blue',
        'verification' => 'green',
        'blood'        => 'red',
        'system'       => 'amber',
    ];
}
