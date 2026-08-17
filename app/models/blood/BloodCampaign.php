<?php
namespace Models\Blood;

use Core\Database;
use Core\Model;

/**
 * A blood donation camp posted by an organization or blood bank.
 *
 * `organizer_id` points at `users.id` regardless of `organizer_type` — the
 * type column just says which kind of account posted it, the same shape
 * `reuse_items.donor_id` uses for a single donor role.
 */
class BloodCampaign extends Model
{
    protected string $table = 'blood_campaigns';
    protected array  $fillable = [
        'organizer_id', 'organizer_type', 'title', 'description', 'venue',
        'city_id', 'campaign_date', 'start_time', 'end_time',
        'target_donors', 'contact_phone', 'cover_image', 'auto_group_chat',
        'status',
    ];

    /** Labels for the status ENUM, as the organiser's manage screen shows them. */
    public const STATUSES = [
        'pending'   => 'Pending Approval',
        'published' => 'Visible',
        'ongoing'   => 'Ongoing',
        'completed' => 'Completed',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    /** The eight groups, in the order the Figma and `screening_questions` use. */
    public const GROUPS = ['O-', 'O+', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

    /**
     * Public browse list — the "Upcoming Blood Donation Camps" tab.
     * Only published camps that haven't already happened.
     */
    public function browse(array $filters = [], int $limit = 0, int $offset = 0): array
    {
        [$where, $params] = $this->browseWhere($filters);

        $sql = "SELECT c.*, ct.name AS city, u.full_name AS organiser_name
                  FROM blood_campaigns c
                  JOIN cities ct ON ct.id = c.city_id
                  JOIN users  u  ON u.id  = c.organizer_id
                 WHERE {$where}
              ORDER BY c.campaign_date ASC";
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
        }

        return Database::all($sql, $params);
    }

    /** One campaign with city + organiser name, for the public detail page. */
    public function findWithCity(int $id): ?array
    {
        return Database::one(
            "SELECT c.*, ct.name AS city, u.full_name AS organiser_name
               FROM blood_campaigns c
               JOIN cities ct ON ct.id = c.city_id
               JOIN users  u  ON u.id  = c.organizer_id
              WHERE c.id = ? AND c.deleted_at IS NULL
              LIMIT 1",
            [$id]
        );
    }

    /**
     * The organiser's own campaigns for /my/blood — including pending and
     * rejected ones, since only the organiser needs to see those.
     */
    public function forOrganizer(int $organizerId): array
    {
        return Database::all(
            "SELECT c.*, ct.name AS city
               FROM blood_campaigns c
               JOIN cities ct ON ct.id = c.city_id
              WHERE c.organizer_id = ?
           ORDER BY c.created_at DESC",
            [$organizerId]
        );
    }

    /** One of the organiser's own campaigns — used by detail/edit/update/destroy. */
    public function findForOrganizer(int $id, int $organizerId): ?array
    {
        return Database::one(
            "SELECT c.*, ct.name AS city
               FROM blood_campaigns c
               JOIN cities ct ON ct.id = c.city_id
              WHERE c.id = ? AND c.organizer_id = ?
              LIMIT 1",
            [$id, $organizerId]
        );
    }

    // ---------- filter helpers ----------

    /** Shared WHERE for browse(), so future paging cannot drift. */
    private function browseWhere(array $filters): array
    {
        $sql    = 'c.deleted_at IS NULL AND c.status = ? AND c.campaign_date >= CURDATE()';
        $params = ['published'];

        if (!empty($filters['city_id'])) {
            $sql .= ' AND c.city_id = ?';
            $params[] = (int) $filters['city_id'];
        }
        if (!empty($filters['q'])) {
            $sql .= ' AND (c.title LIKE ? OR c.venue LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        return [$sql, $params];
    }
}