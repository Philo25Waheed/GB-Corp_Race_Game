<?php
/**
 * Migration Script: Populate all Business Units and Job Families
 * with Exact Colors from the User Palette Image
 */
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

echo "Starting Departments & Palette Migration...\n";

// 1. Add `category` column to departments table if not exists
try {
    $stmtCol = $pdo->query("SHOW COLUMNS FROM `departments` LIKE 'category'");
    if ($stmtCol->rowCount() === 0) {
        $pdo->exec("ALTER TABLE `departments` ADD COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'bu' AFTER `code`");
        echo "Added 'category' column to `departments` table.\n";
    } else {
        echo "'category' column already exists.\n";
    }
} catch (Exception $e) {
    echo "Column check note: " . $e->getMessage() . "\n";
}

// 2. Exact Color Palette Extracted from User's Image (media_1788996946947.png)
// Col 1 (Royal Blue): #2b51a4, #4062ac, #5574b5, #6b85c0
// Col 2 (Cyan Blue):   #049eda, #1da9de, #35b2e2, #4fbce5
// Col 3 (Orange):      #f78c2a, #f79740, #f7a454, #f9ae6a
// Col 4 (Teal):        #119aaa, #28a4b0, #40aebb, #58b8c4
// Col 5 (Green):       #3bae49, #4eb75b, #62bd6e, #75c681
// Col 6 (Slate Grey):  #7f8487, #8c9093, #989da0, #a5a9ac
// Col 7 (Silver):      #d9d8d6, #dcdcda, #e0e0de, #e4e4e2

$departmentsData = [
    // =========================================================================
    // 1. قطاعات الأعمال والوحدات الرئيسية (Business Units - BU)
    // =========================================================================
    [
        'id' => 'hr',
        'name_en' => 'Human Resources',
        'name_ar' => 'قطاع الموارد البشرية',
        'code' => 'BU-HR',
        'category' => 'bu',
        'color' => '#2b51a4', // Col 1 100%
        'car_emoji' => '🚕'
    ],
    [
        'id' => 'it',
        'name_en' => 'Information Technology',
        'name_ar' => 'قطاع تكنولوجيا المعلومات',
        'code' => 'BU-IT',
        'category' => 'bu',
        'color' => '#049eda', // Col 2 100%
        'car_emoji' => '🏎️'
    ],
    [
        'id' => 'finance',
        'name_en' => 'Finance',
        'name_ar' => 'قطاع الإدارة المالية',
        'code' => 'BU-FIN',
        'category' => 'bu',
        'color' => '#4062ac', // Col 1 90%
        'car_emoji' => '🚙'
    ],
    [
        'id' => 'marketing',
        'name_en' => 'Marketing',
        'name_ar' => 'قطاع التسويق والعلاقات',
        'code' => 'BU-MKT',
        'category' => 'bu',
        'color' => '#f78c2a', // Col 3 100%
        'car_emoji' => '🏎️'
    ],
    [
        'id' => 'operations',
        'name_en' => 'Operations',
        'name_ar' => 'قطاع العمليات والتشغيل',
        'code' => 'BU-OPS',
        'category' => 'bu',
        'color' => '#7f8487', // Col 6 100%
        'car_emoji' => '🚗'
    ],
    [
        'id' => 'administration',
        'name_en' => 'Administration',
        'name_ar' => 'الشؤون الإدارية والخدمات العامة',
        'code' => 'BU-ADM',
        'category' => 'bu',
        'color' => '#8c9093', // Col 6 90%
        'car_emoji' => '🚙'
    ],
    [
        'id' => 'procurement',
        'name_en' => 'Procurement',
        'name_ar' => 'قطاع المشتريات وسلاسل الإمداد',
        'code' => 'BU-PROC',
        'category' => 'bu',
        'color' => '#119aaa', // Col 4 100%
        'car_emoji' => '🚐'
    ],
    [
        'id' => 'legal_loans',
        'name_en' => 'Legal & Problem Loans',
        'name_ar' => 'الشؤون القانونية والقروض المتعثرة',
        'code' => 'BU-LEG',
        'category' => 'bu',
        'color' => '#5574b5', // Col 1 80%
        'car_emoji' => '🚘'
    ],
    [
        'id' => 'manufacturing',
        'name_en' => 'Manufacturing',
        'name_ar' => 'قطاع التصنيع والإنتاج',
        'code' => 'BU-MFG',
        'category' => 'bu',
        'color' => '#3bae49', // Col 5 100%
        'car_emoji' => '🏗️'
    ],
    [
        'id' => 'central_warehousing',
        'name_en' => 'Central Warehousing',
        'name_ar' => 'المستودعات المركزية',
        'code' => 'BU-WH',
        'category' => 'bu',
        'color' => '#f79740', // Col 3 90%
        'car_emoji' => '📦'
    ],
    [
        'id' => 'quality_excellence',
        'name_en' => 'Quality / Business Excellence',
        'name_ar' => 'الجودة والتميز المؤسسي',
        'code' => 'BU-QUAL',
        'category' => 'bu',
        'color' => '#4eb75b', // Col 5 90%
        'car_emoji' => '⭐'
    ],
    [
        'id' => 'digital_transformation',
        'name_en' => 'Digital Transformation',
        'name_ar' => 'التحول الرقمي والابتكار',
        'code' => 'BU-DIG',
        'category' => 'bu',
        'color' => '#1da9de', // Col 2 90%
        'car_emoji' => '⚡'
    ],
    [
        'id' => 'data_dept',
        'name_en' => 'Data',
        'name_ar' => 'إدارة وتحليل البيانات',
        'code' => 'BU-DATA',
        'category' => 'bu',
        'color' => '#28a4b0', // Col 4 90%
        'car_emoji' => '📊'
    ],
    [
        'id' => 'projects_bu',
        'name_en' => 'Projects',
        'name_ar' => 'إدارة المشاريع الاستراتيجية',
        'code' => 'BU-PMO',
        'category' => 'bu',
        'color' => '#f7a454', // Col 3 80%
        'car_emoji' => '🎯'
    ],
    [
        'id' => 'internal_audit',
        'name_en' => 'Internal Audit',
        'name_ar' => 'المراجعة والتدقيق الداخلي',
        'code' => 'BU-AUD',
        'category' => 'bu',
        'color' => '#989da0', // Col 6 80%
        'car_emoji' => '🛡️'
    ],
    [
        'id' => 'crm_complaints',
        'name_en' => 'CRM & Complaints',
        'name_ar' => 'علاقات العملاء والشكاوى',
        'code' => 'BU-CRM',
        'category' => 'bu',
        'color' => '#35b2e2', // Col 2 80%
        'car_emoji' => '🎧'
    ],
    [
        'id' => 'gov_sales',
        'name_en' => 'Government Sales / Relations',
        'name_ar' => 'المبيعات والعلاقات الحكومية',
        'code' => 'BU-GOV',
        'category' => 'bu',
        'color' => '#6b85c0', // Col 1 70%
        'car_emoji' => '🏛️'
    ],
    [
        'id' => 'planning_performance',
        'name_en' => 'Planning and Performance Monitoring',
        'name_ar' => 'التخطيط ومتابعة الأداء',
        'code' => 'BU-PLAN',
        'category' => 'bu',
        'color' => '#40aebb', // Col 4 80%
        'car_emoji' => '📈'
    ],
    [
        'id' => 'passenger_cars',
        'name_en' => 'PC (Passenger Cars)',
        'name_ar' => 'قطاع سيارات الركوب (PC)',
        'code' => 'BU-PC',
        'category' => 'bu',
        'color' => '#049eda', // Col 2 100%
        'car_emoji' => '🏎️'
    ],
    [
        'id' => 'cv_ce',
        'name_en' => 'CV & CE (Commercial & Equipment)',
        'name_ar' => 'السيارات التجارية والمعدات الإنشائية',
        'code' => 'BU-CVCE',
        'category' => 'bu',
        'color' => '#f78c2a', // Col 3 100%
        'car_emoji' => '🚛'
    ],
    [
        'id' => 'two_three_wheelers',
        'name_en' => '2&3 Wheelers',
        'name_ar' => 'الدراجات والمركبات الخفيفة (2&3 Wheelers)',
        'code' => 'BU-23W',
        'category' => 'bu',
        'color' => '#3bae49', // Col 5 100%
        'car_emoji' => '🛵'
    ],
    [
        'id' => 'tires',
        'name_en' => 'Tires',
        'name_ar' => 'قطاع الإطارات والخدمات',
        'code' => 'BU-TIRE',
        'category' => 'bu',
        'color' => '#a5a9ac', // Col 6 70%
        'car_emoji' => '🛞'
    ],
    [
        'id' => 'ghabbour_foundation',
        'name_en' => 'Ghabbour Foundation',
        'name_ar' => 'مؤسسة غبور للتنمية المجتمعية',
        'code' => 'BU-GF',
        'category' => 'bu',
        'color' => '#62bd6e', // Col 5 80%
        'car_emoji' => '🌟'
    ],
    [
        'id' => 'gb_group_companies',
        'name_en' => 'GB Bus / Itamco / Group Companies',
        'name_ar' => 'جي بي باص / إيتامكو / شركات المجموعة',
        'code' => 'BU-GBC',
        'category' => 'bu',
        'color' => '#2b51a4', // Col 1 100%
        'car_emoji' => '🚌'
    ],

    // =========================================================================
    // 2. العائلات والمجالات الوظيفية الكبرى (Job Families)
    // =========================================================================
    [
        'id' => 'jf_hr',
        'name_en' => 'Human Resources (Job Family)',
        'name_ar' => 'عائلة الموارد البشرية',
        'code' => 'JF-HR',
        'category' => 'job_family',
        'color' => '#4062ac', // Col 1 90%
        'car_emoji' => '🚕'
    ],
    [
        'id' => 'jf_it_digital',
        'name_en' => 'Information Technology/Digital',
        'name_ar' => 'تكنولوجيا المعلومات والحلول الرقمية',
        'code' => 'JF-IT',
        'category' => 'job_family',
        'color' => '#1da9de', // Col 2 90%
        'car_emoji' => '💻'
    ],
    [
        'id' => 'jf_finance_acct',
        'name_en' => 'Finance and Accounting',
        'name_ar' => 'المالية والمحاسبة',
        'code' => 'JF-FIN',
        'category' => 'job_family',
        'color' => '#2b51a4', // Col 1 100%
        'car_emoji' => '🚙'
    ],
    [
        'id' => 'jf_marketing',
        'name_en' => 'Marketing (Job Family)',
        'name_ar' => 'التسويق والاتصال المؤسسي',
        'code' => 'JF-MKT',
        'category' => 'job_family',
        'color' => '#f79740', // Col 3 90%
        'car_emoji' => '🏎️'
    ],
    [
        'id' => 'jf_sales',
        'name_en' => 'Sales',
        'name_ar' => 'المبيعات وتطوير الأعمال',
        'code' => 'JF-SALES',
        'category' => 'job_family',
        'color' => '#f78c2a', // Col 3 100%
        'car_emoji' => '🚗💨'
    ],
    [
        'id' => 'jf_engineering',
        'name_en' => 'Engineering',
        'name_ar' => 'الهندسة والعمليات الفنية',
        'code' => 'JF-ENG',
        'category' => 'job_family',
        'color' => '#119aaa', // Col 4 100%
        'car_emoji' => '⚙️'
    ],
    [
        'id' => 'jf_production_mfg',
        'name_en' => 'Production / Advanced Manufacturing',
        'name_ar' => 'الإنتاج والتصنيع المتقدم',
        'code' => 'JF-MFG',
        'category' => 'job_family',
        'color' => '#3bae49', // Col 5 100%
        'car_emoji' => '🏭'
    ],
    [
        'id' => 'jf_quality_assurance',
        'name_en' => 'Quality Assurance',
        'name_ar' => 'توكيد الجودة والامتثال',
        'code' => 'JF-QA',
        'category' => 'job_family',
        'color' => '#4eb75b', // Col 5 90%
        'car_emoji' => '🏆'
    ],
    [
        'id' => 'jf_logistics_supply',
        'name_en' => 'Logistics/Supply Chain',
        'name_ar' => 'اللوجستيات وسلاسل الإمداد والتوريد',
        'code' => 'JF-LOG',
        'category' => 'job_family',
        'color' => '#f7a454', // Col 3 80%
        'car_emoji' => '🚚'
    ],
    [
        'id' => 'jf_legal',
        'name_en' => 'Legal (Job Family)',
        'name_ar' => 'الشؤون القانونية والاستشارات',
        'code' => 'JF-LEG',
        'category' => 'job_family',
        'color' => '#5574b5', // Col 1 80%
        'car_emoji' => '⚖️'
    ],
    [
        'id' => 'jf_credit_collections',
        'name_en' => 'Credit & Collections',
        'name_ar' => 'الائتمان وإدارة التحصيل',
        'code' => 'JF-CRED',
        'category' => 'job_family',
        'color' => '#7f8487', // Col 6 100%
        'car_emoji' => '💳'
    ],
    [
        'id' => 'jf_customer_service',
        'name_en' => 'Customer Service / Call Center',
        'name_ar' => 'خدمة العملاء ومركز الاتصال',
        'code' => 'JF-CS',
        'category' => 'job_family',
        'color' => '#35b2e2', // Col 2 80%
        'car_emoji' => '📞'
    ],
    [
        'id' => 'jf_analytics_data',
        'name_en' => 'Analytics and Data Science',
        'name_ar' => 'التحليلات وعلم البيانات والذكاء الاصطناعي',
        'code' => 'JF-DATA',
        'category' => 'job_family',
        'color' => '#28a4b0', // Col 4 90%
        'car_emoji' => '🔬'
    ],
    [
        'id' => 'jf_corporate_affairs',
        'name_en' => 'Corporate Affairs',
        'name_ar' => 'الشؤون المؤسسية والعلاقات العامة',
        'code' => 'JF-CORP',
        'category' => 'job_family',
        'color' => '#6b85c0', // Col 1 70%
        'car_emoji' => '🏢'
    ],
    [
        'id' => 'jf_educational_ops',
        'name_en' => 'Educational Operations',
        'name_ar' => 'العمليات التعليمية والتدريبية',
        'code' => 'JF-EDU',
        'category' => 'job_family',
        'color' => '#75c681', // Col 5 70%
        'car_emoji' => '🎓'
    ],
    [
        'id' => 'jf_healthcare_hse',
        'name_en' => 'Healthcare Service Lines / HSE',
        'name_ar' => 'الخدمات الصحية والسلامة والصحة المهنية والبيئة',
        'code' => 'JF-HSE',
        'category' => 'job_family',
        'color' => '#3bae49', // Col 5 100%
        'car_emoji' => '🚑'
    ],
    [
        'id' => 'jf_project_program',
        'name_en' => 'Project and Program Management',
        'name_ar' => 'إدارة المشاريع والبرامج المؤسسية',
        'code' => 'JF-PPM',
        'category' => 'job_family',
        'color' => '#58b8c4', // Col 4 70%
        'car_emoji' => '📐'
    ],
    [
        'id' => 'jf_property_delivery',
        'name_en' => 'Property Management / Construction',
        'name_ar' => 'إدارة الممتلكات والمشاريع الإنشائية',
        'code' => 'JF-PROP',
        'category' => 'job_family',
        'color' => '#8c9093', // Col 6 90%
        'car_emoji' => '🏙️'
    ],
    [
        'id' => 'jf_risk_asset',
        'name_en' => 'Financial Risk / Asset Management',
        'name_ar' => 'إدارة المخاطر المالية وإدارة الأصول',
        'code' => 'JF-RISK',
        'category' => 'job_family',
        'color' => '#d9d8d6', // Col 7 100%
        'car_emoji' => '💼'
    ]
];

$stmtUpsert = $pdo->prepare("
    INSERT INTO `departments` (`id`, `name_en`, `name_ar`, `code`, `category`, `password`, `color`, `car_emoji`, `position`, `total_points`)
    VALUES (:id, :name_en, :name_ar, :code, :category, '1234', :color, :car_emoji, 0, 0)
    ON DUPLICATE KEY UPDATE
        `name_en` = VALUES(`name_en`),
        `name_ar` = VALUES(`name_ar`),
        `code` = VALUES(`code`),
        `category` = VALUES(`category`),
        `color` = VALUES(`color`),
        `car_emoji` = VALUES(`car_emoji`)
");

$inserted = 0;
foreach ($departmentsData as $dept) {
    $stmtUpsert->execute([
        ':id' => $dept['id'],
        ':name_en' => $dept['name_en'],
        ':name_ar' => $dept['name_ar'],
        ':code' => $dept['code'],
        ':category' => $dept['category'],
        ':color' => $dept['color'],
        ':car_emoji' => $dept['car_emoji']
    ]);
    $inserted++;
}

echo "Successfully migrated and updated {$inserted} departments with exact palette colors!\n";

// Count by category
$countBU = $pdo->query("SELECT COUNT(*) FROM `departments` WHERE `category` = 'bu'")->fetchColumn();
$countJF = $pdo->query("SELECT COUNT(*) FROM `departments` WHERE `category` = 'job_family'")->fetchColumn();
$countTotal = $pdo->query("SELECT COUNT(*) FROM `departments`")->fetchColumn();

echo "Summary:\n";
echo "- Business Units (BU): {$countBU}\n";
echo "- Job Families (JF): {$countJF}\n";
echo "- Total Departments: {$countTotal}\n";
