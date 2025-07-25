<?php
// Mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Establecer zona horaria
date_default_timezone_set('UTC');

// Función para verificar si una extensión está cargada
function check_extension($name) {
    return extension_loaded($name) ? '✅' : '❌';
}

// Función para verificar permisos de directorio
function check_dir($path) {
    if (!file_exists($path)) {
        return "No existe";
    }
    
    $perms = fileperms($path);
    $info = sprintf('%o', $perms);
    $isWritable = is_writable($path) ? 'Escribible' : 'No escribible';
    
    return "Permisos: $info ($isWritable)";
}

// Iniciar el buffer de salida
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico del Sistema</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        h2 { margin-top: 0; color: #333; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { 
            background: #f4f4f4; 
            padding: 10px; 
            border-radius: 5px; 
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Diagnóstico del Sistema</h1>
    
    <div class="section">
        <h2>Información del Servidor</h2>
        <ul>
            <li><strong>Servidor:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'); ?></li>
            <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
            <li><strong>Directorio actual:</strong> <?php echo __DIR__; ?></li>
            <li><strong>Zona horaria:</strong> <?php echo date_default_timezone_get(); ?></li>
            <li><strong>memory_limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
            <li><strong>max_execution_time:</strong> <?php echo ini_get('max_execution_time'); ?>s</li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Extensiones PHP</h2>
        <ul>
            <li>DOM: <?php echo check_extension('dom'); ?></li>
            <li>SimpleXML: <?php echo check_extension('simplexml'); ?></li>
            <li>libxml: <?php echo check_extension('libxml'); ?></li>
            <li>cURL: <?php echo check_extension('curl'); ?></li>
            <li>JSON: <?php echo check_extension('json'); ?></li>
            <li>fileinfo: <?php echo check_extension('fileinfo'); ?></li>
            <li>mbstring: <?php echo check_extension('mbstring'); ?></li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Permisos de Directorios</h2>
        <ul>
            <li><strong>Directorio raíz:</strong> <?php echo check_dir(__DIR__); ?></li>
            <li><strong>Directorio de datos:</strong> <?php echo check_dir(__DIR__ . '/data'); ?></li>
            <li><strong>Directorio admin:</strong> <?php echo check_dir(__DIR__ . '/admin'); ?></li>
            <li><strong>Directorio js:</strong> <?php echo check_dir(__DIR__ . '/js'); ?></li>
            <li><strong>Directorio css:</strong> <?php echo check_dir(__DIR__ . '/css'); ?></li>
        </ul>
    </div>
    
    <div class="section">
        <h2>Prueba de Conexión</h2>
        <?php
        $testUrl = 'https://en.wikipedia.org';
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
        
        echo "<p>Probando conexión a $testUrl...</p>";
        
        $content = @file_get_contents($testUrl, false, $context);
        
        if ($content === false) {
            echo "<p class='error'>❌ No se pudo conectar a $testUrl</p>";
            echo "<p>Error: " . htmlspecialchars(print_r(error_get_last(), true)) . "</p>";
        } else {
            echo "<p class='success'>✅ Conexión exitosa a $testUrl</p>";
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Prueba de Escritura</h2>
        <?php
        $testDir = __DIR__ . '/data';
        $testFile = $testDir . '/test_write.txt';
        $testContent = 'Prueba de escritura ' . date('Y-m-d H:i:s');
        
        echo "<p>Intentando escribir en: " . htmlspecialchars($testFile) . "</p>";
        
        // Crear directorio si no existe
        if (!file_exists($testDir)) {
            if (@mkdir($testDir, 0755, true)) {
                echo "<p class='success'>✅ Directorio creado: $testDir</p>";
            } else {
                echo "<p class='error'>❌ No se pudo crear el directorio: $testDir</p>";
                echo "<p>Error: " . htmlspecialchars(print_r(error_get_last(), true)) . "</p>";
            }
        }
        
        // Intentar escribir en el archivo
        if (file_put_contents($testFile, $testContent) !== false) {
            echo "<p class='success'>✅ Archivo creado correctamente</p>";
            echo "<p>Contenido: " . htmlspecialchars($testContent) . "</p>";
            
            // Intentar leer el archivo
            $readContent = @file_get_contents($testFile);
            if ($readContent === $testContent) {
                echo "<p class='success'>✅ Lectura del archivo exitosa</p>";
            } else {
                echo "<p class='error'>❌ Error al leer el archivo</p>";
                echo "<p>Error: " . htmlspecialchars(print_r(error_get_last(), true)) . "</p>";
            }
            
            // Limpiar: eliminar el archivo de prueba
            @unlink($testFile);
        } else {
            echo "<p class='error'>❌ No se pudo escribir en el archivo</p>";
            echo "<p>Error: " . htmlspecialchars(print_r(error_get_last(), true)) . "</p>";
            
            // Verificar si el directorio es escribible
            if (is_dir($testDir) && !is_writable($testDir)) {
                echo "<p class='warning'>⚠️ El directorio no tiene permisos de escritura</p>";
                echo "<p>Permisos actuales: " . substr(sprintf('%o', fileperms($testDir)), -4) . "</p>";
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>Información de PHP</h2>
        <pre><?php print_r([
            'PHP Version' => phpversion(),
            'SAPI' => php_sapi_name(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'allow_url_fopen' => ini_get('allow_url_fopen'),
            'disable_functions' => ini_get('disable_functions'),
            'open_basedir' => ini_get('open_basedir'),
            'include_path' => get_include_path(),
            'Loaded Extensions' => get_loaded_extensions()
        ]); ?></pre>
    </div>
    
    <div class="section">
        <h2>Variables de Entorno</h2>
        <pre><?php print_r($_SERVER); ?></pre>
    </div>
</body>
</html>
<?php
// Enviar el buffer de salida
ob_end_flush();
