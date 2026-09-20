<?php
namespace Models;

use Core\Database;
use Core\Model;

/**
 * An appreciation certificate. Both modules that issue one share this table:
 * `event_id` names a cleanup campaign, `campaign_id` a blood camp, and exactly
 * one of the two is set.
 *
 * The table has no `deleted_at`, no `updated_at` **and no `created_at`** - it
 * stamps `issued_at` instead. That last one matters: Model::create() always
 * appends `created_at`, so the inherited create() fails here with Unknown
 * column, and insert() below writes the row itself. Same family of trap as
 * `event_feedback` having no `updated_at`; check a child table's columns before
 * assuming the base model can write it.
 *
 * `certificate_no` is UNIQUE and is the number printed on the sheet, so it is
 * generated once and read back for ever after. That is the point of storing it:
 * the number on a volunteer's printed copy has to still be the number the system
 * knows a year later, which a number derived from today's date can never be.
 *
 * `file_path` stays NULL. Nothing renders a PDF server-side - the sheet is an
 * HTML page the browser prints - so there is no file to point at.
 */
class Certificate extends Model
{
    protected string $table = 'certificates';
    protected array  $fillable = ['user_id', 'event_id', 'campaign_id', 'certificate_no', 'file_path'];
    protected bool   $softDelete = false;

    /** The one this member holds for this cleanup campaign, if any. */
    public function forEvent(int $userId, int $eventId): ?array
    {
        return Database::one(
            'SELECT * FROM certificates WHERE user_id = ? AND event_id = ? LIMIT 1',
            [$userId, $eventId]
        );
    }

    /** The one this member holds for this blood camp, if any. */
    public function forCampaign(int $userId, int $campaignId): ?array
    {
        return Database::one(
            'SELECT * FROM certificates WHERE user_id = ? AND campaign_id = ? LIMIT 1',
            [$userId, $campaignId]
        );
    }

    /**
     * Find the member's certificate for this campaign, or issue it.
     *
     * `$prefix` is TT-CL for a cleanup campaign and TT-BD for a blood camp. The
     * number is the prefix, the campaign and the member, which is unique by
     * construction and readable off a printed sheet.
     *
     * The UNIQUE key is the real guarantee - two tabs opening the sheet at the
     * same moment cannot both insert - so a duplicate-key error is caught and
     * turned back into a read rather than raised at the volunteer.
     */
    public function issue(int $userId, string $column, int $campaignId, string $prefix): array
    {
        $existing = $column === 'event_id'
            ? $this->forEvent($userId, $campaignId)
            : $this->forCampaign($userId, $campaignId);

        if ($existing) {
            return $existing;
        }

        try {
            $this->insert($userId, $column, $campaignId,
                sprintf('%s-%d-%d', $prefix, $campaignId, $userId));
        } catch (\PDOException $e) {
            // 23000 is the integrity-constraint family, which here can only mean
            // another request inserted the same row first.
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }

        return ($column === 'event_id'
            ? $this->forEvent($userId, $campaignId)
            : $this->forCampaign($userId, $campaignId)) ?? [];
    }

    /**
     * Write the row.
     *
     * `$column` is checked against a fixed pair rather than interpolated from
     * the caller - it is the only part of this query that is not a placeholder.
     */
    private function insert(int $userId, string $column, int $campaignId, string $no): void
    {
        if (!in_array($column, ['event_id', 'campaign_id'], true)) {
            throw new \InvalidArgumentException('Unknown certificate column: ' . $column);
        }

        Database::run(
            "INSERT INTO certificates (user_id, {$column}, certificate_no, issued_at)
             VALUES (?, ?, ?, NOW())",
            [$userId, $campaignId, $no]
        );
    }

    /** Every certificate this member holds, newest first. */
    public function forUser(int $userId): array
    {
        return Database::all(
            'SELECT * FROM certificates WHERE user_id = ? ORDER BY issued_at DESC',
            [$userId]
        );
    }
}
