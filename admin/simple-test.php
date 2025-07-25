<?php
// Encabezados para permitir CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Función para enviar respuesta JSON
function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

try {
    // Prueba básica de PHP
    $result = [
        'success' => true,
        'message' => 'Conexión exitosa al servidor',
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'No disponible',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'No disponible',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    sendJson($result);
    
} catch (Exception $e) {
    sendJson([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], 500);
}
