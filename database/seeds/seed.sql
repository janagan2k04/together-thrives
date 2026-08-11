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

-- Default admin.  Password below is: Admin@123
-- Regenerate with: php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT);"
INSERT INTO users (full_name, email, phone, password_hash, role, status, city_id, email_verified_at)
VALUES ('Platform Admin','admin@togetherthrives.lk','0771234567',
        '$2y$10$L843mZbEP/0khEFvY64J4uGHK21YLb4FuFyN8YleFYu2K8D0fXWYS',
        'admin','active',1,NOW());
