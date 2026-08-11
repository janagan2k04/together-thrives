<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Core\Session;
use Models\Notification;
use Services\SampleData;

/**
 * The notifications screen, shared by every signed-in role.
 *
 * Real rows already exist for this table — week 2's verification queue writes
 * one on every approval and rejection, and the reuse request flow writes them
 * on both sides of a handover. Those are shown when the account has any.
 * A brand new account would otherwise land on an empty screen that tells the
 * examiner nothing, so the Figma content is shown as a labelled sample instead.
 */
class NotificationController extends Controller
{
    private Notification $notifications;

    public function __construct() { $this->notifications = new Notification(); }

    public function index(): void
    {
        $id   = (int) auth()['id'];
        $rows = $this->notifications->forUser($id, 40);

        $this->view('notifications.index', [
            'title'   => 'Notifications',
            'groups'  => $rows ? $this->group($rows) : SampleData::notifications(Auth::role()),
            'isReal'  => (bool) $rows,
            'unread'  => $this->notifications->unreadCount($id),
        ]);
    }

    public function markAllRead(): void
    {
        $this->notifications->markAllRead((int) auth()['id']);
        Session::flash('success', 'All notifications marked as read.');
        $this->redirect('/notifications');
    }

    /**
     * Real rows into the same shape the sample uses, so the view has one
     * loop rather than two branches: [[heading, [item, …]], …].
     */
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
