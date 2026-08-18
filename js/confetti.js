/**
 * Vanilla JS Canvas Confetti Engine
 * Renders high-performance particle explosions for victory celebration.
 */

const ConfettiEngine = (function () {
    let canvas = null;
    let ctx = null;
    let particles = [];
    let animationId = null;
    const colors = ['#3b82f6', '#22c55e', '#f97316', '#a855f7', '#ef4444', '#ffd700', '#00f0ff'];

    function Particle(x, y) {
        this.x = x;
        this.y = y;
        this.size = Math.random() * 8 + 6;
        this.color = colors[Math.floor(Math.random() * colors.length)];
        this.vx = (Math.random() - 0.5) * 12;
        this.vy = (Math.random() - 0.7) * 16;
        this.gravity = 0.35;
        this.rotation = Math.random() * 360;
        this.rotSpeed = (Math.random() - 0.5) * 10;
        this.opacity = 1;
        this.decay = Math.random() * 0.008 + 0.004;
    }

    Particle.prototype.update = function () {
        this.x += this.vx;
        this.vy += this.gravity;
        this.y += this.vy;
        this.rotation += this.rotSpeed;
        this.opacity -= this.decay;
    };

    Particle.prototype.draw = function (context) {
        context.save();
        context.translate(this.x, this.y);
        context.rotate((this.rotation * Math.PI) / 180);
        context.globalAlpha = Math.max(0, this.opacity);
        context.fillStyle = this.color;
        context.fillRect(-this.size / 2, -this.size / 2, this.size, this.size);
        context.restore();
    };

    function render() {
        if (!ctx || !canvas) return;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        for (let i = particles.length - 1; i >= 0; i--) {
            const p = particles[i];
            p.update();
            p.draw(ctx);

            if (p.opacity <= 0 || p.y > canvas.height + 50) {
                particles.splice(i, 1);
            }
        }

        if (particles.length > 0) {
            animationId = requestAnimationFrame(render);
        } else {
            stop();
        }
    }

    return {
        init: function (canvasElement) {
            canvas = canvasElement;
            if (canvas) {
                ctx = canvas.getContext('2d');
                this.resize();
                window.addEventListener('resize', () => this.resize());
            }
        },

        resize: function () {
            if (canvas) {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
        },

        burst: function () {
            if (!canvas) return;
            this.resize();
            particles = [];

            // Spawn 150 confetti particles from left & right sides
            for (let i = 0; i < 90; i++) {
                particles.push(new Particle(canvas.width * 0.2, canvas.height * 0.4));
                particles.push(new Particle(canvas.width * 0.8, canvas.height * 0.4));
                particles.push(new Particle(canvas.width * 0.5, canvas.height * 0.3));
            }

            if (animationId) cancelAnimationFrame(animationId);
            render();
        },

        stop: function () {
            if (animationId) cancelAnimationFrame(animationId);
            if (ctx && canvas) ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles = [];
        }
    };
})();
