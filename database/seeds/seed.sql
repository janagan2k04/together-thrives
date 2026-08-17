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

INSERT INTO cities (district_id, name) VALUES
(1,'Colombo'),(1,'Dehiwala'),(1,'Moratuwa'),(1,'Nugegoda'),(1,'Maharagama'),
(1,'Kotte'),(1,'Homagama'),(1,'Battaramulla'),(1,'Rathmalana'),
(2,'Gampaha'),(2,'Negombo'),(2,'Kelaniya'),(2,'Kiribathgoda'),(2,'Ja-Ela'),
(3,'Kalutara'),(3,'Panadura'),(3,'Horana'),
(4,'Kandy'),(4,'Peradeniya'),(4,'Gampola'),
(7,'Galle'),(7,'Hikkaduwa'),(7,'Ambalangoda'),
(8,'Matara'),(8,'Weligama'),
(10,'Jaffna'),(10,'Chavakachcheri'),
(15,'Batticaloa'),(17,'Trincomalee'),
(18,'Kurunegala'),(20,'Anuradhapura'),(22,'Badulla'),
(24,'Ratnapura'),(24,'Embilipitiya'),
(25,'Kegalle'),(25,'Ruwanwella'),(25,'Mawanella');

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
-- Demo accounts — one per role, plus a second community member.
--
-- All six arrive ACTIVE and already admin-verified, so a fresh machine can be
-- demonstrated straight after the import with no clicking through the approval
-- queue. The same list is shown on the sign-in page (see AuthController's
-- DEMO_ACCOUNTS const); keep the two in step if you change an email here.
--
-- Passwords:  Platform Admin = Admin@123,  everyone else = Passw0rd1
-- Regenerate a hash with: php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT);"
--
-- IDs are explicit because organizations / grama_niladharis / blood_banks below
-- reference them by user_id, and an auto-increment would not be reproducible.
--
-- document_path / nic_document_path stay NULL on purpose. The real registrations
-- uploaded PDFs that exist on one laptop only; a path pointing at a missing file
-- would 404 through FileController, and NULL renders as "not submitted" instead.
-- ---------------------------------------------------------------------------
INSERT INTO users (id, full_name, email, phone, nic, password_hash, role, status, city_id, blood_group, email_verified_at) VALUES
(1,'Platform Admin','admin@togetherthrives.lk','0771234567',NULL,
   '$2y$10$L843mZbEP/0khEFvY64J4uGHK21YLb4FuFyN8YleFYu2K8D0fXWYS','admin','active',1,NULL,NOW()),
(2,'Hope Foundation Lanka','hope@test.lk','0114455667',NULL,
   '$2y$10$IdIdXfpUA3rJPiszecL7guLabv2AllAWd6z4DRYkXcRiukQbZ8L4a','organization','active',1,NULL,NOW()),
(3,'Sunil Bandara','sunil@test.lk','0712223344','198512345V',
   '$2y$10$0pTkyMIOTbwELfRttyzAqeb6.QfB1ZuAcRIwj6a0iK3Xa7hME4daK','grama_niladhari','active',1,NULL,NOW()),
(4,'National Blood Bank Colombo','bank@test.lk','0119876543',NULL,
   '$2y$10$QQ2TYQDmSkIOR/3rNjLryueNA.xWiQFaamNXGVPuu6dahJny4fK7S','blood_bank','active',1,NULL,NOW()),
(5,'Dilani Fernando','dilani@test.lk','0776665544','199234567V',
   '$2y$10$OdV8GYP37cFZnWeaO1LH6uZ8zLy.264rjn6A91Q/RbbKMGYH2k4nm','community','active',4,'O+',NOW()),
(6,'Nimal Perera','nimal@test.lk','0773334455','199645678V',
   '$2y$10$IHrCzE2SxJUqUn9Ure7QuuYJlTRCi/IKVK2LGCDl/BhytS5CPXd0e','community','active',1,'B+',NOW());

-- Role profiles.  verified_by = 1 is the Platform Admin above.
INSERT INTO organizations (user_id, org_name, reg_number, org_type, address, city_id, website, description, verified_at, verified_by)
VALUES (2,'Hope Foundation Lanka','NGO/2019/4471','non_profit','114 Galle Road, Colombo 03',1,
        'https://hope.lk','We coordinate disaster relief across the Western Province.',NOW(),1);

INSERT INTO grama_niladharis (user_id, gn_division, division_code, ds_division, district_id, office_phone, verified_at, verified_by)
VALUES (3,'Kirulapone North','GN/COL/0421','Thimbirigasyaya',1,'0712223344',NOW(),1);

INSERT INTO blood_banks (user_id, bank_name, license_no, hospital_name, address, city_id, hotline, verified_at)
VALUES (4,'National Blood Bank Colombo','BB/2020/117','National Hospital of Sri Lanka',
        'Elvitigala Mawatha, Narahenpita, Colombo 05',1,'0119876543',NOW());
