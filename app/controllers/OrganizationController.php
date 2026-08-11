<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Services\SampleData;

/**
 * The public organization directory — "Organizations" in every sidebar.
 *
 * Real verified organizations are listed first. Only one or two exist while the
 * group is testing, and a two-row directory shows nothing about the design, so
 * the Figma entries follow them marked as samples. Delete the second half of
 * `index()` once the platform has real organizations to list.
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
             WHERE o.deleted_at IS NULL AND u.deleted_at IS NULL AND u.status = ?
             ORDER BY o.org_name ASC",
            [STATUS_ACTIVE]
        );

        $real = array_map(fn($o) => [
            'id'         => (string) $o['id'],
            'name'       => $o['org_name'],
            'verified'   => (bool) $o['verified_at'],
            'blurb'      => $o['description'] ?: 'This organization has not written a description yet.',
            'tags'       => [ucwords(str_replace('_', ' ', $o['org_type']))],
            'city'       => $o['city'] ?: 'Sri Lanka',
            'site'       => $o['website'] ?: '',
            'volunteers' => '—',
            'activities' => '—',
            'type'       => ucwords(str_replace('_', ' ', $o['org_type'])),
            'sample'     => false,
        ], $rows);

        $samples = array_map(function ($o) {
            $o['id']     = 's' . $o['id'];
            $o['sample'] = true;
            return $o;
        }, SampleData::organizations());

        // web.php declares /organizations inside its `auth` group, so this is
        // always the signed-in shell. If the group later opens the directory to
        // guests, this and show() pick the shell the reuse module does:
        // is_logged_in() ? 'app' : 'guest'. The views already pad themselves
        // for the guest layout.
        $this->view('organizations.index', [
            'title'         => 'Organizations',
            'organizations' => array_merge($real, $samples),
            'realCount'     => count($real),
        ]);
    }

    public function show(string $id): void
    {
        $org = str_starts_with($id, 's')
            ? $this->sample((int) substr($id, 1))
            : $this->real((int) $id);

        if (!$org) {
            $this->abort(404, 'Organization not found.');
        }

        $this->view('organizations.show', [
            'title' => $org['name'],
            'org'   => $org,
        ]);
    }

    private function sample(int $id): ?array
    {
        foreach (SampleData::organizations() as $o) {
            if ($o['id'] === $id) {
                $o['id']     = 's' . $o['id'];
                $o['sample'] = true;
                return $o;
            }
        }
        return null;
    }

    private function real(int $id): ?array
    {
        $o = Database::one(
            "SELECT o.*, c.name AS city, u.email, u.phone, u.status
             FROM organizations o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN cities c ON c.id = o.city_id
             WHERE o.id = ? AND o.deleted_at IS NULL AND u.deleted_at IS NULL LIMIT 1",
            [$id]
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
            'volunteers' => '—',
            'activities' => '—',
            'type'       => ucwords(str_replace('_', ' ', $o['org_type'])),
            'email'      => $o['email'],
            'phone'      => $o['phone'],
            'address'    => $o['address'],
            'reg'        => $o['reg_number'],
            'sample'     => false,
        ];
    }
}
