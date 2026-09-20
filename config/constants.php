<?php
/**
 * Shared enums. Use these constants everywhere instead of raw strings -
 * a typo in a string is silent, a typo in a constant is a fatal error.
 * These values must match the ENUMs in schema.sql.
 */

// ---- roles ----
const ROLE_COMMUNITY    = 'community';
const ROLE_ORGANIZATION = 'organization';
const ROLE_GN           = 'grama_niladhari';
const ROLE_BLOOD_BANK   = 'blood_bank';
const ROLE_ADMIN        = 'admin';

// ---- account status ----
const STATUS_PENDING   = 'pending';    // awaiting admin verification
const STATUS_ACTIVE    = 'active';
const STATUS_REJECTED  = 'rejected';
const STATUS_SUSPENDED = 'suspended';

// ---- generic moderation status ----
const MOD_PENDING  = 'pending';
const MOD_APPROVED = 'approved';
const MOD_REJECTED = 'rejected';

// ---- cleanup events ----
const EVENT_DRAFT     = 'draft';
const EVENT_PUBLISHED = 'published';
const EVENT_ONGOING   = 'ongoing';
const EVENT_COMPLETED = 'completed';
const EVENT_CANCELLED = 'cancelled';

// ---- donation requests ----
const REQUEST_PENDING   = 'pending';
const REQUEST_OPEN      = 'open';
const REQUEST_FULFILLED = 'fulfilled';
const REQUEST_EXPIRED   = 'expired';
const REQUEST_CANCELLED = 'cancelled';

// ---- reuse items ----
// A listing is created ITEM_PENDING and only an administrator moves it to
// ITEM_AVAILABLE. ITEM_REJECTED is the refusal. Both added 2026-08-18 with the
// group rule that nothing a user posts reaches the public unreviewed.
const ITEM_PENDING   = 'pending';
const ITEM_REJECTED  = 'rejected';
const ITEM_AVAILABLE = 'available';
const ITEM_RESERVED  = 'reserved';
const ITEM_DONATED   = 'donated';
const ITEM_REMOVED   = 'removed';

// ---- blood ----
const BLOOD_ELIGIBLE   = 'eligible';
const BLOOD_INELIGIBLE = 'ineligible';
const BLOOD_DEFERRED   = 'deferred';

// ---- misc ----
const PER_PAGE = 12;
