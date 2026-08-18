/**
 * Real-Time Cross-Tab / Cross-Device Synchronization Engine
 * Uses BroadcastChannel API with LocalStorage event fallback for instant state sync.
 */

const SyncEngine = (function () {
    const CHANNEL_NAME = 'corporate_race_sync_channel';
    let channel = null;
    let listenerCallback = null;

    if ('BroadcastChannel' in window) {
        channel = new BroadcastChannel(CHANNEL_NAME);
    }

    return {
        init: function (onMessageReceived) {
            listenerCallback = onMessageReceived;

            // BroadcastChannel listener
            if (channel) {
                channel.onmessage = function (event) {
                    if (typeof listenerCallback === 'function' && event.data) {
                        listenerCallback(event.data);
                    }
                };
            }

            // LocalStorage fallback event listener
            window.addEventListener('storage', function (event) {
                if (event.key === 'corporate_race_sync_event' && event.newValue) {
                    try {
                        const data = JSON.parse(event.newValue);
                        if (typeof listenerCallback === 'function') {
                            listenerCallback(data);
                        }
                    } catch (e) {
                        console.warn('Sync parsing error', e);
                    }
                }
            });
        },

        broadcast: function (eventType, payload = {}) {
            const message = {
                type: eventType,
                payload: payload,
                senderId: Math.random().toString(36).substring(2, 9),
                timestamp: Date.now()
            };

            // Broadcast via BroadcastChannel
            if (channel) {
                channel.postMessage(message);
            }

            // Trigger via LocalStorage for cross-tab storage listener
            try {
                localStorage.setItem('corporate_race_sync_event', JSON.stringify(message));
            } catch (e) {
                // Ignore storage quota
            }
        }
    };
})();
