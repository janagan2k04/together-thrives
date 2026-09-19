<?php
/**
 * Right-hand column of the "Donate an Item" and "Edit Listing" frames.
 *
 * The Fair distribution note used to be one sentence about the cooldown. All
 * five rules are stated here now, because all five are enforced in code and a
 * donor whose recipient is turned away should be able to see why:
 *
 *   1, 2, 3 and 5  Services\ItemRequestService::blockedReason()
 *   4              Services\ItemRequestService::decideLetter(), which is what
 *                  makes the "verified disaster-affected" badge true rather
 *                  than "claimed, and attached a file"
 *
 * The two numbers are read from the constants that enforce them, so the copy
 * cannot drift away from the rule the way a typed "4 weeks" would.
 */
$cooldownWeeks = \Models\Reuse\DistributionCooldown::WEEKS;
$letterMonths  = \Services\ItemRequestService::LETTER_VALID_MONTHS;
?>
<aside>
  <div class="guide-card">
    <div class="guide-card__head"><?= icon('help-circle') ?> Donation Guidelines</div>
    <ul>
      <li>Only list items that are clean, safe and reusable.</li>
      <li>Add clear photos and an honest description of the condition.</li>
      <li>An administrator approves the listing before anyone else can see it.</li>
      <li>You can chat with requesters through in-app messaging.</li>
    </ul>

    <div class="guide-note">
      <strong><?= icon('leaf') ?> Fair distribution</strong>
      <p>How the platform decides who gets an item when several people ask:</p>
      <ol class="guide-rules">
        <li>
          <strong>One request per person, per item.</strong>
          Asking twice does not improve anyone's chances.
        </li>
        <li>
          <strong>A <?= e($cooldownWeeks) ?>-week cooldown on the category.</strong>
          After receiving an item, that member cannot request the same category
          again until the cooldown ends, so the next household gets a turn.
        </li>
        <li>
          <strong>Verified disaster-affected requests come first.</strong>
          They are shown at the top of your queue and marked as verified.
        </li>
        <li>
          <strong>That priority has to be proved, and it expires.</strong>
          The requester attaches a Grama Niladhari letter, an administrator
          checks it, and the approval lasts <?= e($letterMonths) ?> months.
          Being disaster-affected is a circumstance, not a permanent status.
        </li>
        <li>
          <strong>You cannot request your own listing</strong>, or one that is
          reserved, already donated or withdrawn.
        </li>
      </ol>
      <p>The final choice is still yours - these rules decide who may ask.</p>
    </div>
  </div>
</aside>
