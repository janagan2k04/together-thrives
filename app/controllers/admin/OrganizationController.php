<?php
namespace Controllers\Admin;

use Core\Controller;
use Core\Request;
use Models\Organization;

/**
 * Non-profits and blood banks registered on the platform. Verify and reject
 * happen through the shared account actions in UserManagementController — the
 * decision applies to the account, not to the organization row.
 */
class OrganizationController extends Controller
{
    public function index(): void
    {
        $filters = array_filter([
            'org_type' => (string) Request::get('type', ''),
            'status'   => (string) Request::get('status', ''),
        ]);

        $this->view('admin.organizations.index', [
            'title'   => 'Organizations',
            'orgs'    => (new Organization())->browseForAdmin($filters),
            'filters' => $filters,
            'types'   => Organization::TYPES,
        ]);
    }
}
