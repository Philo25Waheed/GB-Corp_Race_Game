/**
 * Keyboard Shortcuts Handler Engine
 */

const ShortcutsHandler = (function () {
    let shortcutsModal = null;

    return {
        init: function (callbacks) {
            shortcutsModal = document.getElementById('shortcuts-modal');

            document.addEventListener('keydown', (e) => {
                // Ignore shortcuts if typing inside text input or password field
                const activeEl = document.activeElement;
                if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'SELECT')) {
                    if (e.key === 'Escape') {
                        activeEl.blur();
                    }
                    return;
                }

                const key = e.key.toUpperCase();

                switch (e.key) {
                    case '1':
                        if (callbacks.onTeamAdvance) callbacks.onTeamAdvance('it', 1);
                        break;
                    case '2':
                        if (callbacks.onTeamAdvance) callbacks.onTeamAdvance('finance', 1);
                        break;
                    case '3':
                        if (callbacks.onTeamAdvance) callbacks.onTeamAdvance('marketing', 1);
                        break;
                    case '4':
                        if (callbacks.onTeamAdvance) callbacks.onTeamAdvance('hr', 1);
                        break;
                    case '5':
                        if (callbacks.onTeamAdvance) callbacks.onTeamAdvance('operations', 1);
                        break;
                    case 'n':
                    case 'N':
                        if (callbacks.onNextQuestion) callbacks.onNextQuestion();
                        break;
                    case 'p':
                    case 'P':
                        if (callbacks.onPrevQuestion) callbacks.onPrevQuestion();
                        break;
                    case ' ':
                        e.preventDefault();
                        if (callbacks.onToggleTimer) callbacks.onToggleTimer();
                        break;
                    case 'r':
                    case 'R':
                        if (callbacks.onResetTimer) callbacks.onResetTimer();
                        break;
                    case 'u':
                    case 'U':
                        if (callbacks.onUndo) callbacks.onUndo();
                        break;
                    case 'm':
                    case 'M':
                        if (callbacks.onToggleGM) callbacks.onToggleGM();
                        break;
                    case 's':
                    case 'S':
                        if (callbacks.onToggleSound) callbacks.onToggleSound();
                        break;
                    case '?':
                    case 'h':
                    case 'H':
                        this.toggleModal();
                        break;
                    case 'Escape':
                        this.hideModal();
                        if (callbacks.onCloseOverlays) callbacks.onCloseOverlays();
                        break;
                }
            });
        },

        toggleModal: function () {
            if (shortcutsModal) {
                shortcutsModal.classList.toggle('hidden');
            }
        },

        hideModal: function () {
            if (shortcutsModal) {
                shortcutsModal.classList.add('hidden');
            }
        }
    };
})();
