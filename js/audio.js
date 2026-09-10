/**
 * Web Audio API Sound Synthesizer Engine
 * Provides rich, zero-external-dependency sound effects for the race game.
 * Supports both AudioEngine and SoundEngine global namespaces.
 */

const AudioEngine = (function () {
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
            audioCtx.resume().catch(() => {});
        }
        return audioCtx;
    }

    // Auto-unlock AudioContext on first user interaction
    function initAudioUnlock() {
        ['click', 'touchstart', 'mousedown', 'keydown'].forEach(evtType => {
            document.addEventListener(evtType, function unlockAudio() {
                if (!audioCtx) {
                    getAudioContext();
                } else if (audioCtx.state === 'suspended') {
                    audioCtx.resume().catch(() => {});
                }
            }, { once: false, passive: true });
        });
    }
    initAudioUnlock();

    const engine = {
        toggleSound: function (enable) {
            if (enable !== undefined) {
                soundEnabled = !!enable;
            } else {
                soundEnabled = !soundEnabled;
            }
            return soundEnabled;
        },

        setEnabled: function (enable) {
            soundEnabled = !!enable;
            return soundEnabled;
        },

        isSoundEnabled: function () {
            return soundEnabled;
        },

        // Universal sound player router
        play: function (soundName) {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (ctx && ctx.state === 'suspended') {
                ctx.resume().then(() => this._executePlay(soundName)).catch(() => this._executePlay(soundName));
            } else {
                this._executePlay(soundName);
            }
        },

        _executePlay: function (soundName) {
            switch (soundName) {
                case 'correct':
                case 'point':
                    this.playCorrect();
                    break;
                case 'wrong':
                case 'incorrect':
                    this.playWrongAnswer();
                    break;
                case 'timeout':
                    this.playTimerFinish();
                    break;
                case 'popper':
                    this.playPopperBurst();
                    break;
                case 'winner':
                case 'celebrate':
                case 'weekly_winner':
                    this.playVictoryFanfare();
                    this.playPopperBurst();
                    break;
                case 'move':
                case 'engine':
                case 'advance':
                    this.playCarMove();
                    break;
                case 'click':
                    this.playClick();
                    break;
                case 'join':
                    this.playPointAward();
                    break;
                case 'tick':
                    this.playTimerTick();
                    break;
                default:
                    this.playClick();
                    break;
            }
        },

        // 🌟 Loud & Joyful Correct Answer Sound (Crystal Major Arpeggio + High Sparkle)
        playCorrect: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            
            // Master gain node for rich volume
            const masterGain = ctx.createGain();
            masterGain.gain.setValueAtTime(0.45, now);
            masterGain.connect(ctx.destination);

            // Upbeat Major Chord progression: C5 (523.25), E5 (659.25), G5 (783.99), C6 (1046.50)
            const notes = [
                { f: 523.25, t: 0.00, dur: 0.22, type: 'triangle' },
                { f: 659.25, t: 0.08, dur: 0.22, type: 'triangle' },
                { f: 783.99, t: 0.16, dur: 0.25, type: 'triangle' },
                { f: 1046.50, t: 0.24, dur: 0.45, type: 'sine' },
                { f: 1318.51, t: 0.32, dur: 0.50, type: 'sine' } // High E6 sparkle
            ];

            notes.forEach(n => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = n.type;
                osc.frequency.setValueAtTime(n.f, now + n.t);

                // Attack and smooth exponential decay
                gain.gain.setValueAtTime(0.001, now + n.t);
                gain.gain.linearRampToValueAtTime(0.5, now + n.t + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, now + n.t + n.dur);

                osc.connect(gain);
                gain.connect(masterGain);

                osc.start(now + n.t);
                osc.stop(now + n.t + n.dur + 0.05);
            });
        },

        // Point awarded chime sound
        playPointAward: function () {
            this.playCorrect();
        },

        // ❌ Classic Punchy Wrong Answer Buzzer (Dual-Tone Descending Sawtooth Buzz)
        playWrongAnswer: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;

            const masterGain = ctx.createGain();
            masterGain.gain.setValueAtTime(0.4, now);
            masterGain.connect(ctx.destination);

            // Two distinct error buzzer pulses
            const pulses = [
                { startF: 220, endF: 130, delay: 0.00, dur: 0.18 },
                { startF: 160, endF: 85,  delay: 0.14, dur: 0.28 }
            ];

            pulses.forEach(p => {
                // Sawtooth oscillator for classic arcade bite
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sawtooth';
                osc1.frequency.setValueAtTime(p.startF, now + p.delay);
                osc1.frequency.exponentialRampToValueAtTime(p.endF, now + p.delay + p.dur);

                gain1.gain.setValueAtTime(0.4, now + p.delay);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + p.delay + p.dur);

                osc1.connect(gain1);
                gain1.connect(masterGain);

                osc1.start(now + p.delay);
                osc1.stop(now + p.delay + p.dur + 0.02);

                // Low square wave undertone for extra punch
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'square';
                osc2.frequency.setValueAtTime(p.startF * 0.5, now + p.delay);
                osc2.frequency.exponentialRampToValueAtTime(p.endF * 0.5, now + p.delay + p.dur);

                gain2.gain.setValueAtTime(0.2, now + p.delay);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + p.delay + p.dur);

                osc2.connect(gain2);
                gain2.connect(masterGain);

                osc2.start(now + p.delay);
                osc2.stop(now + p.delay + p.dur + 0.02);
            });
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

            gain.gain.setValueAtTime(0.08, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.04);

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now);
            osc.stop(now + 0.04);
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

        // 💥 Realistic Party Popper Pop & Sparkle Blast
        playPopperBurst: function () {
            if (!soundEnabled) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;

            // 1. Popper Pop Shockwave (Filtered noise burst)
            try {
                const bufferSize = Math.floor(ctx.sampleRate * 0.08);
                const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
                const data = buffer.getChannelData(0);
                for (let i = 0; i < bufferSize; i++) {
                    data[i] = (Math.random() * 2 - 1) * Math.exp(-i / (ctx.sampleRate * 0.015));
                }

                const noise = ctx.createBufferSource();
                noise.buffer = buffer;

                const filter = ctx.createBiquadFilter();
                filter.type = 'lowpass';
                filter.frequency.setValueAtTime(1400, now);
                filter.frequency.exponentialRampToValueAtTime(120, now + 0.08);

                const noiseGain = ctx.createGain();
                noiseGain.gain.setValueAtTime(0.75, now);
                noiseGain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);

                noise.connect(filter);
                filter.connect(noiseGain);
                noiseGain.connect(ctx.destination);

                noise.start(now);
            } catch (e) {}

            // 2. Rising Party Whistle / Spark
            const whistle = ctx.createOscillator();
            const whistleGain = ctx.createGain();
            whistle.type = 'sine';
            whistle.frequency.setValueAtTime(320, now);
            whistle.frequency.exponentialRampToValueAtTime(1200, now + 0.12);

            whistleGain.gain.setValueAtTime(0.35, now);
            whistleGain.gain.exponentialRampToValueAtTime(0.001, now + 0.22);

            whistle.connect(whistleGain);
            whistleGain.connect(ctx.destination);

            whistle.start(now);
            whistle.stop(now + 0.22);

            // 3. Shimmering high chimes
            [0.08, 0.14, 0.22].forEach((delay, idx) => {
                const chime = ctx.createOscillator();
                const chimeGain = ctx.createGain();
                chime.type = 'triangle';
                chime.frequency.setValueAtTime(1200 + idx * 400, now + delay);

                chimeGain.gain.setValueAtTime(0.2, now + delay);
                chimeGain.gain.exponentialRampToValueAtTime(0.001, now + delay + 0.15);

                chime.connect(chimeGain);
                chimeGain.connect(ctx.destination);

                chime.start(now + delay);
                chime.stop(now + delay + 0.15);
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

    return engine;
})();

// Alias for SoundEngine compatibility
const SoundEngine = AudioEngine;
window.AudioEngine = AudioEngine;
window.SoundEngine = SoundEngine;
