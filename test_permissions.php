<?php
// Mostrar información del servidor
echo "<h2>Información del servidor</h2>";
echo "Usuario: " . get_current_user() . "<br>";
echo "Usuario PHP: " . exec('whoami') . "<br>";
echo "Directorio actual: " . __DIR__ . "<br>";

// Ruta al directorio de datos
$dataDir = __DIR__ . '/data';
$logFile = $dataDir . '/php_errors.log';

echo "<h2>Información del directorio de datos</h2>";
echo "Ruta: " . realpath($dataDir) . "<br>";
echo "Existe: " . (file_exists($dataDir) ? 'Sí' : 'No') . "<br>";
echo "Es directorio: " . (is_dir($dataDir) ? 'Sí' : 'No') . "<br>";
echo "Es escribible: " . (is_writable($dataDir) ? 'Sí' : 'No') . "<br>";
echo "Permisos: " . substr(sprintf('%o', fileperms($dataDir)), -4) . "<br>";

echo "<h2>Información del archivo de log</h2>";
echo "Ruta: " . realpath($logFile) . "<br>";
echo "Existe: " . (file_exists($logFile) ? 'Sí' : 'No') . "<br>";
echo "Es archivo: " . (is_file($logFile) ? 'Sí' : 'No') . "<br>";
echo "Es escribible: " . (is_writable($logFile) ? 'Sí' : 'No') . "<br>";

if (file_exists($logFile)) {
    echo "Tamaño: " . filesize($logFile) . " bytes<br>";
    echo "Última modificación: " . date('Y-m-d H:i:s', filemtime($logFile)) . "<br>";
    echo "Permisos: " . substr(sprintf('%o', fileperms($logFile)), -4) . "<br>";
}

// Intentar escribir en el archivo de log
$testMessage = "[TEST] Prueba de escritura en el log - " . date('Y-m-d H:i:s') . "\n";
$result = @file_put_contents($logFile, $testMessage, FILE_APPEND);

echo "<h2>Prueba de escritura</h2>";
if ($result === false) {
    echo "<p style='color:red;'>Error al escribir en el archivo de log.</p>";
    $error = error_get_last();
    echo "Error: " . ($error['message'] ?? 'Error desconocido') . "<br>";
} else {
    echo "<p style='color:green;'>Mensaje de prueba escrito correctamente en: " . realpath($logFile) . "</p>";
    echo "Bytes escritos: " . $result . "<br>";
}

// Mostrar contenido actual del log
echo "<h2>Contenido actual del log</h2>";
if (file_exists($logFile) && is_readable($logFile)) {
    $content = file_get_contents($logFile);
    if ($content === false) {
        echo "No se pudo leer el archivo de log.";
    } else {
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
    }
} else {
    echo "El archivo de log no existe o no se puede leer.";
}
?>
