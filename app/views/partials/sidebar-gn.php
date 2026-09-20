<?php
/**
 * Grama Niladhari navigation.
 *
 * Reshaped 2026-08-27 by group decision. The officer used to be given the two
 * public browse pages, /cleanup and /blood, as though they were a volunteer:
 *
 *   "Cleanup Campaigns" -> "My Cleanup Campaigns" (/org/cleanup)
 *       A GS officer HOSTS cleanup and volunteering campaigns for their
 *       division; they do not join somebody else's. `cleanup_events` is owned
 *       through an `organizations` row the module creates for the officer the
 *       first time they post - see CleanupEventController::organisationOrFail().
 *   "Blood Donation" -> "My Blood Campaigns" (/my/blood)
 *       Same reasoning. The officer runs a camp; posting an urgent CRITICAL
 *       request stays closed to the role, on the route as well as on the form.
 *
 * routes/cleanup.php and routes/blood.php carry the matching `block_role`, so
 * both browse pages are closed as URLs and not merely undrawn. Hiding a link is
 * not access control.
 *
 * Nothing here is gated to a role this account does not have - that mistake
 * cost the group a day in week 2. Check the middleware before adding a link.
 */
$items = [
  ['/gn/dashboard', 'Dashboard', 'grid'],
  ['/gn/disasters', 'Reported Disasters', 'alert-triangle'],
  ['/org/cleanup', 'My Cleanup Campaigns', 'leaf'],
  ['/my/blood', 'My Blood Campaigns', 'droplet'],
  ['/my/donations', 'My Donation Requests', 'gift'],
  ['/dengue', 'Dengue Alert', 'bug'],
  null,
  ['/organizations', 'Organizations', 'building'],
  null,
  ['/settings', 'Settings', 'settings'],
  ['/support', 'Support', 'life-buoy'],
  ['/report', 'Report a Problem', 'help-circle'],
];
require APP_PATH . '/views/partials/sidebar-render.php';
