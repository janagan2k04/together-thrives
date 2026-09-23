<?php
namespace Controllers;

use Core\Controller;
use Core\Database;

/**
 * The public organization directory - "Organizations" in every sidebar.
 *
 * Real rows only since 2026-08-27. Six Figma organizations used to follow the
 * real ones marked "sample"; a directory of institutions that do not exist is a
 * claim, not a placeholder, and it is the same complaint the dashboards had.
 *
 * `u.role = ROLE_ORGANIZATION` matters: a Grama Niladhari officer is given an
 * `organizations` profile row the first time they host a campaign, because
 * `cleanup_events.organization_id` is foreign-keyed to that table. That row is a
 * campaign owner, not an NGO, and it has no business in a public directory.
 */
class OrganizationController extends Controller
{
    public function index(): void
    {
        $rows = Database::all(
            "SELECT o.id, o.org_name, o.org_type, o.website, o.description,
                    o.verified_at, c.name AS city
             FROM organizations o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN cities c ON c.id = o.city_id
             WHERE o.deleted_at IS NULL AND u.deleted_at IS NULL
               AND u.status = ? AND u.role = ?
             ORDER BY o.org_name ASC",
            [STATUS_ACTIVE, ROLE_ORGANIZATION]
        );

        $real = array_map(fn($o) => [
            'id'         => (string) $o['id'],
            'name'       => $o['org_name'],
            'verified'   => (bool) $o['verified_at'],
            'blurb'      => $o['description'] ?: 'This organization has not written a description yet.',
            'tags'       => [ucwords(str_replace('_', ' ', $o['org_type']))],
            'city'       => $o['city'] ?: 'Sri Lanka',
            'site'       => $o['website'] ?: '',
            'volunteers' => '-',
            'activities' => '-',
            'type'       => ucwords(str_replace('_', ' ', $o['org_type'])),
        ], $rows);

        // web.php declares /organizations inside its `auth` group, so this is
        // always the signed-in shell. If the group later opens the directory to
        // guests, this and show() pick the shell the reuse module does:
        // is_logged_in() ? 'app' : 'guest'. The views already pad themselves
        // for the guest layout.
        $this->view('organizations.index', [
            'title'         => 'Organizations',
            'organizations' => $real,
        ]);
    }

    public function show(string $id): void
    {
        $org = $this->real((int) $id);

        if (!$org) {
            $this->abort(404, 'Organization not found.');
        }

        $this->view('organizations.show', [
            'title' => $org['name'],
            'org'   => $org,
        ]);
    }

    private function real(int $id): ?array
    {
        $o = Database::one(
            "SELECT o.*, c.name AS city, u.email, u.phone, u.status
             FROM organizations o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN cities c ON c.id = o.city_id
             WHERE o.id = ? AND o.deleted_at IS NULL AND u.deleted_at IS NULL
               AND u.role = ? LIMIT 1",
            [$id, ROLE_ORGANIZATION]
        );
        if (!$o || $o['status'] !== STATUS_ACTIVE) {
            return null;
        }

        return [
            'id'         => (string) $o['id'],
            'name'       => $o['org_name'],
            'verified'   => (bool) $o['verified_at'],
            'blurb'      => $o['description'] ?: 'This organization has not written a description yet.',
            'tags'       => [ucwords(str_replace('_', ' ', $o['org_type']))],
            'city'       => $o['city'] ?: 'Sri Lanka',
            'site'       => $o['website'] ?: '',
            'volunteers' => '-',
            'activities' => '-',
            'type'       => ucwords(str_replace('_', ' ', $o['org_type'])),
            'email'      => $o['email'],
            'phone'      => $o['phone'],
            'address'    => $o['address'],
            'reg'        => $o['reg_number'],
        ];
    }
}
