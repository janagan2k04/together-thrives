<?php
namespace Models\Blood;

use Core\Database;
use Core\Model;

/**
 * The medical screening questionnaire.
 *
 * `campaign_id` is NULL on the eight platform defaults seeded with the schema;
 * an organiser may add questions to their own camp, and those carry the camp's
 * id. `defaultsFor()` returns the defaults plus the camp's own, in sort order,
 * so a camp that has added nothing still gets the full standard form.
 *
 * `disqualifying_answer` is the answer that defers a donor. Scoring lives with
 * the question rather than in the controller for one reason: an organiser can
 * add a question, and a rule written in PHP could never know about it.
 *
 * The table has no `updated_at`, so soft delete is on (there is a `deleted_at`)
 * but the inherited update() cannot be used. Nothing here updates.
 */
class ScreeningQuestion extends Model
{
    protected string $table = 'screening_questions';
    protected array  $fillable = [
        'campaign_id', 'question', 'answer_type', 'disqualifying_answer', 'sort_order', 'is_default',
    ];

    /**
     * The questionnaire a donor answers for one camp - the platform defaults
     * first, then anything the organiser added.
     *
     * Passing null gives the defaults alone, which is what a critical request
     * uses: a request is not a camp and has no organiser questions.
     */
    public function formFor(?int $campaignId = null): array
    {
        $sql = 'SELECT * FROM screening_questions
                 WHERE deleted_at IS NULL AND (campaign_id IS NULL';
        $params = [];

        if ($campaignId) {
            $sql     .= ' OR campaign_id = ?';
            $params[] = $campaignId;
        }

        $sql .= ') ORDER BY (campaign_id IS NOT NULL), sort_order ASC, id ASC';

        return Database::all($sql, $params);
    }

    /** The form field name for one question - `q7` for question 7. */
    public static function fieldName(array $question): string
    {
        return 'q' . $question['id'];
    }
}
