-- ============================================================
-- TogetherThrives - COMPLETE DATABASE IN ONE FILE
-- Group 23
--
-- Import this ONE file in phpMyAdmin (Import tab) or with:
--     mysql -u root < togetherthrives-full.sql
-- It creates the `togetherthrives` database, all tables, and the
-- six demo accounts. Nothing else needs to be run.
--
-- Contents, in order:
--   PART 1  schema.sql  - all tables. Every file in migrations/ is
--                         already folded into it, so the migrations
--                         are NOT repeated here. Running them again
--                         would fail with "Duplicate column".
--   PART 2  seeds/seed.sql - districts, cities, reuse categories,
--                         default blood screening questions, and the
--                         six demo accounts with their profile rows.
--
-- Demo accounts - password for all six:  Password@123
--   admin@togetherthrives.com   Platform Admin
--   keerthy@test.com            Keerthy Ganesh (community)
--   janindu@test.com            Janindu Perera (community)
--   hope@test.com               Hope Foundation Lanka (organization)
--   ajmal@test.com              Ajmal Riyaz (Grama Niladhari)
--   bank@test.com               National Blood Bank Colombo (blood bank)
--
-- For a FRESH database only. If `togetherthrives` already has its
-- tables, the import stops at the first CREATE TABLE with "table
-- already exists" and changes nothing. To start over, drop the
-- database in phpMyAdmin first.
--
-- Generated 2026-09-19 from schema.sql + seeds/seed.sql.
-- If either of those changes, regenerate this file.
-- ============================================================

-- ############################################################
-- PART 1 - SCHEMA (schema.sql)
-- ############################################################

-- ============================================================
-- TogetherThrives - FULL SCHEMA
-- Run this ONCE in week 1. Do not alter tables mid-project
-- without telling the whole group.
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS togetherthrives
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE togetherthrives;

-- ---------- LOCATION ----------
CREATE TABLE districts (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE,
  province VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE cities (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  district_id INT UNSIGNED NOT NULL,
  name        VARCHAR(80) NOT NULL,
  INDEX idx_city_district (district_id),
  FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB;

-- ---------- IDENTITY ----------
CREATE TABLE users (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name      VARCHAR(120) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  phone          VARCHAR(15),
  nic            VARCHAR(15),
  -- Scan of the applicant's NIC, uploaded at community registration. Added by
  -- migration 2026_08_07; folded in here so a fresh import needs no migration.
  nic_document_path VARCHAR(255) NULL,
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('community','organization','grama_niladhari','blood_bank','admin')
                 NOT NULL DEFAULT 'community',
  status         ENUM('pending','active','rejected','suspended') NOT NULL DEFAULT 'pending',
  city_id        INT UNSIGNED,
  -- Where the member lives, for the reuse module: a GS letter is issued by the
  -- officer of the division the requester actually lives in. `grama_niladharis`
  -- holds the same two names, but that table describes the OFFICER who holds a
  -- division, not a resident of one. Added by migration 2026_08_27; folded in
  -- here so a fresh import needs no migration.
  ds_division    VARCHAR(120) NULL,
  gn_division    VARCHAR(120) NULL,
  blood_group    ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-'),
  avatar_path    VARCHAR(255),
  locale         ENUM('en','si','ta') NOT NULL DEFAULT 'en',
  email_verified_at DATETIME NULL,
  last_login_at  DATETIME NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL,
  deleted_at     DATETIME NULL,
  INDEX idx_users_role   (role),
  INDEX idx_users_status (status),
  INDEX idx_users_city   (city_id),
  FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB;

CREATE TABLE organizations (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        BIGINT UNSIGNED NOT NULL UNIQUE,
  org_name       VARCHAR(150) NOT NULL,
  reg_number     VARCHAR(60)  NOT NULL,
  org_type       ENUM('non_profit','blood_bank','community_group') NOT NULL DEFAULT 'non_profit',
  address        VARCHAR(255),
  city_id        INT UNSIGNED,
  website        VARCHAR(180),
  description    TEXT,
  logo_path      VARCHAR(255),
  document_path  VARCHAR(255),
  verified_at    DATETIME NULL,
  verified_by    BIGINT UNSIGNED NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL,
  deleted_at     DATETIME NULL,
  FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (city_id)    REFERENCES cities(id),
  FOREIGN KEY (verified_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE grama_niladharis (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  gn_division   VARCHAR(120) NOT NULL,
  division_code VARCHAR(30)  NOT NULL,
  ds_division   VARCHAR(120),
  district_id   INT UNSIGNED NOT NULL,
  office_phone  VARCHAR(15),
  document_path VARCHAR(255),
  verified_at   DATETIME NULL,
  verified_by   BIGINT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL,
  deleted_at    DATETIME NULL,
  FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB;

CREATE TABLE blood_banks (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  bank_name     VARCHAR(150) NOT NULL,
  license_no    VARCHAR(60)  NOT NULL,
  hospital_name VARCHAR(150),
  address       VARCHAR(255),
  city_id       INT UNSIGNED,
  hotline       VARCHAR(15),
  document_path VARCHAR(255),
  verified_at   DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL,
  deleted_at    DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (city_id) REFERENCES cities(id)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(150) NOT NULL,
  token      VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reset_token (token)
) ENGINE=InnoDB;

CREATE TABLE email_verifications (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NOT NULL,
  token      VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_verify_token (token),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- MEMBER 1: CLEANUP ----------
CREATE TABLE disaster_reports (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_id BIGINT UNSIGNED NOT NULL,
  title       VARCHAR(160) NOT NULL,
  disaster_type ENUM('flood','landslide','cyclone','fire','accident','other') NOT NULL,
  description TEXT NOT NULL,
  location    VARCHAR(255) NOT NULL,
  city_id     INT UNSIGNED NOT NULL,
  severity    ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  reject_reason VARCHAR(255),
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NULL,
  deleted_at  DATETIME NULL,
  INDEX idx_disaster_status (status),
  FOREIGN KEY (reporter_id) REFERENCES users(id),
  FOREIGN KEY (city_id)     REFERENCES cities(id)
) ENGINE=InnoDB;

CREATE TABLE disaster_report_photos (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  report_id  BIGINT UNSIGNED NOT NULL,
  file_path  VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (report_id) REFERENCES disaster_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cleanup_events (
  id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id    BIGINT UNSIGNED NOT NULL,
  disaster_report_id BIGINT UNSIGNED NULL,
  title              VARCHAR(160) NOT NULL,
  description        TEXT NOT NULL,
  category           ENUM('river','beach','urban','disaster_relief','dengue','other') NOT NULL,
  event_date         DATE NOT NULL,
  start_time         TIME NOT NULL,
  end_time           TIME NOT NULL,
  meeting_point      VARCHAR(255) NOT NULL,
  city_id            INT UNSIGNED NOT NULL,
  volunteer_limit    INT UNSIGNED NOT NULL DEFAULT 50,
  equipment_provided TEXT,
  safety_guidelines  TEXT NOT NULL,
  cover_image        VARCHAR(255),
  status             ENUM('draft','published','ongoing','completed','cancelled') NOT NULL DEFAULT 'draft',
  cancel_reason      VARCHAR(255),
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NULL,
  deleted_at         DATETIME NULL,
  INDEX idx_event_status (status),
  INDEX idx_event_city   (city_id),
  INDEX idx_event_date   (event_date),
  FOREIGN KEY (organization_id)    REFERENCES organizations(id),
  FOREIGN KEY (disaster_report_id) REFERENCES disaster_reports(id),
  FOREIGN KEY (city_id)            REFERENCES cities(id)
) ENGINE=InnoDB;

CREATE TABLE event_registrations (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id     BIGINT UNSIGNED NOT NULL,
  user_id      BIGINT UNSIGNED NOT NULL,
  emergency_contact_name  VARCHAR(120),
  emergency_contact_phone VARCHAR(15),
  skills       VARCHAR(255),
  status       ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  work_area_id BIGINT UNSIGNED NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NULL,
  deleted_at   DATETIME NULL,
  UNIQUE KEY uq_event_user (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES cleanup_events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE work_areas (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id   BIGINT UNSIGNED NOT NULL,
  name       VARCHAR(120) NOT NULL,
  team_leader_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  FOREIGN KEY (event_id) REFERENCES cleanup_events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE attendance (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_id BIGINT UNSIGNED NOT NULL UNIQUE,
  checked_in_at   DATETIME NULL,
  checked_out_at  DATETIME NULL,
  marked_by       BIGINT UNSIGNED NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (registration_id) REFERENCES event_registrations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE waste_records (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id    BIGINT UNSIGNED NOT NULL,
  waste_type  VARCHAR(80) NOT NULL,
  quantity_kg DECIMAL(10,2) NOT NULL,
  disposal_status ENUM('pending','scheduled','disposed') NOT NULL DEFAULT 'pending',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at  DATETIME NULL,
  FOREIGN KEY (event_id) REFERENCES cleanup_events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE event_photos (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id   BIGINT UNSIGNED NOT NULL,
  file_path  VARCHAR(255) NOT NULL,
  photo_type ENUM('before','after','during') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES cleanup_events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cleanup_impacts (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id          BIGINT UNSIGNED NOT NULL UNIQUE,
  volunteers_attended INT UNSIGNED NOT NULL DEFAULT 0,
  total_waste_kg    DECIMAL(10,2) NOT NULL DEFAULT 0,
  summary           TEXT,
  published_at      DATETIME NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (event_id) REFERENCES cleanup_events(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE certificates (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT UNSIGNED NOT NULL,
  event_id        BIGINT UNSIGNED NULL,
  campaign_id     BIGINT UNSIGNED NULL,
  certificate_no  VARCHAR(60) NOT NULL UNIQUE,
  file_path       VARCHAR(255),
  issued_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE event_feedback (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_id   BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  rating     TINYINT UNSIGNED NOT NULL,
  safety_rating TINYINT UNSIGNED,
  comment    TEXT,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_feedback (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES cleanup_events(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- MEMBER 2: DONATION ----------
CREATE TABLE donation_requests (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requester_id    BIGINT UNSIGNED NOT NULL,
  requester_type  ENUM('grama_niladhari','organization','admin') NOT NULL,
  request_type    ENUM('items','funds') NOT NULL,
  title           VARCHAR(160) NOT NULL,
  description     TEXT NOT NULL,
  disaster_details TEXT,
  affected_area   VARCHAR(255) NOT NULL,
  city_id         INT UNSIGNED NOT NULL,
  pickup_point    VARCHAR(255),
  target_amount   DECIMAL(12,2) NULL,
  collected_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  starts_on       DATE NOT NULL,
  ends_on         DATE NOT NULL,
  cover_image     VARCHAR(255),
  status          ENUM('pending','open','fulfilled','expired','rejected','cancelled') NOT NULL DEFAULT 'pending',
  reviewed_by     BIGINT UNSIGNED NULL,
  reviewed_at     DATETIME NULL,
  reject_reason   VARCHAR(255),
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL,
  deleted_at      DATETIME NULL,
  INDEX idx_dr_status (status),
  INDEX idx_dr_city   (city_id),
  FOREIGN KEY (requester_id) REFERENCES users(id),
  FOREIGN KEY (city_id)      REFERENCES cities(id)
) ENGINE=InnoDB;

CREATE TABLE requested_items (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id   BIGINT UNSIGNED NOT NULL,
  item_name    VARCHAR(120) NOT NULL,
  unit         VARCHAR(30) NOT NULL,
  qty_required INT UNSIGNED NOT NULL,
  qty_reserved INT UNSIGNED NOT NULL DEFAULT 0,
  qty_received INT UNSIGNED NOT NULL DEFAULT 0,
  deleted_at   DATETIME NULL,
  FOREIGN KEY (request_id) REFERENCES donation_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE item_reservations (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requested_item_id BIGINT UNSIGNED NOT NULL,
  donor_id       BIGINT UNSIGNED NOT NULL,
  quantity       INT UNSIGNED NOT NULL,
  status         ENUM('reserved','delivered','confirmed','cancelled') NOT NULL DEFAULT 'reserved',
  confirmed_by   BIGINT UNSIGNED NULL,
  confirmed_at   DATETIME NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL,
  deleted_at     DATETIME NULL,
  FOREIGN KEY (requested_item_id) REFERENCES requested_items(id) ON DELETE CASCADE,
  FOREIGN KEY (donor_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE fund_donations (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id  BIGINT UNSIGNED NOT NULL,
  donor_id    BIGINT UNSIGNED NULL,
  amount      DECIMAL(12,2) NOT NULL,
  fee         DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_anonymous TINYINT(1) NOT NULL DEFAULT 0,
  status      ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NULL,
  deleted_at  DATETIME NULL,
  FOREIGN KEY (request_id) REFERENCES donation_requests(id),
  FOREIGN KEY (donor_id)   REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fund_donation_id BIGINT UNSIGNED NOT NULL,
  gateway         VARCHAR(40) NOT NULL DEFAULT 'payhere_sandbox',
  transaction_ref VARCHAR(120) NOT NULL,
  receipt_no      VARCHAR(60) UNIQUE,
  amount          DECIMAL(12,2) NOT NULL,
  status          ENUM('initiated','success','failed') NOT NULL DEFAULT 'initiated',
  raw_response    TEXT,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (fund_donation_id) REFERENCES fund_donations(id)
) ENGINE=InnoDB;

CREATE TABLE donation_impacts (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id   BIGINT UNSIGNED NOT NULL UNIQUE,
  families_helped INT UNSIGNED,
  summary      TEXT,
  published_at DATETIME NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES donation_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- MEMBER 3: BLOOD ----------
CREATE TABLE blood_campaigns (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organizer_id    BIGINT UNSIGNED NOT NULL,
  organizer_type  ENUM('organization','blood_bank') NOT NULL,
  title           VARCHAR(160) NOT NULL,
  description     TEXT NOT NULL,
  venue           VARCHAR(255) NOT NULL,
  -- Street address of the venue. `venue` is a place name and `city_id` the
  -- city; neither tells a donor which door to walk through. Added by migration
  -- 2026_08_18; folded in here so a fresh import needs no migration.
  address         VARCHAR(255) NULL,
  city_id         INT UNSIGNED NOT NULL,
  campaign_date   DATE NOT NULL,
  -- Closing day of a multi-day camp. Added by migration 2026_08_18.
  campaign_end_date DATE NULL,
  start_time      TIME NOT NULL,
  end_time        TIME NOT NULL,
  target_donors   INT UNSIGNED DEFAULT 0,
  contact_phone   VARCHAR(15),
  cover_image     VARCHAR(255),
  auto_group_chat TINYINT(1) NOT NULL DEFAULT 1,
  status          ENUM('pending','published','ongoing','completed','rejected','cancelled') NOT NULL DEFAULT 'pending',
  reviewed_by     BIGINT UNSIGNED NULL,
  reviewed_at     DATETIME NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL,
  deleted_at      DATETIME NULL,
  INDEX idx_bc_status (status),
  INDEX idx_bc_city   (city_id),
  FOREIGN KEY (organizer_id) REFERENCES users(id),
  FOREIGN KEY (city_id)      REFERENCES cities(id)
) ENGINE=InnoDB;

CREATE TABLE critical_blood_requests (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  requester_id  BIGINT UNSIGNED NOT NULL,
  patient_name  VARCHAR(120),
  blood_group   ENUM('A+','A-','B+','B-','O+','O-','AB+','AB-') NOT NULL,
  units_needed  INT UNSIGNED NOT NULL DEFAULT 1,
  -- How hard the administrator queue and the request card should shout. Added
  -- by migration 2026_08_18.
  priority      ENUM('urgent','high','normal') NOT NULL DEFAULT 'normal',
  hospital      VARCHAR(160) NOT NULL,
  -- Street address of the hospital. Added by migration 2026_08_18.
  address       VARCHAR(255) NULL,
  -- "Ward 12, Bed 4". Free text, optional. Added by migration 2026_08_18.
  ward          VARCHAR(60) NULL,
  city_id       INT UNSIGNED NOT NULL,
  needed_by     DATETIME NOT NULL,
  contact_phone VARCHAR(15) NOT NULL,
  notes         TEXT,
  status        ENUM('pending','published','fulfilled','expired','rejected') NOT NULL DEFAULT 'pending',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL,
  deleted_at    DATETIME NULL,
  INDEX idx_cbr_group (blood_group),
  FOREIGN KEY (requester_id) REFERENCES users(id),
  FOREIGN KEY (city_id)      REFERENCES cities(id)
) ENGINE=InnoDB;

CREATE TABLE screening_questions (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id   BIGINT UNSIGNED NULL,
  question      VARCHAR(255) NOT NULL,
  answer_type   ENUM('yes_no','number','text') NOT NULL DEFAULT 'yes_no',
  disqualifying_answer VARCHAR(40),
  sort_order    INT UNSIGNED DEFAULT 0,
  is_default    TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at    DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE screening_responses (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id  BIGINT UNSIGNED NULL,
  critical_request_id BIGINT UNSIGNED NULL,
  donor_id     BIGINT UNSIGNED NOT NULL,
  answers_json JSON NOT NULL,
  result       ENUM('eligible','ineligible','deferred') NOT NULL,
  reason       VARCHAR(255),
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (donor_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE blood_donation_records (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donor_id    BIGINT UNSIGNED NOT NULL,
  campaign_id BIGINT UNSIGNED NULL,
  donated_at  DATE NOT NULL,
  units       DECIMAL(4,2) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at  DATETIME NULL,
  FOREIGN KEY (donor_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- MEMBER 4: REUSE ----------
CREATE TABLE item_categories (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE reuse_items (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  donor_id     BIGINT UNSIGNED NOT NULL,
  category_id  INT UNSIGNED NOT NULL,
  title        VARCHAR(160) NOT NULL,
  description  TEXT NOT NULL,
  item_condition ENUM('new','good','fair','usable') NOT NULL DEFAULT 'good',
  quantity     INT UNSIGNED NOT NULL DEFAULT 1,
  city_id      INT UNSIGNED NOT NULL,
  pickup_note  VARCHAR(255),
  -- 'pending' is the default: a listing waits for an administrator before the
  -- public sees it, like every other post on the platform. 'rejected' is the
  -- refusal. Both added by migration 2026_08_18.
  status       ENUM('pending','available','reserved','donated','removed','rejected')
               NOT NULL DEFAULT 'pending',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NULL,
  deleted_at   DATETIME NULL,
  INDEX idx_ri_status   (status),
  INDEX idx_ri_category (category_id),
  INDEX idx_ri_city     (city_id),
  FOREIGN KEY (donor_id)    REFERENCES users(id),
  FOREIGN KEY (category_id) REFERENCES item_categories(id),
  FOREIGN KEY (city_id)     REFERENCES cities(id)
) ENGINE=InnoDB;

CREATE TABLE item_images (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id    BIGINT UNSIGNED NOT NULL,
  file_path  VARCHAR(255) NOT NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES reuse_items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE gs_verifications (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  letter_path VARCHAR(255) NOT NULL,
  gn_division VARCHAR(120),
  status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  valid_until DATE NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at  DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE item_requests (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id      BIGINT UNSIGNED NOT NULL,
  requester_id BIGINT UNSIGNED NOT NULL,
  reason       TEXT,
  is_disaster_affected TINYINT(1) NOT NULL DEFAULT 0,
  gs_verification_id BIGINT UNSIGNED NULL,
  financial_statement_path VARCHAR(255),
  status       ENUM('pending','selected','rejected','withdrawn') NOT NULL DEFAULT 'pending',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NULL,
  deleted_at   DATETIME NULL,
  UNIQUE KEY uq_item_requester (item_id, requester_id),
  FOREIGN KEY (item_id)      REFERENCES reuse_items(id) ON DELETE CASCADE,
  FOREIGN KEY (requester_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE distribution_cooldowns (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NOT NULL,
  category_id   INT UNSIGNED NOT NULL,
  received_at   DATETIME NOT NULL,
  cooldown_until DATETIME NOT NULL,
  INDEX idx_cooldown_user (user_id),
  FOREIGN KEY (user_id)     REFERENCES users(id),
  FOREIGN KEY (category_id) REFERENCES item_categories(id)
) ENGINE=InnoDB;

CREATE TABLE reuse_feedback (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id      BIGINT UNSIGNED NOT NULL,
  recipient_id BIGINT UNSIGNED NOT NULL,
  rating       TINYINT UNSIGNED NOT NULL,
  comment      TEXT,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at   DATETIME NULL,
  FOREIGN KEY (item_id)      REFERENCES reuse_items(id) ON DELETE CASCADE,
  FOREIGN KEY (recipient_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- CHAT ----------
CREATE TABLE conversations (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type       ENUM('direct','group') NOT NULL DEFAULT 'direct',
  title      VARCHAR(160),
  context_type ENUM('cleanup','donation','blood','reuse') NULL,
  context_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE conversation_members (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  user_id         BIGINT UNSIGNED NOT NULL,
  last_read_at    DATETIME NULL,
  joined_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_conv_user (conversation_id, user_id),
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)         REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE messages (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender_id       BIGINT UNSIGNED NOT NULL,
  body            TEXT,
  message_type    ENUM('text','image','audio','file') NOT NULL DEFAULT 'text',
  attachment_path VARCHAR(255),
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at      DATETIME NULL,
  INDEX idx_msg_conv (conversation_id, created_at),
  FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id)       REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- SYSTEM ----------
CREATE TABLE notifications (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  title       VARCHAR(160) NOT NULL,
  body        VARCHAR(500),
  category    ENUM('campaign','donation','volunteer','verification','blood','system') NOT NULL DEFAULT 'system',
  link        VARCHAR(255),
  read_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_user (user_id, read_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reports (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_id   BIGINT UNSIGNED NOT NULL,
  content_type  ENUM('user','cleanup_event','donation_request','blood_campaign','reuse_item','message') NOT NULL,
  content_id    BIGINT UNSIGNED NOT NULL,
  reason        VARCHAR(120) NOT NULL,
  details       TEXT,
  status        ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
  handled_by    BIGINT UNSIGNED NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_report_content (content_type, content_id),
  FOREIGN KEY (reporter_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- `address` and `description` added 2026-09-18 by
-- migrations/2026_09_18_add_details_and_photos_to_dengue.sql. A district is
-- 25 rows wide and does not say which lane to go and look at; the description
-- is what the reporter saw. Both nullable - an alert read off a ministry
-- figure has neither.
CREATE TABLE dengue_risk_districts (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  district_id INT UNSIGNED NOT NULL,
  risk_level  ENUM('low','medium','high') NOT NULL DEFAULT 'high',
  address     VARCHAR(255) NULL,
  description TEXT NULL,
  case_count  INT UNSIGNED,
  source_file VARCHAR(255),
  reported_on DATE NOT NULL,
  added_by    BIGINT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (district_id) REFERENCES districts(id)
) ENGINE=InnoDB;

-- Photos of one alert. Same shape as item_images with the key repointed: no
-- `updated_at`, so Model::update() cannot be used on it, and no `is_primary` -
-- the list shows the first photo and the alert shows them all.
CREATE TABLE dengue_alert_images (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  alert_id   BIGINT UNSIGNED NOT NULL,
  file_path  VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (alert_id) REFERENCES dengue_risk_districts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE announcements (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(160) NOT NULL,
  body       TEXT NOT NULL,
  audience   ENUM('all','community','organization','grama_niladhari','blood_bank') NOT NULL DEFAULT 'all',
  published_at DATETIME NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    BIGINT UNSIGNED NULL,
  action     VARCHAR(120) NOT NULL,
  entity     VARCHAR(60),
  entity_id  BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_user (user_id, created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;


-- ############################################################
-- PART 2 - SEED DATA (seeds/seed.sql)
-- ############################################################

USE togetherthrives;

INSERT INTO districts (name, province) VALUES
('Colombo','Western'),('Gampaha','Western'),('Kalutara','Western'),
('Kandy','Central'),('Matale','Central'),('Nuwara Eliya','Central'),
('Galle','Southern'),('Matara','Southern'),('Hambantota','Southern'),
('Jaffna','Northern'),('Kilinochchi','Northern'),('Mannar','Northern'),
('Vavuniya','Northern'),('Mullaitivu','Northern'),
('Batticaloa','Eastern'),('Ampara','Eastern'),('Trincomalee','Eastern'),
('Kurunegala','North Western'),('Puttalam','North Western'),
('Anuradhapura','North Central'),('Polonnaruwa','North Central'),
('Badulla','Uva'),('Monaragala','Uva'),
('Ratnapura','Sabaragamuwa'),('Kegalle','Sabaragamuwa');

-- One row per district, named after it.
--
-- This table used to hold 37 sub-district cities, and only 13 of the 25
-- districts had any - a donor in Mannar had nothing to pick. The group could
-- not list every city in the country, so every form asks for a DISTRICT now.
-- schema.sql is frozen and six tables carry a NOT NULL `city_id` foreign key,
-- so the column stays; what changed is that `cities` now mirrors `districts`
-- one for one. Existing databases are converted by
-- migrations/2026_08_27_districts_replace_cities.sql.
INSERT INTO cities (district_id, name)
SELECT id, name FROM districts ORDER BY id;

INSERT INTO item_categories (name) VALUES
('Furniture'),('Home Appliances'),('Electronics'),('Clothing'),
('Books'),('Baby & Kids'),('Sports'),('Medical Equipment'),
('Kitchenware'),('Stationery'),('Other');

-- Default medical screening questions (campaign_id NULL = global defaults)
INSERT INTO screening_questions (campaign_id, question, answer_type, disqualifying_answer, sort_order, is_default) VALUES
(NULL,'Are you between 18 and 60 years of age?','yes_no','no',1,1),
(NULL,'Is your body weight at least 50 kg?','yes_no','no',2,1),
(NULL,'Have you donated blood within the last 4 months?','yes_no','yes',3,1),
(NULL,'Do you currently have a fever, cough or infection?','yes_no','yes',4,1),
(NULL,'Are you currently taking antibiotics?','yes_no','yes',5,1),
(NULL,'Have you had surgery in the past 6 months?','yes_no','yes',6,1),
(NULL,'Do you have diabetes, heart disease or high blood pressure under treatment?','yes_no','yes',7,1),
(NULL,'Have you consumed alcohol in the last 24 hours?','yes_no','yes',8,1);

-- ---------------------------------------------------------------------------
-- Demo accounts - one per role, plus a second community member.
--
-- All six arrive ACTIVE and already admin-verified, so a fresh machine can be
-- demonstrated straight after the import with no clicking through the approval
-- queue. The same list is shown on the sign-in page (see AuthController's
-- DEMO_ACCOUNTS const); keep the two in step if you change an email here.
--
-- Password: Password@123 for ALL SIX, the admin included (2026-09-13). They were
-- two passwords before and the one that differed was the admin's, which is the
-- account a demo starts on.
-- Regenerate a hash with: php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT);"
--
-- IDs are explicit because organizations / grama_niladharis / blood_banks below
-- reference them by user_id, and an auto-increment would not be reproducible.
--
-- document_path / nic_document_path stay NULL on purpose. The real registrations
-- uploaded PDFs that exist on one laptop only; a path pointing at a missing file
-- would 404 through FileController, and NULL renders as "not submitted" instead.
-- ---------------------------------------------------------------------------
-- `city_id` is a DISTRICT since 2026-08-27 - `cities` holds one row per district.
-- The first community member was city 4, which used to be Nugegoda and is now
-- the fourth district (Kandy); they are Colombo like the rest of the seed, so
-- city 1.
--
-- Three of the six were renamed on 2026-09-13 and every address moved from .lk
-- to .com. The accounts, the ids and the profile rows are otherwise the same
-- six; nothing else in the tree keys on a name or an address except
-- AuthController::DEMO_ACCOUNTS, which is kept in step.
INSERT INTO users (id, full_name, email, phone, nic, password_hash, role, status, city_id, blood_group, email_verified_at) VALUES
(1,'Platform Admin','admin@togetherthrives.com','0771234567',NULL,
   '$2y$10$yQg5hW5a/tbJ4HHBzGTdFugVTOZ/GpdE5mKMjBf4XdlELGQK7CgFm','admin','active',1,NULL,NOW()),
(2,'Hope Foundation Lanka','hope@test.com','0114455667',NULL,
   '$2y$10$tGA9UlCcB5Kq6UMYv/wUCuTzseJrdzBZfLaxGahX2.t5MK4szv2YO','organization','active',1,NULL,NOW()),
(3,'Ajmal Riyaz','ajmal@test.com','0712223344','198512345V',
   '$2y$10$9600zNO1Va3aO2g4bTx4NuVzyYTq5rpRJBqKpaYs.D8KII63cATCu','grama_niladhari','active',1,NULL,NOW()),
(4,'National Blood Bank Colombo','bank@test.com','0119876543',NULL,
   '$2y$10$deWox8Efs44hU6d7St9R4OzrbV2vViZxbxeYFZit18yU/Mp63GKxG','blood_bank','active',1,NULL,NOW()),
(5,'Keerthy Ganesh','keerthy@test.com','0776665544','199234567V',
   '$2y$10$PyyrT2oObGzGHGLEZoAcz.ic5z43B26WtCkN1bKTek0vkzSZmvzMe','community','active',1,'O+',NOW()),
(6,'Janindu Perera','janindu@test.com','0773334455','199645678V',
   '$2y$10$xkQdocCzCyIpHjHj7JkuhuEtYOHi6GQglQp27WlhskwSH8QEl4EEG','community','active',1,'B+',NOW());

-- Role profiles.  verified_by = 1 is the Platform Admin above.
INSERT INTO organizations (user_id, org_name, reg_number, org_type, address, city_id, website, description, verified_at, verified_by)
VALUES (2,'Hope Foundation Lanka','NGO/2019/4471','non_profit','114 Galle Road, Colombo 03',1,
        'https://hope.lk','We coordinate disaster relief across the Western Province.',NOW(),1);

INSERT INTO grama_niladharis (user_id, gn_division, division_code, ds_division, district_id, office_phone, verified_at, verified_by)
VALUES (3,'Kirulapone North','GN/COL/0421','Thimbirigasyaya',1,'0712223344',NOW(),1);

INSERT INTO blood_banks (user_id, bank_name, license_no, hospital_name, address, city_id, hotline, verified_at)
VALUES (4,'National Blood Bank Colombo','BB/2020/117','National Hospital of Sri Lanka',
        'Elvitigala Mawatha, Narahenpita, Colombo 05',1,'0119876543',NOW());
