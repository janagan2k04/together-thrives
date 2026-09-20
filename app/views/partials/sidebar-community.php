<?php
/**
 * Community member navigation.
 *
 * Labels and icons for a shared route are the same in every sidebar - see the
 * table in sidebar-render.php. Two glyph clashes were fixed here: reuse and
 * cleanup were both 'leaf', and Report is no longer repeated in the topbar.
 *
 * "My Listings" added 2026-08-27. /my/reuse is where a member's own listings
 * live, and nothing linked to it: a listing is created `pending` and an
 * administrator has to approve it before it appears in the browse grid, so the
 * donor posted an item, could not find it on /reuse, and concluded nothing had
 * been saved. It had been saved - there was no way to look at it.
 */
$items = [
  ['/dashboard', 'Dashboard', 'grid'],
  ['/reuse', 'Reuse Items', 'recycle'],
  ['/my/reuse', 'My Listings', 'inbox'],
  ['/cleanup', 'Cleanup Campaigns', 'leaf'],
  ['/donations', 'Donation Requests', 'gift'],
  ['/blood', 'Blood Donation', 'droplet'],
  ['/dengue', 'Dengue Alert', 'bug'],
  null,
  ['/my/activities', 'My Activities', 'clipboard'],
  ['/organizations', 'Organizations', 'building'],
  null,
  ['/settings', 'Settings', 'settings'],
  ['/support', 'Support', 'life-buoy'],
  ['/report', 'Report a Problem', 'help-circle'],
];
require APP_PATH . '/views/partials/sidebar-render.php';
