<?php
/**
 * Summer Road Trip - Corporate Team Race
 * GB Corp Interactive Weekly Challenges & Highway Race
 */
require_once __DIR__ . '/db.php';
$pdo = getDBConnection();

// 1. Immediate Logout Handler: Clear session and redirect to home
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php");
    exit;
}

// 2. Strict Session Requirement: Unauthenticated visitors must go to Home
if (empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$stmtUser = $pdo->prepare("SELECT u.*, d.name_en AS dept_name_en, d.name_ar AS dept_name_ar, d.code AS dept_code, d.color AS dept_color, d.secondary_color AS dept_secondary_color, d.car_model, d.livery_style, d.racing_num, d.car_emoji, d.position, d.total_points FROM `users` u LEFT JOIN `departments` d ON u.department_id = d.id WHERE u.id = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$currentUser = $stmtUser->fetch();

if (!$currentUser) {
    $_SESSION = [];
    header("Location: index.php");
    exit;
}

$isAdminUser = ($currentUser['role'] === 'admin' || !empty($_SESSION['is_admin']));
if ($isAdminUser) {
    $_SESSION['is_admin'] = true;
    $currentUser['is_admin'] = true;
} else {
    $currentUser['is_admin'] = false;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>🎒 العودة إلى المدارس | Back to School Race Game</title>
    
    <!-- Server-Injected Session Context (Guarantees zero kickout, instant UI load, and immunity to AJAX latency/CORS blocks) -->
    <script>
        window.__CURRENT_USER__ = <?= json_encode($currentUser, JSON_UNESCAPED_UNICODE) ?>;
        window.__IS_ADMIN__ = <?= $isAdminUser ? 'true' : 'false' ?>;
    </script>
    
    <!-- Google Fonts: Cairo (Unified for Arabic & English) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="css/main.css?v=25">
    <link rel="stylesheet" href="css/track.css?v=25">
    <link rel="stylesheet" href="css/control.css?v=20">
    <link rel="stylesheet" href="css/modals.css?v=20">
    <link rel="stylesheet" href="css/auth.css?v=20">
</head>
<body>
    <div id="app-container" class="app-wrapper">
        <!-- TOP SUMMER ROAD TRIP HEADER & BRANDING -->
        <header class="main-header">
            <!-- BRANDING & ARABIC LOGO TITLE -->
            <div class="header-brand">
                <div class="corp-logo-badge">
                    <span class="corp-logo-icon">🚗</span>
                    <span class="corp-logo-text">GB Corp</span>
                </div>
                <div class="summer-brand-titles">
                    <div class="brand-arabic-title">العودة إلى المدارس <span style="font-weight:400; opacity:0.85; font-size:0.85em;">| Back to School</span></div>
                </div>
            </div>
            
            <!-- GRAND CENTER TITLE & ACTIVE WEEK BADGE -->
            <div class="header-center-title">
                <div class="active-week-pill" id="header-week-pill">
                    <span class="pulse-dot"></span>
                    <span id="header-week-text">الأسبوع 1: كويز سباق المعرفة | Week 1: Knowledge Quiz</span>
                </div>
                <h1 class="road-trip-main-title">
                    <span class="title-sun-icon">☀️</span>
                    <span>ROAD TRIP</span>
                    <span class="title-palm-icon">🌴</span>
                </h1>
                <p class="road-trip-motto" id="role-subtitle">قسمك • سيارتك • سباقك نحو القمة | Your Dept • Your Ride • Your Race</p>
            </div>

            <!-- ACTION CONTROLS & USER PROFILE -->
            <div class="header-actions">
                <!-- Home Screen Link -->
                <a href="index.php" class="btn-icon-toggle" title="الصفحة الرئيسية | Home" style="text-decoration:none;">
                    <span class="icon">🏠</span>
                    <span>الرئيسية | Home</span>
                </a>

                <!-- Current User Profile Badge -->
                <div id="btn-user-profile" class="btn-icon-toggle" title="المتسابق الحالي | Current Player" style="cursor:default;">
                    <span class="icon" id="user-avatar-icon">🏎️</span>
                    <span id="label-user-status">المتسابق</span>
                </div>

                <!-- Sound FX Toggle -->
                <button id="btn-sound-toggle" class="btn-icon-toggle" title="تشغيل / كتم الصوت | Toggle Sound FX">
                    <span class="icon-sound">🔊</span>
                    <span class="sound-label">الصوت يعمل | Sound ON</span>
                </button>

                <!-- Admin Dashboard Link (Only shown to verified Admins) -->
                <a href="admin.php" id="btn-admin-panel" class="btn-gm-primary <?= $isAdminUser ? '' : 'hidden' ?>" style="<?= $isAdminUser ? '' : 'display:none;' ?>" title="فتح لوحة تحكم المشرف | Open Admin Dashboard">
                    <span class="icon">⚙️</span> لوحة التحكم | Admin
                </a>

                <!-- Dedicated Logout Button -->
                <a href="logout.php" id="btn-header-logout" class="btn-icon-toggle highlight-btn" title="تسجيل الخروج | Logout" style="text-decoration:none;">
                    <span class="icon">🚪</span>
                    <span>خروج | Logout</span>
                </a>
            </div>
        </header>

        <!-- SURPRISE FINALE REVEAL BANNER (SHOWN WHEN ACTIVE) -->
        <div id="finale-announcement-banner" class="finale-banner hidden">
            <div class="finale-banner-content">
                <span class="trophy-spin">🏆</span>
                <div class="finale-banner-text">
                    <h3 id="banner-finale-title">🎉 تم إعلان مفاجأة البونص الكبرى وبطل الموسم! | Bonus Surprise Revealed! 🎉</h3>
                    <p id="banner-finale-sub">اضغط هنا لمشاهدة تتويج القسم الفائز ومفاجأة البونص | Click to view crowned champion & bonus</p>
                </div>
                <button id="btn-open-finale" class="btn-finale-glow">🎁 كشف مفاجأة البونص | Reveal Bonus</button>
            </div>
        </div>

        <!-- 4-WEEK JOURNEY TIMELINE BAR -->
        <section class="journey-steps-banner" id="journey-timeline">
            <!-- Dynamically populated with 4 weeks and their active status -->
        </section>

        <!-- LOGGED-IN USER & DEPARTMENT HERO BAR -->
        <section id="dept-player-card" class="dept-player-card hidden">
            <div class="dept-header-banner" id="dept-banner">
                <div class="dept-info">
                    <span class="dept-car-icon" id="dept-car-icon">🏎️</span>
                    <div>
                        <div class="user-greeting-badge">مرحباً بك | Welcome, <strong id="player-user-name">المتسابق</strong> 👋</div>
                        <h2 class="dept-title-name" id="dept-title-name">قسم تكنولوجيا المعلومات | IT Department</h2>
                    </div>
                </div>
                <div class="dept-stats-right">
                    <div class="dept-position-badge">
                        <span class="pos-val" id="dept-pos-val">0</span>
                        <span class="pos-lbl">المسافة | Distance (MILES)</span>
                    </div>
                    <div class="dept-points-badge">
                        <span class="points-val" id="dept-points-val">0</span>
                        <span class="points-lbl">إجمالي النقاط | Total (PTS)</span>
                    </div>
                    <a href="logout.php" id="btn-switch-user" class="btn-tiny-switch" title="تسجيل الخروج | Logout" style="text-decoration:none;">خروج | Logout 🚪</a>
                </div>
            </div>
        </section>

        <!-- MAIN GAME ARENA (DYNAMIC CHALLENGE CONTAINER) -->
        <main class="game-arena">
            
            <!-- WEEKLY MULTI-CHALLENGE NAVIGATION HUB TABS -->
            <nav class="weekly-challenge-hub-nav" id="hub-challenge-nav" aria-label="تبويبات تحديات الأسبوع">
                <button type="button" class="hub-tab-btn active" data-target="quiz" id="tab-btn-quiz">
                    <span class="hub-tab-icon">📝</span>
                    <div class="hub-tab-texts">
                        <span class="hub-tab-title">كويز الأسبوع | Weekly Quiz</span>
                        <span class="hub-tab-badge" id="hub-quiz-badge">5 أسئلة • +50 نقطة</span>
                    </div>
                    <span class="hub-check-icon hidden" id="hub-quiz-check" title="مكتمل">✓</span>
                </button>
                <button type="button" class="hub-tab-btn" data-target="photo" id="tab-btn-photo">
                    <span class="hub-tab-icon">📸</span>
                    <div class="hub-tab-texts">
                        <span class="hub-tab-title">تحدي التصوير | Photo Challenge</span>
                        <span class="hub-tab-badge" id="hub-photo-badge">+15 نقطة وميل</span>
                    </div>
                    <span class="hub-check-icon hidden" id="hub-photo-check" title="تم الرفع">✓</span>
                </button>
                <button type="button" class="hub-tab-btn" data-target="team" id="tab-btn-team">
                    <span class="hub-tab-icon">👥</span>
                    <div class="hub-tab-texts">
                        <span class="hub-tab-title">النشاط الجماعي | Team Activity</span>
                        <span class="hub-tab-badge" id="hub-team-badge">+30 نقطة وميل</span>
                    </div>
                    <span class="hub-check-icon hidden" id="hub-team-check" title="تم الإنجاز">✓</span>
                </button>
            </nav>

            <!-- CHALLENGE SECTION 1: QUIZ CHALLENGE MODE (REDESIGNED) -->
            <section class="challenge-card challenge-mode-container" id="quiz-challenge-section">
                <!-- TOP INTERACTIVE PROGRESS BAR -->
                <div class="quiz-progress-track">
                    <div class="quiz-progress-fill" id="quiz-progress-fill" style="width: 20%;"></div>
                </div>

                <div class="challenge-header">
                    <div class="challenge-badge-wrap">
                        <span class="challenge-badge" id="challenge-badge">سؤال 1 / 5 | Question 1 / 5</span>
                        <span class="category-badge" id="category-badge">معلومات عامة | General Knowledge</span>
                        <span class="challenge-points-badge" id="challenge-points-badge">⭐ +10 نقاط | PTS</span>
                    </div>
                    
                    <!-- COUNTDOWN TIMER DISPLAY -->
                    <div class="timer-display-box" id="timer-box">
                        <div class="timer-icon">⏱️</div>
                        <div class="timer-val" id="timer-value">15</div>
                        <div class="timer-unit">ثانية | SEC</div>
                    </div>
                </div>

                <div class="challenge-body">
                    <div class="bilingual-question-box">
                        <div class="question-header-tag">
                            <span class="anti-cheat-pill">🛡️ مسابقة مؤمنة • يمنع تبديل الشاشة أثناء السؤال</span>
                        </div>
                        <h2 class="question-text-ar" id="question-text-ar">جاري تحميل السؤال...</h2>
                        <p class="question-text-en" id="question-text-en">Loading challenge question...</p>
                    </div>
                    
                    <!-- MODERNIZED OPTIONS GRID -->
                    <div class="options-grid" id="options-grid">
                        <div class="option-item" data-opt="A">
                            <span class="opt-prefix-badge">A</span>
                            <div class="opt-content">
                                <span class="opt-text-ar" id="opt-a-ar">-</span>
                                <span class="opt-text-en" id="opt-a-en">-</span>
                            </div>
                        </div>
                        <div class="option-item" data-opt="B">
                            <span class="opt-prefix-badge">B</span>
                            <div class="opt-content">
                                <span class="opt-text-ar" id="opt-b-ar">-</span>
                                <span class="opt-text-en" id="opt-b-en">-</span>
                            </div>
                        </div>
                        <div class="option-item" data-opt="C">
                            <span class="opt-prefix-badge">C</span>
                            <div class="opt-content">
                                <span class="opt-text-ar" id="opt-c-ar">-</span>
                                <span class="opt-text-en" id="opt-c-en">-</span>
                            </div>
                        </div>
                        <div class="option-item" data-opt="D">
                            <span class="opt-prefix-badge">D</span>
                            <div class="opt-content">
                                <span class="opt-text-ar" id="opt-d-ar">-</span>
                                <span class="opt-text-en" id="opt-d-en">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="quiz-nav-row">
                        <button id="btn-prev-question" class="btn-nav-quiz" style="display:none;" disabled>◀ السابق | Previous</button>
                        <span id="quiz-question-indicator" class="quiz-indicator">1 / 5</span>
                        <button id="btn-next-question" class="btn-nav-quiz">تخطي السؤال | Skip ▶</button>
                    </div>
                </div>
            </section>

            <!-- CHALLENGE SECTION 2: PHOTO CHALLENGE UPLOADER MODE -->
            <section class="challenge-card challenge-mode-container hidden" id="photo-challenge-section">
                <div class="challenge-header">
                    <div class="challenge-badge-wrap">
                        <span class="challenge-badge challenge-photo-badge">📷 تحدي التصوير الصيفي | Summer Photo Challenge</span>
                        <span class="category-badge">إبداع الفريق | Team Creativity</span>
                        <span class="challenge-points-badge">+15 نقطة وميل | +15 PTS & MI</span>
                    </div>
                </div>

                <div class="challenge-body">
                    <div class="photo-challenge-intro">
                        <h2 class="photo-title-main">شاركنا أجمل لحظات الصيف مع قسمك 📸🌴 | Summer Moments</h2>
                        <p class="photo-subtitle">ارفع صورة مميزة لفريق العمل وسجل 15 نقطة لسيارة قسمك مباشرة! | Upload team photo to earn +15 PTS & Miles!</p>
                    </div>

                    <!-- PHOTO UPLOADER BOX -->
                    <form id="form-photo-upload" class="photo-uploader-form" enctype="multipart/form-data">
                        <div class="upload-dropzone" id="upload-dropzone">
                            <input type="file" id="input-photo-file" name="photo" accept="image/jpeg,image/png,image/webp,image/jpg" class="file-input-hidden" required>
                            <div class="dropzone-content" id="dropzone-prompt">
                                <span class="dropzone-icon">📷</span>
                                <div class="dropzone-texts">
                                    <strong class="dropzone-title">اضغط هنا أو اسحب الصورة لإفلاتها | Click or Drag & Drop Photo Here</strong>
                                    <span class="dropzone-hint">JPG, PNG, WEBP (الحد الأقصى 10 ميجابايت | Max 10MB)</span>
                                </div>
                                <button type="button" class="btn-browse-file" id="btn-browse-file">اختيار صورة من جهازك | Choose File</button>
                            </div>

                            <!-- Image Preview Area -->
                            <div class="preview-area hidden" id="preview-area">
                                <img id="image-preview-element" src="" alt="Photo Preview" class="img-preview">
                                <button type="button" class="btn-remove-preview" id="btn-remove-preview" title="إلغاء الصورة | Cancel">&times;</button>
                            </div>
                        </div>

                        <div class="caption-input-group">
                            <label for="input-photo-caption">✍️ تعليق على الصورة / أفراد الفريق | Caption / Team members (اختياري / Optional):</label>
                            <input type="text" id="input-photo-caption" name="caption" placeholder="مثال: فريق الـ IT يستمتع بأجواء الصيف... | e.g. IT Team enjoying summer vibes..." class="input-caption" autocomplete="off">
                        </div>

                        <button type="submit" id="btn-submit-photo" class="btn-upload-submit">
                            <span>🚀 رفع الصورة وحصد 15 نقطة للقسم | Upload Photo (+15 PTS)</span>
                        </button>
                    </form>
                </div>
            </section>

            <!-- CHALLENGE SECTION 3: TEAM ACTIVITY MODE -->
            <section class="challenge-card challenge-mode-container hidden" id="team-activity-section">
                <div class="challenge-header">
                    <div class="challenge-badge-wrap">
                        <span class="challenge-badge challenge-team-badge">👥 تحدي النشاط الجماعي | Team Activity</span>
                        <span class="category-badge">العمل المشترك | Team Collaboration</span>
                        <span class="challenge-points-badge">+30 نقطة وميل | +30 PTS & MI</span>
                    </div>
                </div>

                <div class="challenge-body">
                    <div class="team-mission-box">
                        <div class="mission-icon">🎯</div>
                        <h2 class="mission-title" id="team-mission-title">مهمة الأسبوع التعاونية للقسم | Weekly Mission</h2>
                        <p class="mission-desc" id="team-mission-desc">اجتمعوا كفريق لإنجاز المهمة المشتركة وتسليم النتائج لمنسق المسابقة لحصد 30 نقطة دفعة واحدة لسيارة قسمكم!</p>
                        
                        <div class="mission-steps-box">
                            <div class="step-card">
                                <span class="step-badge">1</span>
                                <strong>تواصلوا مع الفريق | Connect</strong>
                                <p>نسقوا مع زملائكم في القسم لتنفيذ النشاط التعاوني | Coordinate with teammates to accomplish the task.</p>
                            </div>
                            <div class="step-card">
                                <span class="step-badge">2</span>
                                <strong>وثقوا الإنجاز | Document</strong>
                                <p>التقطوا صورة توثيقية أو ارفعوا الملاحظات | Take photo evidence or submit task notes.</p>
                            </div>
                            <div class="step-card">
                                <span class="step-badge">3</span>
                                <strong>اعتماد النقاط | Award Points</strong>
                                <p>يقوم المشرف باعتماد 30 نقطة مباشرة على لوحة السباق | Admin verifies and awards +30 PTS instantly.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- RACING TRACK (SERPENTINE ROAD & SPRINT LANES) -->
            <section class="track-section">
                <div class="track-view-header">
                    <div class="track-title-wrap">
                        <span class="track-main-heading">🛣️ مضمار سباق الصيف المباشر | SUMMER HIGHWAY RACETRACK</span>
                    </div>
                    
                    <div class="track-controls-toolbar">
                        <!-- Category Filter: All / BU / Job Families -->
                        <div class="track-category-filter-group" id="track-dept-filter-group">
                            <button type="button" class="btn-track-filter active" data-category="all">الكل | All</button>
                            <button type="button" class="btn-track-filter" data-category="bu">قطاعات الأعمال | BU</button>
                            <button type="button" class="btn-track-filter" data-category="job_family">العائلات الوظيفية | Job Families</button>
                        </div>

                        <div class="track-view-toggle-group">
                            <button id="btn-view-serpentine" class="btn-view-mode active" title="الطريق المتعرج | Serpentine Road">🛣️ الطريق المتعرج | Highway</button>
                            <button id="btn-view-sprint" class="btn-view-mode" title="المسارات المستقيمة | Sprint Lanes">🏁 مسارات السرعة | Sprint</button>
                        </div>
                    </div>
                </div>

                <!-- 1. SERPENTINE WINDING HIGHWAY -->
                <div id="serpentine-road-wrapper" class="serpentine-road-wrapper">
                    <!-- Road SVG & Cars generated dynamically in track.js -->
                </div>

                <!-- 2. STRAIGHT SPRINT LANES -->
                <div id="sprint-lanes-wrapper" class="sprint-lanes-wrapper hidden">
                    <div class="track-header-bar">
                        <div class="start-banner">START البداية</div>
                        <div class="track-distance-markers" id="track-markers"></div>
                        <div class="finish-banner">🏁 FINISH النهاية</div>
                    </div>

                    <div class="racing-lanes-container" id="lanes-container"></div>
                </div>
            </section>

            <!-- WEEKLY DEPARTMENT CHAMPIONS SPOTLIGHT (القسم الأعلى سكور لكل أسبوع) -->
            <section class="weekly-spotlight-section">
                <div class="spotlight-header">
                    <h3>🏆 لوحة شرف الأسابيع | WEEKLY TOP DEPARTMENTS</h3>
                    <p>تكريم الأقسام الأعلى سكور ونقاط في كل أسبوع من أسابيع رحلتنا الصيفية | Honoring top scoring teams each week</p>
                </div>
                <div class="weekly-champions-grid" id="weekly-champions-grid">
                    <!-- Populated dynamically via JS from DB -->
                </div>
            </section>

            <!-- LIVE LEADERBOARD (OVERALL STANDINGS & TOP SCORERS) -->
            <section class="leaderboard-section">
                <h3 class="leaderboard-title">🏆 لوحة الشرف والجوائز | LEADERBOARD & TOP SCORERS</h3>
                
                <div class="leaderboard-dual-layout">
                    <!-- Top 3 Departments -->
                    <div class="leaderboard-column">
                        <div class="leaderboard-subhead">
                            <span>🏢 أعلى 3 أقسام متصدرة | Top 3 Departments</span>
                        </div>
                        <div class="leaderboard-grid" id="leaderboard-grid">
                            <!-- Leaderboard cards auto-generated JS -->
                        </div>
                    </div>

                    <!-- Top 3 Individuals -->
                    <div class="leaderboard-column">
                        <div class="leaderboard-subhead">
                            <span>🌟 أعلى 3 أشخاص في السكور | Top 3 MVP Scorers</span>
                        </div>
                        <div class="leaderboard-grid" id="top-users-grid">
                            <!-- Top users auto-generated JS -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- BOTTOM SUMMER DASHBOARD (EARN POINTS, FLEET GRID, REWARDS & ODOMETER) -->
            <section class="summer-dashboard-grid">
                <!-- CARD 1: HOW TO EARN MILEAGE POINTS -->
                <div class="mileage-points-card">
                    <h4 class="dashboard-card-title">
                        <span>🏁</span> كيف تكسب النقاط والأميال؟ | How to Earn Points?
                    </h4>
                    <div class="points-rules-list">
                        <div class="point-rule-item">
                            <div class="rule-label">
                                <span class="rule-icon">❓</span>
                                <span>كويز المعرفة | Knowledge Quiz</span>
                            </div>
                            <span class="rule-pts">10 نقاط | PTS</span>
                        </div>
                        <div class="point-rule-item">
                            <div class="rule-label">
                                <span class="rule-icon">📷</span>
                                <span>تحدي التصوير | Photo Challenge</span>
                            </div>
                            <span class="rule-pts">15 نقطة | PTS</span>
                        </div>
                        <div class="point-rule-item">
                            <div class="rule-label">
                                <span class="rule-icon">👥</span>
                                <span>النشاط الجماعي | Team Activity</span>
                            </div>
                            <span class="rule-pts">30 نقطة | PTS</span>
                        </div>
                        <div class="point-rule-item">
                            <div class="rule-label">
                                <span class="rule-icon">⭐</span>
                                <span>نقاط تميز إضافية | Extra Bonus</span>
                            </div>
                            <span class="rule-pts bonus">+10 نقاط | PTS</span>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: FLEET DEPARTMENT OVERVIEW -->
                <div class="fleet-grid-card">
                    <h4 class="dashboard-card-title">
                        <span>🚗</span> أسطول أقسام GB Corp | GB Corp Fleet
                    </h4>
                    <div class="fleet-departments-container" id="fleet-departments-container">
                        <!-- Fleet cards auto-generated JS -->
                    </div>
                </div>

                <!-- CARD 3: REWARDS & TOTAL MILEAGE ODOMETER -->
                <div class="rewards-odometer-card">
                    <h4 class="dashboard-card-title">
                        <span>🏆</span> إجمالي مسافة الأسطول | TOTAL FLEET MILEAGE
                    </h4>
                    
                    <div class="odometer-box">
                        <span class="odometer-label">TOTAL FLEET MILEAGE</span>
                        <div class="odometer-led-digits" id="odometer-led-digits">00000</div>
                    </div>

                    <div class="summer-polaroid-card">
                        <div class="polaroid-photo-area">
                            <span class="polaroid-sun">☀️</span>
                            <span class="polaroid-van">🚐🌴</span>
                        </div>
                        <p class="polaroid-caption">العودة إلى المدارس | Back to School - Unforgettable Journey!</p>
                    </div>
                </div>
            </section>
        </main>

        <!-- ================= MODALS & POPUPS ================= -->



        <!-- QUIZ ANNOUNCEMENT POPUP (AFTER SIGN IN OR REGISTER) -->
        <div id="quiz-announcement-modal" class="modal-backdrop" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); display:none; align-items:center; justify-content:center;">
            <div class="quiz-announce-card">
                <button type="button" id="btn-close-quiz-announce" class="btn-modal-close" title="إغلاق | Close">&times;</button>
                <div class="announce-bell-hero">🔔</div>
                <div class="announce-tagline-badge">موسم العودة للمدارس • BACK TO SCHOOL QUIZ</div>
                
                <div style="display:flex; flex-direction:column; gap:0.4rem;">
                    <h2 class="announce-title-main" id="quiz-announce-title">استعد للتحدي! أنت على وشك دخول الكويز</h2>
                    <div class="announce-title-sub">The School Bell is Ringing! You are entering the Quiz Challenge!</div>
                </div>

                <div class="announce-info-box">
                    <span>⚡ أجب على أسئلة الكويز السريعة بدقة لجمع النقاط ودفع سيارة قسمك نحو خط النهاية!</span>
                    <br>
                    <small style="color:var(--baby-blue-soft);">Answer quickly and correctly to push your department car across the finish line!</small>
                </div>

                <button type="button" id="btn-start-quiz-now" class="btn-lets-start">
                    <span>🚀 يلا نبدأ | Let's Start!</span>
                </button>
            </div>
        </div>

        <!-- 2. GRAND FINALE "BONUS" SURPRISE MODAL (مفاجأة البونص) -->
        <div id="finale-modal" class="winner-overlay hidden">
            <div class="winner-content finale-grand-card">
                <div class="finale-crown-anim">👑 🏆 🌴</div>
                <h1 class="winner-title" id="finale-winner-title">🎉 مفاجأة البونص الكبرى! 🎉</h1>
                <h2 class="finale-champ-name" id="finale-champ-name">قسم IT هو بطل موسم الصيف!</h2>
                
                <div class="finale-message-box">
                    <p class="msg-ar" id="finale-modal-msg-ar">ألف مبروك لجميع الأقسام على هذه الرحلة الصيفية الرائعة المليئة بالحماس والطاقة الإيجابية! تهانينا الحارة للقسم المتوج بالمركز الأول!</p>
                    <p class="msg-en" id="finale-modal-msg-en">Huge congratulations to all departments for this unforgettable Summer Road Trip filled with energy, unity and excitement!</p>
                </div>

                <div class="winner-car-display" id="finale-car-display">🏎️</div>

                <div class="podium-display" id="finale-podium">
                    <!-- 1st, 2nd, 3rd places dynamic JS -->
                </div>

                <div class="winner-actions">
                    <button id="btn-finale-close" class="btn-secondary btn-large">✖ إغلاق الشاشة | Close</button>
                </div>
            </div>
        </div>

        <!-- 3. WEEKLY WINNER CELEBRATION MODAL (بطل وقسم الأسبوع) -->
        <div id="weekly-winner-modal" class="winner-overlay hidden">
            <div class="winner-content weekly-winner-card">
                <button type="button" id="btn-close-weekly-winner" class="btn-modal-close" title="إغلاق | Close">&times;</button>
                <div class="weekly-popper-badge-top">🎉 👑 🎊</div>
                <span class="weekly-winner-kicker">🏆 بطل الأسبوع | DEPARTMENT OF THE WEEK 🏆</span>
                <h1 class="weekly-winner-week-title" id="weekly-winner-week-title">الأسبوع 1: كويز المعرفة</h1>
                
                <div class="weekly-winner-hero-box">
                    <div class="weekly-champ-glow-ring">
                        <div class="weekly-champ-car-anim" id="weekly-champ-car">🏎️</div>
                    </div>
                    <h2 class="weekly-champ-name" id="weekly-champ-name">قسم تكنولوجيا المعلومات</h2>
                    <span class="weekly-champ-dept-en" id="weekly-champ-dept-en">IT Department</span>
                    
                    <div class="weekly-score-badge" id="weekly-winner-score-badge">
                        <span class="score-icon">⚡</span>
                        <span class="score-text" id="weekly-winner-score-val">+120 نقطة | 120 PTS</span>
                    </div>
                </div>

                <div class="weekly-winner-message-box">
                    <p class="weekly-msg-ar" id="weekly-winner-msg-ar">تهانينا الحارة! حصد هذا القسم المركز الأول وأعلى النقاط لهذا الأسبوع بفضل تفاعل وتميز أبطاله!</p>
                    <p class="weekly-msg-en" id="weekly-winner-msg-en">Congratulations! Highest score achieved this week through outstanding performance and team synergy!</p>
                </div>

                <div class="winner-actions weekly-winner-actions">
                    <button id="btn-re-pop-celebration" class="btn-primary btn-large btn-popper-sparkle">
                        <span>🎉 فرقعة احتفال إضافية! | Pop Confetti!</span>
                    </button>
                    <button id="btn-weekly-winner-close" class="btn-secondary btn-large">
                        <span>🏁 متابعة السباق | Continue Race</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- GLOBAL REALISTIC CONFETTI CANVAS -->
        <canvas id="global-confetti-canvas" class="global-confetti-canvas"></canvas>

        <!-- TOAST CONTAINER -->
        <div id="toast-container" class="toast-container"></div>
    </div>

    <!-- JavaScript Modules -->
    <script src="js/audio.js?v=16"></script>
    <script src="js/confetti.js?v=16"></script>
    <script src="js/sync.js?v=16"></script>
    <script src="js/state.js?v=16"></script>
    <script src="js/cars.js?v=1"></script>
    <script src="js/timer.js?v=16"></script>
    <script src="js/track.js?v=26"></script>
    <script src="js/app.js?v=36"></script>
</body>
</html>
