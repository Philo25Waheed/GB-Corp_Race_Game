-- ========================================================================
-- GB CORP - SUMMER ROAD TRIP RACE GAME
-- MySQL Database Tables Schema & Initial Seed Data
-- ========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------
-- CLEANUP EXISTING TABLES (Reverse Dependency Order - Child Tables First)
-- ------------------------------------------------------------------------
DROP TABLE IF EXISTS `quiz_attempts`;
DROP TABLE IF EXISTS `photo_submissions`;
DROP TABLE IF EXISTS `team_activity_submissions`;
DROP TABLE IF EXISTS `weekly_scores`;
DROP TABLE IF EXISTS `questions`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `weeks`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `system_settings`;

-- ------------------------------------------------------------------------
-- 1. Table: `departments` (أقسام الشركة وسيارات السباق)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `departments` (
    `id` VARCHAR(32) PRIMARY KEY,
    `name_en` VARCHAR(100) NOT NULL,
    `name_ar` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `category` VARCHAR(50) NOT NULL DEFAULT 'bu',
    `password` VARCHAR(255) NOT NULL DEFAULT '1234',
    `color` VARCHAR(32) NOT NULL DEFAULT '#38bdf8',
    `secondary_color` VARCHAR(32) NOT NULL DEFAULT '#d9d8d6',
    `car_model` VARCHAR(50) NOT NULL DEFAULT 'gt_coupe',
    `livery_style` VARCHAR(50) NOT NULL DEFAULT 'stripes',
    `racing_num` INT NOT NULL DEFAULT 1,
    `car_emoji` VARCHAR(16) NOT NULL DEFAULT '🏎️',
    `position` INT NOT NULL DEFAULT 0,
    `total_points` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`id`, `name_en`, `name_ar`, `code`, `category`, `password`, `color`, `secondary_color`, `car_model`, `livery_style`, `racing_num`, `car_emoji`, `position`, `total_points`) VALUES
-- 1. قطاعات الأعمال والوحدات الرئيسية (Business Units - BU)
('hr', 'Human Resources', 'قطاع الموارد البشرية', 'BU-HR', 'bu', '1234', '#2b51a4', '#d9d8d6', 'gt_coupe', 'stripes', 1, '🚗', 0, 0),
('it', 'Information Technology', 'قطاع تكنولوجيا المعلومات', 'BU-IT', 'bu', '1234', '#049eda', '#f3f3f3', 'formula', 'velocity', 2, '🏎️', 0, 0),
('finance', 'Finance', 'قطاع الإدارة المالية', 'BU-FIN', 'bu', '1234', '#4062ac', '#dcdcda', 'speedster', 'dual_bars', 3, '🏎️', 0, 0),
('marketing', 'Marketing', 'قطاع التسويق والعلاقات', 'BU-MKT', 'bu', '1234', '#f78c2a', '#2b51a4', 'hypercar', 'arrow', 4, '🏎️', 0, 0),
('operations', 'Operations', 'قطاع العمليات والتشغيل', 'BU-OPS', 'bu', '1234', '#7f8487', '#3bae49', 'aero_fastback', 'side_swoop', 5, '🚗', 0, 0),
('administration', 'Administration', 'الشؤون الإدارية والخدمات العامة', 'BU-ADM', 'bu', '1234', '#8c9093', '#049eda', 'gt_coupe', 'stripes', 6, '🚗', 0, 0),
('procurement', 'Procurement', 'قطاع المشتريات وسلاسل الإمداد', 'BU-PROC', 'bu', '1234', '#119aaa', '#f79740', 'speedster', 'dual_bars', 7, '🏎️', 0, 0),
('legal_loans', 'Legal & Problem Loans', 'الشؤون القانونية والقروض المتعثرة', 'BU-LEG', 'bu', '1234', '#5574b5', '#d9d8d6', 'aero_fastback', 'chevrons', 8, '🚗', 0, 0),
('manufacturing', 'Manufacturing', 'قطاع التصنيع والإنتاج', 'BU-MFG', 'bu', '1234', '#3bae49', '#7f8487', 'hauler_truck', 'heavy_shield', 9, '🚛', 0, 0),
('central_warehousing', 'Central Warehousing', 'المستودعات المركزية', 'BU-WH', 'bu', '1234', '#f79740', '#2b51a4', 'aero_van', 'cargo_bars', 10, '🚐', 0, 0),
('quality_excellence', 'Quality / Business Excellence', 'الجودة والتميز المؤسسي', 'BU-QUAL', 'bu', '1234', '#4eb75b', '#dcdcda', 'hypercar', 'apex_fin', 11, '🏎️', 0, 0),
('digital_transformation', 'Digital Transformation', 'التحول الرقمي والابتكار', 'BU-DIG', 'bu', '1234', '#1da9de', '#f7a454', 'formula', 'cyber_grid', 12, '🏎️', 0, 0),
('data_dept', 'Data', 'إدارة وتحليل البيانات', 'BU-DATA', 'bu', '1234', '#28a4b0', '#eaeef7', 'streamliner', 'telemetry', 13, '⚡', 0, 0),
('projects_bu', 'Projects', 'إدارة المشاريع الاستراتيجية', 'BU-PMO', 'bu', '1234', '#f7a454', '#119aaa', 'hypercar', 'arrow', 14, '🏎️', 0, 0),
('internal_audit', 'Internal Audit', 'المراجعة والتدقيق الداخلي', 'BU-AUD', 'bu', '1234', '#989da0', '#2b51a4', 'aero_fastback', 'radar_ring', 15, '🚗', 0, 0),
('crm_complaints', 'CRM & Complaints', 'علاقات العملاء والشكاوى', 'BU-CRM', 'bu', '1234', '#35b2e2', '#f78c2a', 'gt_coupe', 'side_swoop', 16, '🚗', 0, 0),
('gov_sales', 'Government Sales / Relations', 'المبيعات والعلاقات الحكومية', 'BU-GOV', 'bu', '1234', '#6b85c0', '#e0e0de', 'aero_fastback', 'executive_trim', 17, '🚗', 0, 0),
('planning_performance', 'Planning and Performance Monitoring', 'التخطيط ومتابعة الأداء', 'BU-PLAN', 'bu', '1234', '#40aebb', '#f9ae6a', 'rally_suv', 'vector_speed', 18, '🚙', 0, 0),
('passenger_cars', 'PC (Passenger Cars)', 'قطاع سيارات الركوب (PC)', 'BU-PC', 'bu', '1234', '#049eda', '#2b51a4', 'gt_coupe', 'twin_gt', 19, '🚗', 0, 0),
('cv_ce', 'CV & CE (Commercial & Equipment)', 'السيارات التجارية والمعدات الإنشائية', 'BU-CVCE', 'bu', '1234', '#f78c2a', '#7f8487', 'hauler_truck', 'heavy_shield', 20, '🚛', 0, 0),
('two_three_wheelers', '2&3 Wheelers', 'الدراجات والمركبات الخفيفة (2&3 Wheelers)', 'BU-23W', 'bu', '1234', '#3bae49', '#049eda', 'trike_racer', 'sprint_slash', 21, '🛵', 0, 0),
('tires', 'Tires', 'قطاع الإطارات والخدمات', 'BU-TIRE', 'bu', '1234', '#a5a9ac', '#f78c2a', 'hypercar', 'tread_edge', 22, '🏎️', 0, 0),
('ghabbour_foundation', 'Ghabbour Foundation', 'مؤسسة غبور للتنمية المجتمعية', 'BU-GF', 'bu', '1234', '#62bd6e', '#2b51a4', 'aero_fastback', 'star_beam', 23, '🚗', 0, 0),
('gb_group_companies', 'GB Bus / Itamco / Group Companies', 'جي بي باص / إيتامكو / شركات المجموعة', 'BU-GBC', 'bu', '1234', '#2b51a4', '#1da9de', 'transporter', 'aero_express', 24, '🚌', 0, 0),
-- 2. العائلات والمجالات الوظيفية الكبرى (Job Families)
('jf_hr', 'Human Resources (Job Family)', 'عائلة الموارد البشرية', 'JF-HR', 'job_family', '1234', '#4062ac', '#dcdcda', 'gt_coupe', 'stripes', 25, '🚗', 0, 0),
('jf_it_digital', 'Information Technology/Digital', 'تكنولوجيا المعلومات والحلول الرقمية', 'JF-IT', 'job_family', '1234', '#1da9de', '#f78c2a', 'formula', 'cyber_grid', 26, '🏎️', 0, 0),
('jf_finance_acct', 'Finance and Accounting', 'المالية والمحاسبة', 'JF-FIN', 'job_family', '1234', '#2b51a4', '#f79740', 'speedster', 'dual_bars', 27, '🏎️', 0, 0),
('jf_marketing', 'Marketing (Job Family)', 'التسويق والاتصال المؤسسي', 'JF-MKT', 'job_family', '1234', '#f79740', '#049eda', 'hypercar', 'arrow', 28, '🏎️', 0, 0),
('jf_sales', 'Sales', 'المبيعات وتطوير الأعمال', 'JF-SALES', 'job_family', '1234', '#f78c2a', '#d9d8d6', 'formula', 'velocity', 29, '🏎️', 0, 0),
('jf_engineering', 'Engineering', 'الهندسة والعمليات الفنية', 'JF-ENG', 'job_family', '1234', '#119aaa', '#3bae49', 'aero_fastback', 'gear_mesh', 30, '🚗', 0, 0),
('jf_production_mfg', 'Production / Advanced Manufacturing', 'الإنتاج والتصنيع المتقدم', 'JF-MFG', 'job_family', '1234', '#3bae49', '#f7a454', 'hauler_truck', 'heavy_shield', 31, '🚛', 0, 0),
('jf_quality_assurance', 'Quality Assurance', 'توكيد الجودة والامتثال', 'JF-QA', 'job_family', '1234', '#4eb75b', '#2b51a4', 'hypercar', 'apex_fin', 32, '🏎️', 0, 0),
('jf_logistics_supply', 'Logistics/Supply Chain', 'اللوجستيات وسلاسل الإمداد والتوريد', 'JF-LOG', 'job_family', '1234', '#f7a454', '#7f8487', 'aero_van', 'cargo_bars', 33, '🚐', 0, 0),
('jf_legal', 'Legal (Job Family)', 'الشؤون القانونية والاستشارات', 'JF-LEG', 'job_family', '1234', '#5574b5', '#e4e4e2', 'aero_fastback', 'chevrons', 34, '🚗', 0, 0),
('jf_credit_collections', 'Credit & Collections', 'الائتمان وإدارة التحصيل', 'JF-CRED', 'job_family', '1234', '#7f8487', '#119aaa', 'gt_coupe', 'side_swoop', 35, '🚗', 0, 0),
('jf_customer_service', 'Customer Service / Call Center', 'خدمة العملاء ومركز الاتصال', 'JF-CS', 'job_family', '1234', '#35b2e2', '#d9d8d6', 'speedster', 'wave_glide', 36, '🏎️', 0, 0),
('jf_analytics_data', 'Analytics and Data Science', 'التحليلات وعلم البيانات والذكاء الاصطناعي', 'JF-DATA', 'job_family', '1234', '#28a4b0', '#f9ae6a', 'streamliner', 'telemetry', 37, '⚡', 0, 0),
('jf_corporate_affairs', 'Corporate Affairs', 'الشؤون المؤسسية والعلاقات العامة', 'JF-CORP', 'job_family', '1234', '#6b85c0', '#4eb75b', 'aero_fastback', 'executive_trim', 38, '🚗', 0, 0),
('jf_educational_ops', 'Educational Operations', 'العمليات التعليمية والتدريبية', 'JF-EDU', 'job_family', '1234', '#75c681', '#2b51a4', 'rally_suv', 'vector_speed', 39, '🚙', 0, 0),
('jf_healthcare_hse', 'Healthcare Service Lines / HSE', 'الخدمات الصحية والسلامة والصحة المهنية والبيئة', 'JF-HSE', 'job_family', '1234', '#3bae49', '#fef4ea', 'hse_rapid', 'cross_beacon', 40, '🚑', 0, 0),
('jf_project_program', 'Project and Program Management', 'إدارة المشاريع والبرامج المؤسسية', 'JF-PPM', 'job_family', '1234', '#58b8c4', '#f78c2a', 'hypercar', 'arrow', 41, '🏎️', 0, 0),
('jf_property_delivery', 'Property Management / Construction', 'إدارة الممتلكات والمشاريع الإنشائية', 'JF-PROP', 'job_family', '1234', '#8c9093', '#f79740', 'hauler_truck', 'heavy_shield', 42, '🚛', 0, 0),
('jf_risk_asset', 'Financial Risk / Asset Management', 'إدارة المخاطر المالية وإدارة الأصول', 'JF-RISK', 'job_family', '1234', '#d9d8d6', '#2b51a4', 'speedster', 'dual_bars', 43, '🏎️', 0, 0);

-- ------------------------------------------------------------------------
-- 2. Table: `users` (المتسابقون والمشاركون المسجلون)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `department_id` VARCHAR(32) NOT NULL,
    `password` VARCHAR(255) NULL DEFAULT NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `is_admin` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 3. Table: `weeks` (جدول الأسابيع وتنوع التحديات)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `weeks` (
    `id` INT PRIMARY KEY,
    `week_number` INT NOT NULL UNIQUE,
    `title_en` VARCHAR(150) NOT NULL,
    `title_ar` VARCHAR(150) NOT NULL,
    `challenge_type` ENUM('quiz', 'photo_challenge', 'team_activity') NOT NULL DEFAULT 'quiz',
    `description_en` TEXT NULL,
    `description_ar` TEXT NULL,
    `points_reward` INT NOT NULL DEFAULT 10,
    `is_active` TINYINT(1) DEFAULT 0,
    `is_completed` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `weeks` (`id`, `week_number`, `title_en`, `title_ar`, `challenge_type`, `description_en`, `description_ar`, `points_reward`, `is_active`, `is_completed`) VALUES
(1, 1, 'Week 1: Summer Kickoff Knowledge Quiz', 'الأسبوع الأول: انطلاقة الصيف وكويز المعلومات', 'quiz', 'Answer bilingual trivia questions and advance your department car on the highway!', 'أجب على أسئلة الكويز السريعة واجمع النقاط لتقديم سيارة قسمك نحو خط النهاية!', 10, 1, 0),
(2, 2, 'Week 2: Summer Vibes Photo Challenge', 'الأسبوع الثاني: تحدي التقاط صور الصيف والمقر', 'photo_challenge', 'Capture and upload team photos embracing summer spirit to earn points!', 'التقط وشارك أجمل صور فريقك في العمل وأجواء الصيف لتحصل على نقاط إضافية فورية!', 15, 0, 0),
(3, 3, 'Week 3: Department Synergy Team Activity', 'الأسبوع الثالث: تحدي النشاط التعاوني المشترك', 'team_activity', 'Complete collaborative team mission for mega points boost!', 'أنجز المهمة الجماعية المشتركة مع زملائك في القسم لقفزة كبرى في رصيد النقاط!', 30, 0, 0),
(4, 4, 'Week 4: The Grand Finale & Bonus Reveal', 'الأسبوع الرابع: السباق الختامي ومفاجأة البونص', 'quiz', 'The final week showdown with the grand secret bonus surprise revealed at the end!', 'الأسبوع الختامي الحاسم مع إعلان بطل الموسم وكشف مفاجأة البونص الكبرى!', 20, 0, 0);

-- ------------------------------------------------------------------------
-- 4. Table: `questions` (بنك أسئلة الكويز ثنائية اللغة)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `week_id` INT NOT NULL,
    `category_en` VARCHAR(100) NOT NULL,
    `category_ar` VARCHAR(100) NOT NULL,
    `question_en` TEXT NOT NULL,
    `question_ar` TEXT NOT NULL,
    `option_a_en` VARCHAR(255) NOT NULL,
    `option_a_ar` VARCHAR(255) NOT NULL,
    `option_b_en` VARCHAR(255) NOT NULL,
    `option_b_ar` VARCHAR(255) NOT NULL,
    `option_c_en` VARCHAR(255) NOT NULL,
    `option_c_ar` VARCHAR(255) NOT NULL,
    `option_d_en` VARCHAR(255) NOT NULL,
    `option_d_ar` VARCHAR(255) NOT NULL,
    `correct_option` ENUM('A', 'B', 'C', 'D') NOT NULL,
    `points` INT NOT NULL DEFAULT 10,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `questions` (`week_id`, `category_en`, `category_ar`, `question_en`, `question_ar`, `option_a_en`, `option_a_ar`, `option_b_en`, `option_b_ar`, `option_c_en`, `option_c_ar`, `option_d_en`, `option_d_ar`, `correct_option`, `points`) VALUES
(1, 'General Knowledge', 'معلومات عامة', 'What is the official capital city of Egypt?', 'ما هي العاصمة الرسمية لجمهورية مصر العربية؟', 'Alexandria', 'الإسكندرية', 'Cairo', 'القاهرة', 'Giza', 'الجيزة', 'Luxor', 'الأقصر', 'B', 10),
(1, 'Company & HR', 'الموارد البشرية والشركة', 'Which department is responsible for talent acquisition and onboarding?', 'أي قسم في المؤسسة مسؤول عن استقطاب الكفاءات والتوظيف وإدارة المواهب؟', 'Operations', 'العمليات', 'Finance', 'الإدارة المالية', 'Human Resources (HR)', 'الموارد البشرية', 'IT Support', 'الدعم الفني', 'C', 10),
(1, 'Technology', 'تكنولوجيا المعلومات', 'What does the abbreviation "HTML" stand for in web technology?', 'إلى ماذا يرمز الاختصار "HTML" في تطوير وتصميم صفحات الويب؟', 'HyperText Markup Language', 'لغة ترميز النص الفائق', 'HighTech Machine Learning', 'تعلم الآلة عالي التقنية', 'Hyper Transfer Main Logic', 'منطق النقل الرئيسي الفائق', 'Home Tool Markup Language', 'لغة أدوات الترميز المنزلية', 'A', 10),
(1, 'Business & Finance', 'الأعمال والمالية', 'What does the business acronym "ROI" stand for?', 'ماذا يعني الاختصار المالي الشهير "ROI" في قياس نجاح المشاريع والاستثمارات؟', 'Rate of Interest', 'معدل الفائدة', 'Return on Investment', 'العائد على الاستثمار', 'Risk of Inflation', 'مخاطر التضخم', 'Revenue over Income', 'الإيرادات مقارنة بالدخل', 'B', 10),
(1, 'Operations & Teamwork', 'العمليات والعمل الجماعي', 'What is the key objective of the Summer Road Trip corporate campaign?', 'ما هو الهدف الأساسي من حملة ومسابقة Summer Road Trip الصيفية؟', 'Only working overtime', 'العمل لساعات إضافية فقط', 'Team bonding, engagement and summer spirit', 'تعزيز روح الفريق والترابط الإيجابي والمرح الصيفي', 'Buying more cars', 'شراء سيارات جديدة', 'Individual isolation', 'العمل الفردي المنعزل', 'B', 10),
(4, 'Corporate Culture', 'ثقافة الشركة والتميز', 'What defines a great team culture during our summer journey?', 'ما الذي يميز ثقافة الفريق المتميز خلال رحلتنا الصيفية؟', 'Collaboration, appreciation, and continuous energy', 'التعاون والتقدير المتبادل والطاقة الإيجابية المستمرة', 'Competition without empathy', 'المنافسة دون تعاطف', 'Working in strict silos', 'الانعزال التام عن باقي الأقسام', 'Avoiding challenges', 'تجنب المشاركة في التحديات', 'A', 20),
(4, 'Innovation', 'الابتكار والريادة', 'How can every department contribute to overall company excellence?', 'كيف يساهم كل قسم في تحقيق التميز المؤسسي لشركتنا؟', 'By driving continuous improvement and creative ideas', 'من خلال التطوير المستمر وتقديم الأفكار الإبداعية', 'By ignoring feedback', 'بتجاهل آراء الزملاء', 'By doing minimal effort', 'ببذل الحد الأدنى فقط', 'By delaying projects', 'بتأخير إنجاز المشروعات', 'A', 20);

-- ------------------------------------------------------------------------
-- 5. Table: `quiz_attempts` (محاولات وإجابات الكويز)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `department_id` VARCHAR(32) NOT NULL,
    `week_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `selected_option` ENUM('A', 'B', 'C', 'D') NOT NULL,
    `is_correct` TINYINT(1) NOT NULL,
    `points_earned` INT NOT NULL DEFAULT 0,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 6. Table: `photo_submissions` (مشاركات صور الصيف)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `photo_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `department_id` VARCHAR(32) NOT NULL,
    `week_id` INT NOT NULL,
    `photo_path` VARCHAR(255) NOT NULL,
    `caption` TEXT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    `points_awarded` INT NOT NULL DEFAULT 15,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 7. Table: `team_activity_submissions` (توثيق مهام الأنشطة الجماعية)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_activity_submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_id` VARCHAR(32) NOT NULL,
    `week_id` INT NOT NULL,
    `activity_name` VARCHAR(150) NOT NULL,
    `notes` TEXT NULL,
    `points_awarded` INT NOT NULL DEFAULT 30,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 8. Table: `weekly_scores` (سكور وترتيب الأقسام لكل أسبوع)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `weekly_scores` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `week_id` INT NOT NULL,
    `department_id` VARCHAR(32) NOT NULL,
    `score` INT NOT NULL DEFAULT 0,
    `is_weekly_winner` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_week_dept` (`week_id`, `department_id`),
    FOREIGN KEY (`week_id`) REFERENCES `weeks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 9. Table: `system_settings` (إعدادات النظام والـ PIN ومفاجأة البونص)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` VARCHAR(64) PRIMARY KEY,
    `setting_value` TEXT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('active_week_id', '1'),
('race_length', '15'),
('gm_pin', '1234'),
('admin_password', 'admin123'),
('finale_revealed', '0'),
('finale_surprise_title_ar', '🎉 مفاجأة البونص الكبرى! 🎉'),
('finale_surprise_title_en', '🎉 The Grand Bonus Surprise! 🎉'),
('finale_surprise_message_ar', 'ألف مبروك لجميع الأقسام على هذه الرحلة الصيفية الرائعة المليئة بالحماس والطاقة الإيجابية! تهانينا الحارة للقسم البطل المتوج بالمركز الأول ولكل من شارك في صنع هذا الصيف المميز! 🌴☀️🏎️'),
('finale_surprise_message_en', 'Huge congratulations to all departments for this unforgettable Summer Road Trip filled with energy, unity and excitement! Special cheers to our crowned champion! 🌴☀️🏎️'),
('sound_enabled', '1');

SET FOREIGN_KEY_CHECKS = 1;
