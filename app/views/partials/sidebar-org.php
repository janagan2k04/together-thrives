<?php
$items = [
  ['/org/dashboard', 'Dashboard', 'grid'],
  ['/org/cleanup', 'My Cleanup Campaigns', 'leaf'],
  ['/my/donations', 'My Donation Requests', 'gift'],
  ['/my/blood', 'Blood Donation', 'droplet'],
  ['/reuse', 'Reuse Items', 'leaf'],
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
