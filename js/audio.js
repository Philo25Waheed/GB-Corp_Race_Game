/**
 * Web Audio API Sound Synthesizer Engine
 * Provides rich, zero-external-dependency sound effects for the race game.
 */

const SoundEngine = (function () {
    let audioCtx = null;
    let soundEnabled = true;

    function getAudioContext() {
        if (!audioCtx) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                audioCtx = new AudioContextClass();
            }
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    return {
        toggleSound: function (enable) {
            if (enable !== undefined) {
                soundEnabled = !!enable;
            } else {
                soundEnabled = !soundEnabled;
            }
            return soundEnabled;
        },

        isSoundEnabled: function () {
            return soundEnabled;
        },

        // Car engine movement rev sound
        playCarMove: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(120, now);
            osc.frequency.exponentialRampToValueAtTime(380, now + 0.15);
            osc.frequency.exponentialRampToValueAtTime(90, now + 0.35);

            gain.gain.setValueAtTime(0.25, now);
            gain.gain.linearRampToValueAtTime(0.01, now + 0.35);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.35);
        },

        // Point awarded chime sound
        playPointAward: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const freqs = [523.25, 659.25, 783.99, 1046.50]; // C5 - E5 - G5 - C6
            freqs.forEach((freq, idx) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(freq, now + idx * 0.06);

                gain.gain.setValueAtTime(0.2, now + idx * 0.06);
                gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.06 + 0.25);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(now + idx * 0.06);
                osc.stop(now + idx * 0.06 + 0.25);
            });
        },

        // Wrong answer error buzzer sound
        playWrongAnswer: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(150, now);
            osc.frequency.exponentialRampToValueAtTime(80, now + 0.25);

            gain.gain.setValueAtTime(0.3, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.25);
        },

        // Countdown timer tick sound
        playTimerTick: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'square';
            osc.frequency.setValueAtTime(800, now);

            gain.gain.setValueAtTime(0.1, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.05);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.05);
        },

        // Timer finished alarm sound
        playTimerFinish: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            [0, 0.12, 0.24].forEach((delay) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'triangle';
                osc.frequency.setValueAtTime(880, now + delay);

                gain.gain.setValueAtTime(0.3, now + delay);
                gain.gain.exponentialRampToValueAtTime(0.001, now + delay + 0.1);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(now + delay);
                osc.stop(now + delay + 0.1);
            });
        },

        // Winner fanfare celebration
        playVictoryFanfare: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const notes = [
                { note: 523.25, duration: 0.15 },
                { note: 659.25, duration: 0.15 },
                { note: 783.99, duration: 0.15 },
                { note: 1046.50, duration: 0.4 },
                { note: 880.00, duration: 0.2 },
                { note: 1046.50, duration: 0.6 }
            ];

            let timeOffset = 0;
            notes.forEach((n) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'triangle';
                osc.frequency.setValueAtTime(n.note, now + timeOffset);

                gain.gain.setValueAtTime(0.3, now + timeOffset);
                gain.gain.exponentialRampToValueAtTime(0.001, now + timeOffset + n.duration);

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start(now + timeOffset);
                osc.stop(now + timeOffset + n.duration);

                timeOffset += n.duration * 0.9;
            });
        },

        // Undo move sound
        playUndo: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(300, now);
            osc.frequency.exponentialRampToValueAtTime(100, now + 0.2);

            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.2);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.2);
        },

        // Standard UI click
        playClick: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(600, now);

            gain.gain.setValueAtTime(0.08, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.04);
        }
    };
})();
