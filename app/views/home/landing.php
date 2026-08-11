<?php
/**
 * Unregistered visitor homepage.
 * Stats are placeholders until the module dashboards land in weeks 3–5.
 */
require_once APP_PATH . '/views/partials/icons.php';

$stats = [
    ['2,480',     'Volunteers registered'],
    ['LKR 4.2M',  'Donated through platform'],
    ['186',       'Cleanup campaigns'],
    ['1,340',     'Blood donations'],
    ['62',        'Verified organizations'],
];

$modules = [
    ['green', 'Disaster Cleanup & Volunteering',
     'When floods, landslides, cyclones, fires, or other disasters occur, communities need '
     . 'volunteers to help clean affected areas and support recovery. Verified organizations '
     . 'organize cleanup campaigns while volunteers register and participate safely.',
     ['Disaster is reported', 'Administrator verifies the disaster',
      'Verified organizations create cleanup campaigns', 'Volunteers register',
      'Volunteers receive safety updates', 'Cleanup is conducted',
      'Impact report and certificates are generated']],

    ['amber', 'Item & Fund Donation',
     'Disaster-affected communities often need food, clothing, drinking water, medicine, and '
     . 'financial support. Verified Grama Niladharis and non-profit organizations request '
     . 'donations while community members contribute items or funds.',
     ['GS or organization creates a donation request', 'Admin verifies the request',
      'Public views approved requests', 'Donors reserve items or donate funds',
      'Organization confirms received donations', 'Impact report is published']],

    ['red', 'Blood Donation',
     'Blood donation is a continuous community need, not only during disasters. Blood banks '
     . 'and organizations promote campaigns to nearby eligible donors, and registered users '
     . 'can raise critical blood requests for accidents, emergency surgeries and other '
     . 'medical emergencies.',
     ['A campaign or critical blood request is submitted',
      'Administrator verifies the submission',
      'Verified posts published with location-based notifications',
      'Donors complete the medical screening form',
      'System automatically evaluates eligibility',
      'Eligible donors join, or contact details shared for critical requests',
      'Organizers share updates and publish the impact report']],

    ['blue', 'Reuse Item Donation',
     'Many household items remain useful after their owners no longer need them. This module '
     . 'promotes sustainable living by giving items a second life, and prioritizes verified '
     . 'disaster-affected users when multiple people request the same item.',
     ['A donor lists a reusable item', 'Community members browse and submit requests',
      'Disaster-affected users upload a GS verification letter',
      'Fair distribution rules prioritize disaster-affected users',
      'Donor or administrator selects the recipient',
      'The selected recipient receives the item',
      'Donation is recorded and feedback collected']],
];

$trust = [
    ['Verified Content', 'Every disaster report, cleanup event, donation request, blood donation campaign, critical blood request, and reuse item listing is reviewed and approved by an administrator before publication.'],
    ['Verified Organizations', 'Grama Niladharis, blood banks, and non-profit organizations are verified before they can create official campaigns, requests, or events.'],
    ['Fair Resource Distribution', 'The platform applies fair distribution rules to help ensure donated resources are allocated to those with the greatest need, especially verified disaster-affected communities.'],
    ['Transparency & Accountability', 'Donation records, campaign outcomes, and impact reports are maintained within the platform, allowing users to see how contributions make a difference.'],
    ['Continuous Monitoring', 'Administrators continuously monitor the platform, review reported content, and take action against false or inappropriate posts to maintain a safe and reliable community.'],
    ['Available in Your Language', 'The platform is available in Sinhala, Tamil and English so language is never a barrier to getting or giving help.'],
];
?>

<section class="hero">
  <div class="wrap">
    <span class="hero__badge"><i></i> Built for disaster response &amp; community support in Sri Lanka</span>
    <h1>Welcome to TogetherThrives</h1>
    <p>
      A centralized disaster response and community support platform that connects people,
      volunteers, non-profit organizations, and donors to coordinate disaster relief, blood
      donation, cleanup campaigns, and reusable item sharing — all in one trusted place.
    </p>
    <div class="hero__actions">
      <a class="btn btn--lg btn--onbrand" href="<?= url('/register/community') ?>">Join as a Community Member</a>
      <a class="btn btn--lg btn--ink" href="<?= url('/register/organization') ?>">Register an Organization</a>
    </div>
  </div>
</section>

<div class="wrap">
  <div class="stats">
    <?php foreach ($stats as [$value, $label]): ?>
      <div class="stat">
        <strong><?= e($value) ?></strong>
        <span><?= e($label) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<section class="section section--tint" id="modules">
  <div class="wrap">
    <div class="section__head">
      <span class="eyebrow">Our modules</span>
      <h2>Four connected ways to help</h2>
      <p>Every module works toward one shared goal — helping communities respond to and recover from disaster.</p>
    </div>

    <div class="modules">
      <?php foreach ($modules as $i => [$tone, $name, $overview, $steps]): ?>
        <article class="module module--<?= e($tone) ?>">
          <div class="module__main">
            <div class="module__title">
              <span class="module__num"><?= $i + 1 ?></span>
              <h3><?= e($name) ?></h3>
            </div>
            <span class="module__label">Overview</span>
            <p><?= e($overview) ?></p>
          </div>
          <div class="module__how">
            <strong>How it works</strong>
            <ol>
              <?php foreach ($steps as $n => $step): ?>
                <li><i><?= $n + 1 ?></i> <?= e($step) ?></li>
              <?php endforeach; ?>
            </ol>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--plain" id="roles">
  <div class="wrap">
    <div class="section__head">
      <span class="eyebrow">What can I do?</span>
      <h2>Choose the role that fits you</h2>
      <p>Each account type unlocks a different set of functions across the four modules.</p>
    </div>

    <?php require APP_PATH . '/views/partials/role-cards.php'; ?>
  </div>
</section>

<section class="band">
  <div class="wrap">
    <span class="eyebrow eyebrow--brand">Our goal</span>
    <h2>A trusted digital platform for communities in crisis</h2>
    <p>
      Our goal is to create a trusted digital platform that brings communities and organizations
      together to coordinate disaster response and support communities through blood donation
      and reusable item donation.
    </p>
  </div>
</section>

<section class="section section--tint" id="trust">
  <div class="wrap">
    <div class="section__head">
      <span class="eyebrow">Why you can trust TogetherThrives</span>
      <h2>Verified, fair and accountable</h2>
      <p>Nothing reaches the public until an administrator has reviewed it.</p>
    </div>

    <div class="trust">
      <?php foreach ($trust as [$tTitle, $tDesc]): ?>
        <article class="trust__card">
          <span class="trust__icon"><?= icon('check') ?></span>
          <h3><?= e($tTitle) ?></h3>
          <p><?= e($tDesc) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--plain">
  <div class="wrap">
    <div class="cta">
      <h2>Ready to help your community?</h2>
      <p>Creating an account is free and takes less than two minutes. Browse everything as a guest first if you prefer.</p>
      <div class="cta__actions">
        <a class="btn btn--lg btn--primary" href="<?= url('/register') ?>">Create a Free Account</a>
        <a class="btn btn--lg btn--outline" href="<?= url('/login') ?>">Sign In</a>
      </div>
    </div>
  </div>
</section>
