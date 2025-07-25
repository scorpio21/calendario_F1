<?php
// Encabezados para permitir CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Función para ejecutar un comando de forma segura
function executeCommand($command) {
    $output = [];
    $return_var = 0;
    
    // Ejecutar el comando
    exec($command . ' 2>&1', $output, $return_var);
    
    return [
        'command' => $command,
        'output' => $output,
        'return_code' => $return_var,
        'success' => $return_var === 0
    ];
}

try {
    // Pruebas básicas
    $tests = [
        'node_version' => executeCommand('node -v'),
        'npm_version' => executeCommand('npm -v'),
        'whoami' => executeCommand('whoami'),
        'pwd' => executeCommand('pwd'),
        'ls_root' => executeCommand('ls -la /'),
        'php_user' => [
            'function' => 'get_current_user',
            'result' => function_exists('get_current_user') ? get_current_user() : 'No disponible'
        ],
        'php_process_user' => [
            'function' => 'posix_getpwuid',
            'result' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid()) : 'No disponible'
        ]
    ];
    
    // Resultado final
    $result = [
        'success' => true,
        'message' => 'Pruebas completadas',
        'server' => [
            'php_version' => phpversion(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'No disponible',
            'script_filename' => __FILE__
        ],
        'tests' => $tests
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
