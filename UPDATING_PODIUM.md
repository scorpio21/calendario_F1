# Actualización de Datos - Calendario F1 2025

Este documento explica cómo actualizar los datos de la aplicación, incluyendo podios, clasificaciones y resultados de carreras.

## 1. Actualización Automática de Datos

### Actualizar todos los datos (carreras, pilotos y constructores)

1. **Abre una terminal** en la carpeta del proyecto
2. **Navega a la carpeta del scraper**:
   ```bash
   cd d:\xampp\htdocs\calendarios\calendario-F1-2025\scraper
   ```
3. **Instala las dependencias** (solo la primera vez):
   ```bash
   npm install
   ```
4. **Ejecuta el script de actualización**:
   ```bash
   node update_data.js
   ```

### ¿Qué datos se actualizan?

- **Carreras**: Información básica en `data/calendar.json`
- **Pilotos**: Clasificación en `data/drivers.json`
- **Constructores**: Clasificación en `data/teams.json`

### Notas importantes

- Los podios **no** se actualizan automáticamente con este proceso
- Los datos se obtienen de fuentes externas (Wikipedia, etc.)
- Revisa la salida en consola para verificar que todo se actualizó correctamente

## 2. Actualización de Podios

### Actualización Manual en el Código

1. **Localizar el archivo**:
   Abre `js/podium.js`

2. **Actualizar el podio de una carrera**:
   Busca la carrera en el objeto `racePodiums` y actualiza los datos:
   ```javascript
   "Nombre de la carrera": [
       { position: 1, name: "Piloto 1", team: "Equipo 1", image: "imagen1" },
       { position: 2, name: "Piloto 2", team: "Equipo 2", image: "imagen2" },
       { position: 3, name: "Piloto 3", team: "Equipo 3", image: "imagen3" }
   ]
   ```

### Actualización por Consola (Recomendada para pruebas)

1. **Abre la consola del navegador** (F12 > Consola)
2. **Ejecuta**:
   ```javascript
   updatePodium("Gran Premio de España", [
       { position: 1, name: "Max Verstappen", team: "Red Bull Racing", image: "max-verstappen" },
       { position: 2, name: "Lando Norris", team: "McLaren F1", image: "lando-norris" },
       { position: 3, name: "Carlos Sainz", team: "Ferrari", image: "carlos-sainz" }
   ]);
   ```

## 3. Consideraciones Importantes

1. **Nombres de carreras**: Deben coincidir exactamente con los nombres en `races-data.js`
2. **Imágenes**: Usar minúsculas, sin espacios, con guiones
3. **Equipos**: Usar nombres completos (ej: "Red Bull Racing", no solo "Red Bull")
4. **Verificación**:
   - Recarga la página después de los cambios
   - Revisa la consola del navegador por errores

## 4. Notas Adicionales

- Los cambios manuales en `podium.js` se mantendrán a menos que se actualice el archivo desde el repositorio
- Para cambios permanentes, actualiza `podium.js` directamente
- Usa `updatePodium()` para pruebas rápidas sin modificar el código fuente
