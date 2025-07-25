<?php
// Encabezados para permitir CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

// Mostrar todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "=== Prueba de ejecución de comandos ===\n\n";

// Función para probar un comando
function testCommand($cmd) {
    echo "Comando: $cmd\n";
    echo "----------------------------------------\n";
    
    // Ejecutar el comando
    exec($cmd . ' 2>&1', $output, $return_var);
    
    // Mostrar resultado
    echo "Código de salida: $return_var\n";
    echo "Salida:\n" . implode("\n", $output) . "\n\n";
    
    return $return_var === 0;
}

// Probar comandos básicos
testCommand('pwd');
testCommand('ls -la');
testCommand('which node || echo "Node no encontrado"');
testCommand('node -v || echo "Node no funciona"');

// Mostrar información del servidor
echo "\n=== Información del servidor ===\n";
echo "PHP version: " . phpversion() . "\n";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'No disponible') . "\n";
echo "Script filename: " . __FILE__ . "\n";

// Mostrar variables de entorno si están disponibles
if (function_exists('getenv')) {
    echo "\n=== Variables de entorno ===\n";
    echo "PATH: " . (getenv('PATH') ?: 'No disponible') . "\n";
}

// Mostrar información del usuario
echo "\n=== Usuario ===\n";
if (function_exists('get_current_user')) {
    echo "Usuario actual: " . get_current_user() . "\n";
}
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
    $processUser = posix_getpwuid(posix_geteuid());
    echo "Usuario del proceso: " . ($processUser['name'] ?? 'No disponible') . "\n";
}
