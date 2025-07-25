<?php
// Script para limpiar la caché de la aplicación

// Directorios a limpiar
$cacheDirs = [
    __DIR__ . '/../data/cache/',
    // Agrega aquí otros directorios de caché si es necesario
];

$results = [];
$success = true;

foreach ($cacheDirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $results[] = "Eliminado: $file";
                } else {
                    $results[] = "Error al eliminar: $file";
                    $success = false;
                }
            }
        }
    } else {
        $results[] = "Directorio no encontrado: $dir";
        $success = false;
    }
}

// Si no se encontraron archivos
if (empty($results)) {
    $results[] = "No se encontraron archivos en caché para eliminar.";
}

// Devolver resultados
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'results' => $results
]);
?>
