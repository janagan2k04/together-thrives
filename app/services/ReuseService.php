<?php
namespace Services;

use Core\Database;
use Core\FileUploader;
use Core\Logger;
use Models\Reuse\ItemImage;
use Models\Reuse\ReuseItem;

/**
 * Business rules for reuse listings.
 *
 * A listing is a row in `reuse_items` plus zero or more rows in `item_images`,
 * and the photos are files on disk as well. Moving a file cannot be rolled
 * back, so photos are stored first and deleted again if the insert fails -
 * the same shape RegistrationService uses for proof documents.
 */
class ReuseService
{
    /** The design allows five photos per listing, 5 MB each. */
    public const MAX_PHOTOS = 5;

    private ReuseItem $items;
    private ItemImage $images;

    public function __construct()
    {
        $this->items  = new ReuseItem();
        $this->images = new ItemImage();
    }

    /**
     * @param array $photos entries from $_FILES, already normalised
     * @return int the new item id
     */
    public function create(int $donorId, array $data, array $photos): int
    {
        $paths = $this->storePhotos($photos);

        try {
            return Database::transaction(function () use ($donorId, $data, $paths) {
                $itemId = $this->items->create([
                    'donor_id'       => $donorId,
                    'category_id'    => (int) $data['category_id'],
                    'title'          => $data['title'],
                    'description'    => $data['description'],
                    'item_condition' => $data['item_condition'],
                    'quantity'       => (int) $data['quantity'],
                    'city_id'        => (int) $data['city_id'],
                    'pickup_note'    => ($data['pickup_note'] ?? '') ?: null,
                    // Not ITEM_AVAILABLE. Every post on the platform waits for
                    // an administrator; reuse was the last module publishing on
                    // save. ContentVerificationService moves it on.
                    'status'         => ITEM_PENDING,
                ]);

                $this->attachPhotos($itemId, $paths, true);
                Logger::audit("Reuse item {$itemId} listed by user {$donorId}");

                return $itemId;
            });
        } catch (\Throwable $e) {
            $this->discard($paths);
            throw $e;
        }
    }

    /**
     * Edit an existing listing. New photos are added to the ones already there;
     * the donor removes old ones explicitly through $removeImageIds.
     *
     * An approved listing goes back to `pending` on save - the title, condition
     * and photos are exactly what the administrator checked, so letting an edit
     * through silently would publish unreviewed content. The same rule the
     * other three modules apply. A listing that already found a recipient
     * (`reserved` / `donated`) is left alone: sending it back to the queue
     * would strip a decision that has already been acted on.
     *
     * @param array $removeImageIds ids from the edit form - checked against the item
     */
    public function update(int $itemId, array $data, array $photos, array $removeImageIds = []): void
    {
        $current = $this->items->findAny($itemId);
        $requeue = $current && in_array($current['status'], [ITEM_AVAILABLE, ITEM_REJECTED], true);

        $paths = $this->storePhotos($photos);
        $dropped = [];

        try {
            $dropped = Database::transaction(function () use ($itemId, $data, $paths, $removeImageIds, $requeue) {
                $columns = [
                    'category_id'    => (int) $data['category_id'],
                    'title'          => $data['title'],
                    'description'    => $data['description'],
                    'item_condition' => $data['item_condition'],
                    'quantity'       => (int) $data['quantity'],
                    'city_id'        => (int) $data['city_id'],
                    'pickup_note'    => ($data['pickup_note'] ?? '') ?: null,
                ];
                if ($requeue) {
                    $columns['status'] = ITEM_PENDING;
                }

                $this->items->update($itemId, $columns);

                $dropped = $this->detachPhotos($itemId, $removeImageIds);

                // If removing photos left the listing with none, the first of
                // the newly uploaded ones becomes the cover.
                $needsCover = $this->images->primaryFor($itemId) === null;
                $this->attachPhotos($itemId, $paths, $needsCover);

                Logger::audit("Reuse item {$itemId} updated");
                return $dropped;
            });
        } catch (\Throwable $e) {
            $this->discard($paths);
            throw $e;
        }

        // Only once the rows are gone for good - a rolled-back transaction must
        // not leave the listing pointing at files that were already unlinked.
        $this->discard($dropped);
    }

    /**
     * Withdraw a listing. Soft delete only: the row stays for the donor's
     * impact figures and for any request history hanging off it.
     */
    public function withdraw(int $itemId): void
    {
        Database::transaction(function () use ($itemId) {
            $this->items->update($itemId, ['status' => ITEM_REMOVED]);
            $this->items->delete($itemId);
        });

        Logger::audit("Reuse item {$itemId} withdrawn");
    }

    /** Undo a withdrawal: the row comes back and the item is offered again. */
    public function relist(int $itemId): void
    {
        Database::transaction(function () use ($itemId) {
            $this->items->restore($itemId);
            // Back to the queue, not straight back to the grid. A withdrawn
            // listing may never have been approved in the first place, and this
            // way relist cannot be used to put rejected content back in public.
            $this->items->update($itemId, ['status' => ITEM_PENDING]);
        });

        Logger::audit("Reuse item {$itemId} relisted");
    }

    // ---------- photos ----------

    /**
     * $_FILES gives one entry per key with parallel arrays when the input is
     * multiple; turn that back into a list of single-file arrays and drop the
     * empty slots a browser sends for unfilled inputs.
     */
    public static function normalizeFiles(?array $field): array
    {
        if (!$field || !isset($field['name'])) {
            return [];
        }

        // Single-file input - already the right shape.
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
        if (count($photos) > self::MAX_PHOTOS) {
            throw new \RuntimeException('You can upload at most ' . self::MAX_PHOTOS . ' photos.');
        }

        $paths = [];
        try {
            foreach ($photos as $photo) {
                $paths[] = FileUploader::store($photo, 'items', ['jpg', 'png', 'webp'], 5);
            }
        } catch (\Throwable $e) {
            // One bad file must not leave the earlier ones orphaned on disk.
            $this->discard($paths);
            throw $e;
        }
        return $paths;
    }

    private function attachPhotos(int $itemId, array $paths, bool $firstIsCover): void
    {
        foreach ($paths as $i => $path) {
            $this->images->create([
                'item_id'    => $itemId,
                'file_path'  => $path,
                'is_primary' => ($firstIsCover && $i === 0) ? 1 : 0,
            ]);
        }
    }

    /**
     * Remove the photos the donor unticked. Ids come from a form, so each one
     * is matched against this item before anything is deleted.
     *
     * @return string[] the paths whose rows were removed
     */
    private function detachPhotos(int $itemId, array $removeImageIds): array
    {
        $paths = [];
        foreach ($removeImageIds as $imageId) {
            $image = $this->images->find((int) $imageId);
            if (!$image || (int) $image['item_id'] !== $itemId) {
                continue;
            }
            $this->images->delete((int) $image['id']);
            $paths[] = $image['file_path'];
        }

        // Deleting the cover promotes whatever is left, so a listing never
        // shows a blank card while it still has photos.
        $remaining = $paths ? $this->images->forItem($itemId) : [];
        if ($remaining && !$remaining[0]['is_primary']) {
            $this->images->makePrimary((int) $remaining[0]['id'], $itemId);
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
