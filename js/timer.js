/**
 * High Accuracy Countdown Timer Engine
 */

const CountdownTimer = (function () {
    let duration = 15;
    let remaining = 15;
    let timerId = null;
    let isRunning = false;
    let onTickCallback = null;
    let onFinishCallback = null;

    function tick() {
        if (remaining > 0) {
            remaining--;
            if (typeof onTickCallback === 'function') {
                onTickCallback(remaining);
            }
            if (remaining <= 5 && remaining > 0) {
                SoundEngine.playTimerTick();
            }
        } else {
            stopTimer();
            SoundEngine.playTimerFinish();
            if (typeof onFinishCallback === 'function') {
                onFinishCallback();
            }
        }
    }

    function stopTimer() {
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
        isRunning = false;
    }

    return {
        init: function (initialSeconds, onTick, onFinish) {
            duration = initialSeconds || 15;
            remaining = duration;
            onTickCallback = onTick;
            onFinishCallback = onFinish;
        },

        start: function () {
            if (isRunning) return;
            if (remaining <= 0) remaining = duration;
            isRunning = true;
            timerId = setInterval(tick, 1000);
        },

        pause: function () {
            stopTimer();
        },

        reset: function () {
            stopTimer();
            remaining = duration;
            if (typeof onTickCallback === 'function') {
                onTickCallback(remaining);
            }
        },

        setDuration: function (sec) {
            duration = sec;
            this.reset();
        },

        getRemaining: function () {
            return remaining;
        },

        isTimerRunning: function () {
            return isRunning;
        }
    };
})();
