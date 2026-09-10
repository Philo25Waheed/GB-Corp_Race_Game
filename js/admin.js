/**
 * Admin Dashboard Controller
 * Summer Road Trip - Corporate Team Race
 */

document.addEventListener('DOMContentLoaded', function () {
    const API_ADMIN = 'api/admin.php';
    const API_GAME = 'api/game_state.php';

    // Security: Auto-inject CSRF token into all Admin requests
    function getMetaCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        if (typeof url === 'string' && (url.includes('api/admin.php') || url.includes('api/auth.php'))) {
            options.headers = options.headers || {};
            if (options.headers instanceof Headers) {
                options.headers.set('X-CSRF-Token', getMetaCsrfToken());
            } else if (Array.isArray(options.headers)) {
                options.headers.push(['X-CSRF-Token', getMetaCsrfToken()]);
            } else {
                options.headers['X-CSRF-Token'] = getMetaCsrfToken();
            }
        }
        return originalFetch.call(this, url, options);
    };

    // State
    let adminState = {
        authenticated: false,
        activeTab: 'tab-highway',
        gameState: null,
        questions: [],
        photoSubmissions: [],
        users: []
    };

    // DOM Elements
    const elements = {
        authModal: document.getElementById('admin-auth-modal'),
        formAuth: document.getElementById('form-admin-auth'),
        inputPassword: document.getElementById('input-admin-password'),
        authError: document.getElementById('admin-auth-error'),
        btnLogout: document.getElementById('btn-admin-logout'),
        
        // Counters
        topActiveWeekVal: document.getElementById('top-active-week-val'),
        topUsersCount: document.getElementById('top-users-count'),
        topPhotosCount: document.getElementById('top-photos-count'),

        // Nav tabs
        tabBtns: document.querySelectorAll('.nav-tab-btn'),
        tabPanels: document.querySelectorAll('.tab-panel'),

        // Tab 1: Highway
        highwayGrid: document.getElementById('highway-departments-controls'),

        // Tab 2: Weeks
        weeksGrid: document.getElementById('weeks-manager-grid'),

        // Tab 3: Quiz
        tableQuestionsBody: document.getElementById('tbody-questions'),
        filterQuizWeek: document.getElementById('select-quiz-filter-week'),
        btnOpenAddQuestion: document.getElementById('btn-open-add-question'),
        modalQuestion: document.getElementById('modal-question'),
        btnCloseQModal: document.getElementById('btn-close-q-modal'),
        btnCancelQModal: document.getElementById('btn-cancel-q-modal'),
        formSaveQuestion: document.getElementById('form-save-question'),
        modalQuestionTitle: document.getElementById('modal-question-title'),

        // Tab 4: Photos
        photoGrid: document.getElementById('photo-submissions-grid'),

        // Tab 5: Activities
        formAwardActivity: document.getElementById('form-award-activity'),

        // Tab 6: Users
        tableUsersBody: document.getElementById('tbody-users'),
        inputSearchUsers: document.getElementById('input-search-users'),

        // Tab 7: Finale
        finaleStatusText: document.getElementById('finale-status-text'),
        btnToggleFinaleReveal: document.getElementById('btn-toggle-finale-reveal'),
        formFinaleContent: document.getElementById('form-finale-content'),
        inputFinaleTitleAr: document.getElementById('input-finale-title-ar'),
        inputFinaleTitleEn: document.getElementById('input-finale-title-en'),
        inputFinaleMsgAr: document.getElementById('input-finale-msg-ar'),
        inputFinaleMsgEn: document.getElementById('input-finale-msg-en'),

        // Global Reset
        btnGlobalResetRace: document.getElementById('btn-global-reset-race'),
        toastContainer: document.getElementById('admin-toast')
    };

    // Helper: Escape HTML (XSS Protection)
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Helper: Toast
    function showToast(msg) {
        if (!elements.toastContainer) return;
        const toast = document.createElement('div');
        toast.className = 'admin-toast';
        toast.textContent = msg;
        elements.toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 3500);
    }

    // 1. Initial Authentication Check & Init
    async function checkAuth() {
        if (elements.highwayGrid) {
            // Already inside authenticated dashboard view rendered by server
            adminState.authenticated = true;
            loadAllDashboardData();
            return;
        }

        if (elements.formAuth) {
            // Unauthenticated view (login form present)
            return;
        }
    }

    // 2. Admin Login Submit
    if (elements.formAuth) {
        elements.formAuth.addEventListener('submit', async function (e) {
            e.preventDefault();
            const pass = elements.inputPassword ? elements.inputPassword.value.trim() : '';
            if (elements.authError) elements.authError.classList.add('hidden');

            try {
                const res = await fetch('api/auth.php?action=admin_login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: pass })
                });
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (jsonErr) {
                    if (elements.authError) {
                        elements.authError.textContent = 'Server Error: ' + (text.replace(/<[^>]*>?/gm, '').trim().substring(0, 150) || 'Response non-JSON');
                        elements.authError.classList.remove('hidden');
                    }
                    return;
                }
                if (data.success) {
                    window.location.reload();
                } else {
                    if (elements.authError) {
                        elements.authError.textContent = data.message || 'كلمة المرور غير صحيحة';
                        elements.authError.classList.remove('hidden');
                    }
                }
            } catch (err) {
                if (elements.authError) {
                    elements.authError.textContent = 'Connection error: ' + (err.message || 'Error');
                    elements.authError.classList.remove('hidden');
                }
            }
        });
    }

    // 3. Logout
    if (elements.btnLogout) {
        elements.btnLogout.addEventListener('click', async function (e) {
            e.preventDefault();
            try {
                await fetch('api/auth.php?action=logout');
            } catch (e) {}
            window.location.href = 'index.php';
        });
    }

    // 4. Tab Switching
    elements.tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetTab = this.dataset.tab;
            elements.tabBtns.forEach(b => b.classList.remove('active'));
            elements.tabPanels.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            const targetPanel = document.getElementById(targetTab);
            if (targetPanel) targetPanel.classList.add('active');
            adminState.activeTab = targetTab;
        });
    });

    // 5. Load All Data Concurrently & Resiliently
    async function loadAllDashboardData() {
        if (elements.highwayGrid && elements.highwayGrid.children.length === 0) {
            elements.highwayGrid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:30px; color:var(--admin-accent); font-weight:bold;">جاري تحميل بيانات السباق... ⏳</div>';
        }

        await Promise.allSettled([
            fetchGameState(),
            fetchQuestions(),
            fetchPhotoSubmissions(),
            fetchUsers()
        ]);
    }

    // Fetch Game State
    async function fetchGameState() {
        try {
            const res = await fetch(API_GAME);
            const data = await res.json();
            if (data.success && data.data) {
                adminState.gameState = data.data;
                renderHighwayControls(data.data.departments || []);
                renderWeeksManager(data.data.all_weeks || [], data.data.active_week);
                renderFinaleControls(data.data);
                updateTopStats(data.data);
            }
        } catch (e) {
            console.error('Failed to load game state', e);
        }
    }

    function updateTopStats(state) {
        if (state.active_week && elements.topActiveWeekVal) {
            elements.topActiveWeekVal.textContent = `الأسبوع ${state.active_week.week_number}`;
        }
    }

    let cachedAdminDepts = [];
    let adminHighwayCat = 'all';
    let adminHighwayQuery = '';
    let adminFiltersInitialized = false;

    // Render Tab 1: Highway Controls
    function renderHighwayControls(departments) {
        if (departments && departments.length > 0) cachedAdminDepts = departments;
        if (!elements.highwayGrid) return;
        elements.highwayGrid.innerHTML = '';

        let deptsToShow = cachedAdminDepts;
        if (adminHighwayCat !== 'all') {
            deptsToShow = deptsToShow.filter(d => d.category === adminHighwayCat);
        }
        if (adminHighwayQuery) {
            const q = adminHighwayQuery.toLowerCase();
            deptsToShow = deptsToShow.filter(d => 
                (d.name_ar && d.name_ar.toLowerCase().includes(q)) ||
                (d.name_en && d.name_en.toLowerCase().includes(q)) ||
                (d.code && d.code.toLowerCase().includes(q))
            );
        }

        if (deptsToShow.length === 0) {
            elements.highwayGrid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:2rem; color:var(--admin-text-sub);">لا توجد أقسام مطابقة للبحث أو الفلتر المحدد.</div>';
            return;
        }

        deptsToShow.forEach(dept => {
            const card = document.createElement('div');
            card.className = 'dept-ctrl-card';
            const carPreview = (typeof window.getDepartmentCarSvg === 'function')
                ? window.getDepartmentCarSvg(dept, { width: 44, height: 18, showGlow: false })
                : dept.car_emoji;

            card.innerHTML = `
                <div class="dept-ctrl-head">
                    <div class="dept-ctrl-title">
                        <span class="dept-ctrl-car" style="display:inline-flex;align-items:center;">${carPreview}</span>
                        <div>
                            <div class="dept-ctrl-name">${dept.name_ar}</div>
                            <small style="color:var(--admin-text-sub);">${dept.name_en}</small>
                        </div>
                    </div>
                    <span class="dept-ctrl-badge" style="background:${dept.color}; color:#000;">${dept.code}</span>
                </div>
                <div class="dept-ctrl-stats">
                    <div class="stat-item">
                        <span>المسافة | Distance</span>
                        <strong>${dept.position} / 15 MI</strong>
                    </div>
                    <div class="stat-item">
                        <span>النقاط | Points</span>
                        <strong style="color:var(--admin-accent);">${dept.total_points} PTS</strong>
                    </div>
                </div>
                <div class="dept-ctrl-btns">
                    <button class="btn-step" data-id="${dept.id}" data-step="1">+1 خطوة | Step</button>
                    <button class="btn-step" data-id="${dept.id}" data-step="2">+2 خطوة | Steps</button>
                    <button class="btn-step" data-id="${dept.id}" data-step="3">+3 خطوة | Steps</button>
                    <button class="btn-step neg" data-id="${dept.id}" data-step="-1">-1 رجوع | Back</button>
                    <button class="btn-step neg" data-id="${dept.id}" data-step="-2">-2 رجوع | Back</button>
                </div>
                <div class="dept-ctrl-points-row">
                    <button class="btn-pts-adj btn-pts-pos" data-id="${dept.id}" data-pts="10">+10 نقاط | PTS</button>
                    <button class="btn-pts-adj btn-pts-pos" data-id="${dept.id}" data-pts="20">+20 نقطة | PTS</button>
                    <button class="btn-pts-adj btn-pts-neg" data-id="${dept.id}" data-pts="-10">➖ 10 نقاط | PTS</button>
                    <button class="btn-pts-adj btn-pts-neg" data-id="${dept.id}" data-pts="-20">➖ 20 نقطة | PTS</button>
                    <button class="btn-pts-adj btn-pts-neg" data-id="${dept.id}" data-pts="-50">➖ 50 نقطة | PTS</button>
                </div>
            `;
            elements.highwayGrid.appendChild(card);
        });

        // Initialize filter buttons & search listener once
        if (!adminFiltersInitialized) {
            adminFiltersInitialized = true;
            const adminFilterBtns = document.querySelectorAll('#admin-highway-filter-group .btn-admin-filter');
            adminFilterBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    adminFilterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    adminHighwayCat = this.dataset.cat || 'all';
                    renderHighwayControls();
                });
            });

            const inputSearchDept = document.getElementById('admin-search-dept');
            if (inputSearchDept) {
                inputSearchDept.addEventListener('input', function () {
                    adminHighwayQuery = this.value.trim();
                    renderHighwayControls();
                });
            }
        }

        // Event listeners for step movement
        elements.highwayGrid.querySelectorAll('.btn-step').forEach(btn => {
            btn.addEventListener('click', async function () {
                const deptId = this.dataset.id;
                const steps = parseInt(this.dataset.step, 10);
                await moveDepartmentCar(deptId, steps);
            });
        });

        // Event listeners for direct points adjust (add / minus)
        elements.highwayGrid.querySelectorAll('.btn-pts-adj').forEach(btn => {
            btn.addEventListener('click', async function () {
                const deptId = this.dataset.id;
                const pts = parseInt(this.dataset.pts, 10);
                await adjustDepartmentPointsDirect(deptId, pts);
            });
        });
    }

    async function adjustDepartmentPointsDirect(deptId, pointsDelta) {
        try {
            const res = await fetch(`${API_ADMIN}?action=adjust_points`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    department_id: deptId,
                    points: pointsDelta,
                    activity_name: (pointsDelta >= 0) ? `إضافة نقاط سريعة (+${pointsDelta})` : `خصم نقاط سريع (${pointsDelta})`
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                await fetchGameState();
            } else {
                showToast(data.message || 'فشل تعديل النقاط');
            }
        } catch (e) {
            showToast('Error updating points');
        }
    }

    async function moveDepartmentCar(deptId, steps) {
        try {
            const res = await fetch(`${API_ADMIN}?action=move_car`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ department_id: deptId, steps: steps })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                await fetchGameState();
            }
        } catch (e) {
            showToast('Error updating car position');
        }
    }

    // Render Tab 2: Weeks & Challenges Manager
    function renderWeeksManager(weeks, activeWeek) {
        if (!elements.weeksGrid) return;
        elements.weeksGrid.innerHTML = '';

        const typeLabels = {
            'quiz': '❓ كويز المعرفة (Quiz)',
            'photo_challenge': '📷 تحدي التصوير (Photo Challenge)',
            'team_activity': '👥 النشاط الجماعي (Team Activity)'
        };

        weeks.forEach(w => {
            const isActive = activeWeek && activeWeek.id == w.id;
            const card = document.createElement('div');
            card.className = `week-admin-card ${isActive ? 'active-week-card' : ''}`;
            card.innerHTML = `
                ${isActive ? '<span class="week-active-badge">⚡ الأسبوع النشط | Active Week</span>' : ''}
                <div class="week-card-head">
                    <span class="week-num-badge">الأسبوع ${w.week_number} | Week ${w.week_number}</span>
                    <h3 class="week-card-title">${w.title_ar}</h3>
                    <small style="color:var(--admin-text-sub); display:block;">${w.title_en}</small>
                </div>

                <div class="week-type-pill">
                    <span>${typeLabels[w.challenge_type] || w.challenge_type}</span>
                </div>

                <p class="week-card-desc">${w.description_ar || ''}</p>
                <small style="color:var(--admin-text-sub); display:block; margin-bottom:12px;">${w.description_en || ''}</small>

                <div class="week-card-actions">
                    ${!isActive ? `<button class="btn-set-active" data-id="${w.id}">🎯 تفعيل كأسبوع نشط | Set Active</button>` : '<span style="color:var(--admin-green); font-weight:bold; font-size:13px;">مفعل لجميع المتسابقين | Active Now</span>'}
                </div>
            `;
            elements.weeksGrid.appendChild(card);
        });

        elements.weeksGrid.querySelectorAll('.btn-set-active').forEach(btn => {
            btn.addEventListener('click', async function () {
                const weekId = parseInt(this.dataset.id, 10);
                await setActiveWeek(weekId);
            });
        });
    }

    async function setActiveWeek(weekId) {
        try {
            const res = await fetch(`${API_ADMIN}?action=set_active_week`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ week_id: weekId })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                await fetchGameState();
            }
        } catch (e) {
            showToast('Error changing week');
        }
    }

    // Render Tab 3: Quiz Questions
    async function fetchQuestions() {
        try {
            const filterWeek = elements.filterQuizWeek.value;
            const url = filterWeek ? `${API_ADMIN}?action=list_questions&week_id=${filterWeek}` : `${API_ADMIN}?action=list_questions`;
            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                adminState.questions = data.data || [];
                renderQuestionsTable(adminState.questions);
            }
        } catch (e) {
            console.error('Failed to fetch questions', e);
        }
    }

    function renderQuestionsTable(questions) {
        if (!elements.tableQuestionsBody) return;
        elements.tableQuestionsBody.innerHTML = '';

        if (questions.length === 0) {
            elements.tableQuestionsBody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px;">لا توجد أسئلة مسجلة</td></tr>';
            return;
        }

        questions.forEach((q, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${idx + 1}</td>
                <td><strong>أسبوع ${q.week_id}</strong></td>
                <td><span style="background:rgba(56,189,248,0.15); color:var(--admin-accent); padding:2px 8px; border-radius:4px; font-size:12px;">${q.category_ar}</span></td>
                <td>${q.question_ar}</td>
                <td style="direction:ltr; text-align:left;">${q.question_en}</td>
                <td><strong style="color:var(--admin-green);">${q.correct_option}</strong></td>
                <td>${q.points} PTS</td>
                <td>
                    <button class="btn-row-action btn-edit-q" data-id="${q.id}">✏️</button>
                    <button class="btn-row-action delete btn-del-q" data-id="${q.id}">🗑️</button>
                </td>
            `;
            elements.tableQuestionsBody.appendChild(tr);
        });

        // Edit Question
        elements.tableQuestionsBody.querySelectorAll('.btn-edit-q').forEach(btn => {
            btn.addEventListener('click', function () {
                const qId = parseInt(this.dataset.id, 10);
                const q = adminState.questions.find(x => x.id == qId);
                if (q) openQuestionModal(q);
            });
        });

        // Delete Question
        elements.tableQuestionsBody.querySelectorAll('.btn-del-q').forEach(btn => {
            btn.addEventListener('click', async function () {
                const qId = parseInt(this.dataset.id, 10);
                if (confirm('هل أنت متأكد من حذف هذا السؤال؟')) {
                    await deleteQuestion(qId);
                }
            });
        });
    }

    if (elements.filterQuizWeek) elements.filterQuizWeek.addEventListener('change', fetchQuestions);
    if (elements.btnOpenAddQuestion) elements.btnOpenAddQuestion.addEventListener('click', () => openQuestionModal(null));
    if (elements.btnCloseQModal) elements.btnCloseQModal.addEventListener('click', () => elements.modalQuestion && elements.modalQuestion.classList.add('hidden'));
    if (elements.btnCancelQModal) elements.btnCancelQModal.addEventListener('click', () => elements.modalQuestion && elements.modalQuestion.classList.add('hidden'));

    function openQuestionModal(q) {
        if (q) {
            elements.modalQuestionTitle.textContent = '✏️ تعديل السؤال';
            document.getElementById('input-q-id').value = q.id;
            document.getElementById('select-q-week').value = q.week_id;
            document.getElementById('input-q-points').value = q.points;
            document.getElementById('input-q-cat-ar').value = q.category_ar;
            document.getElementById('input-q-cat-en').value = q.category_en;
            document.getElementById('input-q-text-ar').value = q.question_ar;
            document.getElementById('input-q-text-en').value = q.question_en;
            document.getElementById('input-opt-a-ar').value = q.option_a_ar;
            document.getElementById('input-opt-a-en').value = q.option_a_en;
            document.getElementById('input-opt-b-ar').value = q.option_b_ar;
            document.getElementById('input-opt-b-en').value = q.option_b_en;
            document.getElementById('input-opt-c-ar').value = q.option_c_ar;
            document.getElementById('input-opt-c-en').value = q.option_c_en;
            document.getElementById('input-opt-d-ar').value = q.option_d_ar;
            document.getElementById('input-opt-d-en').value = q.option_d_en;
            document.getElementById('select-q-correct').value = q.correct_option;
        } else {
            elements.modalQuestionTitle.textContent = '➕ إضافة سؤال كويز جديد';
            elements.formSaveQuestion.reset();
            document.getElementById('input-q-id').value = '0';
        }
        elements.modalQuestion.classList.remove('hidden');
    }

    if (elements.formSaveQuestion) {
        elements.formSaveQuestion.addEventListener('submit', async function (e) {
            e.preventDefault();
            const payload = {
                id: parseInt(document.getElementById('input-q-id').value, 10),
                week_id: parseInt(document.getElementById('select-q-week').value, 10),
                points: parseInt(document.getElementById('input-q-points').value, 10),
                category_ar: document.getElementById('input-q-cat-ar').value.trim(),
                category_en: document.getElementById('input-q-cat-en').value.trim(),
                question_ar: document.getElementById('input-q-text-ar').value.trim(),
                question_en: document.getElementById('input-q-text-en').value.trim(),
                option_a_ar: document.getElementById('input-opt-a-ar').value.trim(),
                option_a_en: document.getElementById('input-opt-a-en').value.trim(),
                option_b_ar: document.getElementById('input-opt-b-ar').value.trim(),
                option_b_en: document.getElementById('input-opt-b-en').value.trim(),
                option_c_ar: document.getElementById('input-opt-c-ar').value.trim(),
                option_c_en: document.getElementById('input-opt-c-en').value.trim(),
                option_d_ar: document.getElementById('input-opt-d-ar').value.trim(),
                option_d_en: document.getElementById('input-opt-d-en').value.trim(),
                correct_option: document.getElementById('select-q-correct').value
            };

            try {
                const res = await fetch(`${API_ADMIN}?action=save_question`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    if (elements.modalQuestion) elements.modalQuestion.classList.add('hidden');
                    await fetchQuestions();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                showToast('Failed to save question');
            }
        });
    }

    async function deleteQuestion(qId) {
        try {
            const res = await fetch(`${API_ADMIN}?action=delete_question`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: qId })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                await fetchQuestions();
            }
        } catch (e) {
            showToast('Failed to delete question');
        }
    }

    // Render Tab 4: Photo Submissions
    async function fetchPhotoSubmissions() {
        try {
            const res = await fetch(`${API_ADMIN}?action=list_photo_submissions`);
            const data = await res.json();
            if (data.success) {
                adminState.photoSubmissions = data.data || [];
                if (elements.topPhotosCount) elements.topPhotosCount.textContent = adminState.photoSubmissions.length;
                renderPhotoSubmissions(adminState.photoSubmissions);
            }
        } catch (e) {
            console.error('Failed to fetch photos', e);
        }
    }

    function renderPhotoSubmissions(submissions) {
        if (!elements.photoGrid) return;
        elements.photoGrid.innerHTML = '';

        if (submissions.length === 0) {
            elements.photoGrid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--admin-text-sub);">لا توجد صور مرفوعة حتى الآن</div>';
            return;
        }

        submissions.forEach(sub => {
            const card = document.createElement('div');
            card.className = 'photo-sub-card';
            card.innerHTML = `
                <div class="photo-sub-img-wrap">
                    <img src="${escapeHtml(sub.photo_path)}" alt="Photo" class="photo-sub-img" onclick="window.open('${escapeHtml(sub.photo_path)}', '_blank')">
                </div>
                <div class="photo-sub-body">
                    <div class="photo-sub-uploader">
                        <strong style="color:var(--admin-accent);">${escapeHtml(sub.user_name)}</strong>
                        <span class="dept-ctrl-badge" style="background:${escapeHtml(sub.dept_color)}; color:#000;">${escapeHtml(sub.dept_code)}</span>
                    </div>
                    <div class="photo-sub-caption">${sub.caption ? `"${escapeHtml(sub.caption)}"` : 'بدون تعليق | No caption'}</div>
                    <div style="font-size:12px; color:var(--admin-text-sub); margin-bottom:10px;">
                        <span>التاريخ | Date: ${escapeHtml(sub.created_at)}</span> • <strong>+${parseInt(sub.points_awarded, 10)} PTS</strong>
                    </div>
                    <div class="photo-sub-actions">
                        <button class="btn-grade-approve" data-id="${sub.id}" data-status="approved">✓ معتمدة | Approved (+15)</button>
                    </div>
                </div>
            `;
            elements.photoGrid.appendChild(card);
        });
    }

    // Render Tab 5: Points Adjustment (Add & Deduct)
    if (elements.formAwardActivity) {
        const opAddRadio = document.getElementById('op-type-add');
        const opDeductRadio = document.getElementById('op-type-deduct');
        const lblOpAdd = document.getElementById('lbl-op-add');
        const lblOpDeduct = document.getElementById('lbl-op-deduct');
        const formPointsTitle = document.getElementById('form-points-title');
        const lblPointsAmount = document.getElementById('lbl-points-amount');
        const lblReasonTitle = document.getElementById('lbl-reason-title');
        const btnSubmitPoints = document.getElementById('btn-submit-points-action');
        const inputReason = document.getElementById('input-activity-name');

        function updateOpTypeUI(type) {
            if (type === 'deduct') {
                if (lblOpDeduct) lblOpDeduct.classList.add('active');
                if (lblOpAdd) lblOpAdd.classList.remove('active');
                if (formPointsTitle) formPointsTitle.textContent = '➖ خصم نقاط / تطبيق عقوبة | Minus / Deduct Points';
                if (lblPointsAmount) lblPointsAmount.textContent = 'عدد النقاط المخصومة | Points Amount (-):';
                if (lblReasonTitle) lblReasonTitle.textContent = 'سبب الخصم / نوع المخالفة | Deduction Reason:';
                if (inputReason) inputReason.placeholder = 'مثال: مخالفة شروط المسابقة | e.g. Competition rule violation';
                if (btnSubmitPoints) {
                    btnSubmitPoints.classList.add('danger-mode');
                    btnSubmitPoints.innerHTML = '<span>⚠️ خصم النقاط وتأخير سيارة القسم | Deduct Points</span>';
                }
            } else {
                if (lblOpAdd) lblOpAdd.classList.add('active');
                if (lblOpDeduct) lblOpDeduct.classList.remove('active');
                if (formPointsTitle) formPointsTitle.textContent = '➕ منح نقاط نشاط إضافية | Add / Award Points (+)';
                if (lblPointsAmount) lblPointsAmount.textContent = 'عدد النقاط الممنوحة | Points Amount (+):';
                if (lblReasonTitle) lblReasonTitle.textContent = 'السبب / اسم النشاط | Reason / Activity Name:';
                if (inputReason) inputReason.placeholder = 'مثال: إنجاز نشاط العمل الجماعي | e.g. Team mission accomplished';
                if (btnSubmitPoints) {
                    btnSubmitPoints.classList.remove('danger-mode');
                    btnSubmitPoints.innerHTML = '<span>🚀 منح النقاط وتقديم سيارة القسم | Award Points</span>';
                }
            }
        }

        if (opAddRadio && opDeductRadio) {
            opAddRadio.addEventListener('change', () => updateOpTypeUI('add'));
            opDeductRadio.addEventListener('change', () => updateOpTypeUI('deduct'));
        }

        elements.formAwardActivity.addEventListener('submit', async function (e) {
            e.preventDefault();
            const deptVal = document.getElementById('select-activity-dept').value;
            const actName = document.getElementById('input-activity-name').value.trim();
            const rawPts = parseInt(document.getElementById('input-activity-points').value, 10);
            const notesVal = document.getElementById('input-activity-notes').value.trim();
            const isDeduct = opDeductRadio && opDeductRadio.checked;

            const finalPoints = isDeduct ? -Math.abs(rawPts) : Math.abs(rawPts);

            const payload = {
                department_id: deptVal,
                activity_name: actName,
                points: finalPoints,
                notes: notesVal
            };

            try {
                const res = await fetch(`${API_ADMIN}?action=adjust_points`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    elements.formAwardActivity.reset();
                    updateOpTypeUI('add');
                    await fetchGameState();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                showToast('Failed to adjust points');
            }
        });
    }

    // Render Tab 6: Users Directory
    async function fetchUsers() {
        try {
            const res = await fetch(`${API_ADMIN}?action=list_users`);
            const data = await res.json();
            if (data.success) {
                adminState.users = data.data || [];
                if (elements.topUsersCount) elements.topUsersCount.textContent = adminState.users.length;
                renderUsersTable(adminState.users);
            }
        } catch (e) {
            console.error('Failed to fetch users', e);
        }
    }

    function renderUsersTable(users) {
        if (!elements.tableUsersBody) return;
        elements.tableUsersBody.innerHTML = '';

        if (users.length === 0) {
            elements.tableUsersBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:20px;">لا يوجد متسابقون مسجلون بعد | No registered users yet</td></tr>';
            return;
        }

        users.forEach((u, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${idx + 1}</td>
                <td><strong>${escapeHtml(u.name)}</strong></td>
                <td style="direction:ltr; text-align:left;">${escapeHtml(u.email)}</td>
                <td><span class="dept-ctrl-badge" style="background:${escapeHtml(u.dept_color)}; color:#000;">${escapeHtml(u.dept_code)} - ${escapeHtml(u.dept_name_ar)}</span></td>
                <td><strong>${parseInt(u.quiz_attempts_count, 10)}</strong> إجابة | Ans</td>
                <td><strong>${parseInt(u.photo_count, 10)}</strong> صورة | Pic</td>
                <td style="font-size:12px; color:var(--admin-text-sub);">${escapeHtml(u.created_at)}</td>
            `;
            elements.tableUsersBody.appendChild(tr);
        });
    }

    // Users Search Filter
    if (elements.inputSearchUsers) {
        elements.inputSearchUsers.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            if (!query) {
                renderUsersTable(adminState.users);
                return;
            }
            const filtered = adminState.users.filter(u => 
                (u.name && u.name.toLowerCase().includes(query)) ||
                (u.email && u.email.toLowerCase().includes(query)) ||
                (u.dept_name_ar && u.dept_name_ar.toLowerCase().includes(query)) ||
                (u.dept_code && u.dept_code.toLowerCase().includes(query))
            );
            renderUsersTable(filtered);
        });
    }

    // Render Tab 7: Finale & Al-Wans Surprise
    function renderFinaleControls(state) {
        if (!elements.finaleStatusText || !elements.btnToggleFinaleReveal) return;
        const isRevealed = state.finale_revealed;
        if (isRevealed) {
            elements.finaleStatusText.innerHTML = 'المفاجأة حالياً: <strong style="color:var(--admin-green);">مفعلة وظاهرة لجميع المتسابقين 🎉</strong>';
            elements.btnToggleFinaleReveal.innerHTML = '<span>🔒 إخفاء المفاجأة</span>';
            elements.btnToggleFinaleReveal.style.background = 'var(--admin-card-bg)';
            elements.btnToggleFinaleReveal.style.border = '1px solid var(--admin-red)';
            elements.btnToggleFinaleReveal.style.color = '#fca5a5';
        } else {
            elements.finaleStatusText.innerHTML = 'المفاجأة حالياً: <strong style="color:var(--admin-orange);">مخفية (لم يتم كشفها بعد)</strong>';
            elements.btnToggleFinaleReveal.innerHTML = '<span>🎁 كشف وتفعيل المفاجأة الآن</span>';
            elements.btnToggleFinaleReveal.style.background = 'linear-gradient(135deg, #f97316, #ea580c)';
            elements.btnToggleFinaleReveal.style.color = '#fff';
        }

        if (elements.inputFinaleTitleAr) elements.inputFinaleTitleAr.value = state.finale_surprise_title_ar || '';
        if (elements.inputFinaleTitleEn) elements.inputFinaleTitleEn.value = state.finale_surprise_title_en || '';
        if (elements.inputFinaleMsgAr) elements.inputFinaleMsgAr.value = state.finale_surprise_message_ar || '';
        if (elements.inputFinaleMsgEn) elements.inputFinaleMsgEn.value = state.finale_surprise_message_en || '';
    }

    if (elements.btnToggleFinaleReveal) {
        elements.btnToggleFinaleReveal.addEventListener('click', async function () {
            const currentRevealed = adminState.gameState ? adminState.gameState.finale_revealed : false;
            const newRevealState = !currentRevealed;

            try {
                const res = await fetch(`${API_ADMIN}?action=toggle_finale_surprise`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ reveal: newRevealState })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    await fetchGameState();
                }
            } catch (e) {
                showToast('Error toggling finale');
            }
        });
    }

    if (elements.formFinaleContent) {
        elements.formFinaleContent.addEventListener('submit', async function (e) {
            e.preventDefault();
            const payload = {
                title_ar: elements.inputFinaleTitleAr.value.trim(),
                title_en: elements.inputFinaleTitleEn.value.trim(),
                msg_ar: elements.inputFinaleMsgAr.value.trim(),
                msg_en: elements.inputFinaleMsgEn.value.trim()
            };

            try {
                const res = await fetch(`${API_ADMIN}?action=update_finale_content`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    await fetchGameState();
                }
            } catch (e) {
                showToast('Failed to save finale content');
            }
        });
    }

    // Global Reset Race Modal Trigger & Execution
    const modalResetConfirm = document.getElementById('modal-reset-confirm');
    const btnCancelReset = document.getElementById('btn-cancel-reset');
    const btnExecuteReset = document.getElementById('btn-execute-reset');

    if (elements.btnGlobalResetRace) {
        elements.btnGlobalResetRace.addEventListener('click', function (e) {
            e.preventDefault();
            if (modalResetConfirm) {
                modalResetConfirm.classList.remove('hidden');
            } else {
                if (confirm('هل أنت متأكد من تصفير وإعادة تعيين السباق بالكامل؟')) {
                    executeResetRace();
                }
            }
        });
    }

    if (btnCancelReset) {
        btnCancelReset.addEventListener('click', function () {
            if (modalResetConfirm) modalResetConfirm.classList.add('hidden');
        });
    }

    if (btnExecuteReset) {
        btnExecuteReset.addEventListener('click', async function () {
            await executeResetRace();
        });
    }

    async function executeResetRace() {
        if (btnExecuteReset) {
            btnExecuteReset.disabled = true;
            btnExecuteReset.textContent = 'جاري تصفير وإعادة التعيين... ⏳';
        }

        try {
            const res = await fetch(`${API_ADMIN}?action=reset_race`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ admin_pin: '1234' })
            });
            const data = await res.json().catch(() => null);
            if (data && data.success) {
                showToast(data.message || 'تمت إعادة تعيين السباق بالكامل وتصفير النقاط! 🔄');
                if (modalResetConfirm) modalResetConfirm.classList.add('hidden');
                await loadAllDashboardData();
            } else {
                showToast((data && data.message) ? data.message : 'فشل إعادة التعيين');
            }
        } catch (e) {
            console.error('Reset error', e);
            showToast('حدث خطأ أثناء الاتصال بالخادم لإعادة التعيين');
        } finally {
            if (btnExecuteReset) {
                btnExecuteReset.disabled = false;
                btnExecuteReset.innerHTML = '🔄 نعم، متأكد - إعادة التعيين الآن';
            }
        }
    }

    // Initial load
    checkAuth();
});
