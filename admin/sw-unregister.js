// Desregistrar todos los Service Workers
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
        for (let registration of registrations) {
            console.log('Desregistrando ServiceWorker:', registration.scope);
            registration.unregister();
        }
        
        // Limpiar caché
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    console.log('Eliminando caché:', cacheName);
                    return caches.delete(cacheName);
                })
            );
        }).then(function() {
            console.log('Todos los Service Workers han sido desregistrados y la caché ha sido limpiada.');
            alert('Service Workers desregistrados y caché limpiada. Por favor, actualiza la página.');
        });
    });
} else {
    console.log('Service Workers no soportados en este navegador.');
    alert('Service Workers no soportados en este navegador.');
}
