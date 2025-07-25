<?php

// Cargar las clases DOM necesarias
use DOMDocument;
use DOMXPath;
use Exception;

class Scraper {
    private $baseUrl = 'https://www.formula1.com';
    private $cacheTime = 3600; // 1 hora de caché
    
    public function __construct() {
        try {
            // Crear directorios necesarios con manejo de errores detallado
            $this->ensureDirectoryExists(DATA_DIR);
            $this->ensureDirectoryExists(CACHE_DIR);
            
            // Verificar si las extensiones necesarias están cargadas
            $requiredExtensions = ['dom', 'simplexml', 'libxml', 'curl'];
            $missingExtensions = [];
            
            foreach ($requiredExtensions as $ext) {
                if (!extension_loaded($ext)) {
                    $missingExtensions[] = $ext;
                }
            }
            
            if (!empty($missingExtensions)) {
                throw new Exception("Las siguientes extensiones de PHP son requeridas pero no están cargadas: " . implode(', ', $missingExtensions));
            }
            
            error_log("Scraper inicializado correctamente en modo " . (IS_PRODUCTION ? 'producción' : 'desarrollo'));
        } catch (Exception $e) {
            error_log("Error al inicializar Scraper: " . $e->getMessage());
            throw $e; // Relanzar la excepción para que el llamador la maneje
        }
    }
    
    /**
     * Asegura que un directorio exista y tenga los permisos correctos
     */
    private function ensureDirectoryExists($dir) {
        if (!file_exists($dir)) {
            $result = @mkdir($dir, 0755, true);
            if ($result === false) {
                $error = error_get_last();
                throw new Exception("No se pudo crear el directorio {$dir}: " . ($error['message'] ?? 'Error desconocido'));
            }
            error_log("Directorio creado: {$dir}");
        } elseif (!is_dir($dir)) {
            throw new Exception("La ruta {$dir} existe pero no es un directorio");
        } elseif (!is_writable($dir)) {
            throw new Exception("El directorio {$dir} no tiene permisos de escritura");
        }
        return true;
    }
    
    /**
     * Obtiene el nombre del archivo de caché para una URL
     */
    private function getCacheFilename($url) {
        $filename = md5($url) . '.cache';
        return CACHE_DIR . '/' . $filename;
    }
    
    /**
     * Obtiene el contenido de una URL con caché usando cURL
     * 
     * @param string $url URL a obtener
     * @param int|null $cacheTime Tiempo de caché en segundos (null para usar el valor por defecto, 0 para forzar caché)
     * @return string|false Contenido de la URL o false en caso de error
     */
    public function getCachedContent($url, $cacheTime = null) {
        $cacheFile = $this->getCacheFilename($url);
        $cacheTime = $cacheTime ?? $this->cacheTime;
        
        // Verificar si hay una versión en caché
        if (file_exists($cacheFile)) {
            // Si cacheTime es 0, siempre devolver el caché si existe
            if ($cacheTime === 0) {
                error_log("Devolviendo datos en caché forzado para: $url");
                return file_get_contents($cacheFile);
            }
            
            // Verificar si el caché sigue siendo válido
            if ((time() - filemtime($cacheFile)) < $cacheTime) {
                error_log("Devolviendo datos en caché válido para: $url");
                return file_get_contents($cacheFile);
            }
        }
        
        // Si cacheTime es 0, solo devolver caché si existe
        if ($cacheTime === 0) {
            error_log("No se encontró caché para: $url");
            return false;
        }
        
        error_log("Solicitando contenido fresco para: $url");
        
        // Inicializar cURL
        $ch = curl_init();
        
        // Configurar opciones de cURL
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FAILONERROR => true
        ]);
        
        // Ejecutar la solicitud
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Verificar si la solicitud fue exitosa
        if ($content === false || $httpCode !== 200) {
            $errorMsg = "Error al obtener $url - Código: $httpCode - Error: $error";
            error_log($errorMsg);
            
            // Si hay un error, intentar devolver la versión en caché si existe
            if (file_exists($cacheFile)) {
                error_log("Devolviendo versión en caché debido a error en la solicitud");
                return file_get_contents($cacheFile);
            }
            
            return false;
        }
        
        // Asegurarse de que el directorio de caché existe
        $cacheDir = dirname($cacheFile);
        if (!file_exists($cacheDir)) {
            if (!@mkdir($cacheDir, 0755, true)) {
                error_log("No se pudo crear el directorio de caché: $cacheDir");
                // Continuar sin caché
                return $content;
            }
        }
        
        // Guardar en caché
        if (@file_put_contents($cacheFile, $content) === false) {
            error_log("No se pudo guardar en caché el contenido de: $url");
        } else {
            error_log("Contenido guardado en caché: $cacheFile");
        }
        
        return $content;
    }
    
    /**
     * Actualiza los datos de las carreras
     */
    public function updateRaces() {
        $url = $this->baseUrl . '/en/racing/' . date('Y') . '.html';
        $content = $this->getCachedContent($url, 'races_' . date('Y') . '.html');
        
        if (!$content) {
            return ['success' => false, 'error' => 'No se pudieron obtener los datos de las carreras'];
        }
        
        // Aquí iría el código para parsear el HTML y extraer los datos
        // Por ahora, devolvemos un ejemplo
        $races = [
            // Datos de ejemplo
        ];
        
        // Guardar los datos procesados
        $result = file_put_contents(DATA_DIR . '/races.json', json_encode($races, JSON_PRETTY_PRINT));
        
        return [
            'success' => $result !== false,
            'updated' => date('Y-m-d H:i:s'),
            'race_count' => count($races)
        ];
    }
    
    /**
     * Limpia la caché de archivos
     */
    public function clearCache() {
        $files = glob(CACHE_DIR . '/*');
        $deleted = 0;
        $errors = [];
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if (@unlink($file)) {
                    $deleted++;
                } else {
                    $errors[] = "No se pudo eliminar: " . basename($file);
                }
            }
        }
        
        return [
            'success' => empty($errors),
            'deleted' => $deleted,
            'errors' => $errors,
            'message' => empty($errors) 
                ? "Se eliminaron $deleted archivos de caché"
                : "Se encontraron errores al eliminar algunos archivos"
        ];
    }
    
    /**
     * Obtiene el calendario de carreras desde Wikipedia
     */
    public function scrapeWikipediaCalendar($url) {
        try {
            // Verificar si la extensión DOM está disponible
            if (!extension_loaded('dom')) {
                throw new Exception('La extensión DOM de PHP no está habilitada');
            }
            
            // Obtener el contenido usando el método con caché
            $html = $this->getCachedContent($url);
            
            if ($html === false) {
                throw new Exception('No se pudo obtener el contenido de Wikipedia');
            }
            
            // Cargar el HTML en DOMDocument
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
            
            $errors = libxml_get_errors();
            if (!empty($errors)) {
                error_log('Advertencias de análisis HTML: ' . print_r($errors, true));
                libxml_clear_errors();
            }
            
            $xpath = new DOMXPath($dom);
            
            // Buscar la tabla de calendario (primera tabla con clase 'wikitable')
            $tables = $xpath->query("//table[contains(@class, 'wikitable')]");
            
            if ($tables->length === 0) {
                throw new Exception('No se encontraron tablas con la clase "wikitable" en la página');
            }
            
            $table = $tables->item(0);
            
            $races = [];
            // Usar XPath para obtener las filas
            $rows = $xpath->query('.//tr', $table);
            
            foreach ($rows as $row) {
                $cols = $xpath->query('.//td', $row);
                
                // Esperamos al menos 8 columnas (Nº, Fecha, Gran Premio, Circuito, Localidad, Resultados, Clasificación, Referencia)
                if ($cols->length >= 8) {
                    $race = [
                        'round' => trim($cols->item(0)->nodeValue),
                        'date' => trim($cols->item(1)->nodeValue),
                        'grand_prix' => trim($cols->item(2)->nodeValue),
                        'circuit' => trim($cols->item(3)->nodeValue),
                        'location' => trim($cols->item(4)->nodeValue),
                        'results' => trim($cols->item(5)->nodeValue),
                        'qualifying' => trim($cols->item(6)->nodeValue),
                        'reference' => trim($cols->item(7)->nodeValue)
                    ];
                    
                    $races[] = $race;
                }
            }
            
            return $races;
            
        } catch (Exception $e) {
            error_log('Error en scrapeWikipediaCalendar: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtiene los resultados de carreras desde Wikipedia
     */
    public function scrapeWikipediaResults($url) {
        try {
            // Verificar si la extensión DOM está disponible
            if (!extension_loaded('dom')) {
                throw new Exception('La extensión DOM de PHP no está habilitada');
            }
            
            // Obtener el contenido usando el método con caché
            $html = $this->getCachedContent($url);
            
            if ($html === false) {
                throw new Exception('No se pudo obtener el contenido de Wikipedia');
            }
            
            // Cargar el HTML en DOMDocument
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
            
            $errors = libxml_get_errors();
            if (!empty($errors)) {
                error_log('Advertencias de análisis HTML: ' . print_r($errors, true));
                libxml_clear_errors();
            }
            
            $xpath = new DOMXPath($dom);
            
            // Buscar la tabla de resultados (segunda tabla con clase 'wikitable')
            $tables = $xpath->query("//table[contains(@class, 'wikitable')]");
            
            if ($tables->length < 2) {
                throw new Exception('No se encontró la tabla de resultados. Se esperaba al menos 2 tablas, pero se encontraron ' . $tables->length);
            }
            
            $results = [];
            // Procesar la segunda tabla (índice 1)
            $table = $tables->item(1);
            $rows = $xpath->query('.//tr', $table);
            
            // Saltar la fila de encabezado
            foreach ($rows as $i => $row) {
                if ($i === 0) continue; // Saltar encabezado
                
                $cells = $xpath->query('.//td', $row);
                
                if ($cells->length >= 3) {
                    $result = [
                        'position' => trim($cells->item(0)->nodeValue),
                        'number' => trim($cells->item(1)->nodeValue),
                        'driver' => trim($cells->item(2)->nodeValue),
                        'constructor' => $cells->length > 3 ? trim($cells->item(3)->nodeValue) : '',
                        'laps' => $cells->length > 4 ? trim($cells->item(4)->nodeValue) : '',
                        'time' => $cells->length > 5 ? trim($cells->item(5)->nodeValue) : '',
                        'points' => $cells->length > 6 ? trim($cells->item(6)->nodeValue) : ''
                    ];
                    
                    $results[] = $result;
                }
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log('Error en scrapeWikipediaResults: ' . $e->getMessage());
            throw $e;
        }
    }
    
    // Más métodos para actualizar pilotos, equipos, etc.
}
