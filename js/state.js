/**
 * Game State Management & Department Passwords Engine
 */

const GameState = (function () {
    const STORAGE_KEY = 'corporate_race_game_state_v2';
    const SESSION_ROLE_KEY = 'corporate_race_session_role';

    // Initial default teams configuration with department passwords
    const initialTeams = [
        {
            id: 'it',
            name: 'IT',
            code: 'IT',
            password: '1001',
            color: '#38bdf8', // Baby Blue
            car: '🏎️',
            position: 0
        },
        {
            id: 'finance',
            name: 'Finance',
            code: 'FIN',
            password: '2002',
            color: '#0284c7', // Blue
            car: '🚙',
            position: 0
        },
        {
            id: 'marketing',
            name: 'Marketing',
            code: 'MKT',
            password: '3003',
            color: '#f97316', // Orange
            car: '🏎️',
            position: 0
        },
        {
            id: 'hr',
            name: 'HR',
            code: 'HR',
            password: '4004',
            color: '#ffffff', // White
            car: '🚕',
            position: 0
        },
        {
            id: 'operations',
            name: 'Operations',
            code: 'OPS',
            password: '5005',
            color: '#fb923c', // Warm Orange
            car: '🚗',
            position: 0
        }
    ];

    let state = {
        teams: JSON.parse(JSON.stringify(initialTeams)),
        raceLength: 15,
        currentQuestionIndex: 0,
        timerDuration: 15,
        soundEnabled: true,
        winner: null,
        history: [],
        gmPin: '1234'
    };

    function loadFromStorage() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                const parsed = JSON.parse(saved);
                state = Object.assign({}, state, parsed);
                // Ensure team colors follow the current palette
                if (state.teams && Array.isArray(state.teams)) {
                    state.teams.forEach(t => {
                        const defaultTeam = initialTeams.find(it => it.id === t.id);
                        if (defaultTeam) {
                            t.color = defaultTeam.color;
                        }
                    });
                }
            }
        } catch (e) {
            console.warn('Failed to load state from localStorage', e);
        }
    }

    function saveToStorage() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            console.warn('Failed to save state to localStorage', e);
        }
    }

    loadFromStorage();

    return {
        getState: function () {
            return state;
        },

        getTeams: function () {
            return state.teams;
        },

        getTeamById: function (id) {
            if (!id) return null;
            return state.teams.find(t => t.id === id.toLowerCase());
        },

        getTeamByCode: function (code) {
            if (!code) return null;
            const cleanCode = code.trim().toUpperCase();
            const codeMap = {
                'IT': 'it',
                'FIN': 'finance',
                'FINANCE': 'finance',
                'MKT': 'marketing',
                'MARKETING': 'marketing',
                'HR': 'hr',
                'OPS': 'operations',
                'OPERATIONS': 'operations'
            };
            const teamId = codeMap[cleanCode];
            return teamId ? this.getTeamById(teamId) : null;
        },

        // Department & Role Password Authenticator
        authenticateRole: function (roleId, password) {
            const cleanPass = (password || '').trim();
            if (roleId === 'gm') {
                return cleanPass === state.gmPin || cleanPass === '1234';
            }

            const team = this.getTeamById(roleId);
            if (team) {
                return cleanPass === team.password;
            }
            return false;
        },

        // Active Session Role Getter/Setter
        getSessionRole: function () {
            return sessionStorage.getItem(SESSION_ROLE_KEY) || 'main_screen';
        },

        setSessionRole: function (roleId) {
            sessionStorage.setItem(SESSION_ROLE_KEY, roleId);
        },

        moveTeam: function (teamId, steps) {
            const team = this.getTeamById(teamId);
            if (!team) return null;

            const oldPos = team.position;
            let newPos = Math.max(0, team.position + steps);
            newPos = Math.min(state.raceLength, newPos);

            team.position = newPos;

            state.history.push({
                teamId: team.id,
                teamName: team.name,
                oldPos: oldPos,
                newPos: newPos,
                steps: steps,
                timestamp: Date.now()
            });

            if (newPos >= state.raceLength && !state.winner) {
                state.winner = team;
            }

            saveToStorage();
            return { team, oldPos, newPos, steps, winner: state.winner };
        },

        undoLastMove: function () {
            if (state.history.length === 0) return null;

            const lastMove = state.history.pop();
            const team = this.getTeamById(lastMove.teamId);
            if (team) {
                team.position = lastMove.oldPos;
                if (state.winner && state.winner.id === team.id && team.position < state.raceLength) {
                    state.winner = null;
                }
            }

            saveToStorage();
            return { team, restoredPos: lastMove.oldPos };
        },

        resetRace: function () {
            state.teams.forEach(t => t.position = 0);
            state.history = [];
            state.winner = null;
            state.currentQuestionIndex = 0;
            saveToStorage();
        },

        setQuestionIndex: function (idx) {
            state.currentQuestionIndex = idx;
            saveToStorage();
        },

        setRaceLength: function (len) {
            state.raceLength = parseInt(len, 10) || 15;
            saveToStorage();
        },

        setSoundEnabled: function (enabled) {
            state.soundEnabled = enabled;
            saveToStorage();
        },

        getRankings: function () {
            return [...state.teams].sort((a, b) => b.position - a.position);
        },

        save: function () {
            saveToStorage();
        }
    };
})();
