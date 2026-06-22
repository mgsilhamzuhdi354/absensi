<script>
    (function () {
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            return;
        }

        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            return;
        }

        function urlBase64ToUint8Array(base64String) {
            var padding = '='.repeat((4 - base64String.length % 4) % 4);
            var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            var rawData = window.atob(base64);
            var outputArray = new Uint8Array(rawData.length);

            for (var i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }

            return outputArray;
        }

        function contentEncoding() {
            if (window.PushManager && PushManager.supportedContentEncodings && PushManager.supportedContentEncodings.includes('aes128gcm')) {
                return 'aes128gcm';
            }

            return 'aesgcm';
        }

        function sendSubscription(subscription) {
            var payload = subscription.toJSON();
            payload.contentEncoding = contentEncoding();

            return fetch('/pwa-push/subscribe', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                },
                body: JSON.stringify(payload)
            });
        }

        function subscribeWithKey(registration, publicKey) {
            return registration.pushManager.getSubscription().then(function (subscription) {
                if (subscription) {
                    return sendSubscription(subscription);
                }

                return registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey)
                }).then(sendSubscription);
            });
        }

        function registerPush(requestPermission) {
            return fetch('/pwa-push/public-key', {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function (response) { return response.json(); })
                .then(function (config) {
                    if (!config.enabled || !config.publicKey) {
                        return null;
                    }

                    return navigator.serviceWorker.register('/pwa-service-worker.js', { scope: '/' })
                        .then(function (registration) {
                            if (Notification.permission === 'granted') {
                                return subscribeWithKey(registration, config.publicKey);
                            }

                            if (!requestPermission || Notification.permission === 'denied') {
                                return null;
                            }

                            return Notification.requestPermission().then(function (permission) {
                                if (permission === 'granted') {
                                    return subscribeWithKey(registration, config.publicKey);
                                }

                                return null;
                            });
                        });
                })
                .catch(function (error) {
                    console.log('PWA push registration skipped:', error);
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            registerPush(false);
        });

        var requestedFromGesture = false;
        function requestFromGesture() {
            if (requestedFromGesture || Notification.permission !== 'default') {
                return;
            }

            requestedFromGesture = true;
            registerPush(true);
        }

        window.enablePwaPushNotifications = function () {
            requestedFromGesture = true;
            return registerPush(true);
        };

        window.addEventListener('click', requestFromGesture, { once: true, passive: true });
        window.addEventListener('touchstart', requestFromGesture, { once: true, passive: true });
    })();
</script>
