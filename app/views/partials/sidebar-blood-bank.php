<?php
/**
 * Blood banks used to be given the organization sidebar. That looked close
 * enough - both run campaigns - but /org/* is gated `role:organization`, so
 * every one of those links answered 403 to a blood bank, and none of them led
 * back to /blood-bank/dashboard. A blood bank had no working navigation at all.
 *
 * Only routes a blood bank may actually open belong here. `/my/blood` is
 * `role:blood_bank,organization` and is this role's real CRUD screen; the rest
 * of the entries below are the shared pages every signed-in role gets.
 * Check the middleware on a route before adding it.
 *
 * Two entries are deliberately absent, and this is per-role rather than
 * per-account, so it holds for every blood bank registered from now on:
 *
 *   /blood  - "Blood Donation" is the donor's side of the module: browse a
 *             camp, join it, answer the screening form. A blood bank collects
 *             blood, it does not donate it. Its own camps are /my/blood.
 *   /reuse  - the reuse module is for community members and organizations
 *             giving away household items; a blood bank has no part in it.
 *   /my/activities
 *           - reads back the reuse requests this account has made, and nothing
 *             else yet, so with /reuse gone it is an empty page whose only
 *             links point back into reuse. It comes back the day another
 *             module adds a section to MyActivityController.
 */
$items = [
  ['/blood-bank/dashboard', 'Dashboard', 'grid'],
  ['/my/blood', 'My Blood Campaigns', 'droplet'],
  null,
  ['/organizations', 'Organizations', 'building'],
  null,
  ['/settings', 'Settings', 'settings'],
  ['/support', 'Support', 'life-buoy'],
  ['/report', 'Report a Problem', 'help-circle'],
];
require APP_PATH . '/views/partials/sidebar-render.php';
