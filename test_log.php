<?php
// Ruta al archivo de log
$logPath = __DIR__ . '/data/php_errors.log';

// Intentar escribir en el archivo de log
$testMessage = "[TEST] Prueba de escritura en el log - " . date('Y-m-d H:i:s') . "\n";
$result = @file_put_contents($logPath, $testMessage, FILE_APPEND);

if ($result === false) {
    echo "Error al escribir en el archivo de log. Verifica los permisos.\n";
    $error = error_get_last();
    echo "Error: " . ($error['message'] ?? 'Error desconocido') . "\n";
} else {
    echo "Mensaje de prueba escrito correctamente en: " . realpath($logPath) . "\n";
}

// Mostrar información de permisos
echo "\nInformación de permisos:\n";
echo "Es legible: " . (is_readable($logPath) ? 'Sí' : 'No') . "\n";
echo "Es escribible: " . (is_writable($logPath) ? 'Sí' : 'No') . "\n";

// Mostrar información del propietario del archivo
$perms = fileperms($logPath);
echo "Permisos: " . substr(sprintf('%o', $perms), -4) . "\n";
?>
