<?php
namespace Controllers;

use Core\Controller;
use Core\Database;
use Core\Request;

/**
 * The topbar search box.
 *
 * Only one module has real rows to search for the interim, so this searches
 * reuse listings for real and lists the other three as sections waiting on
 * their modules. Whoever finishes a module adds their query here rather than
 * building a second search screen.
 */
class SearchController extends Controller
{
    public function index(): void
    {
        $q = trim((string) Request::get('q', ''));

        $items = $q === '' ? [] : Database::all(
            "SELECT i.id, i.title, i.description, i.status, c.name AS city, cat.name AS category
             FROM reuse_items i
             JOIN cities c ON c.id = i.city_id
             JOIN item_categories cat ON cat.id = i.category_id
             WHERE i.deleted_at IS NULL AND i.status != ?
               AND (i.title LIKE ? OR i.description LIKE ?)
             ORDER BY i.created_at DESC LIMIT 20",
            [ITEM_REMOVED, "%{$q}%", "%{$q}%"]
        );

        // The search box only exists on the signed-in topbar, so the route is
        // `auth` and this is always the app shell.
        $this->view('search.index', [
            'title' => $q === '' ? 'Search' : 'Search — ' . $q,
            'q'     => $q,
            'items' => $items,
        ]);
    }
}
