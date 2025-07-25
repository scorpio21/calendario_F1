<?php
// Permitir solicitudes desde cualquier origen (solo para desarrollo)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$data = json_decode(file_get_contents("php://input"));
$logMessage = date('Y-m-d H:i:s') . " - " . print_r($data, true) . "\n";
$logFile = __DIR__ . '/debug.log';

// Escribir en el archivo de log
file_put_contents($logFile, $logMessage, FILE_APPEND);

echo json_encode(["status" => "success"]);
?>
