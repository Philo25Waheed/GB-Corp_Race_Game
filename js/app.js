/**
 * Main Application Controller & Automatic Question Progression Engine
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Canvas Confetti
    const confettiCanvas = document.getElementById('confetti-canvas');
    if (confettiCanvas) ConfettiEngine.init(confettiCanvas);

    TrackRenderer.init();

    // 2. DOM Element References
    const elements = {
        // Header
        roleSubtitle: document.getElementById('role-subtitle'),
        btnLoginToggle: document.getElementById('btn-login-toggle'),
        labelRoleStatus: document.getElementById('label-role-status'),
        btnSoundToggle: document.getElementById('btn-sound-toggle'),
        btnShortcutsToggle: document.getElementById('btn-shortcuts-toggle'),
        btnGmToggle: document.getElementById('btn-gm-toggle'),

        // Department Player Card View
        deptPlayerCard: document.getElementById('dept-player-card'),
        deptBanner: document.getElementById('dept-banner'),
        deptCarIcon: document.getElementById('dept-car-icon'),
        deptTitleName: document.getElementById('dept-title-name'),
        deptPosVal: document.getElementById('dept-pos-val'),
        
        // Challenge Card & Options
        challengeBadge: document.getElementById('challenge-badge'),
        categoryBadge: document.getElementById('category-badge'),
        challengePointsBadge: document.getElementById('challenge-points-badge'),
        questionText: document.getElementById('question-text'),
        optionsGrid: document.getElementById('options-grid'),
        optionItems: document.querySelectorAll('.option-item'),
        optA: document.getElementById('opt-a'),
        optB: document.getElementById('opt-b'),
        optC: document.getElementById('opt-c'),
        optD: document.getElementById('opt-d'),
        
        // Timer
        timerBox: document.getElementById('timer-box'),
        timerValue: document.getElementById('timer-value'),
        
        // Game Master Drawer
        gmDrawer: document.getElementById('gm-drawer'),
        btnCloseGm: document.getElementById('btn-close-gm'),
        gmTeamsList: document.getElementById('gm-teams-list'),
        inputTeamCode: document.getElementById('input-team-code'),
        btnExecuteCode: document.getElementById('btn-execute-code'),
        btnPrevQ: document.getElementById('btn-prev-q'),
        btnNextQ: document.getElementById('btn-next-q'),
        selectQuestion: document.getElementById('select-question'),
        btnTimerStart: document.getElementById('btn-timer-start'),
        btnTimerPause: document.getElementById('btn-timer-pause'),
        btnTimerReset: document.getElementById('btn-timer-reset'),
        btnUndo: document.getElementById('btn-undo'),
        btnResetRace: document.getElementById('btn-reset-race'),
        selectRaceLength: document.getElementById('select-race-length'),

        // Role Login Modal
        roleLoginModal: document.getElementById('role-login-modal'),
        btnCloseRoleModal: document.getElementById('btn-close-role-modal'),
        selectRoleType: document.getElementById('select-role-type'),
        passwordGroup: document.getElementById('password-group'),
        inputRolePassword: document.getElementById('input-role-password'),
        roleLoginError: document.getElementById('role-login-error'),
        btnConfirmRole: document.getElementById('btn-confirm-role'),
        btnCancelRole: document.getElementById('btn-cancel-role'),

        // PIN Modal
        pinModal: document.getElementById('pin-modal'),
        inputPin: document.getElementById('input-pin'),
        pinError: document.getElementById('pin-error'),
        btnSubmitPin: document.getElementById('btn-submit-pin'),
        btnCancelPin: document.getElementById('btn-cancel-pin'),

        // Shortcuts Modal
        shortcutsModal: document.getElementById('shortcuts-modal'),
        btnCloseShortcuts: document.getElementById('btn-close-shortcuts'),

        // Winner Overlay
        winnerModal: document.getElementById('winner-modal'),
        winnerTitle: document.getElementById('winner-title'),
        winnerCarDisplay: document.getElementById('winner-car-display'),
        btnWinnerReset: document.getElementById('btn-winner-reset'),
        btnWinnerClose: document.getElementById('btn-winner-close'),

        // Toast Container
        toastContainer: document.getElementById('toast-container')
    };

    let selectedAmount = 1;
    let autoAdvanceTimeout = null;

    // 3. Initialize Cross-Tab / Cross-Device Real-Time Sync Engine
    SyncEngine.init(function (syncEvent) {
        if (!syncEvent || !syncEvent.type) return;

        const state = GameState.getState();

        switch (syncEvent.type) {
            case 'CAR_MOVED':
                TrackRenderer.updateTeam(syncEvent.payload.team, state.raceLength, GameState.getRankings(), true);
                if (syncEvent.payload.steps > 0) {
                    SoundEngine.playCarMove();
                    SoundEngine.playPointAward();
                } else {
                    SoundEngine.playUndo();
                }
                updateDepartmentPlayerCard();
                if (syncEvent.payload.winner) {
                    showWinnerOverlay(syncEvent.payload.winner);
                }
                break;

            case 'QUESTION_CHANGED':
                displayQuestion(syncEvent.payload.index, false);
                showToast(`❓ Question switched to #${syncEvent.payload.index + 1}`);
                break;

            case 'ANSWER_FEEDBACK':
                highlightOptionCard(syncEvent.payload.optIndex, syncEvent.payload.isCorrect);
                if (syncEvent.payload.teamName) {
                    const statusText = syncEvent.payload.isCorrect ? '✅ CORRECT' : '❌ INCORRECT';
                    showToast(`${statusText}: ${syncEvent.payload.teamName} selected Option ${String.fromCharCode(65 + syncEvent.payload.optIndex)}`);
                }
                break;

            case 'TIMER_SYNC':
                if (syncEvent.payload.action === 'start') CountdownTimer.start();
                else if (syncEvent.payload.action === 'pause') CountdownTimer.pause();
                else if (syncEvent.payload.action === 'reset') CountdownTimer.reset();
                break;

            case 'RACE_RESET':
                TrackRenderer.renderAll(GameState.getTeams(), state.raceLength, GameState.getRankings());
                displayQuestion(0, false);
                CountdownTimer.reset();
                hideWinnerOverlay();
                updateDepartmentPlayerCard();
                showToast('🔄 Race reset across all screens');
                break;
        }
    });

    // 4. Render Initial State
    function renderInitialState() {
        const state = GameState.getState();
        
        TrackRenderer.renderAll(state.teams, state.raceLength, GameState.getRankings());
        updateSoundUI(state.soundEnabled);

        if (elements.selectRaceLength) {
            elements.selectRaceLength.value = state.raceLength;
        }

        renderGmTeamsList();
        renderQuestionOptionsSelect();
        displayQuestion(state.currentQuestionIndex, false);
        CountdownTimer.init(15, onTimerTick, onTimerFinish);

        applySessionRoleUI(GameState.getSessionRole());

        if (state.winner) {
            showWinnerOverlay(state.winner);
        }

        window.addEventListener('resize', () => {
            const currentState = GameState.getState();
            TrackRenderer.renderAll(currentState.teams, currentState.raceLength, GameState.getRankings());
        });
    }

    // 5. Apply Session Role UI Views
    function applySessionRoleUI(roleId) {
        GameState.setSessionRole(roleId);

        if (roleId === 'main_screen') {
            if (elements.labelRoleStatus) elements.labelRoleStatus.textContent = '📺 MAIN DISPLAY';
            if (elements.roleSubtitle) elements.roleSubtitle.textContent = 'YOUR DEPARTMENT. YOUR RIDE. YOUR RACE.';
            if (elements.deptPlayerCard) elements.deptPlayerCard.classList.add('hidden');
        } else if (roleId === 'gm') {
            if (elements.labelRoleStatus) elements.labelRoleStatus.textContent = '🎮 GAME MASTER';
            if (elements.roleSubtitle) elements.roleSubtitle.textContent = 'GAME MASTER CONTROL CENTER';
            if (elements.deptPlayerCard) elements.deptPlayerCard.classList.add('hidden');
            elements.gmDrawer.classList.remove('hidden');
        } else {
            // Department Role (it, finance, marketing, hr, operations)
            const team = GameState.getTeamById(roleId);
            if (team) {
                if (elements.labelRoleStatus) elements.labelRoleStatus.textContent = `🏎️ ${team.name} DEVICE`;
                if (elements.roleSubtitle) elements.roleSubtitle.textContent = `${team.name.toUpperCase()} DEPARTMENT DEVICE`;
                updateDepartmentPlayerCard();
                if (elements.deptPlayerCard) elements.deptPlayerCard.classList.remove('hidden');
            }
        }
        updateOptionCardsInteractivity();
    }

    function updateDepartmentPlayerCard() {
        const currentRole = GameState.getSessionRole();
        const team = GameState.getTeamById(currentRole);
        if (!team) return;

        if (elements.deptBanner) elements.deptBanner.style.borderLeftColor = team.color;
        if (elements.deptCarIcon) elements.deptCarIcon.textContent = team.car;
        if (elements.deptTitleName) {
            elements.deptTitleName.textContent = `${team.name} DEPARTMENT`;
            elements.deptTitleName.style.color = team.color;
        }
        if (elements.deptPosVal) elements.deptPosVal.textContent = team.position * 10;
    }

    function updateOptionCardsInteractivity() {
        const currentRole = GameState.getSessionRole();
        const isDept = currentRole && currentRole !== 'main_screen' && currentRole !== 'gm';

        elements.optionItems.forEach(item => {
            if (isDept || currentRole === 'gm') {
                item.classList.add('interactive');
            } else {
                item.classList.remove('interactive');
            }
        });
    }

    // 6. Toast Notification
    function showToast(message) {
        if (!elements.toastContainer) return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        elements.toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    // 7. Sound UI Update
    function updateSoundUI(enabled) {
        SoundEngine.toggleSound(enabled);
        const label = elements.btnSoundToggle.querySelector('.sound-label');
        const icon = elements.btnSoundToggle.querySelector('.icon-sound');
        if (enabled) {
            if (label) label.textContent = 'SOUND ON';
            if (icon) icon.textContent = '🔊';
        } else {
            if (label) label.textContent = 'SOUND OFF';
            if (icon) icon.textContent = '🔇';
        }
    }

    // 8. GM Drawer Team Quick Controls Render
    function renderGmTeamsList() {
        if (!elements.gmTeamsList) return;
        elements.gmTeamsList.innerHTML = '';

        GameState.getTeams().forEach(team => {
            const row = document.createElement('div');
            row.className = 'gm-team-row';
            row.style.borderLeftColor = team.color;

            row.innerHTML = `
                <div class="gm-team-name" style="color: ${team.color}">
                    <span>${team.car}</span>
                    <span>${team.name}</span>
                </div>
                <div class="gm-btn-group">
                    <button class="btn-step" data-team="${team.id}" data-steps="1">+1</button>
                    <button class="btn-step" data-team="${team.id}" data-steps="2">+2</button>
                    <button class="btn-step" data-team="${team.id}" data-steps="3">+3</button>
                    <button class="btn-step btn-minus" data-team="${team.id}" data-steps="-1">-1</button>
                </div>
            `;
            elements.gmTeamsList.appendChild(row);
        });

        elements.gmTeamsList.querySelectorAll('.btn-step').forEach(btn => {
            btn.addEventListener('click', function () {
                const teamId = this.getAttribute('data-team');
                const steps = parseInt(this.getAttribute('data-steps'), 10);
                advanceTeam(teamId, steps);
            });
        });
    }

    // 9. Core Team Movement Function with Sync Broadcast
    function advanceTeam(teamId, steps) {
        const result = GameState.moveTeam(teamId, steps);
        if (!result) return;

        const state = GameState.getState();
        
        if (steps > 0) {
            SoundEngine.playCarMove();
            SoundEngine.playPointAward();
        } else {
            SoundEngine.playUndo();
        }

        TrackRenderer.updateTeam(result.team, state.raceLength, GameState.getRankings(), true);
        updateDepartmentPlayerCard();

        SyncEngine.broadcast('CAR_MOVED', {
            team: result.team,
            steps: steps,
            winner: result.winner
        });

        showToast(`🏎️ ${result.team.name} advanced +${steps} step(s)!`);

        if (result.winner) {
            setTimeout(() => {
                showWinnerOverlay(result.winner);
            }, 600);
        }
    }

    // 10. Interactive Answer Validation & Auto Next Question Progression
    function handleOptionClick(optIndex) {
        const currentRole = GameState.getSessionRole();
        const currentQIndex = GameState.getState().currentQuestionIndex;
        const isCorrect = QuestionsManager.checkAnswer(currentQIndex, optIndex);
        const team = GameState.getTeamById(currentRole);

        highlightOptionCard(optIndex, isCorrect);

        SyncEngine.broadcast('ANSWER_FEEDBACK', {
            optIndex: optIndex,
            isCorrect: isCorrect,
            teamName: team ? team.name : (currentRole === 'gm' ? 'Game Master' : null)
        });

        if (isCorrect) {
            SoundEngine.playPointAward();
            if (team) {
                advanceTeam(team.id, 1);
                showToast(`🎉 CORRECT! 1 Point awarded to ${team.name} (+1 Step 🏎️)`);
            } else {
                showToast(`🎉 CORRECT ANSWER!`);
            }

            // AUTO-PROGRESSION TO NEXT QUESTION AFTER 1.5 SECONDS
            if (autoAdvanceTimeout) clearTimeout(autoAdvanceTimeout);
            autoAdvanceTimeout = setTimeout(() => {
                const nextIndex = GameState.getState().currentQuestionIndex + 1;
                if (nextIndex < QuestionsManager.getQuestionCount()) {
                    displayQuestion(nextIndex, true);
                    CountdownTimer.reset();
                    showToast(`➡️ Automatically progressed to Challenge #${nextIndex + 1}`);
                }
            }, 1500);

        } else {
            SoundEngine.playWrongAnswer();
            showToast(`❌ INCORRECT! Try another option or next challenge.`);
        }
    }

    function highlightOptionCard(optIndex, isCorrect) {
        elements.optionItems.forEach((item, idx) => {
            item.classList.remove('correct', 'incorrect');
            if (idx === optIndex) {
                if (isCorrect) {
                    item.classList.add('correct');
                } else {
                    item.classList.add('incorrect');
                    setTimeout(() => item.classList.remove('incorrect'), 1200);
                }
            }
        });
    }

    function formatOptionHtml(optText) {
        if (!optText) return '-';
        if (optText.includes(' | ')) {
            const parts = optText.split(' | ');
            return `<span class="opt-en">${parts[0]}</span><span class="opt-divider">|</span><span class="opt-ar" dir="rtl">${parts[1]}</span>`;
        }
        return optText;
    }

    // 11. Questions Renderer & Navigation
    function renderQuestionOptionsSelect() {
        if (!elements.selectQuestion) return;
        elements.selectQuestion.innerHTML = '';
        const questions = QuestionsManager.getQuestions();

        questions.forEach((q, idx) => {
            const opt = document.createElement('option');
            opt.value = idx;
            const previewEn = q.questionEn ? q.questionEn.substring(0, 22) : (q.category || '');
            const previewAr = q.questionAr ? q.questionAr.substring(0, 18) : '';
            opt.textContent = `#${q.id}: ${previewEn}... | ${previewAr}...`;
            elements.selectQuestion.appendChild(opt);
        });
    }

    function displayQuestion(index, broadcast = true) {
        const q = QuestionsManager.getQuestionByIndex(index);
        if (!q) return;

        GameState.setQuestionIndex(index);

        elements.optionItems.forEach(item => item.classList.remove('correct', 'incorrect'));

        if (elements.challengeBadge) {
            elements.challengeBadge.textContent = `CHALLENGE ${index + 1} / ${QuestionsManager.getQuestionCount()}`;
        }
        if (elements.categoryBadge) {
            elements.categoryBadge.textContent = q.category || `${q.categoryEn || ''} | ${q.categoryAr || ''}`;
        }
        if (elements.challengePointsBadge) {
            elements.challengePointsBadge.textContent = (index === QuestionsManager.getQuestionCount() - 1) ? '+30 PTS FINALE' : '+10 PTS QUIZ';
        }
        if (elements.questionText) {
            elements.questionText.innerHTML = `
                <div class="question-line-en">${q.questionEn || q.question}</div>
                <div class="question-line-ar" dir="rtl">${q.questionAr || ''}</div>
            `;
        }

        if (elements.optA) elements.optA.innerHTML = formatOptionHtml(q.options[0]);
        if (elements.optB) elements.optB.innerHTML = formatOptionHtml(q.options[1]);
        if (elements.optC) elements.optC.innerHTML = formatOptionHtml(q.options[2]);
        if (elements.optD) elements.optD.innerHTML = formatOptionHtml(q.options[3]);

        if (elements.selectQuestion) {
            elements.selectQuestion.value = index;
        }

        if (broadcast) {
            SyncEngine.broadcast('QUESTION_CHANGED', { index: index });
        }
    }

    // 12. Countdown Timer Callbacks
    function onTimerTick(remaining) {
        if (elements.timerValue) {
            elements.timerValue.textContent = remaining;
        }
        if (elements.timerBox) {
            if (remaining <= 5) {
                elements.timerBox.classList.add('warning');
            } else {
                elements.timerBox.classList.remove('warning');
            }
        }
    }

    function onTimerFinish() {
        if (elements.timerValue) {
            elements.timerValue.textContent = "0";
        }
        showToast("⏰ TIME'S UP!");
    }

    // 13. Winner Overlay Display
    function showWinnerOverlay(winnerTeam) {
        if (!elements.winnerModal) return;

        elements.winnerTitle.textContent = `${winnerTeam.name} WINS!`;
        elements.winnerTitle.style.background = `linear-gradient(135deg, ${winnerTeam.color} 0%, #ffd700 100%)`;
        elements.winnerTitle.style.webkitBackgroundClip = 'text';

        elements.winnerCarDisplay.textContent = winnerTeam.car;
        elements.winnerModal.classList.remove('hidden');

        SoundEngine.playVictoryFanfare();
        ConfettiEngine.burst();
    }

    function hideWinnerOverlay() {
        if (elements.winnerModal) {
            elements.winnerModal.classList.add('hidden');
            ConfettiEngine.stop();
        }
    }

    // 14. Role Login Modal Logic
    function openRoleLoginModal() {
        elements.roleLoginModal.classList.remove('hidden');
        elements.inputRolePassword.value = '';
        elements.roleLoginError.classList.add('hidden');
        updatePasswordGroupVisibility();
    }

    function updatePasswordGroupVisibility() {
        const role = elements.selectRoleType.value;
        if (role === 'main_screen') {
            elements.passwordGroup.classList.add('hidden');
        } else {
            elements.passwordGroup.classList.remove('hidden');
            elements.inputRolePassword.focus();
        }
    }

    function submitRoleLogin() {
        const role = elements.selectRoleType.value;
        if (role === 'main_screen') {
            applySessionRoleUI('main_screen');
            elements.roleLoginModal.classList.add('hidden');
            showToast('📺 Switched to Main Display Screen mode');
            return;
        }

        const password = elements.inputRolePassword.value.trim();
        const isValid = GameState.authenticateRole(role, password);

        if (isValid) {
            applySessionRoleUI(role);
            elements.roleLoginModal.classList.add('hidden');
            elements.roleLoginError.classList.add('hidden');
            SoundEngine.playClick();
            showToast(`🔓 Authenticated as ${role.toUpperCase()}`);
        } else {
            elements.roleLoginError.classList.remove('hidden');
            elements.inputRolePassword.value = '';
            elements.inputRolePassword.focus();
        }
    }

    // 15. Bind UI Event Handlers
    elements.optionItems.forEach((item, idx) => {
        item.setAttribute('data-opt-idx', idx);
        item.addEventListener('click', function () {
            handleOptionClick(idx);
        });
    });

    // Login & Role Switcher
    elements.btnLoginToggle.addEventListener('click', openRoleLoginModal);
    elements.btnCloseRoleModal.addEventListener('click', function () {
        elements.roleLoginModal.classList.add('hidden');
    });
    elements.btnCancelRole.addEventListener('click', function () {
        elements.roleLoginModal.classList.add('hidden');
    });
    elements.selectRoleType.addEventListener('change', updatePasswordGroupVisibility);
    elements.btnConfirmRole.addEventListener('click', submitRoleLogin);
    elements.inputRolePassword.addEventListener('keyup', function (e) {
        if (e.key === 'Enter') submitRoleLogin();
    });

    // Sound & Shortcuts
    elements.btnSoundToggle.addEventListener('click', function () {
        const newState = !SoundEngine.isSoundEnabled();
        GameState.setSoundEnabled(newState);
        updateSoundUI(newState);
        SoundEngine.playClick();
    });

    elements.btnShortcutsToggle.addEventListener('click', function () {
        ShortcutsHandler.toggleModal();
        SoundEngine.playClick();
    });

    if (elements.btnCloseShortcuts) {
        elements.btnCloseShortcuts.addEventListener('click', function () {
            ShortcutsHandler.hideModal();
        });
    }

    elements.btnGmToggle.addEventListener('click', function () {
        if (GameState.getSessionRole() === 'gm') {
            elements.gmDrawer.classList.toggle('hidden');
        } else {
            openRoleLoginModal();
            elements.selectRoleType.value = 'gm';
            updatePasswordGroupVisibility();
        }
        SoundEngine.playClick();
    });

    elements.btnCloseGm.addEventListener('click', function () {
        elements.gmDrawer.classList.add('hidden');
    });

    // Amount selector in Team Code Entry
    document.querySelectorAll('.btn-amount').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-amount').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            selectedAmount = parseInt(this.getAttribute('data-amount'), 10);
            SoundEngine.playClick();
        });
    });

    // Execute Team Code Entry
    elements.btnExecuteCode.addEventListener('click', function () {
        const code = elements.inputTeamCode.value.trim();
        if (!code) {
            showToast('⚠️ Please enter a team code (e.g. IT, FIN, MKT, HR, OPS)');
            return;
        }

        const team = GameState.getTeamByCode(code);
        if (team) {
            advanceTeam(team.id, selectedAmount);
            elements.inputTeamCode.value = '';
        } else {
            showToast(`❌ Unknown team code "${code}". Use IT, FIN, MKT, HR, OPS.`);
        }
    });

    elements.inputTeamCode.addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            elements.btnExecuteCode.click();
        }
    });

    // Question Navigation
    elements.btnPrevQ.addEventListener('click', function () {
        const current = GameState.getState().currentQuestionIndex;
        if (current > 0) {
            displayQuestion(current - 1, true);
            SoundEngine.playClick();
        }
    });

    elements.btnNextQ.addEventListener('click', function () {
        const current = GameState.getState().currentQuestionIndex;
        if (current < QuestionsManager.getQuestionCount() - 1) {
            displayQuestion(current + 1, true);
            SoundEngine.playClick();
        }
    });

    elements.selectQuestion.addEventListener('change', function () {
        displayQuestion(parseInt(this.value, 10), true);
        SoundEngine.playClick();
    });

    // Timer Controls with Sync
    elements.btnTimerStart.addEventListener('click', function () {
        CountdownTimer.start();
        SyncEngine.broadcast('TIMER_SYNC', { action: 'start' });
        SoundEngine.playClick();
        showToast('▶ Timer started');
    });

    elements.btnTimerPause.addEventListener('click', function () {
        CountdownTimer.pause();
        SyncEngine.broadcast('TIMER_SYNC', { action: 'pause' });
        SoundEngine.playClick();
        showToast('⏸ Timer paused');
    });

    elements.btnTimerReset.addEventListener('click', function () {
        CountdownTimer.reset();
        SyncEngine.broadcast('TIMER_SYNC', { action: 'reset' });
        SoundEngine.playClick();
        showToast('🔄 Timer reset');
    });

    // Undo & Reset Buttons
    elements.btnUndo.addEventListener('click', function () {
        const undone = GameState.undoLastMove();
        if (undone) {
            SoundEngine.playUndo();
            TrackRenderer.updateTeam(undone.team, GameState.getState().raceLength, GameState.getRankings(), false);
            SyncEngine.broadcast('CAR_MOVED', { team: undone.team, steps: 0 });
            showToast(`↩️ Undid last move for ${undone.team.name}`);
        } else {
            showToast('ℹ️ No moves to undo');
        }
    });

    elements.btnResetRace.addEventListener('click', function () {
        if (confirm('Are you sure you want to reset the entire race? All car positions will return to START.')) {
            GameState.resetRace();
            TrackRenderer.renderAll(GameState.getTeams(), GameState.getState().raceLength, GameState.getRankings());
            displayQuestion(0, false);
            CountdownTimer.reset();
            hideWinnerOverlay();
            updateDepartmentPlayerCard();
            SyncEngine.broadcast('RACE_RESET', {});
            SoundEngine.playUndo();
            showToast('🔄 Race successfully reset to START');
        }
    });

    // Settings: Race Length
    elements.selectRaceLength.addEventListener('change', function () {
        const newLen = parseInt(this.value, 10);
        GameState.setRaceLength(newLen);
        TrackRenderer.renderAll(GameState.getTeams(), newLen, GameState.getRankings());
        SoundEngine.playClick();
        showToast(`📏 Race length set to ${newLen} steps`);
    });

    // Winner Overlay Buttons
    elements.btnWinnerReset.addEventListener('click', function () {
        hideWinnerOverlay();
        elements.btnResetRace.click();
    });

    elements.btnWinnerClose.addEventListener('click', function () {
        hideWinnerOverlay();
    });

    // 16. Initialize Keyboard Shortcuts Handler
    ShortcutsHandler.init({
        onTeamAdvance: (teamId, steps) => advanceTeam(teamId, steps),
        onNextQuestion: () => elements.btnNextQ.click(),
        onPrevQuestion: () => elements.btnPrevQ.click(),
        onToggleTimer: () => {
            if (CountdownTimer.isTimerRunning()) {
                elements.btnTimerPause.click();
            } else {
                elements.btnTimerStart.click();
            }
        },
        onResetTimer: () => elements.btnTimerReset.click(),
        onUndo: () => elements.btnUndo.click(),
        onToggleGM: () => elements.btnGmToggle.click(),
        onToggleSound: () => elements.btnSoundToggle.click(),
        onCloseOverlays: () => {
            elements.gmDrawer.classList.add('hidden');
            elements.pinModal.classList.add('hidden');
            elements.roleLoginModal.classList.add('hidden');
            ShortcutsHandler.hideModal();
            hideWinnerOverlay();
        }
    });

    // 17. Kick off Application
    renderInitialState();
});
