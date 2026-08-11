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
 * Check the middleware on a route before adding it.
 */
$items = [
  ['/blood-bank/dashboard', 'Dashboard', 'grid'],
  ['/my/blood', 'My Blood Campaigns', 'droplet'],
  ['/blood', 'Browse Campaigns', 'droplet'],
  ['/reuse', 'Reuse Items', 'leaf'],
  null,
  ['/my/activities', 'My Activities', 'clipboard'],
  ['/organizations', 'Organizations', 'building'],
  null,
  ['/settings', 'Settings', 'settings'],
  ['/support', 'Support', 'life-buoy'],
  ['/report', 'Report', 'help-circle'],
];
require APP_PATH . '/views/partials/sidebar-render.php';
