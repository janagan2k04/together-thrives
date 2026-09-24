<?php
namespace Services;

use Models\Blood\DonationRecord;
use Models\Blood\ScreeningQuestion;
use Models\Blood\ScreeningResponse;

/**
 * Scores a completed medical screening form and stores the result.
 *
 * The rule is the table's, not this file's: every question carries the answer
 * that disqualifies, so an organiser who adds a question to their own camp is
 * scored on it without a line of PHP changing. That is why the questionnaire is
 * read back from `screening_questions` before scoring rather than trusted from
 * the form that was posted.
 *
 * Three results, matching the `screening_responses.result` ENUM:
 *
 *   eligible    nothing disqualifying.
 *   deferred    a temporary reason - a recent donation, a fever, antibiotics.
 *               The donor is asked to come back.
 *   ineligible  a permanent bar - age, weight.
 *
 * The difference matters to the donor, which is why the two are not merged: a
 * deferral is "not today" and an ineligibility is "not this camp".
 */
final class ScreeningService
{
    /**
     * Questions whose disqualifying answer is a permanent bar rather than a
     * wait. Matched on the question text because the seeded rows have no flag
     * for it and `screening_questions` is in the frozen schema.
     */
    private const PERMANENT = ['age', 'weight'];

    private ScreeningQuestion $questions;
    private ScreeningResponse $responses;

    public function __construct()
    {
        $this->questions = new ScreeningQuestion();
        $this->responses = new ScreeningResponse();
    }

    /** The questionnaire to draw, defaults plus this camp's own additions. */
    public function form(?int $campaignId = null): array
    {
        return $this->questions->formFor($campaignId);
    }

    /**
     * Score the posted answers and write the response row.
     *
     * `$link` is ['campaign_id' => 12] or ['critical_request_id' => 4]; exactly
     * one, which is what the two nullable columns encode.
     *
     * Returns the stored row's result and reason so the caller can flash it -
     * the same two values the donor will read back on My Registrations, rather
     * than a second sentence written here that could drift from the stored one.
     */
    public function record(int $donorId, array $link, array $answers, ?int $campaignId = null): array
    {
        $questions = $this->questions->formFor($campaignId);
        $failed    = [];

        foreach ($questions as $question) {
            $field = ScreeningQuestion::fieldName($question);
            $given = strtolower(trim((string) ($answers[$field] ?? '')));

            if ($given !== '' && $given === strtolower((string) $question['disqualifying_answer'])) {
                $failed[] = $question;
            }
        }

        [$result, $reason] = $this->verdict($failed);

        $this->responses->create($link + [
            'donor_id'     => $donorId,
            'answers_json' => json_encode($this->answersFor($questions, $answers), JSON_UNESCAPED_UNICODE),
            'result'       => $result,
            'reason'       => $reason,
        ]);

        return ['result' => $result, 'reason' => $reason];
    }

    /**
     * The stored JSON: the question as it was worded on the day, beside the
     * answer given.
     *
     * Storing the text and not only the id is deliberate - a question can be
     * edited or removed later, and a medical record that cannot be read back
     * without the row that produced it is not a record.
     */
    private function answersFor(array $questions, array $answers): array
    {
        $out = [];
        foreach ($questions as $question) {
            $out[] = [
                'id'       => (int) $question['id'],
                'question' => $question['question'],
                'answer'   => (string) ($answers[ScreeningQuestion::fieldName($question)] ?? ''),
            ];
        }
        return $out;
    }

    /** Turn the failed questions into a result and one sentence for the donor. */
    private function verdict(array $failed): array
    {
        if (!$failed) {
            return ['eligible', 'Cleared to donate. Bring your NIC to the venue.'];
        }

        foreach ($failed as $question) {
            foreach (self::PERMANENT as $word) {
                if (stripos($question['question'], $word) !== false) {
                    return ['ineligible', $question['question'] . ' - your answer does not meet the donation criteria.'];
                }
            }
        }

        $first = $failed[0]['question'];
        $more  = count($failed) - 1;

        return ['deferred', $more > 0
            ? $first . ' - this and ' . $more . ' other answer' . ($more === 1 ? '' : 's') . ' defer you for now.'
            : $first . ' - this answer defers you for now.'];
    }

    /**
     * The wait between whole-blood donations, as a sentence, or null when the
     * donor has never given blood.
     */
    public function nextEligibleNote(int $donorId): ?string
    {
        $on = (new DonationRecord())->eligibleAgainOn($donorId);
        if (!$on) {
            return null;
        }

        return $on > date('Y-m-d')
            ? 'You can donate again on ' . format_date($on) . '.'
            : 'You are eligible to donate again.';
    }
}
