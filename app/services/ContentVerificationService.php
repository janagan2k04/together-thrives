<?php
namespace Services;

use Core\Database;
use Core\Logger;
use Models\Notification;

/**
 * Administrator decisions on CONTENT - the things users post.
 *
 * VerificationService is the twin of this one and decides on ACCOUNTS. The two
 * are deliberately separate: an account decision changes `users.status` and who
 * may sign in, a content decision changes a row in one of five module tables and
 * who may see it. They share nothing but the shape of the answer.
 *
 * Group rule, 2026-08-18: nothing a user posts reaches the public before an
 * administrator has approved it, and the author is told either way. Cleanup,
 * donation and blood already had per-module review screens; each wrote a status
 * and stopped there, so an organiser was never told their campaign had gone
 * live. Reuse published on save and had no queue at all.
 *
 * Everything about a post type lives in TYPES below - its table, the three
 * states it uses, how to reach its author, and the link the author should be
 * sent to. Adding a sixth means adding an entry, not another controller.
 *
 * `owner_join` is there because the five tables do not agree on what "the
 * author" is. Four hold a `users.id` directly; `cleanup_events.organization_id`
 * is foreign-keyed to `organizations(id)`, the profile row, so reaching the
 * account behind a cleanup campaign takes one more hop. Getting this wrong
 * would not error - it would join to whichever user happens to share the id
 * and notify a stranger.
 *
 * Each module keeps its own review screen; this is what those screens call, so
 * a decision taken from the unified queue and the same decision taken from the
 * module queue are the same decision.
 */
class ContentVerificationService
{
    /**
     * The five things a user can post.
     *
     * `published` is not one word across the four modules - a donation request
     * becomes `open`, a reuse listing becomes `available` - and cleanup has no
     * `rejected` state at all: it expresses a refusal as `cancelled` plus a
     * `cancel_reason`, which is why it alone carries `reject_extra`.
     */
    public const TYPES = [
        'cleanup' => [
            'label'     => 'Cleanup Campaign',
            'table'     => 'cleanup_events',
            'owner'     => 'organization_id',
            'owner_join' => 'JOIN organizations o ON o.id = t.organization_id JOIN users u ON u.id = o.user_id',
            'title'     => 'title',
            'pending'   => EVENT_DRAFT,
            'published' => EVENT_PUBLISHED,
            'rejected'  => EVENT_CANCELLED,
            'reject_extra' => 'cancel_reason',
            'link'      => '/org/cleanup/',
            'icon'      => 'leaf',
        ],
        'donation' => [
            'label'     => 'Donation Request',
            'table'     => 'donation_requests',
            'owner'     => 'requester_id',
            'owner_join' => 'JOIN users u ON u.id = t.requester_id',
            'title'     => 'title',
            'pending'   => REQUEST_PENDING,
            'published' => REQUEST_OPEN,
            'rejected'  => MOD_REJECTED,
            'reject_extra' => 'reject_reason',
            'link'      => '/my/donations/',
            'icon'      => 'gift',
        ],
        'blood' => [
            'label'     => 'Blood Campaign',
            'table'     => 'blood_campaigns',
            'owner'     => 'organizer_id',
            'owner_join' => 'JOIN users u ON u.id = t.organizer_id',
            'title'     => 'title',
            'pending'   => MOD_PENDING,
            'published' => EVENT_PUBLISHED,
            'rejected'  => MOD_REJECTED,
            'reject_extra' => null,
            'link'      => '/my/blood/',
            'icon'      => 'droplet',
        ],
        'critical' => [
            'label'     => 'Critical Blood Request',
            'table'     => 'critical_blood_requests',
            'owner'     => 'requester_id',
            'owner_join' => 'JOIN users u ON u.id = t.requester_id',
            'title'     => 'patient_name',
            'pending'   => MOD_PENDING,
            'published' => EVENT_PUBLISHED,
            'rejected'  => MOD_REJECTED,
            'reject_extra' => null,
            'link'      => '/blood/critical/',
            'icon'      => 'droplet',
        ],
        'reuse' => [
            'label'     => 'Reuse Listing',
            'table'     => 'reuse_items',
            'owner'     => 'donor_id',
            'owner_join' => 'JOIN users u ON u.id = t.donor_id',
            'title'     => 'title',
            'pending'   => ITEM_PENDING,
            'published' => ITEM_AVAILABLE,
            'rejected'  => ITEM_REJECTED,
            'reject_extra' => null,
            'link'      => '/reuse/',
            'icon'      => 'recycle',
        ],
    ];

    private Notification $notifications;

    public function __construct()
    {
        $this->notifications = new Notification();
    }

    // ---------- reading the queue ----------

    /**
     * Every post across all five tables in one list, newest first.
     *
     * A UNION rather than five queries because the screen pages and sorts over
     * the whole set - sorting five separate lists and interleaving them in PHP
     * would give the wrong answer the moment paging is added.
     *
     * @param array $filters `status` ('pending'|'published'|'rejected'),
     *                       `role` (the AUTHOR's role), `type` (a TYPES key)
     */
    public function queue(array $filters = []): array
    {
        $wantStatus = $filters['status'] ?? '';
        $wantRole   = $filters['role']   ?? '';
        $wantType   = $filters['type']   ?? '';

        $selects = [];
        $params  = [];

        foreach (self::TYPES as $key => $t) {
            if ($wantType !== '' && $wantType !== $key) {
                continue;
            }

            // Only the three states this screen decides on. A campaign that is
            // completed or cancelled by its own organiser is not awaiting
            // anything and does not belong in a verification queue.
            $states = [
                'pending'   => $t['pending'],
                'published' => $t['published'],
                'rejected'  => $t['rejected'],
            ];
            if ($wantStatus !== '') {
                if (!isset($states[$wantStatus])) {
                    continue;
                }
                $states = [$wantStatus => $states[$wantStatus]];
            }

            // cleanup shares `cancelled` between "rejected by an admin" and
            // "withdrawn by its organiser". Only the first has a cancel_reason,
            // so that is what separates them.
            $extra = '';
            if ($key === 'cleanup' && isset($states['rejected'])) {
                $extra = count($states) === 1
                    ? " AND t.cancel_reason IS NOT NULL"
                    : " AND (t.status <> '" . EVENT_CANCELLED . "' OR t.cancel_reason IS NOT NULL)";
            }

            $ph = implode(',', array_fill(0, count($states), '?'));
            $selects[] =
                "SELECT '{$key}' AS type,
                        t.id AS row_id,
                        t.{$t['title']} AS title,
                        t.status AS raw_status,
                        t.created_at,
                        u.id AS author_id, u.full_name AS author, u.email AS author_email,
                        u.role AS author_role
                   FROM {$t['table']} t
                   {$t['owner_join']}
                  WHERE t.deleted_at IS NULL AND t.status IN ({$ph}){$extra}";
            foreach ($states as $value) {
                $params[] = $value;
            }
        }

        if (!$selects) {
            return [];
        }

        $sql = '(' . implode(") UNION ALL (", $selects) . ')';
        if ($wantRole !== '') {
            $sql = "SELECT * FROM ({$sql}) q WHERE q.author_role = ?";
            $params[] = $wantRole;
        }
        $sql .= ' ORDER BY created_at DESC';

        return array_map(function (array $r): array {
            $t = self::TYPES[$r['type']];
            $r['label']  = $t['label'];
            $r['icon']   = $t['icon'];
            $r['link']   = $t['link'] . $r['row_id'];
            $r['state']  = $this->stateOf($r['type'], $r['raw_status']);
            return $r;
        }, Database::all($sql, $params));
    }

    /**
     * Extra columns the DETAIL screen needs and the queue does not.
     *
     * Five tables, five shapes. What they share is `city_id`, which since
     * 2026-08-27 names a district, so every one of them joins `cities`. Reuse
     * adds its category name, and cleanup the campaign-owner row that
     * `organization_id` points at - an administrator reading a cleanup campaign
     * wants the organisation's name, not only the account behind it.
     *
     * A type missing from here simply gets the author and `t.*`.
     */
    private const DETAIL = [
        'cleanup'  => [
            'select' => 'c.name AS district, o.org_name AS org_name',
            'join'   => 'LEFT JOIN cities c ON c.id = t.city_id',
        ],
        'donation' => [
            'select' => 'c.name AS district',
            'join'   => 'LEFT JOIN cities c ON c.id = t.city_id',
        ],
        'blood'    => [
            'select' => 'c.name AS district',
            'join'   => 'LEFT JOIN cities c ON c.id = t.city_id',
        ],
        'critical' => [
            'select' => 'c.name AS district',
            'join'   => 'LEFT JOIN cities c ON c.id = t.city_id',
        ],
        'reuse'    => [
            'select' => 'c.name AS district, ic.name AS category_name',
            'join'   => 'LEFT JOIN cities c ON c.id = t.city_id
                         LEFT JOIN item_categories ic ON ic.id = t.category_id',
        ],
    ];

    /**
     * One post in full, so an administrator can read what was submitted before
     * deciding on it.
     *
     * Every column of the row, not a chosen few: the screen decides what to
     * print, and a field left out of a query is a field the administrator was
     * never shown. Unlike `pendingRow()` this reads a post in ANY state, so a
     * decision already taken can still be looked back at.
     */
    public function detail(string $type, int $id): ?array
    {
        $t = self::TYPES[$type] ?? null;
        if (!$t) {
            return null;
        }

        $extra  = self::DETAIL[$type] ?? ['select' => '', 'join' => ''];
        $select = $extra['select'] !== '' ? $extra['select'] . ',' : '';

        $row = Database::one(
            "SELECT t.*, {$select}
                    u.id AS author_id, u.full_name AS author, u.email AS author_email,
                    u.phone AS author_phone, u.role AS author_role
               FROM {$t['table']} t
               {$t['owner_join']}
               {$extra['join']}
              WHERE t.id = ? AND t.deleted_at IS NULL
              LIMIT 1",
            [$id]
        );

        if (!$row) {
            return null;
        }

        $row['type']  = $type;
        $row['label'] = $t['label'];
        $row['icon']  = $t['icon'];
        $row['link']  = $t['link'] . $id;
        $row['state'] = $this->stateOf($type, $row['status']);
        $row['title'] = $row[$t['title']];

        // Child rows the administrator needs to read the post at all. A
        // donation request is a list of things wanted, and a reuse listing is
        // mostly its photographs; neither is on the parent row.
        $row['photos'] = $type === 'reuse'
            ? Database::all(
                "SELECT id, is_primary FROM item_images WHERE item_id = ? ORDER BY is_primary DESC, id ASC",
                [$id]
            )
            : [];

        $row['items'] = $type === 'donation'
            ? Database::all(
                "SELECT item_name, unit, qty_required FROM requested_items
                  WHERE request_id = ? AND deleted_at IS NULL ORDER BY id ASC",
                [$id]
            )
            : [];

        return $row;
    }

    /** How many posts, of any type, are waiting on a decision. */
    public function pendingCount(): int
    {
        $total = 0;
        foreach (self::TYPES as $t) {
            $total += (int) Database::value(
                "SELECT COUNT(*) FROM {$t['table']} WHERE status = ? AND deleted_at IS NULL",
                [$t['pending']]
            );
        }
        return $total;
    }

    // ---------- deciding ----------

    /**
     * Publish a post and tell its author. Returns false when it was not
     * actually pending - a replayed form must not overwrite a decision already
     * taken, or re-notify the author.
     */
    public function approve(string $type, int $id, int $adminId): bool
    {
        $t   = self::TYPES[$type] ?? null;
        $row = $t ? $this->pendingRow($type, $id) : null;
        if (!$row) {
            return false;
        }

        Database::transaction(function () use ($t, $type, $id, $row, $adminId) {
            // Clear any reason left by an earlier refusal, or an approved row
            // would still be carrying the note explaining why it was turned down.
            $clear = $t['reject_extra'] ? [$t['reject_extra'] => null] : [];
            $this->setStatus($t, $id, $t['published'], $adminId, $clear);

            $this->notifications->create([
                'user_id'  => (int) $row['author_id'],
                'title'    => $t['label'] . ' approved',
                'body'     => '“' . $row['title'] . '” has been verified and is now visible on the platform.',
                'category' => Notification::CATEGORY_VERIFICATION,
                'link'     => $t['link'] . $id,
            ]);
        });

        Logger::audit("Admin {$adminId} approved {$type} {$id}");
        return true;
    }

    /** Turn a post down, with a reason the author is shown. */
    public function reject(string $type, int $id, int $adminId, string $reason = ''): bool
    {
        $t   = self::TYPES[$type] ?? null;
        $row = $t ? $this->pendingRow($type, $id) : null;
        if (!$row) {
            return false;
        }

        Database::transaction(function () use ($t, $type, $id, $row, $adminId, $reason) {
            // cancel_reason and reject_reason are both VARCHAR(255); a longer
            // note still reaches the author in full through the notification.
            $extra = $t['reject_extra']
                ? [$t['reject_extra'] => mb_substr($reason ?: 'Rejected by an administrator', 0, 255)]
                : [];
            $this->setStatus($t, $id, $t['rejected'], $adminId, $extra);

            $body = '“' . $row['title'] . '” was not approved, so it is not visible on the platform.';
            if ($reason !== '') {
                $body .= ' Reason: ' . $reason;
            }

            $this->notifications->create([
                'user_id'  => (int) $row['author_id'],
                'title'    => $t['label'] . ' not approved',
                'body'     => mb_substr($body, 0, 500),
                'category' => Notification::CATEGORY_VERIFICATION,
                'link'     => $t['link'] . $id,
            ]);
        });

        Logger::audit("Admin {$adminId} rejected {$type} {$id}" . ($reason !== '' ? ": {$reason}" : ''));
        return true;
    }

    // ---------- internals ----------

    /** The row plus its author, only while it is still awaiting a decision. */
    private function pendingRow(string $type, int $id): ?array
    {
        $t = self::TYPES[$type];

        return Database::one(
            "SELECT t.id, t.{$t['title']} AS title, u.id AS author_id, u.full_name AS author
               FROM {$t['table']} t
               {$t['owner_join']}
              WHERE t.id = ? AND t.status = ? AND t.deleted_at IS NULL
              LIMIT 1",
            [$id, $t['pending']]
        );
    }

    /**
     * Write the new status, and the review stamp where the table has one.
     *
     * `blood_campaigns` and `donation_requests` carry `reviewed_by` /
     * `reviewed_at`; the other three have no such columns, so naming them
     * unconditionally would fail with Unknown column. `updated_at` every table
     * has, and it is what tells the author's screen the decision is recent.
     */
    private const STAMPS_REVIEWER = ['blood_campaigns', 'donation_requests'];

    private function setStatus(array $t, int $id, string $status, int $adminId, array $extra = []): void
    {
        $now     = date('Y-m-d H:i:s');
        $columns = ['status' => $status, 'updated_at' => $now] + $extra;

        if (in_array($t['table'], self::STAMPS_REVIEWER, true)) {
            $columns['reviewed_by'] = $adminId;
            $columns['reviewed_at'] = $now;
        }

        $sets   = implode(', ', array_map(static fn($c) => "{$c} = ?", array_keys($columns)));
        $params = array_values($columns);
        $params[] = $id;

        Database::run("UPDATE {$t['table']} SET {$sets} WHERE id = ?", $params);
    }

    /** Map a module's own word for a state back onto the three this screen uses. */
    private function stateOf(string $type, string $raw): string
    {
        $t = self::TYPES[$type];

        return match ($raw) {
            $t['pending']   => 'pending',
            $t['published'] => 'published',
            $t['rejected']  => 'rejected',
            default         => $raw,
        };
    }
}
