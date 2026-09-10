/**
 * Realistic Canvas Confetti & Party Popper Celebration Engine
 * Summer Road Trip - Corporate Team Race
 * 
 * Features:
 * - High-speed radial party popper burst physics with air resistance (drag)
 * - True 3D tumbling rectangular confetti with pitch, roll and yaw
 * - Elegant twisting ribbons & streamers with sinusoidal air currents
 * - Shimmering metallic stars & shiny sequins
 * - Department-tailored celebration color theming
 * - Synchronized multi-cannon corner & center popper blasts
 */

const ConfettiEngine = (function () {
    let canvas = null;
    let ctx = null;
    let particles = [];
    let animationId = null;
    let continuousInterval = null;

    // Default celebratory luxury palette (Gold, Cyan, Orange, Emerald, Crimson, Electric Purple, Pearl White)
    const DEFAULT_PALETTE = [
        '#f59e0b', '#fbbf24', '#fde047', // Golds & Ambers
        '#38bdf8', '#0284c7', '#0ea5e9', // Cyans & Sky blues
        '#f97316', '#ea580c', '#ffedd5', // GB Corp Oranges & Coral
        '#10b981', '#34d399', '#059669', // Emeralds
        '#ec4899', '#f43f5e', '#a855f7', // Magentas & Electric Purples
        '#ffffff', '#e2e8f0', '#cbd5e1'  // Silver & Pearl Whites
    ];

    /**
     * Helper to generate color variants based on a department color
     */
    function buildDeptPalette(primaryColor) {
        if (!primaryColor) return DEFAULT_PALETTE;
        return [
            primaryColor,
            '#fbbf24', '#f59e0b', '#fde047', // Gold accents
            '#ffffff', '#e2e8f0',             // Silver accents
            primaryColor, primaryColor,
            '#38bdf8', '#f97316'              // Festive brand accents
        ];
    }

    /**
     * Base Particle with realistic aerodynamics
     */
    function Particle(x, y, angleRad, speed, palette, type = 'confetti') {
        this.x = x;
        this.y = y;
        this.type = type; // 'confetti' | 'ribbon' | 'star' | 'disc' | 'spark'
        
        // Initial explosive velocity
        this.vx = Math.cos(angleRad) * speed;
        this.vy = Math.sin(angleRad) * speed;

        // Aerodynamic constants
        this.drag = 0.94 + Math.random() * 0.035; // Air resistance slows down fast burst
        this.gravity = 0.22 + Math.random() * 0.16; // Gentle realistic fall
        
        // Lateral oscillation (wobble / air flutter)
        this.wobble = Math.random() * Math.PI * 2;
        this.wobbleSpeed = 0.06 + Math.random() * 0.08;
        this.wobbleRadius = 1.2 + Math.random() * 2.2;

        // 3D Rotation (Pitch & Yaw)
        this.rotation = Math.random() * 360;
        this.rotSpeed = (Math.random() - 0.5) * 8;
        this.tiltAngle = Math.random() * Math.PI * 2;
        this.tiltSpeed = 0.08 + Math.random() * 0.12;
        
        // Dimensions
        if (type === 'ribbon') {
            this.width = 6 + Math.random() * 4;
            this.height = 20 + Math.random() * 18;
        } else if (type === 'star') {
            this.size = 8 + Math.random() * 8;
        } else if (type === 'disc') {
            this.radius = 4 + Math.random() * 4;
        } else { // standard rectangular confetti flake
            this.width = 8 + Math.random() * 8;
            this.height = 6 + Math.random() * 7;
        }

        // Color & Opacity
        const colors = palette && palette.length > 0 ? palette : DEFAULT_PALETTE;
        this.color = colors[Math.floor(Math.random() * colors.length)];
        this.opacity = 1;
        this.decay = 0.003 + Math.random() * 0.004; // Lifespan in seconds
        this.sparklePhase = Math.random() * Math.PI * 2;
    }

    Particle.prototype.update = function () {
        // 1. Air drag decelerates initial popper explosion
        this.vx *= this.drag;
        this.vy *= this.drag;

        // 2. Gravity gradually pulls down
        this.vy += this.gravity;

        // 3. Sine-wave lateral flutter in air
        this.wobble += this.wobbleSpeed;
        const flutterX = Math.sin(this.wobble) * this.wobbleRadius;

        this.x += this.vx + flutterX;
        this.y += this.vy;

        // 4. 3D Tumbling & Tilting
        this.rotation += this.rotSpeed;
        this.tiltAngle += this.tiltSpeed;
        this.sparklePhase += 0.15;

        // 5. Gradual gentle fade out
        this.opacity -= this.decay;
    };

    Particle.prototype.draw = function (c) {
        if (this.opacity <= 0) return;
        c.save();
        c.translate(this.x, this.y);
        c.rotate((this.rotation * Math.PI) / 180);

        const currentOpacity = Math.max(0, Math.min(1, this.opacity));
        const tiltCos = Math.cos(this.tiltAngle);

        c.globalAlpha = currentOpacity;
        c.fillStyle = this.color;
        c.strokeStyle = this.color;

        if (this.type === 'disc') {
            // Shiny circular sequin with tilt
            c.beginPath();
            c.ellipse(0, 0, Math.max(1, this.radius * Math.abs(tiltCos)), this.radius, 0, 0, Math.PI * 2);
            c.fill();
        } else if (this.type === 'star') {
            // 4-pointed shimmering star
            const s = this.size * (0.8 + 0.3 * Math.sin(this.sparklePhase));
            c.beginPath();
            c.moveTo(0, -s);
            c.quadraticCurveTo(0, 0, s, 0);
            c.quadraticCurveTo(0, 0, 0, s);
            c.quadraticCurveTo(0, 0, -s, 0);
            c.quadraticCurveTo(0, 0, 0, -s);
            c.fill();
        } else if (this.type === 'ribbon') {
            // Curving twisting ribbon streamer
            const w = this.width * Math.abs(tiltCos);
            const h = this.height;
            c.beginPath();
            c.moveTo(-w / 2, -h / 2);
            c.bezierCurveTo(w, -h / 4, -w, h / 4, w / 2, h / 2);
            c.lineWidth = Math.max(2, w);
            c.stroke();
        } else {
            // Classic 3D tumbling rectangular confetti
            const w = this.width * Math.abs(tiltCos);
            const h = this.height;
            c.fillRect(-w / 2, -h / 2, Math.max(1, w), h);
        }

        c.restore();
    };

    function render() {
        if (!ctx || !canvas) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        for (let i = particles.length - 1; i >= 0; i--) {
            const p = particles[i];
            p.update();
            p.draw(ctx);

            if (p.opacity <= 0 || p.y > canvas.height + 80 || p.x < -100 || p.x > canvas.width + 100) {
                particles.splice(i, 1);
            }
        }

        if (particles.length > 0) {
            animationId = requestAnimationFrame(render);
        } else {
            stop();
        }
    }

    function stop() {
        if (animationId) {
            cancelAnimationFrame(animationId);
            animationId = null;
        }
        if (ctx && canvas) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    function ensureCanvas() {
        if (!canvas) {
            canvas = document.getElementById('global-confetti-canvas') || document.getElementById('confetti-canvas');
        }
        if (canvas) {
            ctx = canvas.getContext('2d');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
    }

    return {
        init: function (canvasElement) {
            if (canvasElement) {
                canvas = canvasElement;
            } else {
                ensureCanvas();
            }
            if (canvas) {
                ctx = canvas.getContext('2d');
                this.resize();
                window.addEventListener('resize', () => this.resize());
            }
        },

        resize: function () {
            if (!canvas) ensureCanvas();
            if (canvas) {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
        },

        /**
         * 💥 Directional Party Popper Cannon Blast
         * Launches realistic particles in a conical arc from origin (xPct, yPct)
         */
        popperBlast: function (xPct = 0.5, yPct = 0.7, angleDeg = -90, spreadDeg = 75, count = 90, palette = DEFAULT_PALETTE) {
            ensureCanvas();
            if (!canvas) return;

            const originX = canvas.width * xPct;
            const originY = canvas.height * yPct;
            const centralAngleRad = (angleDeg * Math.PI) / 180;
            const spreadRad = (spreadDeg * Math.PI) / 180;

            const types = ['confetti', 'confetti', 'ribbon', 'star', 'disc'];

            for (let i = 0; i < count; i++) {
                // Random angle within conical spread
                const angle = centralAngleRad + (Math.random() - 0.5) * spreadRad;
                // High explosive launch speed
                const speed = 18 + Math.random() * 22;
                const type = types[Math.floor(Math.random() * types.length)];

                particles.push(new Particle(originX, originY, angle, speed, palette, type));
            }

            if (!animationId) {
                render();
            }
        },

        /**
         * 💥 Dual / Triple Synchronized Party Poppers
         */
        partyPoppers: function (deptColor = null) {
            const palette = buildDeptPalette(deptColor);
            
            // Left Corner Cannon (shoots up & right)
            this.popperBlast(0.08, 0.85, -60, 65, 85, palette);
            
            // Right Corner Cannon (shoots up & left)
            this.popperBlast(0.92, 0.85, -120, 65, 85, palette);
            
            // Center Cannon Burst (shoots upward 360 wide)
            setTimeout(() => {
                this.popperBlast(0.5, 0.6, -90, 110, 100, palette);
            }, 150);

            // Audio fanfare / pop sound if available
            if (typeof AudioEngine !== 'undefined' && typeof AudioEngine.playPopperBurst === 'function') {
                AudioEngine.playPopperBurst();
            }
        },

        /**
         * 🏆 Comprehensive Weekly Winner Celebration
         * Plays realistic initial poppers burst + ongoing gentle celebratory confetti shower
         */
        startWeeklyCelebration: function (deptColor = null) {
            const palette = buildDeptPalette(deptColor);

            // 1. Initial explosive multi-popper blast
            this.partyPoppers(deptColor);

            // 2. Secondary booster burst at 0.5s
            setTimeout(() => {
                this.popperBlast(0.25, 0.7, -75, 60, 60, palette);
                this.popperBlast(0.75, 0.7, -105, 60, 60, palette);
            }, 500);

            // 3. Gentle raining confetti shower from sky
            if (continuousInterval) clearInterval(continuousInterval);
            continuousInterval = setInterval(() => {
                ensureCanvas();
                if (!canvas) return;
                const x = Math.random() * canvas.width;
                const speed = 2 + Math.random() * 6;
                const angle = Math.PI / 2 + (Math.random() - 0.5) * 0.5; // downwards
                particles.push(new Particle(x, -20, angle, speed, palette, 'confetti'));
                if (Math.random() > 0.5) {
                    particles.push(new Particle(x + 20, -20, angle, speed, palette, 'star'));
                }
                if (!animationId) render();
            }, 120);

            // Auto-stop continuous shower after 6.5 seconds
            setTimeout(() => {
                this.stopContinuous();
            }, 6500);
        },

        stopContinuous: function () {
            if (continuousInterval) {
                clearInterval(continuousInterval);
                continuousInterval = null;
            }
        },

        burst: function (deptColor = null) {
            this.partyPoppers(deptColor);
        },

        fire: function (xPct = 0.5, yPct = 0.4, count = 75, deptColor = null) {
            const palette = buildDeptPalette(deptColor);
            this.popperBlast(xPct, yPct, -90, 120, count, palette);
        },

        stop: stop
    };
})();
