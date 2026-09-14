<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

echo "Starting 3-Week Multi-Challenge Migration...\n";

// 1. Alter challenge_type column to VARCHAR(50)
$pdo->exec("ALTER TABLE `weeks` MODIFY `challenge_type` VARCHAR(50) NOT NULL DEFAULT 'multi'");
echo "1. weeks.challenge_type modified to VARCHAR(50)\n";

// 2. Clear out any weeks > 3
$pdo->exec("DELETE FROM `questions` WHERE `week_id` > 3");
$pdo->exec("DELETE FROM `quiz_attempts` WHERE `week_id` > 3");
$pdo->exec("DELETE FROM `photo_submissions` WHERE `week_id` > 3");
$pdo->exec("DELETE FROM `weekly_scores` WHERE `week_id` > 3");
$pdo->exec("DELETE FROM `weeks` WHERE `id` > 3");

// 3. Upsert the 3 Weeks
$weeksData = [
    [
        'id' => 1,
        'week_number' => 1,
        'title_en' => 'Week 1: Season Kickoff',
        'title_ar' => 'الأسبوع الأول: انطلاقة المنافسة',
        'challenge_type' => 'multi',
        'description_en' => 'Complete the Kickoff Quiz, upload team photo, and execute the team collaboration mission!',
        'description_ar' => 'أنجز كويز الأسبوع الأول وتحدي التصوير الصيفي والنشاط الجماعي لحصد أعلى النقاط لقسمك!',
        'points_reward' => 50,
        'is_active' => 1,
        'is_completed' => 0
    ],
    [
        'id' => 2,
        'week_number' => 2,
        'title_en' => 'Week 2: Mid-Journey Sprint',
        'title_ar' => 'الأسبوع الثاني: سباق الصدارة',
        'challenge_type' => 'multi',
        'description_en' => 'Week 2 is live! Answer the knowledge quiz, share creativity photos, and complete the department mission!',
        'description_ar' => 'تحديات الأسبوع الثاني: كويز المعرفة، ومشاركة صور إبداع الفريق، ومهمة القسم التعاونية!',
        'points_reward' => 50,
        'is_active' => 0,
        'is_completed' => 0
    ],
    [
        'id' => 3,
        'week_number' => 3,
        'title_en' => 'Week 3: Championship Finale',
        'title_ar' => 'الأسبوع الثالث: السباق الختامي والتتويج',
        'challenge_type' => 'multi',
        'description_en' => 'The Grand Finale! Face the ultimate quiz, submit celebration photo, and claim the championship trophy!',
        'description_ar' => 'المرحلة الختامية الكبرى: كويز التتويج، وصورة الاحتفال الجماعية، وحسم درع بطل الموسم!',
        'points_reward' => 50,
        'is_active' => 0,
        'is_completed' => 0
    ]
];

$stmtWeek = $pdo->prepare("
    INSERT INTO `weeks` (`id`, `week_number`, `title_en`, `title_ar`, `challenge_type`, `description_en`, `description_ar`, `points_reward`, `is_active`, `is_completed`)
    VALUES (:id, :week_number, :title_en, :title_ar, :challenge_type, :description_en, :description_ar, :points_reward, :is_active, :is_completed)
    ON DUPLICATE KEY UPDATE
        `title_en` = VALUES(`title_en`),
        `title_ar` = VALUES(`title_ar`),
        `challenge_type` = VALUES(`challenge_type`),
        `description_en` = VALUES(`description_en`),
        `description_ar` = VALUES(`description_ar`),
        `points_reward` = VALUES(`points_reward`)
");

foreach ($weeksData as $w) {
    $stmtWeek->execute($w);
}
echo "2. 3 Weeks configured successfully!\n";

// 4. Populate 5 Questions for Week 1, Week 2, and Week 3
$pdo->exec("DELETE FROM `questions` WHERE `week_id` IN (2, 3)");

$newQuestions = [
    // Week 2 Questions (Innovation, Technology & Leadership)
    [
        2, 'Technology & AI', 'التكنولوجيا والذكاء الاصطناعي',
        'Which programming language is predominantly used for Artificial Intelligence & Data Science?',
        'أي من لغات البرمجة التالية تُستخدم بكثرة وبشكل أساسي في مجالات الذكاء الاصطناعي وعلم البيانات؟',
        'C++', 'سي بلس بلس',
        'Python', 'بايثون',
        'HTML', 'إتش تي إم إل',
        'PHP', 'بي إتش بي',
        'B', 10
    ],
    [
        2, 'Cybersecurity', 'الأمن السيبراني وحماية البيانات',
        'What is the practice of protecting systems, networks, and data from digital attacks called?',
        'ما هو المفهوم الذي يُعبر عن حماية الأنظمة والشبكات والبيانات الرقمية من الهجمات والاختراق؟',
        'Cloud Computing', 'الحوسبة السحابية',
        'Data Mining', 'التنقيب عن البيانات',
        'Cybersecurity', 'الأمن السيبراني',
        'Digital Marketing', 'التسويق الرقمي',
        'C', 10
    ],
    [
        2, 'Project Management', 'إدارة المشاريع',
        'In project management, what is a "Milestone"?',
        'في إدارة وتنفيذ المشاريع، ماذا يعني مصطلح "Milestone"؟',
        'A major critical event or achievement in project timeline', 'حدث رئيسي أو نقطة إنجاز هامة في الجدول الزمني',
        'The financial penalty for project delay', 'الغرامة المالية للتأخير',
        'The project software tool', 'اسم برنامج الإدارة',
        'The project cancellation notice', 'إلغاء المشروع',
        'A', 10
    ],
    [
        2, 'General Science', 'علوم عامة',
        'What is the most abundant chemical element in Earth atmosphere?',
        'ما هو الغاز الكيميائي الأكثر وفرة في الغلاف الجوي لكوكب الأرض؟',
        'Oxygen', 'الأكسجين',
        'Carbon Dioxide', 'ثاني أكسيد الكربون',
        'Nitrogen', 'النيتروجين',
        'Hydrogen', 'الهيدروجين',
        'C', 10
    ],
    [
        2, 'Geography', 'جغرافيا ومعالم',
        'What is the capital city of France?',
        'ما هي عاصمة جمهورية فرنسا؟',
        'Lyon', 'ليون',
        'Marseille', 'مارسيليا',
        'Nice', 'نيس',
        'Paris', 'باريس',
        'D', 10
    ],

    // Week 3 Questions (Championship, Quality & Business Excellence)
    [
        3, 'World Geography', 'جغرافيا العالم',
        'What is the longest river in the world?',
        'ما هو أطول نهر في العالم؟',
        'Amazon River', 'نهر الأمازون',
        'Nile River', 'نهر النيل',
        'Mississippi River', 'نهر المسيسيبي',
        'Yangtze River', 'نهر يانجتسي',
        'B', 10
    ],
    [
        3, 'Astronomy', 'علوم الفضاء',
        'Which planet in our solar system is famously known as the "Red Planet"?',
        'أي كواكب مجموعتنا الشمسية يُعرف باسم "الكوكب الأحمر"؟',
        'Venus', 'الزهرة',
        'Jupiter', 'المشتري',
        'Mars', 'المريخ',
        'Saturn', 'زحل',
        'C', 10
    ],
    [
        3, 'Quality & Standards', 'إدارة الجودة والتميز',
        'Which international standard is globally renowned for Quality Management Systems (QMS)?',
        'ما هي المواصفة والمعيار الدولي الأشهر عالمياً لإدارة وتوكيد الجودة (QMS) في المؤسسات؟',
        'ISO 9001', 'آيزو 9001',
        'ISO 14001', 'آيزو 14001',
        'ISO 27001', 'آيزو 27001',
        'ISO 45001', 'آيزو 45001',
        'A', 10
    ],
    [
        3, 'Organizational Excellence', 'التميز المؤسسي والتآزر',
        'What is the organizational concept of "Synergy"?',
        'في علم الإدارة وثقافة العمل الجماعي، ماذا يعني مفهوم "Synergy" (التآزر)؟',
        'Working without communicating', 'العمل دون تواصل',
        'Collective teamwork producing greater results than individual efforts combined', 'أن التعاون الجماعي ينتج أثراً ونتائج أعظم من مجموع الجهود الفردية',
        'Reducing the work pace', 'تقليل سرعة الإنجاز',
        'Relying solely on external consultants', 'الاعتماد على جهات خارجية فقط',
        'B', 10
    ],
    [
        3, 'General Knowledge', 'معلومات عامة',
        'How many continents are there in the world?',
        'كم عدد قارات العالم المعترف بها جغرافياً؟',
        '5', '5 قارات',
        '6', '6 قارات',
        '7', '7 قارات',
        '8', '8 قارات',
        'C', 10
    ]
];

$stmtQ = $pdo->prepare("
    INSERT INTO `questions` (`week_id`, `category_en`, `category_ar`, `question_en`, `question_ar`, `option_a_en`, `option_a_ar`, `option_b_en`, `option_b_ar`, `option_c_en`, `option_c_ar`, `option_d_en`, `option_d_ar`, `correct_option`, `points`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($newQuestions as $q) {
    $stmtQ->execute($q);
}

echo "3. Seeded questions for Week 2 and Week 3!\n";

$counts = $pdo->query("SELECT week_id, count(*) as cnt FROM questions GROUP BY week_id")->fetchAll(PDO::FETCH_ASSOC);
print_r($counts);

echo "MIGRATION COMPLETED SUCCESSFULLY!\n";
