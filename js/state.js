/**
 * Game State Management & API Synchronization Engine
 * Summer Road Trip - Native PHP & MySQL Backend Connector
 */

const GameState = (function () {
    let state = {
        user: null,
        activeWeek: null,
        allWeeks: [],
        departments: [],
        raceLength: 15,
        weeklyWinners: {},
        overallLeader: null,
        finaleRevealed: false,
        finaleContent: {},
        questions: [],
        currentQuestionIndex: 0,
        soundEnabled: true,
        isProjectorMode: false
    };

    let onStateChangeListeners = [];

    function notifyListeners() {
        onStateChangeListeners.forEach(cb => {
            if (typeof cb === 'function') cb(state);
        });
    }

    return {
        // Fetch fresh state from PHP & MySQL API
        fetchFreshState: async function () {
            try {
                const res = await fetch('api/game_state.php');
                const json = await res.json();
                if (json && json.success && json.data) {
                    const d = json.data;
                    state.activeWeek = d.active_week;
                    state.allWeeks = d.all_weeks || [];
                    state.departments = d.departments || [];
                    state.raceLength = d.race_length || 15;
                    state.weeklyWinners = d.weekly_winners || {};
                    state.overallLeader = d.overall_leader || null;
                    state.finaleRevealed = d.finale_revealed || false;
                    state.finaleContent = {
                        titleAr: d.finale_surprise_title_ar,
                        titleEn: d.finale_surprise_title_en,
                        msgAr: d.finale_surprise_message_ar,
                        msgEn: d.finale_surprise_message_en
                    };
                    state.topDepartments = d.top_departments || [];
                    state.topUsers = d.top_users || [];
                    if (d.user) {
                        state.user = d.user;
                    }
                    notifyListeners();
                    return state;
                }
            } catch (e) {
                console.warn('Failed to fetch game state from backend', e);
            }
            return state;
        },

        getState: function () {
            return state;
        },

        getTopDepartments: function () {
            return state.topDepartments || [];
        },

        getTopUsers: function () {
            return state.topUsers || [];
        },

        getUser: function () {
            return state.user;
        },

        setUser: function (user) {
            state.user = user;
            notifyListeners();
        },

        getDepartments: function () {
            return state.departments;
        },

        getDepartmentById: function (id) {
            if (!id) return null;
            return state.departments.find(d => d.id === id.toLowerCase());
        },

        getActiveWeek: function () {
            return state.activeWeek;
        },

        getAllWeeks: function () {
            return state.allWeeks;
        },

        getQuestions: function () {
            return state.questions;
        },

        setQuestions: function (questions) {
            state.questions = questions;
            notifyListeners();
        },

        getCurrentQuestionIndex: function () {
            return state.currentQuestionIndex;
        },

        setCurrentQuestionIndex: function (idx) {
            state.currentQuestionIndex = idx;
            notifyListeners();
        },

        getRankings: function () {
            return [...state.departments].sort((a, b) => (b.position * 100 + b.total_points) - (a.position * 100 + a.total_points));
        },

        isSoundEnabled: function () {
            return state.soundEnabled;
        },

        setSoundEnabled: function (val) {
            state.soundEnabled = !!val;
            notifyListeners();
        },

        onStateChange: function (callback) {
            if (typeof callback === 'function') {
                onStateChangeListeners.push(callback);
            }
        }
    };
})();
