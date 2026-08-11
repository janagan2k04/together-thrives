-- ---------------------------------------------------------------------------
-- Community registration uploads a scan of the applicant's NIC, but `users` had
-- nowhere to record it: `nic` is VARCHAR(15) (a number, not a ~50 character
-- path) and `avatar_path` is the profile picture.
--
-- Additive and nullable, so existing rows and the other three modules are
-- unaffected. Everyone must run this once against their local database.
--
--   mysql -u root togetherthrives < database/migrations/2026_08_07_add_nic_document_to_users.sql
--
-- Someone also needs to add the same line to the `users` table in schema.sql so
-- a fresh import matches. That file is shared — agree it in the group first.
-- ---------------------------------------------------------------------------

ALTER TABLE users
  ADD COLUMN nic_document_path VARCHAR(255) NULL AFTER nic;
