<?php
$items = [
  ['/dashboard', 'Dashboard', 'grid'],
  ['/reuse', 'Reuse Items', 'leaf'],
  ['/cleanup', 'Cleanup / Volunteering', 'leaf'],
  ['/donations', 'Item / Funds Donation', 'gift'],
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
