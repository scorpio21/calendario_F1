<?php
// Evitar que se muestren errores en el navegador
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/data/php_errors.log');

try {
    // Incluir configuración
    require_once __DIR__ . '/admin/config.php';
    
    // Crear directorio de datos si no existe
    if (!file_exists(DATA_DIR)) {
        if (!@mkdir(DATA_DIR, 0755, true)) {
            throw new Exception("No se pudo crear el directorio de datos: " . DATA_DIR);
        }
    }
    
    // Crear archivo de log si no existe
    $logFile = DATA_DIR . '/diagnostico.log';
    $log = fopen($logFile, 'a');
    if (!$log) {
        throw new Exception("No se pudo abrir el archivo de log: " . $logFile);
    }
    
    // Función para escribir en el log
    $writeLog = function($message) use ($log) {
        $timestamp = date('Y-m-d H:i:s');
        fwrite($log, "[$timestamp] $message\n");
    };
    
    $writeLog("=== Inicio de diagnóstico ===");
    $writeLog("Directorio actual: " . __DIR__);
    $writeLog("Usuario: " . get_current_user());
    $writeLog("PHP Version: " . phpversion());
    
    // Verificar extensiones necesarias
    $requiredExtensions = ['dom', 'simplexml', 'libxml', 'curl'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    
    if (!empty($missingExtensions)) {
        $writeLog("ERROR: Extensiones faltantes: " . implode(', ', $missingExtensions));
    } else {
        $writeLog("Todas las extensiones necesarias están cargadas");
    }
    
    // Verificar permisos de directorios
    $directories = [
        DATA_DIR => 'Directorio de datos',
        CACHE_DIR => 'Directorio de caché',
        __DIR__ . '/admin' => 'Directorio admin',
        __DIR__ . '/js' => 'Directorio js',
        __DIR__ . '/css' => 'Directorio css'
    ];
    
    foreach ($directories as $dir => $desc) {
        if (!file_exists($dir)) {
            $writeLog("ADVERTENCIA: $desc no existe: $dir");
            continue;
        }
        
        $perms = fileperms($dir);
        $writeLog(sprintf(
            "%s: %s - Permisos: %s, Propietario: %s, Grupo: %s",
            $desc,
            $dir,
            substr(sprintf('%o', $perms), -4),
            fileowner($dir),
            filegroup($dir)
        ));
        
        if (!is_writable($dir)) {
            $writeLog("ADVERTENCIA: $desc no es escribible: $dir");
        }
    }
    
    // Probar escritura en el directorio de datos
    $testFile = DATA_DIR . '/test_write.txt';
    if (file_put_contents($testFile, 'Prueba de escritura ' . date('Y-m-d H:i:s'))) {
        $writeLog("PRUEBA: Escritura en archivo exitosa: $testFile");
        unlink($testFile);
    } else {
        $writeLog("ERROR: No se pudo escribir en el archivo de prueba: $testFile");
    }
    
    // Verificar configuración de PHP
    $writeLog("memory_limit: " . ini_get('memory_limit'));
    $writeLog("max_execution_time: " . ini_get('max_execution_time'));
    $writeLog("allow_url_fopen: " . ini_get('allow_url_fopen'));
    
    // Verificar si se puede acceder a URLs externas
    $testUrl = 'https://en.wikipedia.org/wiki/2025_Formula_One_World_Championship';
    $context = stream_context_create(['http' => ['timeout' => 10]]);
    $content = @file_get_contents($testUrl, false, $context);
    
    if ($content === false) {
        $writeLog("ADVERTENCIA: No se pudo acceder a $testUrl");
        $writeLog("Error: " . print_r(error_get_last(), true));
    } else {
        $writeLog("PRUEBA: Acceso a URL externa exitosa");
    }
    
    $writeLog("=== Fin de diagnóstico ===\n");
    fclose($log);
    
    // Mostrar mensaje de éxito
    echo "Diagnóstico completado. Revisa el archivo de log: " . realpath($logFile);
    
} catch (Exception $e) {
    // Registrar el error
    error_log("Error en diagnóstico: " . $e->getMessage());
    
    // Mostrar mensaje de error genérico
    echo "Ocurrió un error durante el diagnóstico. Por favor, revisa el archivo de error de PHP para más detalles.";
}
