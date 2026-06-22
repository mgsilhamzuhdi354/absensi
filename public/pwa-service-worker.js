self.addEventListener('push', function (event) {
    var payload = {};

    if (event.data) {
        try {
            payload = event.data.json();
        } catch (error) {
            payload = {
                title: 'Notifikasi Baru',
                body: event.data.text()
            };
        }
    }

    var title = payload.title || 'Notifikasi Baru';
    var options = {
        body: payload.body || 'Ada notifikasi baru.',
        icon: payload.icon || '/myhr/app/icons/icon-192x192.png',
        badge: payload.badge || '/myhr/app/icons/icon-192x192.png',
        tag: payload.tag || 'absensi-notification',
        renotify: payload.renotify !== false,
        requireInteraction: !!payload.requireInteraction,
        vibrate: payload.vibrate || [180, 90, 180],
        silent: payload.silent === true ? true : false,
        data: payload.data || {}
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var targetUrl = '/notifications';
    if (event.notification.data && event.notification.data.url) {
        targetUrl = event.notification.data.url;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }

            return clients.openWindow(targetUrl);
        })
    );
});
