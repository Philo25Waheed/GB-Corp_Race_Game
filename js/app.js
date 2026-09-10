/**
 * Main Application Controller
 * Summer Road Trip - Corporate Team Race
 * GB Corp Interactive Weekly Challenges Engine
 */

document.addEventListener('DOMContentLoaded', async function () {
    // 1. Initialize Canvas Confetti & Track Renderer
    const confettiCanvas = document.getElementById('global-confetti-canvas') || document.getElementById('confetti-canvas');
    if (typeof ConfettiEngine !== 'undefined') {
        ConfettiEngine.init(confettiCanvas);
    }
    TrackRenderer.init();

    // Security: Retrieve CSRF Token from meta tag
    function getMetaCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // 2. DOM Elements
    const elements = {
        // Header
        headerWeekPill: document.getElementById('header-week-pill'),
        headerWeekText: document.getElementById('header-week-text'),
        btnUserProfile: document.getElementById('btn-user-profile'),
        labelUserStatus: document.getElementById('label-user-status'),
        userAvatarIcon: document.getElementById('user-avatar-icon'),
        btnSoundToggle: document.getElementById('btn-sound-toggle'),

        // Finale Announcement Banner
        finaleBanner: document.getElementById('finale-announcement-banner'),
        bannerFinaleTitle: document.getElementById('banner-finale-title'),
        btnOpenFinale: document.getElementById('btn-open-finale'),

        // Journey Timeline
        journeyTimeline: document.getElementById('journey-timeline'),

        // Department Player Hero Card
        deptPlayerCard: document.getElementById('dept-player-card'),
        deptBanner: document.getElementById('dept-banner'),
        deptCarIcon: document.getElementById('dept-car-icon'),
        deptTitleName: document.getElementById('dept-title-name'),
        playerUserName: document.getElementById('player-user-name'),
        deptPosVal: document.getElementById('dept-pos-val'),
        deptPointsVal: document.getElementById('dept-points-val'),
        btnSwitchUser: document.getElementById('btn-switch-user'),

        // Challenge Sections (Containers)
        quizSection: document.getElementById('quiz-challenge-section'),
        photoSection: document.getElementById('photo-challenge-section'),
        teamSection: document.getElementById('team-activity-section'),

        // Weekly Multi-Challenge Hub Nav & Badges
        hubChallengeNav: document.getElementById('hub-challenge-nav'),
        tabBtnQuiz: document.getElementById('tab-btn-quiz'),
        tabBtnPhoto: document.getElementById('tab-btn-photo'),
        tabBtnTeam: document.getElementById('tab-btn-team'),
        hubQuizBadge: document.getElementById('hub-quiz-badge'),
        hubPhotoBadge: document.getElementById('hub-photo-badge'),
        hubTeamBadge: document.getElementById('hub-team-badge'),
        hubQuizCheck: document.getElementById('hub-quiz-check'),
        hubPhotoCheck: document.getElementById('hub-photo-check'),
        hubTeamCheck: document.getElementById('hub-team-check'),
        quizProgressFill: document.getElementById('quiz-progress-fill'),

        // Quiz Mode Elements
        challengeBadge: document.getElementById('challenge-badge'),
        categoryBadge: document.getElementById('category-badge'),
        challengePointsBadge: document.getElementById('challenge-points-badge'),
        timerBox: document.getElementById('timer-box'),
        timerValue: document.getElementById('timer-value'),
        questionTextAr: document.getElementById('question-text-ar'),
        questionTextEn: document.getElementById('question-text-en'),
        optionsGrid: document.getElementById('options-grid'),
        optionItems: document.querySelectorAll('.option-item'),
        optAAr: document.getElementById('opt-a-ar'),
        optAEn: document.getElementById('opt-a-en'),
        optBAr: document.getElementById('opt-b-ar'),
        optBEn: document.getElementById('opt-b-en'),
        optCAr: document.getElementById('opt-c-ar'),
        optCEn: document.getElementById('opt-c-en'),
        optDAr: document.getElementById('opt-d-ar'),
        optDEn: document.getElementById('opt-d-en'),
        btnPrevQuestion: document.getElementById('btn-prev-question'),
        btnNextQuestion: document.getElementById('btn-next-question'),
        quizIndicator: document.getElementById('quiz-question-indicator'),

        // Photo Challenge Elements
        formPhotoUpload: document.getElementById('form-photo-upload'),
        uploadDropzone: document.getElementById('upload-dropzone'),
        inputPhotoFile: document.getElementById('input-photo-file'),
        dropzonePrompt: document.getElementById('dropzone-prompt'),
        btnBrowseFile: document.getElementById('btn-browse-file'),
        previewArea: document.getElementById('preview-area'),
        imagePreviewElement: document.getElementById('image-preview-element'),
        btnRemovePreview: document.getElementById('btn-remove-preview'),
        inputPhotoCaption: document.getElementById('input-photo-caption'),
        btnSubmitPhoto: document.getElementById('btn-submit-photo'),
        miniPhotosGrid: document.getElementById('mini-photos-grid'),

        // Team Activity Elements
        teamMissionTitle: document.getElementById('team-mission-title'),
        teamMissionDesc: document.getElementById('team-mission-desc'),

        // Weekly Spotlight Grid
        weeklyChampionsGrid: document.getElementById('weekly-champions-grid'),

        // Onboarding Modal
        onboardingModal: document.getElementById('onboarding-modal'),
        formOnboarding: document.getElementById('form-onboarding'),
        inputUserName: document.getElementById('input-user-name'),
        inputUserEmail: document.getElementById('input-user-email'),
        selectUserDepartment: document.getElementById('select-user-department'),
        onboardingError: document.getElementById('onboarding-error'),

        // Grand Finale Modal
        finaleModal: document.getElementById('finale-modal'),
        finaleWinnerTitle: document.getElementById('finale-winner-title'),
        finaleChampName: document.getElementById('finale-champ-name'),
        finaleModalMsgAr: document.getElementById('finale-modal-msg-ar'),
        finaleModalMsgEn: document.getElementById('finale-modal-msg-en'),
        finaleCarDisplay: document.getElementById('finale-car-display'),
        finalePodium: document.getElementById('finale-podium'),
        btnFinaleClose: document.getElementById('btn-finale-close'),

        // Weekly Winner Celebration Modal
        weeklyWinnerModal: document.getElementById('weekly-winner-modal'),
        weeklyWinnerWeekTitle: document.getElementById('weekly-winner-week-title'),
        weeklyChampCar: document.getElementById('weekly-champ-car'),
        weeklyChampName: document.getElementById('weekly-champ-name'),
        weeklyChampDeptEn: document.getElementById('weekly-champ-dept-en'),
        weeklyWinnerScoreVal: document.getElementById('weekly-winner-score-val'),
        weeklyWinnerMsgAr: document.getElementById('weekly-winner-msg-ar'),
        weeklyWinnerMsgEn: document.getElementById('weekly-winner-msg-en'),
        btnRePopCelebration: document.getElementById('btn-re-pop-celebration'),
        btnWeeklyWinnerClose: document.getElementById('btn-weekly-winner-close'),
        btnCloseWeeklyWinner: document.getElementById('btn-close-weekly-winner'),

        // Toast Container
        toastContainer: document.getElementById('toast-container')
    };

    let timerInterval = null;
    let timerSecondsLeft = 15;
    let isQuestionAnswered = false;
    let currentActiveSubTab = 'quiz';

    // Toast Notification Function
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast-item';
        toast.textContent = message;
        elements.toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // 3. Check Session / User Onboarding
    async function initUserSession() {
        // A. Instant Bootstrap from Server-Injected User (immune to AJAX/CORS latency & host firewalls)
        if (window.__CURRENT_USER__ && window.__CURRENT_USER__.id) {
            const user = window.__CURRENT_USER__;
            GameState.setUser(user);
            updateUserProfileUI(user);
            if (elements.onboardingModal) {
                elements.onboardingModal.classList.add('hidden');
            }
            checkQuizAnnouncementPrompt(user);

            // Show Admin Panel button ONLY if user is a verified admin
            const btnAdminPanel = document.getElementById('btn-admin-panel');
            if (btnAdminPanel) {
                if (window.__IS_ADMIN__ || user.is_admin || user.role === 'admin') {
                    btnAdminPanel.style.display = 'inline-flex';
                    btnAdminPanel.classList.remove('hidden');
                } else {
                    btnAdminPanel.style.display = 'none';
                    btnAdminPanel.classList.add('hidden');
                }
            }

            // Start timer if quiz challenge is currently active
            if ((currentLoadedType === 'quiz' || currentActiveSubTab === 'quiz') && !isQuestionAnswered) {
                startQuestionTimer();
            }
            return;
        }

        // B. Dynamic Session Fallback
        try {
            const res = await fetch('api/auth.php?action=check_session', { credentials: 'same-origin' });
            const data = await res.json();
            if (data && data.success && data.data && data.data.logged_in) {
                GameState.setUser(data.data.user);
                updateUserProfileUI(data.data.user);
                if (elements.onboardingModal) {
                    elements.onboardingModal.classList.add('hidden');
                }
                checkQuizAnnouncementPrompt(data.data.user);

                const btnAdminPanel = document.getElementById('btn-admin-panel');
                if (btnAdminPanel) {
                    if (data.data.is_admin || (data.data.user && data.data.user.role === 'admin')) {
                        btnAdminPanel.style.display = 'inline-flex';
                        btnAdminPanel.classList.remove('hidden');
                    } else {
                        btnAdminPanel.style.display = 'none';
                        btnAdminPanel.classList.add('hidden');
                    }
                }

                if ((currentLoadedType === 'quiz' || currentActiveSubTab === 'quiz') && !isQuestionAnswered) {
                    startQuestionTimer();
                }
            } else {
                // Only redirect if absolutely unauthenticated and no user object in memory
                if (!GameState.getUser() && !window.__CURRENT_USER__) {
                    clearInterval(timerInterval);
                    window.location.href = 'index.php';
                    return;
                }
            }
        } catch (e) {
            console.warn('Session verification fallback note:', e);
            if (!GameState.getUser() && !window.__CURRENT_USER__) {
                clearInterval(timerInterval);
                window.location.href = 'index.php';
                return;
            }
        }
    }

    function updateUserProfileUI(user) {
        if (!user) return;
        elements.playerUserName.textContent = user.name;
        elements.deptTitleName.textContent = `${user.dept_name_en || user.dept_name_ar} | ${user.dept_code}`;

        if (typeof window.getDepartmentCarSvg === 'function') {
            elements.deptCarIcon.innerHTML = window.getDepartmentCarSvg(user.department_id || user.dept_code, { width: 48, height: 20, showGlow: true });
            elements.userAvatarIcon.innerHTML = window.getDepartmentCarSvg(user.department_id || user.dept_code, { width: 32, height: 14, showGlow: false });
        } else {
            elements.deptCarIcon.textContent = user.car_emoji || '🏎️';
            elements.userAvatarIcon.textContent = user.car_emoji || '👤';
        }

        elements.deptPosVal.textContent = (user.position || 0) * 10;
        elements.deptPointsVal.textContent = user.total_points || 0;
        elements.labelUserStatus.textContent = `${user.name.split(' ')[0]} (${user.dept_code})`;
        elements.deptPlayerCard.classList.remove('hidden');
    }

    // Handle Onboarding Form Submit (if present in DOM)
    if (elements.formOnboarding) {
        elements.formOnboarding.addEventListener('submit', async function (e) {
            e.preventDefault();
            const csrfToken = getMetaCsrfToken();
            const payload = {
                name: elements.inputUserName.value.trim(),
                email: elements.inputUserEmail.value.trim(),
                department_id: elements.selectUserDepartment.value,
                csrf_token: csrfToken
            };

            if (elements.onboardingError) elements.onboardingError.classList.add('hidden');

            try {
                const formBody = new URLSearchParams();
                Object.keys(payload).forEach(k => formBody.append(k, payload[k]));
                const res = await fetch('api/auth.php?action=register_login', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': csrfToken
                    },
                    body: formBody.toString()
                });
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (jsonErr) {
                    if (elements.onboardingError) {
                        elements.onboardingError.textContent = 'خطأ من الخادم: ' + (text.replace(/<[^>]*>?/gm, '').trim().substring(0, 150) || 'Response non-JSON');
                        elements.onboardingError.classList.remove('hidden');
                    }
                    return;
                }
                if (data.success && data.data) {
                    GameState.setUser(data.data.user);
                    updateUserProfileUI(data.data.user);
                    if (elements.onboardingModal) elements.onboardingModal.classList.add('hidden');
                    checkQuizAnnouncementPrompt(data.data.user);
                    showToast(`مرحباً بك يا ${data.data.user.name.split(' ')[0]} في فريق ${data.data.user.dept_name_ar}! 🎉`);
                    if (typeof AudioEngine !== 'undefined') AudioEngine.play('join');
                    await refreshFullGameState();
                    if ((currentLoadedType === 'quiz' || currentActiveSubTab === 'quiz') && !isQuestionAnswered) {
                        startQuestionTimer();
                    }
                } else {
                    if (elements.onboardingError) {
                        elements.onboardingError.textContent = data.message || 'حدث خطأ في معالجة الطلب';
                        elements.onboardingError.classList.remove('hidden');
                    }
                }
            } catch (err) {
                if (elements.onboardingError) {
                    elements.onboardingError.textContent = 'خطأ في الاتصال: ' + (err.message || 'Connection error');
                    elements.onboardingError.classList.remove('hidden');
                }
            }
        });
    }

    // Direct, Immediate Logout Handlers
    if (elements.btnSwitchUser) {
        elements.btnSwitchUser.addEventListener('click', (e) => {
            e.preventDefault();
            clearInterval(timerInterval);
            window.location.href = 'logout.php';
        });
    }

    const btnHeaderLogout = document.getElementById('btn-header-logout');
    if (btnHeaderLogout) {
        btnHeaderLogout.addEventListener('click', (e) => {
            e.preventDefault();
            clearInterval(timerInterval);
            window.location.href = 'logout.php';
        });
    }

    const btnCloseOnboarding = document.getElementById('btn-close-onboarding');
    if (btnCloseOnboarding) {
        btnCloseOnboarding.addEventListener('click', () => {
            if (elements.onboardingModal) {
                elements.onboardingModal.classList.add('hidden');
            }
        });
    }

    // Quiz Announcement Modal Handler (Back to School Flow)
    const quizAnnounceModal = document.getElementById('quiz-announcement-modal');
    const btnStartQuizNow = document.getElementById('btn-start-quiz-now');
    const btnCloseQuizAnnounce = document.getElementById('btn-close-quiz-announce');
    const quizAnnounceTitle = document.getElementById('quiz-announce-title');

    function checkQuizAnnouncementPrompt(user) {
        const urlParams = new URLSearchParams(window.location.search);

        // 1. Direct Start Quiz (e.g. from login page "Let's Start" click)
        if (urlParams.get('start_quiz') === '1') {
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
            setTimeout(() => {
                focusAndStartQuiz();
            }, 300);
            return;
        }

        // 2. Announce Modal (e.g. from Home page or direct link)
        if (urlParams.get('announce') === 'quiz') {
            if (user && quizAnnounceTitle) {
                quizAnnounceTitle.textContent = `مرحباً بك يا ${user.name}! أنت على وشك دخول الكويز`;
            }
            if (quizAnnounceModal) {
                quizAnnounceModal.classList.remove('hidden');
                quizAnnounceModal.style.display = 'flex';
                if (typeof AudioEngine !== 'undefined') AudioEngine.play('join');
            }
        }
    }

    function focusAndStartQuiz() {
        if (quizAnnounceModal) {
            quizAnnounceModal.classList.add('hidden');
            quizAnnounceModal.style.display = 'none';
        }

        switchWeeklySubChallenge('quiz');

        // Ensure quiz section is visible even if previously hidden
        if (elements.quizSection) {
            elements.quizSection.classList.remove('hidden');
            elements.quizSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            elements.quizSection.style.transition = 'box-shadow 0.6s ease, border-color 0.6s ease';
            elements.quizSection.style.boxShadow = '0 0 45px rgba(249, 115, 22, 0.9)';
            elements.quizSection.style.borderColor = '#f97316';
            setTimeout(() => {
                if (elements.quizSection) {
                    elements.quizSection.style.boxShadow = '';
                    elements.quizSection.style.borderColor = '';
                }
            }, 3000);
        }

        if (typeof AudioEngine !== 'undefined') AudioEngine.play('countdown');

        // Start question timer for active question
        isQuestionAnswered = false;
        startQuestionTimer();
    }

    if (btnStartQuizNow) {
        btnStartQuizNow.addEventListener('click', () => {
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
            focusAndStartQuiz();
        });
    }

    if (btnCloseQuizAnnounce) {
        btnCloseQuizAnnounce.addEventListener('click', () => {
            if (quizAnnounceModal) {
                quizAnnounceModal.classList.add('hidden');
                quizAnnounceModal.style.display = 'none';
            }
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        });
    }

    // 4. Load & Refresh Game State from Backend
    async function refreshFullGameState() {
        const state = await GameState.fetchFreshState();
        if (!state) return;

        // Render Track & Highway
        TrackRenderer.renderAll(state.departments, state.raceLength, GameState.getRankings());

        // Update User Hero Card if user active
        if (state.user) {
            const freshDept = state.departments.find(d => d.id === state.user.department_id);
            if (freshDept) {
                elements.deptPosVal.textContent = freshDept.position * 10;
                elements.deptPointsVal.textContent = freshDept.total_points;
            }
        }

        // Render 3-Week Journey Timeline
        renderJourneyTimeline(state.allWeeks, state.activeWeek);

        // Update Active Week Pill in Header
        if (state.activeWeek) {
            elements.headerWeekText.textContent = `الأسبوع ${state.activeWeek.week_number}: ${state.activeWeek.title_ar}`;
        }

        // Render Active Challenge Mode
        renderActiveChallenge(state.activeWeek);

        // Update Weekly Multi-Challenge Hub Badges & Checkmarks
        if (state.active_week_progress) {
            updateWeeklyProgressHubUI(state.active_week_progress);
        }

        // Render Weekly Champions Spotlight
        renderWeeklyChampions(state.weeklyWinners, state.allWeeks);

        // Check Finale Surprise Reveal
        checkFinaleSurprise(state);

        // Check & Auto-Trigger Weekly Winner Celebration for Completed Weeks
        checkAutoWeeklyCelebration(state.allWeeks, state.weeklyWinners);
    }

    // 5. Render 3-Week Journey Timeline
    function renderJourneyTimeline(allWeeks, activeWeek) {
        if (!elements.journeyTimeline) return;
        elements.journeyTimeline.innerHTML = '';

        const typeIcons = {
            'quiz': '❓',
            'photo_challenge': '📷',
            'team_activity': '👥',
            'multi': '⚡'
        };

        const typeTitles = {
            'quiz': 'كويز المعرفة | Quiz',
            'photo_challenge': 'تحدي التصوير | Photo',
            'team_activity': 'نشاط جماعي | Activity',
            'multi': '📝 كويز • 📸 صور • 👥 نشاط'
        };

        allWeeks.forEach(w => {
            const isActive = activeWeek && activeWeek.id == w.id;
            const node = document.createElement('div');
            node.className = `journey-week-node ${isActive ? 'active-week' : ''}`;
            const icon = (w.challenge_type === 'multi') ? '⚡' : (typeIcons[w.challenge_type] || '🏁');
            const subTitle = (w.challenge_type === 'multi') ? '📝 كويز • 📸 صور • 👥 نشاط' : (typeTitles[w.challenge_type] || w.challenge_type);
            node.innerHTML = `
                <div class="week-icon-circle">${icon}</div>
                <div class="week-node-texts">
                    <span class="week-node-title">الأسبوع ${w.week_number} | W${w.week_number} ${isActive ? '⚡' : ''}</span>
                    <span class="week-node-type">${subTitle}</span>
                </div>
            `;
            elements.journeyTimeline.appendChild(node);
        });
    }

    // Weekly Sub-Challenge Navigation Hub Handler (Quiz / Photo / Team)
    function switchWeeklySubChallenge(tabName) {
        currentActiveSubTab = tabName || 'quiz';

        // Update active buttons
        if (elements.tabBtnQuiz) elements.tabBtnQuiz.classList.toggle('active', currentActiveSubTab === 'quiz');
        if (elements.tabBtnPhoto) elements.tabBtnPhoto.classList.toggle('active', currentActiveSubTab === 'photo');
        if (elements.tabBtnTeam) elements.tabBtnTeam.classList.toggle('active', currentActiveSubTab === 'team');

        // Toggle challenge sections visibility
        if (elements.quizSection) elements.quizSection.classList.toggle('hidden', currentActiveSubTab !== 'quiz');
        if (elements.photoSection) elements.photoSection.classList.toggle('hidden', currentActiveSubTab !== 'photo');
        if (elements.teamSection) elements.teamSection.classList.toggle('hidden', currentActiveSubTab !== 'team');

        const activeWeek = GameState.getActiveWeek();

        if (currentActiveSubTab === 'quiz') {
            // If quiz is shown and question isn't answered yet, resume/start timer
            if (!isQuestionAnswered && GameState.getUser()) {
                startQuestionTimer();
            }
        } else {
            // When user switches in-page to Photo or Team, pause countdown timer
            clearInterval(timerInterval);
        }

        if (currentActiveSubTab === 'photo' && activeWeek) {
            loadPhotoGallery(activeWeek.id);
        }

        if (currentActiveSubTab === 'team' && activeWeek) {
            if (elements.teamMissionTitle) elements.teamMissionTitle.textContent = activeWeek.title_ar;
            if (elements.teamMissionDesc) elements.teamMissionDesc.textContent = activeWeek.description_ar;
        }
    }

    // Update Badges & Checkmarks on the Multi-Challenge Navigation Hub
    function updateWeeklyProgressHubUI(progress) {
        if (!progress) return;

        // Quiz Hub Badge & Checkmark
        if (elements.hubQuizCheck) {
            elements.hubQuizCheck.classList.toggle('hidden', !progress.quiz_completed);
        }
        if (elements.hubQuizBadge) {
            if (progress.quiz_completed) {
                elements.hubQuizBadge.textContent = '✓ مكتمل (+50) | Completed';
                elements.hubQuizBadge.style.color = '#34d399';
            } else {
                const ans = progress.quiz_answered_count || 0;
                const tot = progress.quiz_total_count || 5;
                elements.hubQuizBadge.textContent = `${ans}/${tot} أسئلة • +50 نقطة`;
                elements.hubQuizBadge.style.color = 'var(--baby-blue)';
            }
        }

        // Photo Hub Badge & Checkmark
        if (elements.hubPhotoCheck) {
            elements.hubPhotoCheck.classList.toggle('hidden', !progress.photo_submitted);
        }
        if (elements.hubPhotoBadge) {
            if (progress.photo_submitted) {
                elements.hubPhotoBadge.textContent = '✓ تم الرفع (+15 نقطة)';
                elements.hubPhotoBadge.style.color = '#34d399';
            } else {
                elements.hubPhotoBadge.textContent = '+15 نقطة وميل | +15 PTS';
                elements.hubPhotoBadge.style.color = 'var(--baby-blue)';
            }
        }

        // Team Activity Hub Badge & Checkmark
        if (elements.hubTeamCheck) {
            elements.hubTeamCheck.classList.toggle('hidden', !progress.team_activity_done);
        }
        if (elements.hubTeamBadge) {
            if (progress.team_activity_done) {
                elements.hubTeamBadge.textContent = '✓ تم الإنجاز (+30 نقطة)';
                elements.hubTeamBadge.style.color = '#34d399';
            } else {
                elements.hubTeamBadge.textContent = '+30 نقطة وميل | +30 PTS';
                elements.hubTeamBadge.style.color = 'var(--baby-blue)';
            }
        }
    }

    let currentLoadedWeekId = null;
    let currentLoadedType = null;

    // 6. Render Active Challenge (Multi-Challenge Hub or Single Mode)
    async function renderActiveChallenge(activeWeek, forceReload = false) {
        if (!activeWeek) return;

        const type = activeWeek.challenge_type;
        const isMulti = (type === 'multi');

        // Always show the hub tabs if week is multi-challenge
        if (elements.hubChallengeNav) {
            elements.hubChallengeNav.classList.toggle('hidden', !isMulti);
        }

        // Only reload content if week or challenge type actually changed, or if forceReload is requested
        const shouldReload = forceReload || (currentLoadedWeekId !== activeWeek.id) || (currentLoadedType !== type);
        if (!shouldReload) return;

        currentLoadedWeekId = activeWeek.id;
        currentLoadedType = type;

        if (isMulti) {
            // Multi-Challenge Week: Preload Quiz, Photo gallery, and Team Mission
            await loadQuizQuestions(activeWeek.id);
            await loadPhotoGallery(activeWeek.id);
            if (elements.teamMissionTitle) elements.teamMissionTitle.textContent = activeWeek.title_ar;
            if (elements.teamMissionDesc) elements.teamMissionDesc.textContent = activeWeek.description_ar;
            switchWeeklySubChallenge(currentActiveSubTab || 'quiz');
        } else if (type === 'quiz') {
            elements.quizSection.classList.remove('hidden');
            elements.photoSection.classList.add('hidden');
            elements.teamSection.classList.add('hidden');
            await loadQuizQuestions(activeWeek.id);
        } else if (type === 'photo_challenge') {
            elements.quizSection.classList.add('hidden');
            elements.photoSection.classList.remove('hidden');
            elements.teamSection.classList.add('hidden');
            await loadPhotoGallery(activeWeek.id);
        } else if (type === 'team_activity') {
            elements.quizSection.classList.add('hidden');
            elements.photoSection.classList.add('hidden');
            elements.teamSection.classList.remove('hidden');
            if (elements.teamMissionTitle) elements.teamMissionTitle.textContent = activeWeek.title_ar;
            if (elements.teamMissionDesc) elements.teamMissionDesc.textContent = activeWeek.description_ar;
        }
    }

    // ================= QUIZ ENGINE (ANTI-CHEAT HARDENED) =================
    let isForfeiting = false;

    async function loadQuizQuestions(weekId) {
        try {
            const user = GameState.getUser();
            const userParam = user ? `&user_id=${user.id}` : '';
            const res = await fetch(`api/quiz.php?action=get_questions&week_id=${weekId}${userParam}`);
            const data = await res.json();
            if (data.success && data.data && data.data.questions) {
                const questions = data.data.questions;
                const userAttempts = data.data.user_attempts || {};
                GameState.setQuestions(questions);

                // Anti-Cheat: Automatically locate the first unanswered/unforfeited question
                let targetIdx = 0;
                let allDone = true;
                for (let i = 0; i < questions.length; i++) {
                    const qId = questions[i].id;
                    const att = userAttempts[qId];
                    if (!att) {
                        targetIdx = i;
                        allDone = false;
                        break;
                    }
                    if (att.selected_option === 'PENDING') {
                        targetIdx = i;
                        allDone = false;
                        break;
                    }
                }

                if (allDone && questions.length > 0) {
                    GameState.setCurrentQuestionIndex(questions.length - 1);
                    displayCurrentQuestion(true); // Completed state
                } else {
                    GameState.setCurrentQuestionIndex(targetIdx);
                    displayCurrentQuestion();
                }
            }
        } catch (e) {
            console.error('Failed to load quiz questions', e);
        }
    }

    async function displayCurrentQuestion(isCompleted = false) {
        const questions = GameState.getQuestions();
        const idx = GameState.getCurrentQuestionIndex();

        if (isCompleted || !questions || questions.length === 0 || idx >= questions.length) {
            clearInterval(timerInterval);
            isQuestionAnswered = true;
            if (elements.quizProgressFill) elements.quizProgressFill.style.width = '100%';
            if (elements.hubQuizCheck) elements.hubQuizCheck.classList.remove('hidden');
            if (elements.hubQuizBadge) {
                elements.hubQuizBadge.textContent = '✓ مكتمل (+50) | Completed';
                elements.hubQuizBadge.style.color = '#34d399';
            }
            if (elements.challengeBadge) elements.challengeBadge.textContent = 'مكتمل | Completed';
            if (elements.categoryBadge) elements.categoryBadge.textContent = 'انتهى التحدي | Done';
            if (elements.challengePointsBadge) elements.challengePointsBadge.textContent = '🏆';
            if (elements.quizIndicator) elements.quizIndicator.textContent = `${questions ? questions.length : 0} / ${questions ? questions.length : 0}`;
            elements.questionTextAr.textContent = '🎉 رائع جداً! لقد أكملت جميع أسئلة هذا الأسبوع!';
            elements.questionTextEn.textContent = 'Awesome! You have completed all questions for this week!';
            if (elements.optionsGrid) elements.optionsGrid.style.display = 'none';
            if (elements.timerBox) elements.timerBox.style.display = 'none';
            if (elements.btnPrevQuestion) elements.btnPrevQuestion.style.display = 'none';
            if (elements.btnNextQuestion) elements.btnNextQuestion.style.display = 'none';
            return;
        }

        if (elements.optionsGrid) elements.optionsGrid.style.display = 'grid';
        if (elements.timerBox) elements.timerBox.style.display = 'flex';

        const q = questions[idx];
        isQuestionAnswered = false;
        isForfeiting = false;

        // Anti-Cheat: Disable and hide the Previous button so players can NEVER return to earlier questions
        if (elements.btnPrevQuestion) {
            elements.btnPrevQuestion.style.display = 'none';
            elements.btnPrevQuestion.disabled = true;
        }
        if (elements.btnNextQuestion) {
            elements.btnNextQuestion.style.display = (idx < questions.length - 1) ? 'inline-flex' : 'none';
            elements.btnNextQuestion.disabled = (idx >= questions.length - 1);
        }

        // Update Progress Bar
        if (elements.quizProgressFill && questions && questions.length > 0) {
            const pct = Math.round(((idx + 1) / questions.length) * 100);
            elements.quizProgressFill.style.width = `${pct}%`;
        }

        // Update Badges & Texts
        elements.challengeBadge.textContent = `سؤال ${idx + 1} / ${questions.length} | Question ${idx + 1} / ${questions.length}`;
        elements.categoryBadge.textContent = `${q.category_ar} | ${q.category_en}`;
        elements.challengePointsBadge.textContent = `+${q.points} نقاط | PTS`;
        elements.quizIndicator.textContent = `${idx + 1} / ${questions.length}`;

        elements.questionTextAr.textContent = q.question_ar;
        elements.questionTextEn.textContent = q.question_en;

        elements.optAAr.textContent = q.option_a_ar;
        elements.optAEn.textContent = q.option_a_en;
        elements.optBAr.textContent = q.option_b_ar;
        elements.optBEn.textContent = q.option_b_en;
        elements.optCAr.textContent = q.option_c_ar;
        elements.optCEn.textContent = q.option_c_en;
        elements.optDAr.textContent = q.option_d_ar;
        elements.optDEn.textContent = q.option_d_en;

        // Reset option styles
        elements.optionItems.forEach(item => {
            item.classList.remove('correct', 'incorrect', 'timeout-disabled');
        });

        // Reset timer box warning animation
        if (elements.timerBox) {
            elements.timerBox.classList.remove('timer-warning');
        }

        // Anti-Cheat: Immediately lock the question on the backend upon presentation
        const user = GameState.getUser();
        if (user && user.id) {
            try {
                const lockBody = new URLSearchParams({
                    question_id: q.id,
                    user_id: user.id,
                    csrf_token: getMetaCsrfToken()
                });
                const resLock = await fetch('api/quiz.php?action=start_question', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': getMetaCsrfToken()
                    },
                    body: lockBody.toString()
                });
                const dataLock = await resLock.json();
                if (!dataLock.success && dataLock.data && dataLock.data.already_attempted) {
                    showToast('⚠️ هذا السؤال تم الدخول إليه مسبقاً! جاري الانتقال للسؤال التالي منعاً للغش...');
                    advanceToNextQuestion();
                    return;
                }
            } catch (err) {
                console.warn('Anti-cheat question lock error:', err);
            }
        }

        // Start Countdown Timer
        startQuestionTimer();
    }

    function advanceToNextQuestion() {
        clearInterval(timerInterval);
        isQuestionAnswered = true;
        isForfeiting = false;
        const totalQuestions = GameState.getQuestions().length;
        const currentIdx = GameState.getCurrentQuestionIndex();
        if (currentIdx < totalQuestions - 1) {
            GameState.setCurrentQuestionIndex(currentIdx + 1);
            displayCurrentQuestion();
        } else {
            displayCurrentQuestion(true);
        }
    }

    // Anti-Cheat Forfeit Trigger (Tab switch, window blur, or navigating away)
    async function triggerAntiCheatForfeit(reason = 'tab_switch') {
        if (currentLoadedType !== 'quiz' && currentActiveSubTab !== 'quiz') return;
        if (isQuestionAnswered || isForfeiting) return;

        const questions = GameState.getQuestions();
        const currentIdx = GameState.getCurrentQuestionIndex();
        if (!questions || !questions[currentIdx]) return;
        const currentQ = questions[currentIdx];
        const user = GameState.getUser();
        if (!user || !user.id) return;

        isForfeiting = true;
        isQuestionAnswered = true;
        clearInterval(timerInterval);

        if (typeof AudioEngine !== 'undefined') AudioEngine.play('wrong');

        // Visually disable options
        elements.optionItems.forEach(optEl => {
            optEl.classList.add('timeout-disabled');
        });

        // Send forfeit to backend (Beacon ensures execution even on browser close)
        const payload = JSON.stringify({
            question_id: currentQ.id,
            user_id: user.id,
            reason: reason,
            csrf_token: getMetaCsrfToken()
        });

        const forfeitParsed = JSON.parse(payload);
        const forfeitBody = new URLSearchParams(forfeitParsed).toString();
        if (navigator.sendBeacon) {
            const blob = new Blob([forfeitBody], { type: 'application/x-www-form-urlencoded' });
            navigator.sendBeacon('api/quiz.php?action=forfeit_question', blob);
        } else {
            fetch('api/quiz.php?action=forfeit_question', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': getMetaCsrfToken()
                },
                body: forfeitBody
            }).catch(() => {});
        }

        showToast('🚨 تم رصد مغادرة الصفحة / تبديل الشاشة! تم إلغاء السؤال لمنع الغش.');

        setTimeout(() => {
            advanceToNextQuestion();
        }, 1600);
    }

    function startQuestionTimer() {
        clearInterval(timerInterval);

        const user = GameState.getUser();
        const isQuizModalOpen = quizAnnounceModal && !quizAnnounceModal.classList.contains('hidden') && quizAnnounceModal.style.display === 'flex';

        timerSecondsLeft = 15;
        if (elements.timerValue) elements.timerValue.textContent = timerSecondsLeft;
        if (elements.timerBox) elements.timerBox.classList.remove('timer-warning');

        if (!user || isQuizModalOpen) {
            return;
        }

        timerInterval = setInterval(() => {
            if (isQuestionAnswered) {
                clearInterval(timerInterval);
                return;
            }
            timerSecondsLeft--;
            if (elements.timerValue) elements.timerValue.textContent = Math.max(0, timerSecondsLeft);

            // Audio & visual tick warning during last 5 seconds
            if (timerSecondsLeft <= 5 && timerSecondsLeft > 0) {
                if (typeof AudioEngine !== 'undefined') AudioEngine.play('tick');
                if (elements.timerBox) elements.timerBox.classList.add('timer-warning');
            }

            if (timerSecondsLeft <= 0) {
                clearInterval(timerInterval);
                isQuestionAnswered = true;

                if (typeof AudioEngine !== 'undefined') AudioEngine.play('timeout');
                showToast('انتهى الوقت المخصص لهذا السؤال! جاري الانتقال للسؤال التالي... ⏱️ | Time is up!');

                // Disable options visually
                elements.optionItems.forEach(optEl => {
                    optEl.classList.add('timeout-disabled');
                });

                // Inform server of timeout
                const qUser = GameState.getUser();
                const qQuestions = GameState.getQuestions();
                const qIdx = GameState.getCurrentQuestionIndex();
                if (qUser && qQuestions && qQuestions[qIdx]) {
                    const timeoutBody = new URLSearchParams({
                        question_id: qQuestions[qIdx].id,
                        user_id: qUser.id,
                        reason: 'timeout'
                    });
                    fetch('api/quiz.php?action=forfeit_question', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: timeoutBody.toString()
                    }).catch(() => {});
                }

                setTimeout(() => {
                    advanceToNextQuestion();
                }, 1200);
            }
        }, 1000);
    }

    // Option Click Handler
    elements.optionItems.forEach(item => {
        item.addEventListener('click', async function () {
            if (isQuestionAnswered || isForfeiting) return;

            const selectedOpt = this.dataset.opt;
            const questions = GameState.getQuestions();
            const currentIdx = GameState.getCurrentQuestionIndex();
            const currentQ = questions[currentIdx];
            const user = GameState.getUser();

            if (!user) {
                showToast('يرجى تسجيل الدخول أولاً! | Please sign in first!');
                window.location.href = 'index.php';
                return;
            }

            // Immediately mark as answered to prevent double submission and clear timer
            isQuestionAnswered = true;
            clearInterval(timerInterval);

            // Subtle click audio
            if (typeof AudioEngine !== 'undefined') AudioEngine.play('click');

            try {
                const answerBody = new URLSearchParams({
                    question_id: currentQ.id,
                    selected_option: selectedOpt,
                    department_id: user.department_id,
                    user_id: user.id,
                    csrf_token: getMetaCsrfToken()
                });
                const res = await fetch('api/quiz.php?action=submit_answer', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': getMetaCsrfToken()
                    },
                    body: answerBody.toString()
                });
                const data = await res.json();

                if (data.success && data.data) {
                    const isCorrect = data.data.is_correct;
                    const correctOpt = data.data.correct_option;

                    // Highlight options
                    elements.optionItems.forEach(optEl => {
                        if (optEl.dataset.opt === correctOpt) {
                            optEl.classList.add('correct');
                        } else if (optEl.dataset.opt === selectedOpt && !isCorrect) {
                            optEl.classList.add('incorrect');
                        }
                    });

                    if (isCorrect) {
                        if (typeof AudioEngine !== 'undefined') AudioEngine.play('correct');
                        if (typeof ConfettiEngine !== 'undefined') ConfettiEngine.fire(0.5, 0.4);
                        showToast(`إجابة صحيحة! أحسنت! حصد قسمك +${data.data.points_earned} نقاط | Correct! +${data.data.points_earned} PTS 🎉`);
                    } else {
                        if (typeof AudioEngine !== 'undefined') AudioEngine.play('wrong');
                        showToast('إجابة خاطئة! حظ أوفر في السؤال التالي | Wrong answer! Try next question');
                    }

                    // Refresh Game State & Highway in background WITHOUT resetting the quiz question
                    refreshFullGameState(false);

                    // Auto advance smoothly to next question after 1.8 seconds
                    setTimeout(() => {
                        advanceToNextQuestion();
                    }, 1800);
                } else {
                    showToast(data.message || 'خطأ أثناء تسجيل الإجابة | Error submitting answer');
                    setTimeout(() => {
                        advanceToNextQuestion();
                    }, 1500);
                }
            } catch (err) {
                showToast('خطأ في الاتصال بالخادم | Connection error');
                setTimeout(() => {
                    advanceToNextQuestion();
                }, 1500);
            }
        });
    });

    if (elements.btnPrevQuestion) {
        elements.btnPrevQuestion.addEventListener('click', (e) => {
            e.preventDefault();
            // Anti-Cheat: Going backward is disabled
            showToast('⚠️ لا يمكن الرجوع للأسئلة السابقة منعاً للغش! | Cannot return to previous questions');
        });
    }

    if (elements.btnNextQuestion) {
        elements.btnNextQuestion.addEventListener('click', () => {
            if (!isQuestionAnswered) {
                // Manually skipping question forfeits points
                triggerAntiCheatForfeit('user_skipped');
            } else {
                advanceToNextQuestion();
            }
        });
    }

    // Anti-Cheat Listeners: Tab Switch, Blur, Window Minimize & Unload
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            if (currentLoadedType === 'quiz' || currentActiveSubTab === 'quiz') {
                triggerAntiCheatForfeit('tab_switch');
            }
        }
    });

    window.addEventListener('blur', () => {
        if ((currentLoadedType === 'quiz' || currentActiveSubTab === 'quiz') && !isQuestionAnswered && !document.hasFocus()) {
            triggerAntiCheatForfeit('window_blur');
        }
    });

    window.addEventListener('beforeunload', () => {
        if ((currentLoadedType === 'quiz' || currentActiveSubTab === 'quiz') && !isQuestionAnswered) {
            triggerAntiCheatForfeit('page_unload');
        }
    });

    // Weekly Multi-Challenge Navigation Hub Tab Button Click Handlers
    if (elements.tabBtnQuiz) {
        elements.tabBtnQuiz.addEventListener('click', () => switchWeeklySubChallenge('quiz'));
    }
    if (elements.tabBtnPhoto) {
        elements.tabBtnPhoto.addEventListener('click', () => switchWeeklySubChallenge('photo'));
    }
    if (elements.tabBtnTeam) {
        elements.tabBtnTeam.addEventListener('click', () => switchWeeklySubChallenge('team'));
    }

    // Anti-Cheat Listeners: Block Right-Click Context Menu and Copying on Quiz Card
    const quizSection = document.getElementById('quiz-challenge-section');
    if (quizSection) {
        quizSection.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            showToast('⚠️ غير مسموح بنسخ الأسئلة أو فتح القائمة لمنع الغش! | Copying is disabled');
            return false;
        });

        quizSection.addEventListener('copy', (e) => {
            e.preventDefault();
            showToast('⚠️ نسخ السؤال محظور لمنع استخدام الذكاء الاصطناعي! | Copying is disabled');
            return false;
        });

        quizSection.addEventListener('cut', (e) => {
            e.preventDefault();
            return false;
        });
    }

    // Anti-Cheat: Intercept DevTools shortcuts, view-source, and PrintScreen
    window.addEventListener('keydown', (e) => {
        const isQuizActive = (currentLoadedType === 'quiz' || currentActiveSubTab === 'quiz') && !isQuestionAnswered;
        
        // Block F12
        if (e.key === 'F12') {
            e.preventDefault();
            if (isQuizActive) triggerAntiCheatForfeit('devtools_f12');
            showToast('⚠️ أدوات فحص المطورين محظورة أثناء الكويز! | DevTools is blocked');
            return false;
        }

        // Block Ctrl+Shift+I / Ctrl+Shift+J / Ctrl+Shift+C (DevTools)
        if (e.ctrlKey && e.shiftKey && ['I', 'i', 'J', 'j', 'C', 'c'].includes(e.key)) {
            e.preventDefault();
            if (isQuizActive) triggerAntiCheatForfeit('devtools_shortcut');
            showToast('⚠️ أدوات فحص المتصفح محظورة أثناء الكويز! | DevTools is blocked');
            return false;
        }

        // Block Ctrl+U (View Source)
        if (e.ctrlKey && ['u', 'U'].includes(e.key)) {
            e.preventDefault();
            showToast('⚠️ عرض كود الصفحة محظور | View source is blocked');
            return false;
        }

        // Block PrintScreen / Screen Capture key
        if (e.key === 'PrintScreen') {
            e.preventDefault();
            if (isQuizActive) triggerAntiCheatForfeit('screenshot_attempt');
            showToast('⚠️ تصوير الشاشة محظور لمنع استخدام أدوات الذكاء الاصطناعي!');
            return false;
        }
    }, true);

    // Anti-Cheat: DevTools Docked Window Open Detector
    let devtoolsDetected = false;
    setInterval(() => {
        const isQuizActive = (currentLoadedType === 'quiz' || currentActiveSubTab === 'quiz') && !isQuestionAnswered && !isForfeiting;
        if (isQuizActive) {
            const widthThreshold = (window.outerWidth - window.innerWidth) > 160;
            const heightThreshold = (window.outerHeight - window.innerHeight) > 160;
            if (widthThreshold || heightThreshold) {
                if (!devtoolsDetected) {
                    devtoolsDetected = true;
                    triggerAntiCheatForfeit('devtools_docked_open');
                }
            } else {
                devtoolsDetected = false;
            }
        }
    }, 700);

    // ================= PHOTO CHALLENGE ENGINE =================
    if (elements.btnBrowseFile) {
        elements.btnBrowseFile.addEventListener('click', () => elements.inputPhotoFile.click());
    }

    if (elements.uploadDropzone) {
        elements.uploadDropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            elements.uploadDropzone.classList.add('drag-hover');
        });
        elements.uploadDropzone.addEventListener('dragleave', () => elements.uploadDropzone.classList.remove('drag-hover'));
        elements.uploadDropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            elements.uploadDropzone.classList.remove('drag-hover');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                elements.inputPhotoFile.files = e.dataTransfer.files;
                handlePhotoSelection(e.dataTransfer.files[0]);
            }
        });
    }

    if (elements.inputPhotoFile) {
        elements.inputPhotoFile.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                handlePhotoSelection(this.files[0]);
            }
        });
    }

    function handlePhotoSelection(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            elements.imagePreviewElement.src = e.target.result;
            elements.dropzonePrompt.classList.add('hidden');
            elements.previewArea.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }

    if (elements.btnRemovePreview) {
        elements.btnRemovePreview.addEventListener('click', (e) => {
            e.stopPropagation();
            elements.inputPhotoFile.value = '';
            elements.imagePreviewElement.src = '';
            elements.previewArea.classList.add('hidden');
            elements.dropzonePrompt.classList.remove('hidden');
        });
    }

    // Submit Photo Upload Form
    if (elements.formPhotoUpload) {
        // Allow clicking anywhere on dropzone to choose file
        if (elements.uploadDropzone) {
            elements.uploadDropzone.addEventListener('click', function (e) {
                if (e.target.id === 'btn-remove-preview' || e.target.closest('#btn-remove-preview')) return;
                if (!elements.previewArea.classList.contains('hidden')) return;
                elements.inputPhotoFile.click();
            });
        }

        elements.formPhotoUpload.addEventListener('submit', async function (e) {
            e.preventDefault();
            const user = GameState.getUser();

            if (!user) {
                showToast('يرجى تسجيل الدخول أولاً! | Please sign in first!');
                window.location.href = 'index.php';
                return;
            }

            if (!elements.inputPhotoFile.files || !elements.inputPhotoFile.files[0]) {
                showToast('يرجى اختيار صورة أولاً من جهازك للرفع!');
                return;
            }

            const selectedFile = elements.inputPhotoFile.files[0];
            const formData = new FormData();
            formData.append('photo', selectedFile);
            formData.append('caption', elements.inputPhotoCaption ? elements.inputPhotoCaption.value.trim() : '');
            formData.append('user_id', user.id || 0);
            formData.append('user_name', user.name || '');
            formData.append('user_email', user.email || '');
            formData.append('department_id', user.department_id || 'it');
            
            const activeWeek = GameState.getActiveWeek();
            if (activeWeek) formData.append('week_id', activeWeek.id);
            formData.append('csrf_token', getMetaCsrfToken());

            elements.btnSubmitPhoto.disabled = true;
            elements.btnSubmitPhoto.textContent = 'جاري رفع الصورة والتحقق... ⏳';

            try {
                const res = await fetch('api/upload_photo.php?action=upload', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': getMetaCsrfToken() },
                    body: formData
                });
                
                const data = await res.json().catch(() => null);

                if (data && data.success) {
                    try {
                        if (typeof AudioEngine !== 'undefined' && typeof AudioEngine.play === 'function') {
                            AudioEngine.play('advance');
                        }
                        if (typeof ConfettiEngine !== 'undefined' && typeof ConfettiEngine.fire === 'function') {
                            ConfettiEngine.fire(0.5, 0.5, 80);
                        } else if (typeof ConfettiEngine !== 'undefined' && typeof ConfettiEngine.burst === 'function') {
                            ConfettiEngine.burst();
                        }
                    } catch (animErr) {
                        console.warn('Animation error', animErr);
                    }

                    showToast(data.message || 'تم رفع الصورة بنجاح وحصد 15 نقطة للقسم! 📷🎉');

                    // Reset form & preview
                    try {
                        elements.formPhotoUpload.reset();
                        elements.inputPhotoFile.value = '';
                        elements.imagePreviewElement.src = '';
                        elements.previewArea.classList.add('hidden');
                        elements.dropzonePrompt.classList.remove('hidden');
                    } catch (e) {}

                    try {
                        await refreshFullGameState();
                        if (activeWeek) await loadPhotoGallery(activeWeek.id);
                    } catch (refreshErr) {
                        console.warn('Refresh error', refreshErr);
                    }
                } else {
                    showToast((data && data.message) ? data.message : 'فشل رفع الصورة على الخادم');
                }
            } catch (err) {
                console.error('Photo upload network error', err);
                showToast('حدث خطأ أثناء الاتصال بالخادم لرفع الصورة');
            } finally {
                elements.btnSubmitPhoto.disabled = false;
                elements.btnSubmitPhoto.textContent = '🚀 رفع الصورة وحصد 15 نقطة للقسم';
            }
        });
    }

    async function loadPhotoGallery(weekId) {
        if (!elements.miniPhotosGrid) return;
        try {
            const res = await fetch(`api/upload_photo.php?action=list&week_id=${weekId}`);
            const data = await res.json();
            if (data.success && data.data) {
                elements.miniPhotosGrid.innerHTML = '';
                if (data.data.length === 0) {
                    elements.miniPhotosGrid.innerHTML = '<div style="grid-column:1/-1; color:var(--baby-blue-soft); font-size:0.85rem;">كن أول من يرفع صورة قسمه لهذا الأسبوع! | Be the first to upload your team photo! 📷</div>';
                    return;
                }
                data.data.slice(0, 8).forEach(p => {
                    const card = document.createElement('div');
                    card.className = 'mini-photo-card';
                    card.innerHTML = `
                        <img src="${p.photo_path}" alt="Photo" class="mini-photo-img" onclick="window.open('${p.photo_path}', '_blank')">
                        <div class="mini-photo-info">
                            <strong style="color:${p.dept_color};">${p.car_emoji} ${p.dept_code}</strong>
                            <p style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${p.caption || p.user_name}</p>
                        </div>
                    `;
                    elements.miniPhotosGrid.appendChild(card);
                });
            }
        } catch (e) {
            console.error('Failed to load gallery', e);
        }
    }

    // ================= WEEKLY CHAMPIONS SPOTLIGHT & CELEBRATION =================
    let currentCelebratedDeptColor = null;

    function renderWeeklyChampions(weeklyWinners, allWeeks) {
        if (!elements.weeklyChampionsGrid) return;
        elements.weeklyChampionsGrid.innerHTML = '';

        allWeeks.forEach(w => {
            const winnerInfo = weeklyWinners[w.id];
            const topDept = winnerInfo ? winnerInfo.top_department : null;
            const isCompleted = w.is_completed || (topDept && topDept.week_score > 0);

            const card = document.createElement('div');
            card.className = `champion-card ${isCompleted ? 'crowned interactive-champ' : ''}`;

            if (topDept && topDept.week_score > 0) {
                const champCarGraphic = (typeof window.getDepartmentCarSvg === 'function')
                    ? window.getDepartmentCarSvg(topDept, { width: 68, height: 27, showGlow: true })
                    : (topDept.car_emoji || '🏎️');

                card.innerHTML = `
                    <span class="champ-week-tag">الأسبوع ${w.week_number} 🥇 | Week ${w.week_number}</span>
                    <span class="champ-car">${champCarGraphic}</span>
                    <strong class="champ-dept-name" style="color:${topDept.color};">${topDept.name_ar} | ${topDept.name_en}</strong>
                    <span class="champ-score-pill">+${topDept.week_score} PTS</span>
                    <span class="champ-click-hint">اضغط للاحتفال 🎉 | Click to celebrate</span>
                `;
                // Clicking any crowned weekly champion card plays the realistic celebration poppers!
                card.addEventListener('click', () => {
                    triggerWeeklyWinnerCelebration(winnerInfo);
                });
            } else {
                card.innerHTML = `
                    <span class="champ-week-tag">الأسبوع ${w.week_number} | Week ${w.week_number}</span>
                    <span class="champ-car" style="opacity:0.4;">🏁</span>
                    <strong class="champ-dept-name" style="color:var(--baby-blue-soft);">قيد التنافس... | Competing...</strong>
                    <span style="font-size:0.75rem; color:var(--white-muted);">السباق مستمر | Race in progress</span>
                `;
            }

            elements.weeklyChampionsGrid.appendChild(card);
        });
    }

    /**
     * Trigger Realistic Weekly Winner Celebration Popper & Modal
     */
    function triggerWeeklyWinnerCelebration(winnerData) {
        if (!winnerData || !winnerData.top_department) return;
        const topDept = winnerData.top_department;
        const wNum = winnerData.week_number || 1;
        const wTitleAr = winnerData.title_ar || `الأسبوع ${wNum}`;
        const wTitleEn = winnerData.title_en || `Week ${wNum}`;

        currentCelebratedDeptColor = topDept.color || '#38bdf8';

        // Update Modal elements
        if (elements.weeklyWinnerWeekTitle) {
            elements.weeklyWinnerWeekTitle.textContent = `الأسبوع ${wNum}: ${wTitleAr} | ${wTitleEn}`;
        }
        if (elements.weeklyChampCar) {
            if (typeof window.getDepartmentCarSvg === 'function') {
                elements.weeklyChampCar.innerHTML = window.getDepartmentCarSvg(topDept, { width: 96, height: 38, showGlow: true });
            } else {
                elements.weeklyChampCar.textContent = topDept.car_emoji || '🏎️';
            }
        }
        if (elements.weeklyChampName) {
            elements.weeklyChampName.textContent = topDept.name_ar;
            elements.weeklyChampName.style.color = topDept.color || '#fde047';
        }
        if (elements.weeklyChampDeptEn) {
            elements.weeklyChampDeptEn.textContent = `${topDept.name_en} (${topDept.code})`;
        }
        if (elements.weeklyWinnerScoreVal) {
            elements.weeklyWinnerScoreVal.textContent = `+${topDept.week_score || 0} نقطة | ${topDept.week_score || 0} PTS`;
        }

        // Show Weekly Winner Celebration Modal
        if (elements.weeklyWinnerModal) {
            elements.weeklyWinnerModal.classList.remove('hidden');
        }

        // 💥 Launch Synchronized Party Popper Cannons & Confetti Shower!
        if (typeof ConfettiEngine !== 'undefined') {
            ConfettiEngine.startWeeklyCelebration(topDept.color);
        }

        // 🎺 Play celebratory audio fanfare + popper sound
        if (typeof AudioEngine !== 'undefined') {
            AudioEngine.play('weekly_winner');
        }

        showToast(`🏆 تهانينا لقسم ${topDept.name_ar}! بطل الأسبوع ${wNum} بأعلى النقاط! 🎉`);
    }

    /**
     * Check and automatically trigger weekly celebration for completed weeks
     */
    function checkAutoWeeklyCelebration(allWeeks, weeklyWinners) {
        if (!allWeeks || !weeklyWinners) return;
        for (const w of allWeeks) {
            const winnerInfo = weeklyWinners[w.id];
            const topDept = winnerInfo ? winnerInfo.top_department : null;

            if (w.is_completed && topDept && topDept.week_score > 0) {
                const sessionKey = `celebrated_week_${w.id}_${topDept.id}_${topDept.week_score}`;
                if (!sessionStorage.getItem(sessionKey)) {
                    sessionStorage.setItem(sessionKey, 'true');
                    setTimeout(() => {
                        triggerWeeklyWinnerCelebration(winnerInfo);
                    }, 800);
                    break;
                }
            }
        }
    }

    // Weekly Winner Modal Event Listeners
    if (elements.btnRePopCelebration) {
        elements.btnRePopCelebration.addEventListener('click', () => {
            if (typeof ConfettiEngine !== 'undefined') {
                ConfettiEngine.partyPoppers(currentCelebratedDeptColor);
            }
            if (typeof AudioEngine !== 'undefined') {
                AudioEngine.play('popper');
            }
        });
    }

    if (elements.btnWeeklyWinnerClose) {
        elements.btnWeeklyWinnerClose.addEventListener('click', () => {
            if (elements.weeklyWinnerModal) elements.weeklyWinnerModal.classList.add('hidden');
            if (typeof ConfettiEngine !== 'undefined') ConfettiEngine.stopContinuous();
        });
    }

    if (elements.btnCloseWeeklyWinner) {
        elements.btnCloseWeeklyWinner.addEventListener('click', () => {
            if (elements.weeklyWinnerModal) elements.weeklyWinnerModal.classList.add('hidden');
            if (typeof ConfettiEngine !== 'undefined') ConfettiEngine.stopContinuous();
        });
    }

    // ================= GRAND FINALE "AL-WANS" SURPRISE =================
    function checkFinaleSurprise(state) {
        if (state.finaleRevealed) {
            elements.finaleBanner.classList.remove('hidden');
            if (state.finaleContent && state.finaleContent.titleAr) {
                elements.bannerFinaleTitle.textContent = state.finaleContent.titleAr;
            }
        } else {
            elements.finaleBanner.classList.add('hidden');
        }
    }

    if (elements.btnOpenFinale) {
        elements.btnOpenFinale.addEventListener('click', () => {
            openFinaleModal();
        });
    }

    function openFinaleModal() {
        const state = GameState.getState();
        const leader = state.overallLeader;
        const departments = GameState.getRankings();

        if (state.finaleContent) {
            if (state.finaleContent.titleAr) elements.finaleWinnerTitle.textContent = state.finaleContent.titleAr;
            if (state.finaleContent.msgAr) elements.finaleModalMsgAr.textContent = state.finaleContent.msgAr;
            if (state.finaleContent.msgEn) elements.finaleModalMsgEn.textContent = state.finaleContent.msgEn;
        }

        if (leader) {
            elements.finaleChampName.textContent = `🏆 ${leader.name_ar} (${leader.name_en}) هو بطل سباق الصيف المتوج! | Champion! 🏆`;
            if (typeof window.getDepartmentCarSvg === 'function') {
                elements.finaleCarDisplay.innerHTML = window.getDepartmentCarSvg(leader, { width: 110, height: 44, showGlow: true });
            } else {
                elements.finaleCarDisplay.textContent = leader.car_emoji || '🏎️';
            }
        }

        // Render Podium
        elements.finalePodium.innerHTML = '';
        if (departments.length >= 3) {
            const ranks = [
                { rank: 2, dept: departments[1], cls: 'rank-2', medal: '🥈' },
                { rank: 1, dept: departments[0], cls: 'rank-1', medal: '🥇' },
                { rank: 3, dept: departments[2], cls: 'rank-3', medal: '🥉' }
            ];
            ranks.forEach(r => {
                const pillar = document.createElement('div');
                pillar.className = `podium-pillar ${r.cls}`;
                const podiumCarHtml = (typeof window.getDepartmentCarSvg === 'function')
                    ? window.getDepartmentCarSvg(r.dept, { width: 62, height: 25, showGlow: true })
                    : `<span style="font-size:1.8rem;">${r.dept.car_emoji || '🏎️'}</span>`;

                pillar.innerHTML = `
                    <div class="podium-car-wrap">${podiumCarHtml}</div>
                    <span class="podium-rank-num">${r.medal}</span>
                    <strong class="podium-dept-name">${r.dept.code}</strong>
                    <small style="font-size:0.7rem; color:#fff;">${r.dept.total_points} PTS</small>
                `;
                elements.finalePodium.appendChild(pillar);
            });
        }

        elements.finaleModal.classList.remove('hidden');
        if (typeof AudioEngine !== 'undefined') AudioEngine.play('winner');
        if (typeof ConfettiEngine !== 'undefined') {
            ConfettiEngine.startContinuousCelebration();
        }
    }

    if (elements.btnFinaleClose) {
        elements.btnFinaleClose.addEventListener('click', () => {
            elements.finaleModal.classList.add('hidden');
            if (typeof ConfettiEngine !== 'undefined') ConfettiEngine.stopContinuous();
        });
    }

    // Sound toggle
    if (elements.btnSoundToggle) {
        elements.btnSoundToggle.addEventListener('click', function () {
            const current = GameState.isSoundEnabled();
            GameState.setSoundEnabled(!current);
            if (typeof AudioEngine !== 'undefined') AudioEngine.setEnabled(!current);
            const label = this.querySelector('.sound-label');
            const icon = this.querySelector('.icon-sound');
            if (!current) {
                if (label) label.textContent = 'الصوت يعمل | Sound ON';
                if (icon) icon.textContent = '🔊';
            } else {
                if (label) label.textContent = 'الصوت مكتوم | Sound OFF';
                if (icon) icon.textContent = '🔇';
            }
        });
    }

    // Sync Engine cross-tab listener
    if (typeof SyncEngine !== 'undefined') {
        SyncEngine.init(async function () {
            await refreshFullGameState();
        });
    }

    // 7. Initial Boot
    await initUserSession();
    await refreshFullGameState();
});
