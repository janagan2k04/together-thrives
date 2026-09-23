<?php
namespace Services;

use Core\FileUploader;
use Models\DengueAlertImage;
use Models\DengueRiskDistrict;
use Models\District;

/**
 * Shared between the public dengue screen and the administrator's copy of it.
 *
 * The two controllers render the same view and write the same table; the only
 * difference is where the form posts and whether the moderation controls are
 * drawn. Assembling the data in one place is what stops the two screens from
 * drifting apart, which is exactly what happened while both read a sample array
 * and each kept its own copy of the counters.
 */
final class DengueAlertService
{
    private DengueRiskDistrict $alerts;
    private DengueAlertImage   $photos;

    public function __construct()
    {
        $this->alerts = new DengueRiskDistrict();
        $this->photos = new DengueAlertImage();
    }

    /** The validation rules both controllers apply, so neither can be looser. */
    public static function rules(): array
    {
        return [
            'district_id' => 'required|integer',
            'risk_level'  => 'required|in:' . implode(',', array_keys(DengueRiskDistrict::LEVELS)),
            'address'     => 'max:' . DengueRiskDistrict::MAX_ADDRESS,
            'description' => 'max:' . DengueRiskDistrict::MAX_DESCRIPTION,
            'case_count'  => 'integer|min_value:0',
            'reported_on' => 'required|date',
        ];
    }

    /**
     * Everything `views/dengue/index.php` prints.
     *
     * "Alerts near you" is only included when the account has a district on its
     * profile. A member who has not set one is not shown a zero - the tile has
     * no answer for them, so it is left out rather than answered wrongly.
     */
    public function forView(bool $isAdmin): array
    {
        $userId = is_logged_in() ? (int) auth()['id'] : 0;
        $mine   = $userId ? DengueRiskDistrict::districtOf($userId) : null;
        $counts = $this->alerts->stats($mine, $userId);

        $stats = [[(string) $counts['active'], 'Active alerts', 'red']];
        if ($counts['near'] !== null) {
            $stats[] = [(string) $counts['near'], 'Alerts near you', 'ink'];
        }
        if ($userId) {
            $stats[] = [(string) $counts['mine'], 'Alerts you raised', 'green'];
        }
        $stats[] = [(string) $counts['districts'], 'Districts covered', 'ink'];
        $stats[] = [(string) $counts['cases'], 'Cases this month', 'ink'];

        $alerts = $this->alerts->browse();

        return [
            'alerts'     => $alerts,
            'photos'     => $this->photos->forAlerts(array_column($alerts, 'id')),
            'districts'  => (new District())->listAll(),
            'reported'   => $this->alerts->latestPerDistrict(),
            'prevention' => DengueRiskDistrict::PREVENTION,
            'levels'     => DengueRiskDistrict::LEVELS,
            'tones'      => DengueRiskDistrict::TONES,
            'stats'      => $stats,
            'myDistrict' => $mine,
            'isAdmin'    => $isAdmin,
        ];
    }

    /**
     * Write one alert from already-validated input, with any photos of it.
     *
     * `reported_on` is capped at today: an alert dated next week is either a
     * typo or a guess, and neither belongs in a public-health figure.
     *
     * The photos are stored to disk BEFORE the alert row is written, so a
     * rejected file - wrong type, too large - throws while there is still
     * nothing in the database to clean up. Once the row exists the uploads have
     * already succeeded.
     *
     * @param  array $photos normalised $_FILES entries, see normalizeFiles()
     * @throws \RuntimeException when a file is rejected
     */
    public function record(array $data, int $userId, array $photos = []): void
    {
        $on    = $data['reported_on'] > date('Y-m-d') ? date('Y-m-d') : $data['reported_on'];
        $paths = $this->storePhotos($photos);

        try {
            $id = $this->alerts->create([
                'district_id' => (int) $data['district_id'],
                'risk_level'  => $data['risk_level'],
                'address'     => trim((string) ($data['address'] ?? '')) ?: null,
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'case_count'  => ($data['case_count'] ?? '') !== '' ? (int) $data['case_count'] : null,
                'reported_on' => $on,
                'added_by'    => $userId,
            ]);
        } catch (\Throwable $e) {
            $this->discard($paths);
            throw $e;
        }

        foreach ($paths as $path) {
            $this->photos->create(['alert_id' => (int) $id, 'file_path' => $path]);
        }
    }

    /**
     * Turn a multi-file form field into a plain list, dropping the empty slots.
     *
     * PHP gives `photos[]` as parallel arrays rather than a list of files, and
     * an untouched file input still arrives with UPLOAD_ERR_NO_FILE.
     */
    public static function normalizeFiles(?array $field): array
    {
        if (!$field || !isset($field['name'])) {
            return [];
        }

        if (!is_array($field['name'])) {
            return $field['error'] === UPLOAD_ERR_NO_FILE ? [] : [$field];
        }

        $files = [];
        foreach (array_keys($field['name']) as $i) {
            if ($field['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = [
                'name'     => $field['name'][$i],
                'type'     => $field['type'][$i],
                'tmp_name' => $field['tmp_name'][$i],
                'error'    => $field['error'][$i],
                'size'     => $field['size'][$i],
            ];
        }
        return $files;
    }

    /** @return string[] stored relative paths */
    private function storePhotos(array $photos): array
    {
        if (count($photos) > DengueAlertImage::MAX_PHOTOS) {
            throw new \RuntimeException(
                'You can upload at most ' . DengueAlertImage::MAX_PHOTOS . ' photos.'
            );
        }

        $paths = [];
        try {
            foreach ($photos as $photo) {
                $paths[] = FileUploader::store($photo, 'dengue', ['jpg', 'png', 'webp'], 5);
            }
        } catch (\Throwable $e) {
            // One bad file must not leave the earlier ones orphaned on disk.
            $this->discard($paths);
            throw $e;
        }
        return $paths;
    }

    /** @param string[] $paths */
    private function discard(array $paths): void
    {
        foreach ($paths as $path) {
            FileUploader::delete($path);
        }
    }
}
