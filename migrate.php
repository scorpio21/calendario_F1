<?php

// --- Configuración ---
$projectRoot = __DIR__; // Directorio actual del script
$oldBaseUrl = '/calendarios/calendario-F1-2025/';
$newBaseUrl = '/';
$dryRun = false; // MODO DE PRUEBA: true para simular, false para aplicar cambios

// Archivos y directorios a procesar para reemplazo de rutas
$filesToProcess = [
    'index.html',
    'manifest.json',
    // Añade aquí otros archivos HTML si los tienes
];
$directoriesToProcess = [
    'js',
    'css',
    // Añade aquí otros directorios con CSS o JS
];
$fileExtensionsToProcess = ['html', 'css', 'js', 'json'];

// --- Funciones Auxiliares ---

function logMessage($message, $type = 'INFO') {
    echo "[$type] $message" . PHP_EOL;
}

function replaceInFile($filePath, $oldString, $newString, $isDryRun) {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        logMessage("Archivo no encontrado o no legible: $filePath", 'ERROR');
        return false;
    }

    $content = file_get_contents($filePath);
    $newContent = str_replace($oldString, $newString, $content);

    if ($content === $newContent) {
        logMessage("No se encontraron ocurrencias de '$oldString' en '$filePath'. No se realizaron cambios.", 'DEBUG');
        return false;
    }

    logMessage("Cambios propuestos para '$filePath':", 'INFO');
    // Para una mejor visualización de los cambios, podrías implementar un diff aquí
    // Por simplicidad, solo mostraremos si hay cambios.
    // echo "--- OLD ---\n" . substr($content, 0, 200) . "\n--- NEW ---\n" . substr($newContent, 0, 200) . PHP_EOL;


    if (!$isDryRun) {
        if (is_writable($filePath)) {
            if (file_put_contents($filePath, $newContent) !== false) {
                logMessage("Archivo '$filePath' actualizado correctamente.", 'SUCCESS');
                return true;
            } else {
                logMessage("Error al escribir en el archivo '$filePath'.", 'ERROR');
                return false;
            }
        } else {
            logMessage("El archivo '$filePath' no tiene permisos de escritura.", 'ERROR');
            return false;
        }
    } else {
        logMessage("DRY RUN: Se reemplazaría '$oldString' con '$newString' en '$filePath'.", 'INFO');
        return true; // En dry run, asumimos que el cambio se haría.
    }
}

function processDirectory($dirPath, $oldString, $newString, $extensions, $isDryRun) {
    logMessage("Procesando directorio: $dirPath", 'INFO');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath));
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), $extensions)) {
            replaceInFile($file->getPathname(), $oldString, $newString, $isDryRun);
        }
    }
}

// --- Lógica Principal del Script de Migración ---

logMessage("--- INICIO DEL SCRIPT DE MIGRACIÓN ---", 'IMPORTANT');
if ($dryRun) {
    logMessage("MODO DE PRUEBA (DRY RUN) ACTIVADO. No se realizarán cambios permanentes en los archivos.", 'WARNING');
}

// 1. Corregir rutas absolutas en archivos específicos y directorios
logMessage("--- Paso 1: Corrigiendo rutas ('$oldBaseUrl' -> '$newBaseUrl') ---", 'SECTION');
foreach ($filesToProcess as $fileName) {
    $filePath = $projectRoot . DIRECTORY_SEPARATOR . $fileName;
    replaceInFile($filePath, $oldBaseUrl, $newBaseUrl, $dryRun);
}

foreach ($directoriesToProcess as $dirName) {
    $dirPath = $projectRoot . DIRECTORY_SEPARATOR . $dirName;
    processDirectory($dirPath, $oldBaseUrl, $newBaseUrl, $fileExtensionsToProcess, $dryRun);
}

// 2. Actualizar manifest.json específicamente
logMessage("--- Paso 2: Actualizando manifest.json ---", 'SECTION');
$manifestPath = $projectRoot . DIRECTORY_SEPARATOR . 'manifest.json';
if (file_exists($manifestPath)) {
    $manifestContent = file_get_contents($manifestPath);
    $manifestData = json_decode($manifestContent, true);

    if ($manifestData) {
        $originalManifestData = $manifestData; // Copia para comparación

        // Actualizar start_url
        if (isset($manifestData['start_url']) && strpos($manifestData['start_url'], $oldBaseUrl) !== false) {
            $manifestData['start_url'] = str_replace($oldBaseUrl, $newBaseUrl, $manifestData['start_url']);
            // Asegurarse de que si era /calendarios/calendario-F1-2025/index.html ahora sea /index.html
            if ($manifestData['start_url'] === $newBaseUrl . 'index.html' && $newBaseUrl === '/') {
                 $manifestData['start_url'] = '/index.html';
            }
        }
        
        // Actualizar rutas de iconos
        if (isset($manifestData['icons']) && is_array($manifestData['icons'])) {
            foreach ($manifestData['icons'] as &$icon) { // Usar referencia para modificar el array original
                if (isset($icon['src']) && strpos($icon['src'], $oldBaseUrl) !== false) {
                    $icon['src'] = str_replace($oldBaseUrl, $newBaseUrl, $icon['src']);
                } elseif (isset($icon['src']) && strpos($icon['src'], './') === 0 && strpos($icon['src'], $oldBaseUrl) === false) {
                    // Si la ruta es relativa como "./img/icon.png" y no contiene la oldBaseUrl,
                    // la dejamos como está, asumiendo que es correcta para la nueva estructura.
                }
            }
            unset($icon); // Romper la referencia
        }

        if (json_encode($originalManifestData) !== json_encode($manifestData)) {
            logMessage("Cambios propuestos para 'manifest.json':", 'INFO');
            // echo json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            if (!$dryRun) {
                if (is_writable($manifestPath)) {
                    if (file_put_contents($manifestPath, json_encode($manifestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false) {
                        logMessage("'manifest.json' actualizado correctamente.", 'SUCCESS');
                    } else {
                        logMessage("Error al escribir en 'manifest.json'.", 'ERROR');
                    }
                } else {
                    logMessage("'manifest.json' no tiene permisos de escritura.", 'ERROR');
                }
            } else {
                logMessage("DRY RUN: 'manifest.json' se actualizaría.", 'INFO');
            }
        } else {
            logMessage("No se requieren cambios específicos de rutas en 'manifest.json' o ya están correctos.", 'DEBUG');
        }
    } else {
        logMessage("Error al decodificar 'manifest.json'.", 'ERROR');
    }
} else {
    logMessage("'manifest.json' no encontrado.", 'ERROR');
}


// 3. Recordatorios para configuración de APIs y servicios externos (similar a updateExternalServices)
logMessage("--- Paso 3: Recordatorios de Configuración de Servicios Externos ---", 'SECTION');
logMessage("Verificar que la API del tiempo (si se usa una clave) permita solicitudes desde el nuevo dominio f1-2025.wuaze.com.", 'REMINDER');
logMessage("Verificar que el sistema de scraping (si existe y es relevante del lado del servidor) funcione correctamente desde el nuevo dominio/servidor.", 'REMINDER');
logMessage("Verificar que los servicios de mapas (si se usan claves o configuraciones específicas del dominio) estén actualizados.", 'REMINDER');

// 4. Verificación del lado del servidor (similar a partes de checkMigrationStatus)
logMessage("--- Paso 4: Verificación de Archivos Críticos en Servidor ---", 'SECTION');
$criticalFiles = [
    'index.html',
    'manifest.json',
    'sw.js', // Service Worker
    'js/main.js',
    'css/styles.css',
    'js/countdown.js',
    'js/weather.js',
    // Añade otros archivos que consideres críticos
];
foreach ($criticalFiles as $file) {
    if (file_exists($projectRoot . DIRECTORY_SEPARATOR . $file)) {
        logMessage("Archivo crítico encontrado: $file", 'OK');
    } else {
        logMessage("Archivo crítico NO encontrado: $file", 'CRITICAL_ERROR');
    }
}

logMessage("--- FIN DEL SCRIPT DE MIGRACIÓN ---", 'IMPORTANT');
if ($dryRun) {
    logMessage("RECUERDA: Estás en MODO DE PRUEBA. Para aplicar los cambios, edita el script y cambia \$dryRun a false, luego vuelve a ejecutar.", 'WARNING');
}

?>