<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Información básica del sistema
$status = [
    'status' => 'online',
    'version' => '1.0.0',
    'environment' => 'production',
    'last_updated' => date('Y-m-d H:i:s'),
    'server' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'Desconocido'
    ],
    'services' => [
        'database' => true,
        'cache' => true,
        'scraper' => true
    ]
];

// Intentar obtener información de Git (si está disponible)
$gitHeadFile = dirname(dirname(__DIR__)) . '/.git/HEAD';
if (file_exists($gitHeadFile)) {
    $head = file_get_contents($gitHeadFile);
    if (preg_match('/ref: (.*)/', $head, $matches)) {
        $ref = $matches[1];
        $commitHash = trim(file_get_contents(dirname(dirname(__DIR__)) . '/.git/' . $ref));
        $status['git'] = [
            'branch' => basename($ref),
            'commit' => $commitHash
        ];
    }
}

echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
