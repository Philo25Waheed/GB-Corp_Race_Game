<?php
/**
 * Admin Dashboard - Summer Road Trip Race Game
 * GB Corp Management & Race Control Center
 */
require_once __DIR__ . '/db.php';
$pdo = getDBConnection();

// Handle Logout via GET
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
    header('Location: index.php');
    exit;
}

$currentUser = null;
$isAdmin = false;

if (!empty($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $currentUser = $stmtUser->fetch();
    if ($currentUser && ($currentUser['role'] === 'admin' || !empty($currentUser['is_admin']))) {
        $isAdmin = true;
        $_SESSION['is_admin'] = true;
    }
} elseif (!empty($_SESSION['is_admin'])) {
    $isAdmin = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>⚙️ لوحة تحكم المشرفين | GB Corp Race Master Admin</title>
    
    <!-- Google Fonts: Cairo (Unified for Arabic & English) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="css/admin.css?v=25">
</head>
<body>
<?php if (!$isAdmin): ?>
    <!-- ADMIN ACCESS RESTRICTION SCREEN (ONLY FOR VERIFIED ADMINS) -->
    <div id="admin-auth-modal" class="admin-auth-overlay">
        <div class="auth-card" style="max-width:520px; text-align:center; padding:2.5rem 2rem;">
            <?php if ($currentUser): ?>
                <!-- Logged in as regular player -->
                <div class="auth-icon" style="font-size:3.5rem; margin-bottom:1rem;">🚫</div>
                <h2 style="font-size:1.6rem; color:#f87171; margin-bottom:0.5rem;">غير مصرح لك بالدخول | Access Restricted</h2>
                <p style="color:#e0f2fe; line-height:1.6; margin-bottom:1.5rem; font-size:1rem;">
                    عذراً يا <strong><?= htmlspecialchars($currentUser['name']) ?></strong>! لوحة الإدارة مخصصة فقط لمن قاموا بإنشاء حساب وتم التحقق من أنهم مشرفين (Verified Admins). حسابك الحالي مسجل كمتسابق عادي.
                </p>
                <div style="display:flex; flex-direction:column; gap:0.75rem;">
                    <a href="login.php" class="btn-auth-submit" style="text-decoration:none; display:block; text-align:center;">🔑 تسجيل الدخول بحساب مسؤول | Sign In as Admin</a>
                    <a href="register.php" class="back-link" style="color:var(--orange-amber, #fb923c); font-weight:700;">✨ تسجيل حساب مشرف جديد برمز التحقق | Register as Admin</a>
                    <a href="game.php" class="back-link" style="color:#38bdf8;">← العودة للمضمار واللعبة | Back to Game</a>
                </div>
            <?php else: ?>
                <!-- Not logged in at all -->
                <div class="auth-icon" style="font-size:3.5rem; margin-bottom:1rem;">🔒</div>
                <h2 style="font-size:1.6rem; color:#ffffff; margin-bottom:0.5rem;">لوحة تحكم المشرفين | Admin Dashboard</h2>
                <p style="color:#94a3b8; line-height:1.6; margin-bottom:1.5rem; font-size:0.95rem;">
                    هذه اللوحة مخصصة فقط للمستخدمين الذين يملكون حساباً وتم التحقق من أنهم مشرفين (Verified Admins). يرجى تسجيل الدخول بحسابك أو إنشاء حساب مشرف جديد.
                </p>
                <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.5rem;">
                    <a href="login.php" class="btn-auth-submit" style="text-decoration:none; display:block; text-align:center;">🔑 تسجيل دخول المشرفين | Admin Sign In</a>
                    <a href="register.php" class="back-link" style="color:var(--orange-amber, #fb923c); font-weight:700;">✨ إنشاء حساب مشرف جديد برمز التحقق | Register</a>
                </div>

                <div style="border-top:1px dashed rgba(56,189,248,0.25); padding-top:1rem; margin-top:1rem;">
                    <p style="font-size:0.85rem; color:#94a3b8; margin-bottom:0.6rem;">أو أدخل كلمة سر المشرف الرئيسية للوصول السريع:</p>
                    <form id="form-admin-auth">
                        <div class="input-group">
                            <input type="password" id="input-admin-password" placeholder="أدخل كلمة المرور أو PIN (Default: 1234)" required>
                        </div>
                        <p id="admin-auth-error" class="auth-error hidden"></p>
                        <button type="submit" class="btn-auth-submit" style="padding:0.65rem; font-size:0.95rem; margin-top:0.4rem;">دخول سريع | Fast PIN Access 🚀</button>
                    </form>
                </div>
                <a href="index.php" class="back-link" style="margin-top:1rem; display:inline-block;">← العودة للرئيسية | Back to Home</a>
            <?php endif; ?>
        </div>
    </div>
    <div id="admin-toast" class="admin-toast-container"></div>
    <script src="js/admin.js?v=14"></script>
</body>
</html>
<?php exit; endif; ?>

    <!-- MAIN ADMIN DASHBOARD WRAPPER -->
    <div id="admin-dashboard-app" class="admin-app-layout">
        
        <!-- TOP ADMIN NAVBAR -->
        <header class="admin-topbar">
            <div class="brand-side">
                <span class="brand-badge">🚗 GB Corp</span>
                <span class="topbar-title">لوحة التحكم المركزية للسباق | Race Game Master Admin</span>
            </div>

            <!-- KEY COUNTERS -->
            <div class="topbar-stats">
                <div class="stat-pill">
                    <span class="stat-lbl">الأسبوع النشط | Active Week:</span>
                    <span class="stat-val highlight" id="top-active-week-val">الأسبوع 1</span>
                </div>
                <div class="stat-pill">
                    <span class="stat-lbl">المشاركون | Users:</span>
                    <span class="stat-val" id="top-users-count">0</span>
                </div>
                <div class="stat-pill">
                    <span class="stat-lbl">الصور المرفوعة | Photos:</span>
                    <span class="stat-val" id="top-photos-count">0</span>
                </div>
            </div>

            <!-- TOP ACTIONS -->
            <div class="topbar-actions">
                <a href="game.php" target="_blank" class="btn-view-site">
                    <span>👁️ عرض اللعبة والمضمار | Live Race</span>
                </a>
                <a href="logout.php" id="btn-admin-logout" class="btn-logout" title="تسجيل الخروج | Logout">
                    <span>🚪 خروج | Logout</span>
                </a>
            </div>
        </header>

        <!-- DASHBOARD BODY WITH TABS -->
        <div class="admin-container">
            <!-- SIDEBAR NAVIGATION TABS -->
            <aside class="admin-sidebar">
                <nav class="admin-nav">
                    <button class="nav-tab-btn active" data-tab="tab-highway">
                        <span class="tab-icon">🛣️</span>
                        <span>مضمار السباق | Highway Track</span>
                    </button>
                    <button class="nav-tab-btn" data-tab="tab-weeks">
                        <span class="tab-icon">📅</span>
                        <span>إدارة الأسابيع | Weeks Manager</span>
                    </button>
                    <button class="nav-tab-btn" data-tab="tab-quiz">
                        <span class="tab-icon">❓</span>
                        <span>أسئلة الكويز | Quiz Questions</span>
                    </button>
                    <button class="nav-tab-btn" data-tab="tab-photos">
                        <span class="tab-icon">📷</span>
                        <span>تقييم الصور | Photo Submissions</span>
                    </button>
                    <button class="nav-tab-btn" data-tab="tab-activities">
                        <span class="tab-icon">👥</span>
                        <span>إدارة النقاط | Points Management</span>
                    </button>
                    <button class="nav-tab-btn" data-tab="tab-users">
                        <span class="tab-icon">👤</span>
                        <span>دليل المتسابقين | Users Directory</span>
                    </button>
                    <button class="nav-tab-btn" data-tab="tab-finale">
                        <span class="tab-icon">🎉</span>
                        <span>مفاجأة الختام | Grand Finale</span>
                    </button>
                </nav>

                <div class="sidebar-danger-zone">
                    <button id="btn-global-reset-race" class="btn-danger-block">
                        <span>🔄 إعادة تعيين السباق | Reset Race</span>
                    </button>
                </div>
            </aside>

            <!-- MAIN CONTENT PANELS -->
            <main class="admin-content">

                <!-- ================= TAB 1: HIGHWAY RACE CONTROLS ================= -->
                <section id="tab-highway" class="tab-panel active">
                    <div class="panel-header">
                        <h2>🛣️ التحكم بمواقع سيارات الأقسام | Department Highway Controls</h2>
                        <p>تقديم أو تأخير سيارة أي قسم فورياً مع مزامنة لحظية | Move cars forward/backward with instant live sync.</p>
                    </div>

                    <!-- Category Filter & Live Search Toolbar -->
                    <div class="filter-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1.25rem;">
                        <div style="display:flex; gap:0.4rem; flex-wrap:wrap;" id="admin-highway-filter-group">
                            <button type="button" class="btn-admin-filter active" data-cat="all">جميع الأقسام | All (43)</button>
                            <button type="button" class="btn-admin-filter" data-cat="bu">قطاعات الأعمال | BU (24)</button>
                            <button type="button" class="btn-admin-filter" data-cat="job_family">العائلات الوظيفية | Job Families (19)</button>
                        </div>
                        <input type="text" id="admin-search-dept" placeholder="🔍 بحث عن قسم أو سيارة..." class="admin-select" style="max-width:260px; padding:0.4rem 0.85rem; font-size:0.85rem;">
                    </div>

                    <div class="highway-control-grid" id="highway-departments-controls">
                        <!-- Populated dynamically via JS -->
                    </div>
                </section>

                <!-- ================= TAB 2: WEEKS & CHALLENGES MANAGER ================= -->
                <section id="tab-weeks" class="tab-panel">
                    <div class="panel-header">
                        <h2>📅 جدول الأسابيع ونوع التحديات | 4-Week Challenges Manager</h2>
                        <p>تحديد الأسبوع النشط ونوع التحدي وتعديل التفاصيل | Set active week, challenge mode and rewards.</p>
                    </div>

                    <div class="weeks-cards-grid" id="weeks-manager-grid">
                        <!-- Populated dynamically via JS -->
                    </div>
                </section>

                <!-- ================= TAB 3: QUIZ QUESTIONS HUB ================= -->
                <section id="tab-quiz" class="tab-panel">
                    <div class="panel-header flex-between">
                        <div>
                            <h2>❓ بنك أسئلة الكويز (ثنائية اللغة) | Bilingual Quiz Questions Hub</h2>
                            <p>إدارة بنك الأسئلة باللغتين العربية والإنجليزية | Manage questions and answers in Arabic and English.</p>
                        </div>
                        <button id="btn-open-add-question" class="btn-primary-action">
                            <span>➕ إضافة سؤال جديد | Add Question</span>
                        </button>
                    </div>

                    <div class="filter-bar">
                        <label for="select-quiz-filter-week">تصفية حسب الأسبوع | Filter by Week:</label>
                        <select id="select-quiz-filter-week" class="admin-select">
                            <option value="">جميع الأسابيع | All Weeks</option>
                            <option value="1">الأسبوع 1 (Week 1)</option>
                            <option value="2">الأسبوع 2 (Week 2)</option>
                            <option value="3">الأسبوع 3 (Week 3)</option>
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table" id="table-questions">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الأسبوع | Week</th>
                                    <th>الفئة | Category</th>
                                    <th>السؤال (عربي)</th>
                                    <th>Question (English)</th>
                                    <th>الإجابة | Correct</th>
                                    <th>النقاط | PTS</th>
                                    <th>الإجراءات | Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-questions">
                                <!-- Populated dynamically JS -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- ================= TAB 4: PHOTO CHALLENGE SUBMISSIONS ================= -->
                <section id="tab-photos" class="tab-panel">
                    <div class="panel-header">
                        <h2>📷 مراجعة واعتماد صور تحدي التصوير | Photo Submissions Review</h2>
                        <p>استعراض واعتماد جميع الصور المرفوعة من الأقسام | Review and approve employee photos with +15 PTS.</p>
                    </div>

                    <div class="photo-submissions-grid" id="photo-submissions-grid">
                        <!-- Populated dynamically JS -->
                    </div>
                </section>

                <!-- ================= TAB 5: POINTS MANAGEMENT & DEDUCTIONS ================= -->
                <section id="tab-activities" class="tab-panel">
                    <div class="panel-header">
                        <h2>👥 إدارة وتعديل نقاط الأقسام | Department Points Adjustment (+ / -)</h2>
                        <p>منح نقاط إضافية أو تطبيق خصومات وعقوبات بالسالب | Award extra activity points or apply penalty deductions.</p>
                    </div>

                    <div class="award-activity-form-card">
                        <h3 id="form-points-title">⚙️ تخصيص وتعديل نقاط القسم | Adjust Department Points</h3>
                        <form id="form-award-activity" class="grid-form">
                            <!-- Operation Type Selector -->
                            <div class="form-group full-width">
                                <label>نوع العملية / الإجراء المطلوب | Operation Type:</label>
                                <div class="points-type-toggle">
                                    <label class="toggle-option active" id="lbl-op-add">
                                        <input type="radio" name="operation_type" id="op-type-add" value="add" checked>
                                        <span>➕ منح نقاط إضافية | Add Points (+)</span>
                                    </label>
                                    <label class="toggle-option danger" id="lbl-op-deduct">
                                        <input type="radio" name="operation_type" id="op-type-deduct" value="deduct">
                                        <span>➖ خصم نقاط / عقوبة | Minus / Deduct Points (-)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="select-activity-dept">القسم المعني | Department:</label>
                                <select id="select-activity-dept" class="admin-select" required>
                                    <option value="" disabled selected>اختر القسم | Select department...</option>
                                    <option value="it">🏎️ قسم IT Department</option>
                                    <option value="finance">🚙 قسم Finance Department</option>
                                    <option value="marketing">🏎️ قسم Marketing Department</option>
                                    <option value="hr">🚕 قسم HR Department</option>
                                    <option value="operations">🚗 قسم Operations Department</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="input-activity-points" id="lbl-points-amount">عدد النقاط | Points Amount (+):</label>
                                <input type="number" id="input-activity-points" value="30" min="1" max="500" required>
                            </div>

                            <div class="form-group full-width">
                                <label for="input-activity-name" id="lbl-reason-title">السبب / اسم النشاط أو المخالفة | Reason / Activity Name:</label>
                                <input type="text" id="input-activity-name" placeholder="مثال: إنجاز نشاط العمل الجماعي | e.g. Team mission accomplished" required>
                            </div>

                            <div class="form-group full-width">
                                <label for="input-activity-notes">ملاحظات توثيقية | Notes (اختياري / Optional):</label>
                                <textarea id="input-activity-notes" rows="2" placeholder="ملاحظات إضافية حول القرار | Additional context..."></textarea>
                            </div>

                            <div class="form-group full-width">
                                <button type="submit" id="btn-submit-points-action" class="btn-primary-action">
                                    <span>🚀 تنفيذ العملية وتحديث نقاط وموقع القسم | Execute Points Action</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- ================= TAB 6: USERS DIRECTORY ================= -->
                <section id="tab-users" class="tab-panel">
                    <div class="panel-header">
                        <h2>👤 دليل المتسابقين والمشاركين المسجلين | Registered Users Directory</h2>
                        <p>قائمة كاملة بجميع الموظفين المسجلين وإحصائيات التفاعل | Complete list of all participants and activity stats.</p>
                    </div>

                    <div class="search-bar-wrap">
                        <input type="text" id="input-search-users" placeholder="🔍 بحث بالاسم أو الإيميل أو القسم | Search by name, email or department..." class="admin-search-input">
                    </div>

                    <div class="table-responsive">
                        <table class="admin-table" id="table-users">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الاسم | Name</th>
                                    <th>البريد الإلكتروني | Email</th>
                                    <th>القسم | Department</th>
                                    <th>إجابات الكويز | Answers</th>
                                    <th>الصور | Photos</th>
                                    <th>تاريخ التسجيل | Date</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-users">
                                <!-- Populated dynamically JS -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- ================= TAB 7: FINALE & BONUS SURPRISE ================= -->
                <section id="tab-finale" class="tab-panel">
                    <div class="panel-header">
                        <h2>🎉 مفاجأة البونص الكبرى وتتويج الختام | Grand Finale & Bonus Reveal</h2>
                        <p>التحكم في إظهار شاشة المفاجأة الختامية لجميع المتسابقين وتخصيص الرسالة | Toggle bonus reveal and edit congratulation message.</p>
                    </div>

                    <div class="finale-control-card">
                        <div class="finale-status-row">
                            <div>
                                <h3>حالة كشف المفاجأة | Bonus Reveal Status:</h3>
                                <p id="finale-status-text">المفاجأة حالياً: <strong>مخفية | Hidden</strong></p>
                            </div>
                            <button id="btn-toggle-finale-reveal" class="btn-finale-toggle">
                                <span>🎁 كشف وتفعيل المفاجأة | Reveal Bonus Now</span>
                            </button>
                        </div>

                        <hr class="admin-divider">

                        <h3>✍️ تخصيص رسالة المفاجأة والتهنئة | Customize Finale Message:</h3>
                        <form id="form-finale-content" class="grid-form">
                            <div class="form-group full-width">
                                <label for="input-finale-title-ar">عنوان المفاجأة (عربي):</label>
                                <input type="text" id="input-finale-title-ar" value="🎉 مفاجأة البونص الكبرى! 🎉" required>
                            </div>
                            <div class="form-group full-width">
                                <label for="input-finale-title-en">عنوان المفاجأة (English):</label>
                                <input type="text" id="input-finale-title-en" value="🎉 The Grand Bonus Surprise! 🎉" required>
                            </div>
                            <div class="form-group full-width">
                                <label for="input-finale-msg-ar">نص رسالة التهنئة (عربي):</label>
                                <textarea id="input-finale-msg-ar" rows="3" required></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label for="input-finale-msg-en">نص رسالة التهنئة (English):</label>
                                <textarea id="input-finale-msg-en" rows="3" required></textarea>
                            </div>
                            <div class="form-group full-width">
                                <button type="submit" class="btn-save-settings">💾 حفظ نصوص المفاجأة | Save Finale Texts</button>
                            </div>
                        </form>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- MODAL: ADD / EDIT QUESTION -->
    <div id="modal-question" class="admin-modal-backdrop hidden">
        <div class="admin-modal-card">
            <div class="modal-head">
                <h3 id="modal-question-title">➕ إضافة سؤال كويز جديد | Add / Edit Quiz Question</h3>
                <button id="btn-close-q-modal" class="btn-close-modal">&times;</button>
            </div>
            
            <form id="form-save-question" class="modal-form">
                <input type="hidden" id="input-q-id" value="0">
                
                <div class="form-row-2">
                    <div class="form-group">
                        <label for="select-q-week">الأسبوع التابع له | Target Week:</label>
                        <select id="select-q-week" class="admin-select" required>
                            <option value="1">الأسبوع 1 | Week 1</option>
                            <option value="2">الأسبوع 2 | Week 2</option>
                            <option value="3">الأسبوع 3 | Week 3</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="input-q-points">النقاط | Points (PTS):</label>
                        <input type="number" id="input-q-points" value="10" min="5" max="50" required>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label for="input-q-cat-ar">الفئة (عربي):</label>
                        <input type="text" id="input-q-cat-ar" value="معلومات عامة" required>
                    </div>
                    <div class="form-group">
                        <label for="input-q-cat-en">الفئة (English):</label>
                        <input type="text" id="input-q-cat-en" value="General Knowledge" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="input-q-text-ar">نص السؤال (عربي):</label>
                    <textarea id="input-q-text-ar" rows="2" required></textarea>
                </div>

                <div class="form-group">
                    <label for="input-q-text-en">نص السؤال (English):</label>
                    <textarea id="input-q-text-en" rows="2" required></textarea>
                </div>

                <div class="options-edit-grid">
                    <div class="opt-edit-box">
                        <span class="opt-badge">A</span>
                        <input type="text" id="input-opt-a-ar" placeholder="الخيار A بالعربي" required>
                        <input type="text" id="input-opt-a-en" placeholder="Option A in English" required>
                    </div>
                    <div class="opt-edit-box">
                        <span class="opt-badge">B</span>
                        <input type="text" id="input-opt-b-ar" placeholder="الخيار B بالعربي" required>
                        <input type="text" id="input-opt-b-en" placeholder="Option B in English" required>
                    </div>
                    <div class="opt-edit-box">
                        <span class="opt-badge">C</span>
                        <input type="text" id="input-opt-c-ar" placeholder="الخيار C بالعربي" required>
                        <input type="text" id="input-opt-c-en" placeholder="Option C in English" required>
                    </div>
                    <div class="opt-edit-box">
                        <span class="opt-badge">D</span>
                        <input type="text" id="input-opt-d-ar" placeholder="الخيار D بالعربي" required>
                        <input type="text" id="input-opt-d-en" placeholder="Option D in English" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="select-q-correct">الإجابة الصحيحة | Correct Option:</label>
                    <select id="select-q-correct" class="admin-select" required>
                        <option value="A">الخيار A | Option A</option>
                        <option value="B">الخيار B | Option B</option>
                        <option value="C">الخيار C | Option C</option>
                        <option value="D">الخيار D | Option D</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" id="btn-cancel-q-modal" class="btn-secondary">إلغاء | Cancel</button>
                    <button type="submit" class="btn-primary-action">حفظ السؤال | Save Question 💾</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. GLOBAL RESET CONFIRMATION MODAL -->
    <div id="modal-reset-confirm" class="admin-modal-overlay hidden">
        <div class="admin-modal-card danger-modal-card">
            <div class="danger-icon-wrapper">
                <div class="danger-icon-pulse">⚠️</div>
            </div>
            
            <h2 class="danger-modal-title">تأكيد إعادة تعيين السباق بالكامل</h2>
            <div class="danger-modal-subtitle">Confirm Full Season Race Reset</div>
            
            <div class="danger-modal-body">
                <p class="danger-warning-text">
                    <strong>تحذير هام:</strong> هل أنت متأكد من رغبتك في تصفير وإعادة تعيين السباق؟<br>
                    سيتم تصفير جميع نقاط ومسافات الأقسام، وحذف إجابات الكويز والصور، وإرجاع الأسبوع النشط للأسبوع الأول.
                </p>
                <p class="danger-warning-en">
                    All department scores and highway distances will be reset to 0, quiz answers & photos cleared, and active week reset to Week 1.
                </p>
            </div>
            
            <div class="modal-actions">
                <button type="button" id="btn-cancel-reset" class="btn-secondary">
                    <span>✖ إلغاء وتراجع | Cancel</span>
                </button>
                <button type="button" id="btn-execute-reset" class="btn-danger-confirm">
                    <span>🔄 نعم، متأكد - إعادة التعيين الآن | Reset Now</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div id="admin-toast" class="admin-toast-container"></div>

    <script src="js/cars.js?v=1"></script>
    <script src="js/admin.js?v=26"></script>
</body>
</html>
