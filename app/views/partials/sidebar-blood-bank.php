<?php
/**
 * Blood banks used to be given the organization sidebar. That looked close
 * enough — both run campaigns — but /org/* is gated `role:organization`, so
 * every one of those links answered 403 to a blood bank, and none of them led
 * back to /blood-bank/dashboard. A blood bank had no working navigation at all.
 *
 * Only routes a blood bank may actually open belong here. `/my/blood` is
 * `role:blood_bank,organization` and is this role's real CRUD screen; the rest
 * of the entries below are the shared pages every signed-in role gets.
 */
$items = [
  ['/blood-bank/dashboard', 'Dashboard'],
  ['/my/blood',             'My Blood Campaigns'],
  ['/blood',                'Browse Campaigns'],
  null,
  ['/organizations', 'Organizations'],
  null,
  ['/settings', 'Settings'],
  ['/support',  'Support'],
];
require APP_PATH . '/views/partials/sidebar-render.php';
