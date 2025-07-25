<?php
// Script para mostrar la ruta real del archivo y la carpeta pública
header('Content-Type: text/plain');
echo "Ruta absoluta de este archivo: \n" . __FILE__ . "\n\n";
echo "Directorio actual (getcwd): \n" . getcwd() . "\n\n";
echo "DOCUMENT_ROOT del servidor: \n" . $_SERVER['DOCUMENT_ROOT'] . "\n\n";
echo "SCRIPT_FILENAME: \n" . $_SERVER['SCRIPT_FILENAME'] . "\n\n";
echo "REQUEST_URI: \n" . $_SERVER['REQUEST_URI'] . "\n\n";
?>
