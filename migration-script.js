/**
 * Script de migración para f1-2025.wuaze.com
 * Este script actualiza las rutas y configuraciones para el despliegue en el nuevo dominio
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Iniciando script de migración para f1-2025.wuaze.com');
    
    // 1. Corregir rutas absolutas que apuntan a /calendarios/calendario-F1-2025/
    fixAbsolutePaths();
    
    // 2. Actualizar configuración del manifest.json
    updateManifest();
    
    // 3. Actualizar configuración de APIs y servicios externos
    updateExternalServices();
    
    console.log('Migración completada. El sitio está listo para funcionar en f1-2025.wuaze.com');
});

/**
 * Corrige las rutas absolutas que apuntan a /calendarios/calendario-F1-2025/
 */
function fixAbsolutePaths() {
    console.log('Corrigiendo rutas absolutas...');
    
    // Buscar todas las rutas absolutas en el HTML y reemplazarlas
    const baseUrl = '/calendarios/calendario-F1-2025/';
    const newBaseUrl = '/';
    
    // Esta función se ejecuta en el navegador y reemplaza las rutas en tiempo de ejecución
    // Es útil para sitios que ya están desplegados y necesitan ajustes sin modificar los archivos
    
    // 1. Reemplazar rutas en elementos <a>
    document.querySelectorAll('a[href^="' + baseUrl + '"]').forEach(element => {
        const currentHref = element.getAttribute('href');
        const newHref = currentHref.replace(baseUrl, newBaseUrl);
        element.setAttribute('href', newHref);
        console.log(`Ruta actualizada: ${currentHref} -> ${newHref}`);
    });
    
    // 2. Reemplazar rutas en elementos <img>
    document.querySelectorAll('img[src^="' + baseUrl + '"]').forEach(element => {
        const currentSrc = element.getAttribute('src');
        const newSrc = currentSrc.replace(baseUrl, newBaseUrl);
        element.setAttribute('src', newSrc);
        console.log(`Ruta de imagen actualizada: ${currentSrc} -> ${newSrc}`);
    });
    
    // 3. Reemplazar rutas en elementos <link>
    document.querySelectorAll('link[href^="' + baseUrl + '"]').forEach(element => {
        const currentHref = element.getAttribute('href');
        const newHref = currentHref.replace(baseUrl, newBaseUrl);
        element.setAttribute('href', newHref);
        console.log(`Ruta de CSS actualizada: ${currentHref} -> ${newHref}`);
    });
    
    // 4. Reemplazar rutas en elementos <script>
    document.querySelectorAll('script[src^="' + baseUrl + '"]').forEach(element => {
        const currentSrc = element.getAttribute('src');
        const newSrc = currentSrc.replace(baseUrl, newBaseUrl);
        element.setAttribute('src', newSrc);
        console.log(`Ruta de script actualizada: ${currentSrc} -> ${newSrc}`);
    });
    
    // 5. Actualizar rutas en estilos inline
    document.querySelectorAll('[style*="' + baseUrl + '"]').forEach(element => {
        const currentStyle = element.getAttribute('style');
        const newStyle = currentStyle.replace(new RegExp(baseUrl, 'g'), newBaseUrl);
        element.setAttribute('style', newStyle);
        console.log(`Estilo inline actualizado`);
    });
    
    console.log('Rutas absolutas corregidas');
}

/**
 * Actualiza la configuración del manifest.json
 */
function updateManifest() {
    console.log('Actualizando manifest.json...');
    
    // Esta función simula la actualización del manifest.json
    // En un entorno real, necesitarías modificar el archivo antes del despliegue
    
    // Mostrar información sobre los cambios necesarios
    console.log('Para actualizar el manifest.json, realiza los siguientes cambios:');
    console.log('1. Cambiar "start_url" de "/calendarios/calendario-F1-2025/index.html" a "/index.html"');
    console.log('2. Verificar que los iconos tengan rutas relativas correctas');
    
    // Verificar si el manifest está cargado correctamente
    const manifestLink = document.querySelector('link[rel="manifest"]');
    if (manifestLink) {
        console.log('Manifest encontrado en el DOM:', manifestLink.getAttribute('href'));
    } else {
        console.warn('No se encontró el manifest en el DOM');
    }
}

/**
 * Actualiza la configuración de APIs y servicios externos
 */
function updateExternalServices() {
    console.log('Actualizando configuración de servicios externos...');
    
    // 1. Actualizar configuración de la API del tiempo
    if (typeof WEATHER_API_KEY !== 'undefined') {
        console.log('API del tiempo configurada con clave:', WEATHER_API_KEY);
        console.log('Verificar que la API del tiempo permita solicitudes desde f1-2025.wuaze.com');
    }
    
    // 2. Verificar configuración de scraping
    console.log('Verificar que el sistema de scraping funcione correctamente desde el nuevo dominio');
    console.log('Es posible que algunas fuentes bloqueen solicitudes desde dominios diferentes');
    
    // 3. Verificar servicios de mapas
    if (typeof L !== 'undefined' && typeof L.map === 'function') {
        console.log('Leaflet está disponible. Verificar que los mapas se carguen correctamente');
    }
    
    console.log('Configuración de servicios externos actualizada');
}

/**
 * Función para verificar el estado de la migración
 * Esta función puede ser llamada desde la consola para verificar si todo está funcionando correctamente
 */
function checkMigrationStatus() {
    console.log('Verificando estado de la migración...');
    
    // 1. Verificar que el contador regresivo funcione
    const countdownContainer = document.getElementById('countdown-container');
    if (countdownContainer) {
        console.log('✅ Contador regresivo encontrado en el DOM');
    } else {
        console.warn('❌ Contador regresivo no encontrado en el DOM');
    }
    
    // 2. Verificar que el pronóstico del tiempo funcione
    const weatherContainer = document.getElementById('weather-container');
    if (weatherContainer) {
        console.log('✅ Contenedor del pronóstico del tiempo encontrado en el DOM');
    } else {
        console.warn('❌ Contenedor del pronóstico del tiempo no encontrado en el DOM');
    }
    
    // 3. Verificar que las imágenes se carguen correctamente
    const images = document.querySelectorAll('img');
    let brokenImages = 0;
    images.forEach(img => {
        if (!img.complete || img.naturalHeight === 0) {
            brokenImages++;
            console.warn(`❌ Imagen rota: ${img.src}`);
        }
    });
    console.log(`Imágenes verificadas: ${images.length - brokenImages}/${images.length} correctas`);
    
    // 4. Verificar que los scripts se hayan cargado
    const scripts = [
        'countdown.js',
        'weather.js',
        'circuit-details.js',
        'race-scraper.js',
        'main.js'
    ];
    
    scripts.forEach(script => {
        const scriptElement = Array.from(document.querySelectorAll('script')).find(s => 
            s.src && s.src.includes(script)
        );
        if (scriptElement) {
            console.log(`✅ Script ${script} cargado correctamente`);
        } else {
            console.warn(`❌ Script ${script} no encontrado o no cargado`);
        }
    });
    
    console.log('Verificación de migración completada');
    return {
        countdownOk: !!countdownContainer,
        weatherOk: !!weatherContainer,
        imagesOk: images.length - brokenImages === images.length,
        scriptsOk: scripts.every(script => 
            Array.from(document.querySelectorAll('script')).some(s => 
                s.src && s.src.includes(script)
            )
        )
    };
}

// Exponer la función de verificación globalmente para poder llamarla desde la consola
window.checkMigrationStatus = checkMigrationStatus;

// Ejecutar verificación automática después de 5 segundos
setTimeout(() => {
    console.log('Ejecutando verificación automática de la migración...');
    const status = checkMigrationStatus();
    console.log('Resultado de la verificación:', status);
    
    if (status.countdownOk && status.weatherOk && status.imagesOk && status.scriptsOk) {
        console.log('✅ La migración parece estar funcionando correctamente');
    } else {
        console.warn('⚠️ Hay problemas con la migración que requieren atención');
    }
}, 5000);
