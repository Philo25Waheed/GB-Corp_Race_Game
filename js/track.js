/**
 * Racing Track & Car Animation Renderer
 */

const TrackRenderer = (function () {
    let lanesContainer = null;
    let markersContainer = null;
    let leaderboardGrid = null;

    function createDistanceMarkers(raceLength) {
        if (!markersContainer) return;
        markersContainer.innerHTML = '';
        const stepGap = 100 / raceLength;

        for (let i = 0; i <= raceLength; i += 3) {
            const marker = document.createElement('div');
            marker.className = 'marker-step';
            marker.textContent = i === 0 ? 'START' : (i === raceLength ? 'FINISH' : `${i}`);
            markersContainer.appendChild(marker);
        }
    }

    function renderLanes(teams, raceLength) {
        if (!lanesContainer) return;
        lanesContainer.innerHTML = '';

        teams.forEach(team => {
            const lane = document.createElement('div');
            lane.className = 'racing-lane';
            lane.id = `lane-${team.id}`;

            const pct = Math.min(100, (team.position / raceLength) * 100);

            lane.innerHTML = `
                <div class="lane-team-badge" style="color: ${team.color}">
                    <span class="team-color-indicator" style="background-color: ${team.color}"></span>
                    <span>${team.name}</span>
                </div>

                <div class="lane-runway">
                    <div class="car-wrapper" id="car-wrap-${team.id}" style="left: ${pct}%">
                        <span class="car-icon">${team.car}</span>
                        <div class="speed-dust"></div>
                    </div>
                </div>

                <div class="lane-stats">
                    <span class="step-count" id="step-count-${team.id}">${team.position}</span>
                    <span class="step-label">STEPS</span>
                </div>
            `;

            lanesContainer.appendChild(lane);
        });
    }

    function updateCarPosition(team, raceLength, animate = true) {
        const carWrap = document.getElementById(`car-wrap-${team.id}`);
        const stepCountEl = document.getElementById(`step-count-${team.id}`);

        if (carWrap) {
            const pct = Math.min(100, (team.position / raceLength) * 92); // Leave 8% buffer for car width
            carWrap.style.left = `${pct}%`;

            if (animate) {
                carWrap.classList.add('moving');
                setTimeout(() => {
                    carWrap.classList.remove('moving');
                }, 700);
            }
        }

        if (stepCountEl) {
            stepCountEl.textContent = team.position;
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
                <div class="rank-score">${team.position} PTS</div>
            `;
            leaderboardGrid.appendChild(card);
        });
    }

    return {
        init: function () {
            lanesContainer = document.getElementById('lanes-container');
            markersContainer = document.getElementById('track-markers');
            leaderboardGrid = document.getElementById('leaderboard-grid');
        },

        renderAll: function (teams, raceLength, rankings) {
            createDistanceMarkers(raceLength);
            renderLanes(teams, raceLength);
            renderLeaderboard(rankings);
        },

        updateTeam: function (team, raceLength, rankings, animate = true) {
            updateCarPosition(team, raceLength, animate);
            renderLeaderboard(rankings);
        }
    };
})();
