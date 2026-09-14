<?php
require_once __DIR__ . '/../db.php';
$pdo = getDBConnection();

// Check if questions exist for week 2
$stmt = $pdo->query("SELECT COUNT(*) FROM questions WHERE week_id = 2");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO `questions` (`week_id`, `category_en`, `category_ar`, `question_en`, `question_ar`, `option_a_en`, `option_a_ar`, `option_b_en`, `option_b_ar`, `option_c_en`, `option_c_ar`, `option_d_en`, `option_d_ar`, `correct_option`, `points`) VALUES
    (2, 'Company Culture', 'ثقافة الشركة', 'What is our primary focus at GB Corp during Back to School season?', 'ما هو تركيزنا الأساسي في جي بي كورب خلال موسم العودة للمدارس؟', 'Work only', 'العمل الروتيني فقط', 'Team energy and excellence', 'طاقة الفريق والتميز وخدمة المجتمع', 'Delaying tasks', 'تأجيل المهام', 'None', 'لا شيء مما سبق', 'B', 10),
    (2, 'Teamwork', 'العمل الجماعي', 'How can departments collaborate best to boost race points?', 'كيف يمكن للأقسام التعاون لتحقيق أعلى النقاط في السباق؟', 'Sharing knowledge and quick answers', 'مشاركة المعرفة والإجابات السريعة والمشاركة الفعالة', 'Working in isolation', 'العمل الفردي المنعزل', 'Ignoring questions', 'تجاهل الأسئلة', 'Waiting', 'الانتظار دون تفاعل', 'A', 10)
    ");
}

// Check if questions exist for week 3
$stmt3 = $pdo->query("SELECT COUNT(*) FROM questions WHERE week_id = 3");
if ($stmt3->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO `questions` (`week_id`, `category_en`, `category_ar`, `question_en`, `question_ar`, `option_a_en`, `option_a_ar`, `option_b_en`, `option_b_ar`, `option_c_en`, `option_c_ar`, `option_d_en`, `option_d_ar`, `correct_option`, `points`) VALUES
    (3, 'Innovation', 'الابتكار والسرعة', 'What makes a high performance team cross the finish line first?', 'ما الذي يجعل الفريق عالي الأداء يعبر خط النهاية أولاً؟', 'Hesitation', 'التردد والبطء', 'Synergy, speed and accuracy', 'التكامل والسرعة والدقة في التحديات', 'Individual work', 'العمل الفردي', 'Stopping early', 'التوقف قبل النهاية', 'B', 10),
    (3, 'GB Spirit', 'روح جي بي كورب', 'What is our motto in Back to School challenges?', 'ما هو شعارنا في تحديات العودة إلى المدارس؟', 'Move your car if you can!', 'جرس المدرسة يدق! حرّك سيارتك إن استطعت!', 'Stay parked', 'ابق في مكانك', 'No race today', 'لا سباق اليوم', 'Quit early', 'الانسحاب مبكراً', 'A', 15)
    ");
}

echo "Questions populated successfully.\n";
