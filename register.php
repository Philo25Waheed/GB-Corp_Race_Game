<?php
/**
 * GB Corp - Back to School Race Game
 * Registration Page (Bilingual Arabic & English)
 */
require_once __DIR__ . '/db.php';

$pdo = getDBConnection();

// If already logged in, redirect to game
if (!empty($_SESSION['user_id'])) {
    header("Location: game.php?announce=quiz");
    exit;
}

$buDepts = $pdo->query("SELECT * FROM `departments` WHERE `category` = 'bu' ORDER BY `name_ar` ASC")->fetchAll();
$jfDepts = $pdo->query("SELECT * FROM `departments` WHERE `category` = 'job_family' ORDER BY `name_ar` ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>🎒 إنشاء حساب جديد | Register - GB Corp Race Game</title>
    
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
                <a href="index.php" class="btn-b2s-ghost" style="padding:0.45rem 1rem; font-size:0.9rem;">🏠 الرئيسية | Home</a>
                <a href="login.php" class="btn-b2s-secondary" style="padding:0.45rem 1.15rem; font-size:0.9rem;">تسجيل الدخول | Sign In</a>
            </div>
        </header>

        <!-- REGISTER FORM CONTAINER -->
        <main class="auth-page-wrapper">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-icon-badge">🎒🏎️</div>
                    <h1 class="auth-title">إنشاء حساب متسابق جديد</h1>
                    <div class="auth-subtitle">New Racer Registration • Back to School Challenge</div>
                </div>

                <!-- Alert Message -->
                <div id="auth-alert" class="auth-alert-box"></div>

                <form id="form-register" class="auth-form" novalidate>
                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="reg-name">
                            <span>الاسم الكامل</span>
                            <span class="form-label-sub">Full Name</span>
                        </label>
                        <div class="input-with-icon">
                            <span class="input-icon">👤</span>
                            <input type="text" id="reg-name" name="name" class="auth-input" placeholder="مثال: أحمد محمد | Ahmed Mohamed" required autocomplete="name">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="reg-email">
                            <span>البريد الإلكتروني للعمل</span>
                            <span class="form-label-sub">Corporate Email</span>
                        </label>
                        <div class="input-with-icon">
                            <span class="input-icon">✉️</span>
                            <input type="email" id="reg-email" name="email" class="auth-input" placeholder="name@gb-corp.com" required autocomplete="email">
                        </div>
                    </div>

                    <!-- Department -->
                    <div class="form-group">
                        <label class="form-label" for="reg-dept">
                            <span>القسم التابع له</span>
                            <span class="form-label-sub">Department / Racing Team</span>
                        </label>
                        <div class="input-with-icon">
                            <span class="input-icon">🏢</span>
                            <select id="reg-dept" name="department_id" class="auth-select" required>
                                <option value="" disabled selected>-- اختر قسمك أو مجالك الوظيفي | Select Department --</option>
                                <optgroup label="🏢 إدارات الأعمال والوحدات الرئيسية (Business Units - BU)">
                                    <?php foreach ($buDepts as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['id']) ?>">
                                            <?= htmlspecialchars($dept['car_emoji']) ?> <?= htmlspecialchars($dept['name_ar']) ?> (<?= htmlspecialchars($dept['name_en']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="👥 العائلات والمجالات الوظيفية الكبرى (Job Families)">
                                    <?php foreach ($jfDepts as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['id']) ?>">
                                            <?= htmlspecialchars($dept['car_emoji']) ?> <?= htmlspecialchars($dept['name_ar']) ?> (<?= htmlspecialchars($dept['name_en']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        <!-- Live Dedicated Car Preview Card -->
                        <div id="reg-car-preview" style="display:none; margin-top:0.75rem; background:rgba(7, 21, 38, 0.85); border:1px solid rgba(56, 189, 248, 0.25); border-radius:10px; padding:0.75rem 1rem; align-items:center; gap:0.9rem; box-shadow:0 4px 15px rgba(0,0,0,0.5);">
                            <div id="reg-car-svg-box" style="flex-shrink:0;"></div>
                            <div style="flex:1; min-width:0;">
                                <div id="reg-car-name" style="font-weight:800; font-size:0.95rem; color:#ffffff;"></div>
                                <div id="reg-car-specs" style="font-size:0.75rem; color:var(--baby-blue-soft);"></div>
                            </div>
                            <div id="reg-car-num-tag" style="background:rgba(4, 158, 218, 0.2); border:1px solid #049eda; color:#ffffff; font-weight:900; font-size:0.8rem; padding:3px 10px; border-radius:999px;"></div>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label" for="reg-password">
                            <span>كلمة المرور</span>
                            <span class="form-label-sub">Password (min 4 chars)</span>
                        </label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="reg-password" name="password" class="auth-input" placeholder="••••••••" required autocomplete="new-password">
                            <button type="button" class="btn-toggle-pass" onclick="togglePass('reg-password', this)">👁️</button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label" for="reg-password-confirm">
                            <span>تأكيد كلمة المرور</span>
                            <span class="form-label-sub">Confirm Password</span>
                        </label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="reg-password-confirm" class="auth-input" placeholder="••••••••" required autocomplete="new-password">
                            <button type="button" class="btn-toggle-pass" onclick="togglePass('reg-password-confirm', this)">👁️</button>
                        </div>
                    </div>

                    <!-- Admin Verification Box -->
                    <div class="admin-verify-container">
                        <label class="admin-checkbox-label" for="check-is-admin">
                            <input type="checkbox" id="check-is-admin" name="is_admin">
                            <span>⚙️ تسجيل كمسؤول / مشرف (Admin Registration)</span>
                        </label>

                        <div id="admin-verify-group" class="admin-verify-input-group">
                            <label class="form-label" for="reg-admin-key" style="color:#fed7aa;">
                                <span>رمز التحقق الإداري المعتمد</span>
                                <span class="form-label-sub">Admin Verification Key</span>
                            </label>
                            <div class="input-with-icon">
                                <span class="input-icon">🔑</span>
                                <input type="password" id="reg-admin-key" name="admin_verify_password" class="auth-input" style="border-color:rgba(249, 115, 22, 0.5);" placeholder="أدخل كلمة سر التحقق للإدارة">
                                <button type="button" class="btn-toggle-pass" onclick="togglePass('reg-admin-key', this)">👁️</button>
                            </div>
                            <small style="font-size:0.78rem; color:#fed7aa; opacity:0.85;">مخصص لمشرفي النظام فقط للتحقق من صلاحية الإدارة</small>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="btn-submit-register" class="btn-auth-submit">
                        <span>إنشاء الحساب والانطلاق 🚀</span>
                        <span style="font-size:0.85em; opacity:0.85;">| Register</span>
                    </button>
                </form>

                <div class="auth-footer-links">
                    <a href="login.php" class="auth-link">لديك حساب بالفعل؟ تسجيل الدخول 🔑</a>
                    <a href="index.php" class="auth-link">الرئيسية 🏠</a>
                </div>
            </div>
        </main>

        <!-- POST-REGISTRATION QUIZ ANNOUNCEMENT MODAL -->
        <div id="post-reg-modal" class="modal-backdrop" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); display:none; align-items:center; justify-content:center;">
            <div class="quiz-announce-card">
                <div class="announce-bell-hero">🔔</div>
                <div class="announce-tagline-badge">تحدي الكويز الأسبوعي جاهز! • QUIZ READY</div>
                
                <div style="display:flex; flex-direction:column; gap:0.4rem;">
                    <h2 class="announce-title-main" id="announce-player-greeting">استعد للتحدي! أنت على وشك دخول الكويز</h2>
                    <div class="announce-title-sub">The School Bell is Ringing! You are entering the Quiz!</div>
                </div>

                <div class="announce-info-box">
                    <span>⚡ أجب على أسئلة الكويز السريعة بدقة لجمع النقاط ودفع سيارة إداراتك نحو خط النهاية!</span>
                    <br>
                    <small style="color:var(--baby-blue-soft);">Answer quickly and correctly to push your department car across the finish line!</small>
                </div>

                <a href="game.php?start_quiz=1" id="btn-modal-lets-start" class="btn-lets-start">
                    <span>🚀 يلا نبدأ | Let's Start!</span>
                </a>
            </div>
        </div>
    </div>

    <script src="js/cars.js?v=1"></script>
    <script>
        function togglePass(inputId, btn) {
            const el = document.getElementById(inputId);
            if (el.type === 'password') {
                el.type = 'text';
                btn.textContent = '🙈';
            } else {
                el.type = 'password';
                btn.textContent = '👁️';
            }
        }

        const checkIsAdmin = document.getElementById('check-is-admin');
        const adminGroup = document.getElementById('admin-verify-group');
        const adminKeyInput = document.getElementById('reg-admin-key');

        checkIsAdmin.addEventListener('change', function () {
            if (this.checked) {
                adminGroup.classList.add('active');
                adminKeyInput.required = true;
                adminKeyInput.focus();
            } else {
                adminGroup.classList.remove('active');
                adminKeyInput.required = false;
                adminKeyInput.value = '';
            }
        });

        const form = document.getElementById('form-register');
        const alertBox = document.getElementById('auth-alert');
        const btnSubmit = document.getElementById('btn-submit-register');
        const postRegModal = document.getElementById('post-reg-modal');
        const playerGreeting = document.getElementById('announce-player-greeting');

        function showAlert(msg, isError = true) {
            alertBox.textContent = msg;
            alertBox.className = 'auth-alert-box ' + (isError ? 'error' : 'success');
            alertBox.style.display = 'block';
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            alertBox.style.display = 'none';

            const name = document.getElementById('reg-name').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const dept = document.getElementById('reg-dept').value;
            const pass = document.getElementById('reg-password').value;
            const passConfirm = document.getElementById('reg-password-confirm').value;
            const isAdmin = checkIsAdmin.checked;
            const adminKey = adminKeyInput.value.trim();

            if (!name || !email || !dept || !pass) {
                showAlert('يرجى ملء جميع الحقول المطلوبة | Please fill all required fields');
                return;
            }

            if (pass.length < 4) {
                showAlert('كلمة المرور يجب أن تتكون من 4 خانات على الأقل | Password must be at least 4 characters');
                return;
            }

            if (pass !== passConfirm) {
                showAlert('كلمتا المرور غير متطابقتين | Passwords do not match');
                return;
            }

            if (isAdmin && !adminKey) {
                showAlert('يرجى إدخال رمز التحقق الخاص بالإدارة | Please enter admin verification key');
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span>جاري إنشاء الحساب... ⏳</span>';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const res = await fetch('api/auth.php?action=register', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        name: name,
                        email: email,
                        department_id: dept,
                        password: pass,
                        is_admin: isAdmin,
                        admin_verify_password: adminKey,
                        csrf_token: csrfToken
                    })
                });

                const data = await res.json();
                if (data.success) {
                    const userName = (data.data && data.data.user && data.data.user.name) ? data.data.user.name : name;
                    if (userName) {
                        playerGreeting.textContent = `مرحباً بك يا ${userName}! أنت على وشك دخول الكويز`;
                    }

                    // Show Quiz Announcement modal exactly like Sign In
                    postRegModal.classList.remove('hidden');
                    postRegModal.style.display = 'flex';
                } else {
                    showAlert(data.message || 'حدث خطأ أثناء التسجيل | Registration error');
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<span>إنشاء الحساب والانطلاق 🚀</span> <span style="font-size:0.85em; opacity:0.85;">| Register</span>';
                }
            } catch (err) {
                showAlert('تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً | Network connection error');
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span>إنشاء الحساب والانطلاق 🚀</span> <span style="font-size:0.85em; opacity:0.85;">| Register</span>';
            }
        });

        // Dedicated Live Car Preview on Department Selection
        const deptSelect = document.getElementById('reg-dept');
        const carPreviewCard = document.getElementById('reg-car-preview');
        const carSvgBox = document.getElementById('reg-car-svg-box');
        const carNameEl = document.getElementById('reg-car-name');
        const carSpecsEl = document.getElementById('reg-car-specs');
        const carNumTag = document.getElementById('reg-car-num-tag');

        if (deptSelect && carPreviewCard) {
            deptSelect.addEventListener('change', function () {
                const deptId = this.value;
                if (!deptId || typeof window.getDepartmentCarSvg !== 'function') {
                    carPreviewCard.style.display = 'none';
                    return;
                }
                const carData = window.GBCarsEngine.resolveDepartmentData(deptId);
                if (carData) {
                    carSvgBox.innerHTML = window.getDepartmentCarSvg(deptId, { width: 90, height: 36, showGlow: true });
                    carNameEl.textContent = `${carData.name_ar} • ${carData.name_en}`;
                    carSpecsEl.textContent = `طراز السباق: ${carData.model.toUpperCase()} • كود الفريق: ${carData.code}`;
                    carNumTag.textContent = `#${carData.num}`;
                    carPreviewCard.style.display = 'flex';
                }
            });
        }
    </script>
</body>
</html>
