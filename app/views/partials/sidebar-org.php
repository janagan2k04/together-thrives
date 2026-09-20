<?php
/**
 * Organization navigation.
 *
 * `/my/blood` is this role's own campaign screen, so it is labelled the same
 * way the blood bank labels it - it used to read "Blood Donation" here, which
 * is the name of the public browse page a community member sees.
 *
 * Three entries were removed on 2026-08-18 by group decision, and this is
 * per-role rather than per-account, so it holds for every organization
 * registered from now on:
 *
 *   /blood  - "Blood Donation" is the donor's side of the module: browse a
 *             camp, join it, answer the screening form. Only a person donates
 *             blood. The campaigns an organization runs are /my/blood, and it
 *             can still post an urgent need from the Campaign Type switch on
 *             /my/blood/create.
 *   /reuse  - a person-to-person module: a household passes on something it no
 *             longer needs. An organization coordinates relief instead, and
 *             takes no part in that exchange.
 *   /my/activities
 *           - reads back the reuse requests this account has made, and nothing
 *             else yet, so with /reuse gone it is an empty page whose only
 *             links point back into reuse. It comes back the day another module
 *             adds a section to MyActivityController.
 *
 * routes/reuse.php and routes/blood.php carry the matching `block_role`, so
 * these are closed as URLs and not merely undrawn.
 */
$items = [
  ['/org/dashboard', 'Dashboard', 'grid'],
  ['/org/cleanup', 'My Cleanup Campaigns', 'leaf'],
  ['/my/donations', 'My Donation Requests', 'gift'],
  ['/my/blood', 'My Blood Campaigns', 'droplet'],
  ['/dengue', 'Dengue Alert', 'bug'],
  null,
  ['/organizations', 'Organizations', 'building'],
  null,
  ['/settings', 'Settings', 'settings'],
  ['/support', 'Support', 'life-buoy'],
  ['/report', 'Report a Problem', 'help-circle'],
];
require APP_PATH . '/views/partials/sidebar-render.php';
