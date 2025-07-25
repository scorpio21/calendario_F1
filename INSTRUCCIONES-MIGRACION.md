# Instrucciones para migrar a f1-2025.wuaze.com

Este documento contiene las instrucciones paso a paso para migrar correctamente el proyecto Calendario F1 2025 al dominio f1-2025.wuaze.com.

## Pasos previos al despliegue

### 1. Actualizar el archivo manifest.json

Edita el archivo `manifest.json` y cambia la ruta de inicio:

```json
{
  "start_url": "/index.html",
  // Resto del contenido sin cambios
}
```

### 2. Verificar rutas de recursos

Asegúrate de que todas las rutas a recursos (imágenes, CSS, JavaScript) sean relativas y no contengan referencias absolutas a `/calendarios/calendario-F1-2025/`.

### 3. Verificar APIs externas

- Asegúrate de que la clave API de WeatherAPI.com (`WEATHER_API_KEY`) esté configurada para permitir solicitudes desde el dominio f1-2025.wuaze.com
- Verifica que el sistema de scraping funcione correctamente desde el nuevo dominio

## Despliegue

### 1. Subir archivos

Sube todos los archivos del proyecto al servidor web que aloja f1-2025.wuaze.com.

### 2. Configurar el servidor web

- Configura el servidor para que sirva correctamente archivos estáticos
- Asegúrate de que el servidor esté configurado para HTTPS si planeas usar características como geolocalización
- Configura los encabezados CORS adecuados para permitir solicitudes a APIs externas

### 3. Incluir el script de migración

Añade el script de migración al final del archivo `index.html`, justo antes del cierre de la etiqueta `</body>`:

```html
<script src="migration-script.js"></script>
```

## Verificación post-despliegue

### 1. Ejecutar verificación automática

Una vez desplegado el sitio, abre la consola del navegador y ejecuta:

```javascript
checkMigrationStatus();
```

Esta función verificará:
- Que el contador regresivo se muestre correctamente
- Que el pronóstico del tiempo funcione
- Que las imágenes se carguen correctamente
- Que todos los scripts necesarios estén cargados

### 2. Verificar manualmente las funcionalidades principales

- Contador regresivo para el Gran Premio de Canadá (15 de junio)
- Pronóstico del tiempo para el circuito de la próxima carrera
- Visualización de circuitos y detalles
- Clasificación de pilotos y equipos
- Resultados de carreras anteriores

### 3. Solución de problemas comunes

Si encuentras alguno de estos problemas:

#### Imágenes no se cargan
Verifica las rutas en los archivos JavaScript que generan HTML dinámicamente.

#### APIs externas no funcionan
Verifica que las claves API estén configuradas correctamente y que el dominio f1-2025.wuaze.com esté autorizado.

#### Contador regresivo o pronóstico del tiempo no se muestran
Abre la consola del navegador para ver errores específicos y verifica que los datos de fallback estén configurados correctamente.

## Notas adicionales

- El script `migration-script.js` corrige automáticamente algunas rutas absolutas en tiempo de ejecución
- Para una solución más permanente, considera modificar directamente los archivos fuente antes del despliegue
- Recuerda que el contador y el pronóstico del tiempo están configurados para mostrar el Gran Premio de Canadá (15 de junio) como próxima carrera

## Contacto para soporte

Si encuentras problemas durante la migración, contacta al equipo de desarrollo para obtener asistencia.
