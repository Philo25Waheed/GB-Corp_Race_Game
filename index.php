<?php
/**
 * GB Corp - Back to School Race Game
 * Home Screen & Landing Experience
 */
require_once __DIR__ . '/db.php';

$pdo = getDBConnection();

// Check if user is already logged in
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT u.*, d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color, d.car_emoji, d.position, d.total_points FROM `users` u JOIN `departments` d ON u.department_id = d.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
}

// Fetch departments for live preview
$departments = $pdo->query("SELECT * FROM `departments` ORDER BY `position` DESC, `total_points` DESC")->fetchAll();

// Top 3 Departments
$topDepts = $pdo->query("SELECT * FROM `departments` ORDER BY `total_points` DESC, `position` DESC LIMIT 3")->fetchAll();

// Top 3 Individual MVP Scorers
$stmtTopUsers = $pdo->query("
    SELECT 
        u.id, 
        u.name, 
        u.email, 
        u.department_id, 
        d.name_ar AS dept_name_ar, 
        d.name_en AS dept_name_en, 
        d.code AS dept_code,
        d.color AS dept_color, 
        d.car_emoji,
        (
            COALESCE((SELECT SUM(qa.points_earned) FROM `quiz_attempts` qa WHERE qa.user_id = u.id), 0) +
            COALESCE((SELECT SUM(ps.points_awarded) FROM `photo_submissions` ps WHERE ps.user_id = u.id AND ps.status = 'approved'), 0)
        ) AS total_score,
        (
            SELECT COUNT(*) FROM `quiz_attempts` qa WHERE qa.user_id = u.id AND qa.is_correct = 1
        ) AS correct_answers_count
    FROM `users` u
    LEFT JOIN `departments` d ON u.department_id = d.id
    ORDER BY total_score DESC, correct_answers_count DESC, u.id ASC
    LIMIT 3
");
$topUsers = $stmtTopUsers->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>🎒 العودة إلى المدارس | Back to School - GB Corp Race Game</title>
    
    <!-- Google Fonts: Cairo (Unified for Arabic & English) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Favicon / Brand Icon -->
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/main.css?v=30">
    <link rel="stylesheet" href="css/auth.css?v=30">
</head>
<body>
    <div class="app-wrapper">
        <!-- TOP HEADER -->
        <header class="main-header">
            <div class="header-brand">
                <a href="index.php" style="text-decoration:none; display:flex; align-items:center; gap:0.85rem; color:inherit;">
                    <div class="corp-logo-badge">
                        <img src="GB_Corp.png" alt="GB Corp" class="corp-logo-img">
                    </div>
                    <div class="summer-brand-titles">
                        <div class="brand-arabic-title">العودة إلى المدارس <span style="font-weight:400; opacity:0.85; font-size:0.85em;">| Back to School</span></div>
                    </div>
                </a>
            </div>

            <div class="header-controls">
                <?php if ($currentUser): ?>
                    <div class="home-user-badge" style="display:flex; align-items:center; gap:0.75rem; background:rgba(8, 47, 73, 0.8); border:1px solid var(--border-color); padding:0.4rem 1rem; border-radius:var(--radius-md);">
                        <span style="font-size:1.3rem;"><?= htmlspecialchars($currentUser['car_emoji'] ?? '👤') ?></span>
                        <span style="font-weight:700; font-size:0.95rem; color:#ffffff;"><?= htmlspecialchars($currentUser['name']) ?> (<?= htmlspecialchars($currentUser['dept_code']) ?>)</span>
                        <a href="game.php" class="btn-b2s-primary" style="padding:0.4rem 0.9rem; font-size:0.9rem;">السباق والكويز 🚀</a>
                        <a href="logout.php" class="btn-b2s-ghost" style="padding:0.4rem 0.8rem; font-size:0.85rem;">خروج | Logout</a>
                    </div>
                <?php else: ?>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <a href="login.php" class="btn-b2s-ghost" style="padding:0.5rem 1.25rem; font-size:0.95rem;">تسجيل الدخول | Sign In</a>
                        <a href="register.php" class="btn-b2s-primary" style="padding:0.5rem 1.35rem; font-size:0.95rem;">حساب جديد | Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- MAIN HERO & SLOGAN -->
        <main class="home-hero-container">
            <!-- Back to School Pill -->
            <div class="b2s-pill-badge">
                <span class="bell-icon">🔔</span>
                <span>تحديات العودة إلى المدارس | Back to School Challenges</span>
                <span>🎒🏁</span>
            </div>

            <!-- Grand Slogan Card -->
            <div class="slogan-showcase-card">
                <div class="slogan-english">
                    “The school bell is ringing to start the challenges! <span class="highlight-gold">Move your car if you can!</span>”
                </div>
                <div class="slogan-arabic">
                    «<span class="highlight-orange">شارك في تحدى الرجوع للمدرسة</span> وحرك سيارة إداراتك إلى خط النهاية»
                </div>
            </div>

            <!-- Call to Actions -->
            <div class="home-cta-actions">
                <?php if ($currentUser): ?>
                    <a href="game.php?announce=quiz" class="btn-b2s-primary" style="font-size:1.25rem; padding:1.1rem 2.8rem;">
                        <span>🚀 ادخل إلى الكويز والسباق | Enter Race & Quiz</span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-b2s-primary" style="font-size:1.15rem; padding:1rem 2.4rem;">
                        <span>🔑 تسجيل الدخول | Sign In</span>
                    </a>
                    <a href="register.php" class="btn-b2s-secondary" style="font-size:1.15rem; padding:1rem 2.4rem;">
                        <span>✨ إنشاء حساب جديد | Register</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Logged-in Quick Bar if already active -->
            <?php if ($currentUser): ?>
                <div class="home-user-logged-banner">
                    <div class="logged-user-details">
                        <span class="logged-user-avatar"><?= htmlspecialchars($currentUser['car_emoji'] ?? '🏎️') ?></span>
                        <div style="text-align:right;">
                            <div style="font-weight:800; font-size:1.1rem; color:#ffffff;">مرحباً، <?= htmlspecialchars($currentUser['name']) ?>!</div>
                            <div style="font-size:0.88rem; color:var(--baby-blue-soft);">أنت تقود سيارة <?= htmlspecialchars($currentUser['dept_name_ar'] ?? $currentUser['dept_name_en']) ?> (<?= htmlspecialchars($currentUser['dept_code']) ?>) • مجموع النقاط: <?= (int)$currentUser['total_points'] ?> نقطة</div>
                        </div>
                    </div>
                    <a href="game.php?announce=quiz" class="btn-b2s-primary" style="padding:0.6rem 1.4rem; font-size:0.95rem;">
                        يلا نبدأ الكويز 🏎️
                    </a>
                </div>
            <?php endif; ?>
        </main>

        <!-- 3 KEY FEATURES SECTION -->
        <section class="home-features-grid">
            <div class="home-feature-card">
                <span class="home-feature-icon">🏎️💨</span>
                <h3 class="home-feature-title">سباق الأقسام السريع | Fleet Highway</h3>
                <p class="home-feature-desc">تنافس مع زملائك من مختلف أقسام GB Corp! كل إجابة صحيحة تدفع سيارة إداراتك إلى الأمام على مسار السباق المباشر.</p>
            </div>

            <div class="home-feature-card" style="border-color:rgba(249, 115, 22, 0.4);">
                <span class="home-feature-icon">🔔🧠</span>
                <h3 class="home-feature-title" style="color:#fb923c;">كويز التحدي الأسبوعي | Weekly Quiz</h3>
                <p class="home-feature-desc">مع دقات جرس المدرسة تبدأ أسئلة السرعة والمعرفة العامة وثقافة الشركة! اربح النقاط وضاعف تقدم فريقك.</p>
            </div>

            <a href="#home-leaderboard" class="home-feature-card" style="text-decoration:none; cursor:pointer;">
                <span class="home-feature-icon">🏆🥇</span>
                <h3 class="home-feature-title">لوحة الشرف والجوائز | Leaderboard</h3>
                <p class="home-feature-desc">تابع ترتيب الأقسام لحظة بلحظة، واكتشف من سيتوج بطلاً لسباق العودة إلى المدارس ويحصد مكافآت التميز!</p>
            </a>
        </section>

        <!-- LEADERBOARD & HONORS SHOWCASE (TOP 3 DEPARTMENTS & TOP 3 INDIVIDUAL SCORERS) -->
        <section class="home-leaderboard-section" id="home-leaderboard">
            <div class="home-section-header">
                <div>
                    <h2 class="home-section-title">
                        <span>🏆 لوحة الشرف والجوائز | Leaderboard</span>
                    </h2>
                    <div class="home-section-subtitle">
                        أعلى 3 أقسام متصدرة للسباق وأعلى 3 أشخاص في إحراز النقاط والكويزات • Top 3 Leading Departments & Top 3 MVP Scorers
                    </div>
                </div>
                <a href="game.php" class="btn-b2s-primary" style="padding:0.5rem 1.35rem; font-size:0.92rem; text-decoration:none;">
                    دخول السباق المباشر 🏎️
                </a>
            </div>

            <div class="home-leaderboard-dual">
                <!-- COLUMN 1: TOP 3 DEPARTMENTS -->
                <div class="leaderboard-podium-card">
                    <div class="podium-card-header">
                        <span class="podium-card-icon">🏢</span>
                        <div>
                            <h3 class="podium-card-title">أعلى 3 أقسام في الصدارة</h3>
                            <div class="podium-card-sub">Top 3 Leading Departments</div>
                        </div>
                    </div>

                    <div class="podium-entries">
                        <?php 
                        $medals = ['🥇', '🥈', '🥉'];
                        $deptBadgesAr = ['بطل الصدارة 🏆', 'الوصيف 🥈', 'المركز الثالث 🥉'];
                        $podiumClasses = ['gold-podium', 'silver-podium', 'bronze-podium'];
                        foreach ($topDepts as $idx => $d): 
                        ?>
                            <div class="podium-row <?= $podiumClasses[$idx] ?? '' ?>">
                                <div class="podium-row-main">
                                    <div class="podium-medal-col">
                                        <span class="podium-medal-symbol"><?= $medals[$idx] ?></span>
                                        <span class="podium-rank-tag">#<?= $idx + 1 ?></span>
                                    </div>
                                    <div class="podium-info-col">
                                        <div class="podium-title-ar"><?= htmlspecialchars($d['name_ar']) ?></div>
                                        <div class="podium-title-en"><?= htmlspecialchars($d['name_en']) ?> (<?= htmlspecialchars($d['code']) ?>)</div>
                                    </div>
                                    <div class="podium-score-col">
                                        <div class="podium-pts-val"><?= (int)$d['total_points'] ?> <small>نقطة</small></div>
                                        <div class="podium-mile-val"><?= (int)$d['position'] * 10 ?> ميل</div>
                                    </div>
                                </div>
                                <div class="podium-row-footer">
                                    <div class="podium-badge-tag"><?= $deptBadgesAr[$idx] ?></div>
                                    <div class="podium-avatar-col" data-dept-car="<?= htmlspecialchars($d['id']) ?>" data-car-w="68" data-car-h="26" style="filter:drop-shadow(0 0 10px <?= htmlspecialchars($d['color']) ?>);">
                                        <?= htmlspecialchars($d['car_emoji']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- COLUMN 2: TOP 3 INDIVIDUAL SCORERS -->
                <div class="leaderboard-podium-card">
                    <div class="podium-card-header">
                        <span class="podium-card-icon">🌟</span>
                        <div>
                            <h3 class="podium-card-title">أعلى 3 أشخاص في النقاط</h3>
                            <div class="podium-card-sub">Top 3 Individual MVP Scorers</div>
                        </div>
                    </div>

                    <div class="podium-entries">
                        <?php if (empty($topUsers)): ?>
                            <div style="text-align:center; padding:2rem; color:var(--white-muted); font-size:0.95rem;">
                                لا توجد مشاركات حتى الآن. كن أول من يحل الكويز ويدخل لوحة الشرف! 🚀
                            </div>
                        <?php else: ?>
                            <?php 
                            $userBadgesAr = ['المتصدر الأول 🌟', 'المتسابق الثاني ⭐', 'المتسابق الثالث ✨'];
                            foreach ($topUsers as $idx => $u): 
                            ?>
                                <div class="podium-row <?= $podiumClasses[$idx] ?? '' ?>">
                                    <div class="podium-row-main">
                                        <div class="podium-medal-col">
                                            <span class="podium-medal-symbol"><?= $medals[$idx] ?></span>
                                            <span class="podium-rank-tag">#<?= $idx + 1 ?></span>
                                        </div>
                                        <div class="podium-info-col">
                                            <div class="podium-title-ar"><?= htmlspecialchars($u['name']) ?></div>
                                            <div class="podium-title-en">
                                                <?= htmlspecialchars($u['dept_name_ar'] ?? '') ?> (<?= htmlspecialchars($u['dept_code'] ?? '') ?>)
                                            </div>
                                        </div>
                                        <div class="podium-score-col">
                                            <div class="podium-pts-val individual"><?= (int)$u['total_score'] ?> <small>نقطة</small></div>
                                            <div class="podium-mile-val"><?= (int)$u['correct_answers_count'] ?> إجابة صحيحة</div>
                                        </div>
                                    </div>
                                    <div class="podium-row-footer">
                                        <div class="podium-badge-tag individual"><?= $userBadgesAr[$idx] ?></div>
                                        <div class="podium-avatar-col" data-dept-car="<?= htmlspecialchars($u['department_id'] ?? '') ?>" data-car-w="68" data-car-h="26">
                                            <?= htmlspecialchars($u['car_emoji'] ?? '🏎️') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- DEPARTMENTS FLEET OVERVIEW -->
        <section style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:clamp(0.85rem, 2.5vw, 1.75rem); display:flex; flex-direction:column; gap:1.25rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                <h3 style="font-size:1.25rem; font-weight:800; color:#ffffff; display:flex; align-items:center; gap:0.5rem;">
                    <span>🏁 أسطول سيارات جميع الأقسام | All Departments Fleet</span>
                </h3>
                <span style="font-size:0.88rem; color:var(--baby-blue-soft);">النقاط والمراكز المباشرة</span>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 180px), 1fr)); gap:1rem;">
                <?php foreach ($departments as $dept): ?>
                    <div style="background:rgba(7, 21, 38, 0.7); border:1px solid <?= htmlspecialchars($dept['color']) ?>; border-radius:var(--radius-md); padding:1rem; text-align:center; display:flex; flex-direction:column; gap:0.4rem;">
                        <span data-dept-car="<?= htmlspecialchars($dept['id']) ?>" data-car-w="74" data-car-h="30" style="display:inline-flex;justify-content:center;align-items:center;min-height:36px;">
                            <?= htmlspecialchars($dept['car_emoji']) ?>
                        </span>
                        <div style="font-weight:800; font-size:1.05rem; color:#ffffff;"><?= htmlspecialchars($dept['name_ar']) ?></div>
                        <div style="font-size:0.8rem; color:var(--white-muted);"><?= htmlspecialchars($dept['name_en']) ?> (<?= htmlspecialchars($dept['code']) ?>)</div>
                        <div style="margin-top:0.5rem; background:rgba(56, 189, 248, 0.15); border-radius:999px; padding:0.25rem 0.5rem; font-weight:700; color:var(--baby-blue-light); font-size:0.85rem;">
                            <?= (int)$dept['total_points'] ?> نقطة • <?= (int)$dept['position'] * 10 ?> ميل
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- FOOTER -->
        <footer style="text-align:center; padding:1.5rem; color:var(--white-muted); font-size:0.88rem; border-top:1px solid rgba(56, 189, 248, 0.15);">
            GB Corp © <?= date('Y') ?> • مسابقة العودة إلى المدارس | Back to School Interactive Challenge
        </footer>
    </div>

    <!-- Enhanced Dedicated Cars Script -->
    <script src="js/cars.js?v=1"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.getDepartmentCarSvg === 'function') {
            document.querySelectorAll('[data-dept-car]').forEach(function (el) {
                var deptId = el.getAttribute('data-dept-car');
                if (!deptId) return;
                var w = parseInt(el.getAttribute('data-car-w') || '60', 10);
                var h = parseInt(el.getAttribute('data-car-h') || '24', 10);
                el.innerHTML = window.getDepartmentCarSvg(deptId, { width: w, height: h, showGlow: true });
            });
        }
    });
    </script>
</body>
</html>
