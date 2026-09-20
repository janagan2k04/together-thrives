<?php
/**
 * The four account types and what each one can do.
 * Shared by the landing page "Choose the role that fits you" section
 * and the standalone /register chooser.
 */
$roles = [
    ['green', 'Community Member', 'Anyone who wants to volunteer, donate or receive help.',
     '/register/community', 'Register as Community Member', [
        ['Cleanup & Volunteering', 'View cleanup events, register as a volunteer, receive event updates and safety notifications'],
        ['Item & Fund Donation', 'View verified donation requests, donate funds, donate requested relief items, view campaign impact'],
        ['Blood Donation', 'Browse campaigns and critical requests, complete medical screening, join eligible campaigns'],
        ['Reuse Item Donation', 'Donate reusable items, request donated items, communicate with donors, upload GS verification'],
     ]],

    ['blue', 'Grama Niladhari (GS)', 'Government officers coordinating relief at divisional level.',
     '/register/gn', 'Register as Grama Niladhari', [
        ['Cleanup & Volunteering', 'Report disaster areas, verify local conditions, confirm cleanup completion'],
        ['Item & Fund Donation', 'Create disaster relief requests, confirm received donations, publish impact reports'],
        ['Blood Donation', 'Submit critical blood requests when required'],
        ['General', 'Support disaster verification and community coordination'],
     ]],

    ['red', 'Blood Bank', 'Licensed blood banks running donation drives.',
     '/register/blood-bank', 'Register as Blood Bank', [
        ['Blood Donation', 'Create blood donation campaigns, submit critical blood requests, manage campaigns, publish impact reports'],
        ['General', 'Reach nearby eligible donors through location-based notifications'],
     ]],

    ['amber', 'Non-Profit Organization', 'Charities and NGOs coordinating relief operations.',
     '/register/organization', 'Register as Organization', [
        ['Cleanup & Volunteering', 'Organize cleanup events, manage volunteers, publish impact reports'],
        ['Item & Fund Donation', 'Create disaster relief requests, confirm received donations, publish campaign outcomes'],
        ['Blood Donation', 'Organize blood donation campaigns, submit critical blood requests, manage campaign updates'],
        ['General', 'Coordinate volunteers and support disaster recovery'],
     ]],
];
?>
<div class="roles">
  <?php foreach ($roles as [$tone, $name, $tagline, $href, $cta, $caps]): ?>
    <article class="role role--<?= e($tone) ?>">
      <header class="role__head">
        <h3><?= e($name) ?></h3>
        <p><?= e($tagline) ?></p>
      </header>
      <div class="role__body">
        <?php foreach ($caps as [$capTitle, $capDesc]): ?>
          <div class="role__cap">
            <strong><?= e($capTitle) ?></strong>
            <p><?= e($capDesc) ?></p>
          </div>
        <?php endforeach; ?>
        <a class="btn" href="<?= url($href) ?>"><?= e($cta) ?></a>
      </div>
    </article>
  <?php endforeach; ?>
</div>
