<?php
// Incluir configuración primero
require_once __DIR__ . '/../../admin/config.php';

// Configuración de encabezados para CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');

// Manejar solicitud OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir Scraper después de la configuración
require_once __DIR__ . '/../../admin/includes/Scraper.php';

// Verificar que los directorios existen
if (!defined('DATA_DIR') || !defined('CACHE_DIR')) {
    die(json_encode(['error' => 'Configuración incorrecta: directorios no definidos']));
}

// Asegurar que los directorios existen
try {
    if (!file_exists(DATA_DIR)) {
        if (!@mkdir(DATA_DIR, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de datos');
        }
    }
    
    if (!file_exists(CACHE_DIR)) {
        if (!@mkdir(CACHE_DIR, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de caché');
        }
    }
    
    // Configurar archivo de log
    $logPath = DATA_DIR . '/php_errors.log';
    if (!file_exists($logPath)) {
        @file_put_contents($logPath, '');
        @chmod($logPath, 0666);
    }
    
    ini_set('log_errors', 1);
    ini_set('error_log', $logPath);
    
} catch (Exception $e) {
    error_log('Error de inicialización: ' . $e->getMessage());
    die(json_encode(['error' => 'Error de inicialización del servidor']));
}

// Habilitar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Los encabezados ya están definidos al inicio del archivo

// Función para enviar respuesta JSON
function sendResponse($data, $statusCode = 200) {
    // Mostrar todos los errores en la respuesta
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    // Configurar encabezados
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('X-Content-Type-Options: nosniff');
    
    // Si hay un error 500, incluir información detallada
    if ($statusCode >= 400) {
        $data['debug'] = [
            'error_get_last' => error_get_last(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'included_files' => get_included_files(),
            'php_version' => phpversion(),
            'system' => php_uname(),
            'extensions' => get_loaded_extensions()
        ];
    }
    
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit();
}

// Función para enviar datos en tiempo real
function sendStreamData($data) {
    echo json_encode($data) . "\n";
    ob_flush();
    flush();
}

try {
    // Registrar la petición entrante
    error_log('=== Nueva petición ===');
    error_log('Método: ' . $_SERVER['REQUEST_METHOD']);
    error_log('Headers: ' . print_r(getallheaders(), true));
    
    // Obtener el cuerpo de la petición
    $input = file_get_contents('php://input');
    error_log('Cuerpo de la petición: ' . $input);
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos en la petición');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errorMsg = 'Error al decodificar JSON: ' . json_last_error_msg();
        error_log($errorMsg);
        throw new Exception($errorMsg);
    }
    
    error_log('Datos decodificados: ' . print_r($data, true));

    $command = $data['command'] ?? '';
    error_log('Comando recibido: ' . $command);
    
    if (empty($command)) {
        $errorMsg = 'No se especificó ningún comando';
        error_log($errorMsg);
        throw new Exception($errorMsg);
    }

    // Configurar encabezados para streaming
   // header('Content-Type: text/event-stream');
    //header('Cache-Control: no-cache');
   // header('X-Accel-Buffering: no');
    
    // Inicializar scraper
    $scraper = new Scraper();
    $result = [];
    
    // Ejecutar el comando correspondiente
    switch ($command) {
        case 'update_all':
            $result = $scraper->updateRaces();
            // Aquí podrías agregar más actualizaciones
            break;
            
        case 'update_races':
            $result = $scraper->updateRaces();
            break;
            
        case 'update_drivers':
            $result = ['success' => false, 'message' => 'Función no implementada aún'];
            break;
            
        case 'update_teams':
            $result = ['success' => false, 'message' => 'Función no implementada aún'];
            break;
            
        case 'scrape_wikipedia':
            error_log('Iniciando scrape_wikipedia');
            $url = $data['url'] ?? '';
            $type = $data['type'] ?? 'calendar'; // Por defecto a 'calendar' si no se especifica
            
            error_log('URL a procesar: ' . $url);
            error_log('Tipo de scraping: ' . $type);
            
            if (empty($url)) {
                $errorMsg = 'No se especificó la URL de Wikipedia';
                error_log($errorMsg);
                throw new Exception($errorMsg);
            }
            
            // Validar que la URL sea de Wikipedia
            if (strpos($url, 'wikipedia.org') === false) {
                $errorMsg = 'Solo se permiten URLs de Wikipedia';
                error_log($errorMsg);
                throw new Exception($errorMsg);
            }
            
            $scraper = new Scraper();
            
            try {
                error_log("Iniciando scraping de Wikipedia - Tipo: $type - URL: $url");
                
                if ($type === 'results') {
                    $result = $scraper->scrapeWikipediaResults($url);
                } else {
                    $result = $scraper->scrapeWikipediaCalendar($url);
                }
                
                if (empty($result)) {
                    throw new Exception('No se encontraron datos en la página');
                }
                
                error_log("Scraping completado exitosamente - Elementos encontrados: " . count($result));
                
                sendResponse([
                    'success' => true, 
                    'data' => $result,
                    'cache_info' => [
                        'cache_dir' => CACHE_DIR,
                        'cache_files' => glob(CACHE_DIR . '/*.cache')
                    ]
                ]);
                
            } catch (Exception $e) {
                $errorMsg = 'Error al obtener datos de Wikipedia: ' . $e->getMessage();
                error_log($errorMsg);
                
                // Intentar devolver datos en caché si están disponibles
                try {
                    $cachedContent = $scraper->getCachedContent($url, 0); // Forzar lectura del caché
                    if ($cachedContent !== false) {
                        error_log("Devolviendo datos en caché");
                        sendResponse([
                            'success' => true,
                            'data' => $cachedContent,
                            'cached' => true,
                            'warning' => $errorMsg
                        ]);
                        return;
                    }
                } catch (Exception $cacheEx) {
                    error_log("Error al obtener datos en caché: " . $cacheEx->getMessage());
                }
                
                sendResponse([
                    'error' => 'Error al obtener datos de Wikipedia',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
            break;
            
        case 'clear_cache':
            $result = $scraper->clearCache();
            break;
            
        default:
            throw new Exception('Comando no reconocido: ' . $command);
    }
    
    // Enviar resultado final
    sendResponse([
        'success' => true,
        'output' => is_array($result) ? json_encode($result, JSON_PRETTY_PRINT) : $result
    ]);
    
} catch (Exception $e) {
    // Registrar el error
    error_log('Error en execute.php: ' . $e->getMessage());
    
    // Enviar respuesta de error
    $errorResponse = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    sendResponse($errorResponse, 500);
}
?>
