<?php
namespace Controllers\Reuse;

use Core\Controller;
use Models\Reuse\ItemRequest;

/**
 * MEMBER 4 — "My Activities", the screen behind the sidebar link of that name
 * in both the community and organization sidebars. Until now that link 404'd.
 *
 * It answers the requester's question — "what did I ask for, and what came of
 * it?" Every other screen in the reuse module shows the request flow from the
 * donor's side; this is the other half, and it is where a recipient reviews a
 * donor once an item has changed hands.
 *
 * Only reuse activity is listed today, because reuse is the only module built.
 * Cleanup sign-ups, donation pledges and blood appointments belong on this page
 * too — add a section here when your module lands rather than building a second
 * "my activities" screen.
 */
class MyActivityController extends Controller
{
    public function index(): void
    {
        $requests = (new ItemRequest())->forRequesterList((int) auth()['id']);

        $this->view('reuse.activities', [
            'title'    => 'My Activities',
            'requests' => $requests,
        ]);
    }
}
