<?php
// Configuración para el entorno de producción/desarrollo
define('IS_PRODUCTION', $_SERVER['HTTP_HOST'] === 'f1-2025.wuaze.com');

// Rutas base
if (IS_PRODUCTION) {
    // Configuración para producción en InfinityFree
    define('BASE_PATH', dirname(dirname(__DIR__)));
    
    // Crear directorio de datos si no existe
    $dataDir = dirname(dirname(__DIR__)) . '/data';
    if (!file_exists($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    
    $cacheDir = $dataDir . '/cache';
    if (!file_exists($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    
    define('DATA_DIR', $dataDir);
    define('CACHE_DIR', $cacheDir);
} else {
    // Configuración para desarrollo local
    define('BASE_PATH', dirname(dirname(__DIR__)));
    define('DATA_DIR', BASE_PATH . '/data');
    define('CACHE_DIR', DATA_DIR . '/cache');
}

// URL base
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST']);

// Configuración de scraping
define('SCRAPER_TIMEOUT', 30); // segundos
define('USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

// Configuración de errores
if (IS_PRODUCTION) {
    // En producción: Solo registrar errores, no mostrarlos
    ini_set('log_errors', 1);
    ini_set('error_log', DATA_DIR . '/php_errors.log');
    
    // Reportar todos los errores excepto NOTICE y DEPRECATED
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
} else {
    // En desarrollo: Mostrar todos los errores
    error_reporting(-1);  // Equivalente a E_ALL en versiones recientes de PHP
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}
