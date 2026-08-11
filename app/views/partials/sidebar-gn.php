<?php
/**
 * Grama Niladhari navigation, matching the sidebar drawn on every frame of GN.svg.
 *
 * /gn/disasters is this role's own screen; /cleanup and /blood are the public
 * browse pages, which a GN officer may open. Nothing here is gated to a role
 * this account does not have — that mistake cost the group a day in week 2.
 */
$items = [
  ['/gn/dashboard', 'Dashboard', 'grid'],
  ['/gn/disasters', 'Reported Disasters', 'bug'],
  ['/my/donations', 'Donation Requests', 'gift'],
  ['/cleanup', 'Volunteering Camps', 'leaf'],
  ['/blood', 'Blood Donation', 'droplet'],
  ['/dengue', 'Dengue Risk Map', 'bug'],
  null,
  ['/my/activities', 'My Activities', 'clipboard'],
  ['/organizations', 'Organizations', 'building'],
  null,
  ['/settings', 'Settings', 'settings'],
  ['/support', 'Support', 'life-buoy'],
  ['/report', 'Report', 'help-circle'],
];
require APP_PATH . '/views/partials/sidebar-render.php';
