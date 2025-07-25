<?php
// Verificar si el servidor puede ejecutar comandos de Node.js
function testNodeJS() {
    $output = [];
    $return_var = 0;
    exec('node -v 2>&1', $output, $return_var);
    
    if ($return_var !== 0) {
        return [
            'success' => false,
            'message' => 'Node.js no está instalado o no está en el PATH',
            'output' => $output,
            'return_var' => $return_var
        ];
    }
    
    return [
        'success' => true,
        'version' => trim(implode('\n', $output)),
        'message' => 'Node.js está correctamente instalado'
    ];
}

// Verificar permisos de escritura
function testWritePermissions() {
    $testDir = __DIR__ . '/../data/test_' . time();
    $testFile = $testDir . '/test.txt';
    
    // Intentar crear directorio
    if (!@mkdir($testDir, 0755, true)) {
        return [
            'success' => false,
            'message' => 'No se pudo crear el directorio de prueba',
            'path' => $testDir
        ];
    }
    
    // Intentar escribir un archivo
    if (!file_put_contents($testFile, 'test')) {
        @rmdir($testDir);
        return [
            'success' => false,
            'message' => 'No se pudo escribir en el archivo de prueba',
            'path' => $testFile
        ];
    }
    
    // Limpiar
    unlink($testFile);
    rmdir($testDir);
    
    return [
        'success' => true,
        'message' => 'Los permisos de escritura son correctos'
    ];
}

// Ejecutar pruebas
header('Content-Type: application/json');

try {
    $results = [
        'nodejs' => testNodeJS(),
        'write_permissions' => testWritePermissions(),
        'server' => [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Desconocido',
            'script_filename' => __FILE__,
            'current_user' => get_current_user(),
            'user' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid()) : 'No disponible'
        ]
    ];
    
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
