<?php
/**
 * GB Corp - Back to School Race Game
 * Sign In Page (Bilingual Arabic & English)
 */
require_once __DIR__ . '/db.php';

$pdo = getDBConnection();

// If already logged in, redirect to game
if (!empty($_SESSION['user_id'])) {
    header("Location: game.php?announce=quiz");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken()) ?>">
    <title>🎒 تسجيل الدخول | Sign In - GB Corp Race Game</title>
    
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
    <link rel="stylesheet" href="css/modals.css?v=30">
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
                <a href="register.php" class="btn-b2s-primary" style="padding:0.45rem 1.15rem; font-size:0.9rem;">حساب جديد | Register</a>
            </div>
        </header>

        <!-- LOGIN FORM CONTAINER -->
        <main class="auth-page-wrapper">
            <div class="auth-card" id="login-card">
                <div class="auth-header">
                    <div class="auth-icon-badge">🔑🏎️</div>
                    <h1 class="auth-title">تسجيل دخول المتسابق</h1>
                    <div class="auth-subtitle">Racer Sign In • Back to School Challenge</div>
                </div>

                <!-- Alert Message -->
                <div id="auth-alert" class="auth-alert-box"></div>

                <form id="form-login" class="auth-form" novalidate>
                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="login-email">
                            <span>البريد الإلكتروني</span>
                            <span class="form-label-sub">Work Email</span>
                        </label>
                        <div class="input-with-icon">
                            <span class="input-icon">✉️</span>
                            <input type="email" id="login-email" name="email" class="auth-input" placeholder="name@gb-corp.com" required autocomplete="email">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label" for="login-password">
                            <span>كلمة المرور</span>
                            <span class="form-label-sub">Password</span>
                        </label>
                        <div class="input-with-icon">
                            <span class="input-icon">🔒</span>
                            <input type="password" id="login-password" name="password" class="auth-input" placeholder="••••••••" required autocomplete="current-password">
                            <button type="button" class="btn-toggle-pass" onclick="togglePass('login-password', this)">👁️</button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="btn-submit-login" class="btn-auth-submit">
                        <span>تسجيل الدخول والانطلاق 🚀</span>
                        <span style="font-size:0.85em; opacity:0.85;">| Sign In</span>
                    </button>
                </form>

                <div class="auth-footer-links">
                    <a href="register.php" class="auth-link">ليس لديك حساب؟ سجل الآن ✨</a>
                    <a href="index.php" class="auth-link">الرئيسية 🏠</a>
                </div>
            </div>
        </main>

        <!-- POST-LOGIN QUIZ ANNOUNCEMENT MODAL -->
        <div id="post-login-modal" class="modal-backdrop" style="position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); display:none; align-items:center; justify-content:center;">
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

        const form = document.getElementById('form-login');
        const alertBox = document.getElementById('auth-alert');
        const btnSubmit = document.getElementById('btn-submit-login');
        const postLoginModal = document.getElementById('post-login-modal');
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

            const email = document.getElementById('login-email').value.trim();
            const pass = document.getElementById('login-password').value;

            if (!email || !pass) {
                showAlert('يرجى إدخال البريد الإلكتروني وكلمة المرور | Please fill email & password');
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span>جاري التحقق... ⏳</span>';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        email: email,
                        password: pass,
                        csrf_token: csrfToken
                    })
                });

                const data = await res.json();
                if (data.success) {
                    const userName = (data.data && data.data.user && data.data.user.name) ? data.data.user.name : '';
                    if (userName) {
                        playerGreeting.textContent = `مرحباً بك يا ${userName}! أنت على وشك دخول الكويز`;
                    }
                    
                    // Display the post-login announcement modal directly
                    postLoginModal.classList.remove('hidden');
                    postLoginModal.style.display = 'flex';
                } else {
                    showAlert(data.message || 'بيانات الدخول غير صحيحة | Invalid login credentials');
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<span>تسجيل الدخول والانطلاق 🚀</span> <span style="font-size:0.85em; opacity:0.85;">| Sign In</span>';
                }
            } catch (err) {
                showAlert('تعذر الاتصال بالخادم. يرجى المحاولة لاحقاً | Network connection error');
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span>تسجيل الدخول والانطلاق 🚀</span> <span style="font-size:0.85em; opacity:0.85;">| Sign In</span>';
            }
        });
    </script>
</body>
</html>
