/**
 * Summer Serpentine Racetrack & Car Animation Renderer
 */

const TrackRenderer = (function () {
    let serpentineWrapper = null;
    let serpentineSvg = null;
    let roadMainPath = null;
    let serpentineCarsContainer = null;
    let sprintLanesWrapper = null;
    let lanesContainer = null;
    let markersContainer = null;
    let leaderboardGrid = null;
    let fleetContainer = null;
    let odometerDigitsEl = null;

    let currentViewMode = 'serpentine'; // 'serpentine' or 'sprint'

    const milestoneStops = [
        { pct: 0.20, stopNum: 'STOP 1', title: 'FUEL UP', sub: 'Refresh Yourself', icon: '💧', isGold: false },
        { pct: 0.40, stopNum: 'STOP 2', title: 'EXPLORE THE ROUTE', sub: 'Discover GB', icon: '🧭', isGold: false },
        { pct: 0.60, stopNum: 'STOP 3', title: 'TRAVEL TOGETHER', sub: 'Connect & Collaborate', icon: '👥', isGold: false },
        { pct: 0.80, stopNum: 'STOP 4', title: 'CELEBRATE TOGETHER', sub: 'Enjoy & Win', icon: '🎉', isGold: false },
        { pct: 1.00, stopNum: 'STOP 5', title: 'FINISH LINE', sub: 'Celebrate Our Success', icon: '🏆', isGold: true }
    ];

    const sceneryProps = [
        { type: 'palm', x: 70, y: 35, emoji: '🌴' },
        { type: 'palm', x: 500, y: 35, emoji: '🌴' },
        { type: 'palm', x: 860, y: 30, emoji: '🌴' },
        { type: 'cactus', x: 260, y: 160, emoji: '🌵' },
        { type: 'palm', x: 620, y: 175, emoji: '🌴' },
        { type: 'cactus', x: 960, y: 165, emoji: '🌵' },
        { type: 'palm', x: 320, y: 320, emoji: '🌴' },
        { type: 'cactus', x: 780, y: 325, emoji: '🌵' },
        { type: 'palm', x: 50, y: 460, emoji: '🌴' },
        { type: 'palm', x: 520, y: 470, emoji: '🌴' },
        { type: 'cactus', x: 860, y: 465, emoji: '🌵' }
    ];

    function initSerpentineRoad() {
        if (!serpentineWrapper) return;
        serpentineWrapper.innerHTML = `
            <div class="serpentine-sky">
                <div class="sky-sun-element"></div>
                <div class="sky-birds">~ ~ ~</div>
            </div>

            <!-- START SIGNPOST -->
            <div class="start-signpost">
                <span>🚩</span>
                <span>START</span>
            </div>

            <!-- FINISH ARCH BANNER -->
            <div class="finish-arch-banner">
                <span>🏁</span>
                <span>FINISH LINE</span>
                <span>🏆</span>
            </div>

            <!-- SCENERY PROPS (PALMS & CACTI) -->
            <div id="scenery-props-container"></div>

            <!-- SVG ROAD CANVAS -->
            <svg class="serpentine-svg-canvas" viewBox="0 0 1200 520" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="roadGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#1e293b" />
                        <stop offset="50%" stop-color="#334155" />
                        <stop offset="100%" stop-color="#1e293b" />
                    </linearGradient>
                </defs>
                <!-- Road Outer Border / Curb -->
                <path id="road-bg-path" class="road-border-path" d="M 90 95 C 400 95, 800 85, 1060 100 C 1170 105, 1170 230, 1060 235 C 780 245, 420 240, 140 255 C 30 265, 30 385, 140 395 C 420 405, 800 400, 1060 415 C 1140 420, 1160 475, 1090 485" />
                <!-- Road Surface -->
                <path id="road-main-path" class="road-surface-path" d="M 90 95 C 400 95, 800 85, 1060 100 C 1170 105, 1170 230, 1060 235 C 780 245, 420 240, 140 255 C 30 265, 30 385, 140 395 C 420 405, 800 400, 1060 415 C 1140 420, 1160 475, 1090 485" />
                <!-- Dashed Center Lane Divider -->
                <path id="road-lane-divider" class="road-lane-divider-path" d="M 90 95 C 400 95, 800 85, 1060 100 C 1170 105, 1170 230, 1060 235 C 780 245, 420 240, 140 255 C 30 265, 30 385, 140 395 C 420 405, 800 400, 1060 415 C 1140 420, 1160 475, 1090 485" />
            </svg>

            <!-- MILESTONE CHECKPOINT PINS -->
            <div id="serpentine-pins-container"></div>

            <!-- CARS LAYER -->
            <div id="serpentine-cars-container"></div>
        `;

        roadMainPath = document.getElementById('road-main-path');
        serpentineCarsContainer = document.getElementById('serpentine-cars-container');

        renderSceneryProps();
        renderMilestonePins();
    }

    function renderSceneryProps() {
        const container = document.getElementById('scenery-props-container');
        if (!container) return;
        container.innerHTML = '';

        sceneryProps.forEach(prop => {
            const el = document.createElement('div');
            el.className = `scenery-prop ${prop.type}`;
            el.style.left = `${(prop.x / 1200) * 100}%`;
            el.style.top = `${(prop.y / 520) * 100}%`;
            el.textContent = prop.emoji;
            container.appendChild(el);
        });
    }

    function renderMilestonePins() {
        const pinsContainer = document.getElementById('serpentine-pins-container');
        if (!pinsContainer || !roadMainPath) return;
        pinsContainer.innerHTML = '';

        const totalLength = roadMainPath.getTotalLength();

        milestoneStops.forEach((stop, idx) => {
            const dist = totalLength * stop.pct;
            const pt = roadMainPath.getPointAtLength(dist);

            const pinEl = document.createElement('div');
            pinEl.className = 'checkpoint-pin-marker';
            // Safe clamp to ensure pin bubbles never overflow container edges
            const safeLeft = Math.max(6, Math.min(94, (pt.x / 1200) * 100));
            const safeTop = Math.max(8, Math.min(92, (pt.y / 520) * 100));
            pinEl.style.left = `${safeLeft}%`;
            pinEl.style.top = `${safeTop}%`;

            pinEl.innerHTML = `
                <div class="pin-bubble ${stop.isGold ? 'gold' : ''}">
                    <span class="pin-stop-num">${stop.icon} ${stop.stopNum}</span>
                    <span class="pin-title">${stop.title}</span>
                    <span class="pin-sub">${stop.sub}</span>
                </div>
                <div class="pin-stem ${stop.isGold ? 'gold' : ''}"></div>
            `;

            pinsContainer.appendChild(pinEl);
        });
    }

    function renderSerpentineCars(teams, raceLength) {
        if (!serpentineCarsContainer || !roadMainPath) return;
        serpentineCarsContainer.innerHTML = '';

        const totalTeams = teams.length;

        teams.forEach((team, idx) => {
            const carWrap = document.createElement('div');
            carWrap.className = 'serpentine-car-wrapper';
            carWrap.id = `serp-car-${team.id}`;

            carWrap.innerHTML = `
                <div class="car-badge-miles" id="serp-miles-${team.id}">+${team.position * 10} MI</div>
                <div class="serpentine-car-body" id="serp-body-${team.id}">
                    <div class="car-exhaust-trail"></div>
                    <span class="car-sprite-graphic" style="filter: drop-shadow(0 0 8px ${team.color});">${team.car}</span>
                    <div class="car-headlight-beam"></div>
                </div>
                <span class="car-name-tag" style="border-bottom: 2px solid ${team.color};">${team.name}</span>
            `;

            serpentineCarsContainer.appendChild(carWrap);
            positionSerpentineCar(team, idx, totalTeams, raceLength, false);
        });
    }

    function positionSerpentineCar(team, teamIndex, totalTeams, raceLength, animate = true) {
        const carWrap = document.getElementById(`serp-car-${team.id}`);
        const carBody = document.getElementById(`serp-body-${team.id}`);
        const milesBadge = document.getElementById(`serp-miles-${team.id}`);
        if (!carWrap || !roadMainPath) return;

        const totalLength = roadMainPath.getTotalLength();
        const progressPct = Math.min(1, Math.max(0, team.position / raceLength));
        const distance = progressPct * totalLength;

        const pt = roadMainPath.getPointAtLength(distance);
        const ptNext = roadMainPath.getPointAtLength(Math.min(totalLength, distance + 4));

        // Calculate tangent angle along curve
        const angle = Math.atan2(ptNext.y - pt.y, ptNext.x - pt.x) * (180 / Math.PI);

        // Calculate normal lane offset so cars don't overlap
        const normalAngle = (angle + 90) * (Math.PI / 180);
        const isMobile = window.innerWidth < 768;
        const laneSpread = isMobile ? 12 : 16;
        const laneOffset = (teamIndex - (totalTeams - 1) / 2) * laneSpread;

        const finalX = pt.x + Math.cos(normalAngle) * laneOffset;
        const finalY = pt.y + Math.sin(normalAngle) * laneOffset;

        const leftPct = Math.max(3, Math.min(97, (finalX / 1200) * 100));
        const topPct = Math.max(4, Math.min(96, (finalY / 520) * 100));

        carWrap.style.left = `${leftPct}%`;
        carWrap.style.top = `${topPct}%`;

        // Determine if moving left or right along road switchbacks
        let isMovingLeft = false;
        let tilt = angle;

        if (angle > 90) {
            isMovingLeft = true;
            tilt = angle - 180;
        } else if (angle < -90) {
            isMovingLeft = true;
            tilt = angle + 180;
        }

        // Apply forward orientation: scaleX(1) when moving right, scaleX(-1) when moving left
        if (carBody) {
            const dir = isMovingLeft ? -1 : 1;
            carBody.style.transform = `scaleX(${dir}) rotate(${dir * tilt}deg)`;
        }

        if (milesBadge) {
            milesBadge.textContent = `+${team.position * 10} MI`;
        }

        if (animate) {
            carWrap.classList.add('moving');
            setTimeout(() => carWrap.classList.remove('moving'), 700);
        }
    }

    function createDistanceMarkers(raceLength) {
        if (!markersContainer) return;
        markersContainer.innerHTML = '';

        const stepInterval = raceLength <= 10 ? 2 : (raceLength <= 15 ? 3 : 5);
        for (let i = 0; i <= raceLength; i += stepInterval) {
            const marker = document.createElement('div');
            marker.className = 'marker-step';
            marker.innerHTML = `<span>${i * 10}</span><span class="marker-unit">mi</span>`;
            markersContainer.appendChild(marker);
        }
    }

    function renderSprintLanes(teams, raceLength) {
        if (!lanesContainer) return;
        lanesContainer.innerHTML = '';

        teams.forEach(team => {
            const lane = document.createElement('div');
            lane.className = 'racing-lane';
            lane.id = `lane-${team.id}`;

            const pct = Math.min(100, (team.position / raceLength) * 92);

            lane.innerHTML = `
                <div class="lane-team-badge" style="color: ${team.color}">
                    <span class="team-color-indicator" style="background-color: ${team.color}"></span>
                    <span>${team.name}</span>
                </div>

                <div class="lane-runway">
                    <div class="car-wrapper" id="car-wrap-${team.id}" style="left: ${pct}%">
                        <span class="car-icon">${team.car}</span>
                    </div>
                </div>

                <div class="lane-stats">
                    <span class="step-count" id="step-count-${team.id}">${team.position * 10} MI</span>
                    <span class="step-label">${team.position} STEPS</span>
                </div>
            `;

            lanesContainer.appendChild(lane);
        });
    }

    function updateSprintCarPosition(team, raceLength, animate = true) {
        const carWrap = document.getElementById(`car-wrap-${team.id}`);
        const stepCountEl = document.getElementById(`step-count-${team.id}`);

        if (carWrap) {
            const pct = Math.min(100, (team.position / raceLength) * 92);
            carWrap.style.left = `${pct}%`;
            if (animate) {
                carWrap.classList.add('moving');
                setTimeout(() => carWrap.classList.remove('moving'), 700);
            }
        }

        if (stepCountEl) {
            stepCountEl.textContent = `${team.position * 10} MI`;
        }
    }

    function renderLeaderboard(rankings) {
        if (!leaderboardGrid) return;
        leaderboardGrid.innerHTML = '';

        const medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];

        rankings.forEach((team, idx) => {
            const card = document.createElement('div');
            card.className = `rank-card rank-${idx + 1}`;
            card.innerHTML = `
                <div class="rank-info">
                    <span class="rank-medal">${medals[idx] || '🏁'}</span>
                    <span class="rank-name" style="color: ${team.color}">${team.name}</span>
                </div>
                <div class="rank-score">${team.position * 10} MI</div>
            `;
            leaderboardGrid.appendChild(card);
        });
    }

    function renderFleetGrid(teams) {
        if (!fleetContainer) return;
        fleetContainer.innerHTML = '';

        teams.forEach(team => {
            const box = document.createElement('div');
            box.className = 'fleet-dept-box';
            box.style.borderLeftColor = team.color;
            box.innerHTML = `
                <span class="fleet-car-icon" style="filter: drop-shadow(0 0 6px ${team.color});">${team.car}</span>
                <span class="fleet-dept-name" style="color: ${team.color};">${team.name}</span>
                <span class="fleet-dept-mileage">${team.position * 10} MI</span>
            `;
            fleetContainer.appendChild(box);
        });
    }

    function updateOdometer(teams) {
        if (!odometerDigitsEl) return;
        const totalMiles = teams.reduce((acc, t) => acc + (t.position * 10), 0);
        const formatted = String(totalMiles).padStart(5, '0');
        odometerDigitsEl.textContent = formatted;
    }

    return {
        init: function () {
            serpentineWrapper = document.getElementById('serpentine-road-wrapper');
            sprintLanesWrapper = document.getElementById('sprint-lanes-wrapper');
            lanesContainer = document.getElementById('lanes-container');
            markersContainer = document.getElementById('track-markers');
            leaderboardGrid = document.getElementById('leaderboard-grid');
            fleetContainer = document.getElementById('fleet-departments-container');
            odometerDigitsEl = document.getElementById('odometer-led-digits');

            initSerpentineRoad();

            // Set up view switcher buttons
            const btnSerp = document.getElementById('btn-view-serpentine');
            const btnSprint = document.getElementById('btn-view-sprint');

            if (btnSerp) {
                btnSerp.addEventListener('click', () => {
                    TrackRenderer.setViewMode('serpentine');
                });
            }

            if (btnSprint) {
                btnSprint.addEventListener('click', () => {
                    TrackRenderer.setViewMode('sprint');
                });
            }
        },

        setViewMode: function (mode) {
            currentViewMode = mode;
            const btnSerp = document.getElementById('btn-view-serpentine');
            const btnSprint = document.getElementById('btn-view-sprint');

            if (mode === 'serpentine') {
                if (serpentineWrapper) serpentineWrapper.style.display = 'block';
                if (sprintLanesWrapper) sprintLanesWrapper.classList.add('hidden');
                if (btnSerp) btnSerp.classList.add('active');
                if (btnSprint) btnSprint.classList.remove('active');
            } else {
                if (serpentineWrapper) serpentineWrapper.style.display = 'none';
                if (sprintLanesWrapper) sprintLanesWrapper.classList.remove('hidden');
                if (btnSprint) btnSprint.classList.add('active');
                if (btnSerp) btnSerp.classList.remove('active');
            }
        },

        renderAll: function (teams, raceLength, rankings) {
            renderSerpentineCars(teams, raceLength);
            createDistanceMarkers(raceLength);
            renderSprintLanes(teams, raceLength);
            renderLeaderboard(rankings);
            renderFleetGrid(teams);
            updateOdometer(teams);
        },

        updateTeam: function (team, raceLength, rankings, animate = true) {
            const teams = GameState.getTeams();
            const teamIndex = teams.findIndex(t => t.id === team.id);
            positionSerpentineCar(team, teamIndex >= 0 ? teamIndex : 0, teams.length, raceLength, animate);
            updateSprintCarPosition(team, raceLength, animate);
            renderLeaderboard(rankings);
            renderFleetGrid(teams);
            updateOdometer(teams);
        }
    };
})();

