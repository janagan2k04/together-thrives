<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * Photos attached to one dengue alert.
 *
 * Child table: no `deleted_at`, so soft delete is off, and no `updated_at`
 * either - which means the inherited update() would fail, because
 * Model::update() always appends `updated_at = NOW()`. Nothing edits a row
 * here; a photo is added with the alert or not at all.
 *
 * Rows go with the alert through the foreign key's ON DELETE CASCADE.
 */
class DengueAlertImage extends Model
{
    protected string $table = 'dengue_alert_images';
    protected array  $fillable = ['alert_id', 'file_path'];
    protected bool   $softDelete = false;

    /** At most this many photos on one alert. */
    public const MAX_PHOTOS = 4;

    /** Every photo of one alert, oldest first - the order they were uploaded. */
    public function forAlert(int $alertId): array
    {
        return Database::all(
            'SELECT * FROM dengue_alert_images WHERE alert_id = ? ORDER BY id ASC',
            [$alertId]
        );
    }

    /**
     * Every photo of several alerts at once, keyed by alert id.
     *
     * The alert list draws a photo strip on each row, so asking per row would
     * be one query per alert. Returns [] for an id with no photos.
     *
     * @param  int[] $alertIds
     * @return array<int, array<int, array>>
     */
    public function forAlerts(array $alertIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $alertIds)));
        if (!$ids) {
            return [];
        }

        $rows = Database::all(
            'SELECT * FROM dengue_alert_images
              WHERE alert_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')
           ORDER BY alert_id ASC, id ASC',
            $ids
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['alert_id']][] = $row;
        }
        return $out;
    }
}
