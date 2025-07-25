// scraper/races_scraper.js
// Genera data/calendar.json a partir de la tabla de calendario de carreras

const fs = require('fs');
const path = require('path');
const cheerio = require('cheerio');

const DATA_DIR = path.join(__dirname, '../data');
const TABLES_PATH = path.join(DATA_DIR, 'all_tables_full.json');

if (!fs.existsSync(TABLES_PATH)) {
    console.error('Falta data/all_tables_full.json. Ejecuta primero fetch_all_tables.js');
    process.exit(1);
}

// Cargar las tablas completas con su información de tipo
const tables = JSON.parse(fs.readFileSync(TABLES_PATH, 'utf8'));

// Buscar la tabla de calendario con mayor confianza
const calendarTables = tables
    .filter(t => t.type === 'calendar')
    .sort((a, b) => (b.confidence || 0) - (a.confidence || 0));

if (calendarTables.length === 0) {
    console.error('No se encontró ninguna tabla de calendario en:', TABLES_PATH);
    process.exit(1);
}

// Usar la tabla de calendario con mayor confianza
const calendarTable = calendarTables[0];
console.log(`Usando tabla de calendario #${calendarTable.index} con confianza ${(calendarTable.confidence * 100).toFixed(0)}%`);

// Cargar el HTML de la tabla con Cheerio
const $ = cheerio.load(calendarTable.html);
const rows = $('tr');
const races = [];

// Procesar las filas de la tabla
rows.each((i, row) => {
    // Obtener todas las celdas de la fila (td y th)
    const cells = $(row).find('td, th');
    if (cells.length < 4) return; // Necesitamos al menos 4 columnas
    
    // Saltar la fila de encabezados
    const firstCellText = cells.first().text().trim().toLowerCase();
    if (firstCellText === 'ronda' || firstCellText === 'n.º' || firstCellText === 'nº') {
        return;
    }
    
    // Extraer los datos de la fila
    const raceData = {
        round: null,
        title: '',
        location: '',
        date: '',
        status: 'Pendiente',
        winner: null,
        podium: [],
        weather: null
    };
    
    // Procesar cada celda según el índice
    cells.each((j, cell) => {
        const cellText = $(cell).text().trim();
        const cellHtml = $(cell).html() || '';
        
        // Determinar el tipo de dato basado en la posición de la celda
        if (j === 0) {
            // Primera columna - Ronda o número de carrera
            raceData.round = parseInt(cellText) || null;
        } else if (j === 1) {
            // Segunda columna - Nombre del Gran Premio
            raceData.title = cellText;
        } else if (j === 2) {
            // Tercera columna - Circuito y ubicación
            // Limpiar el texto para eliminar banderas y enlaces
            let locationText = cellText;
            // Eliminar cualquier referencia de bandera [imagen] y corchetes
            locationText = locationText.replace(/\[.*?\]/g, '').trim();
            raceData.location = locationText;
        } else if (j === 3) {
            // Cuarta columna - Fecha
            raceData.date = cellText;
        }
    });
    
    // Solo agregar la carrera si tiene un título válido
    if (raceData.title && raceData.title !== 'Título no disponible') {
        // Limpiar el título para eliminar corchetes y referencias
        raceData.title = raceData.title.replace(/\[.*?\]/g, '').trim();
        races.push(raceData);
    }
});

// Ordenar carreras por número de ronda
races.sort((a, b) => (a.round || 0) - (b.round || 0));

// Guardar los datos en un archivo JSON
const outputPath = path.join(DATA_DIR, 'calendar.json');
fs.writeFileSync(outputPath, JSON.stringify(races, null, 2), 'utf8');

console.log(`\nSe han guardado ${races.length} carreras en ${outputPath}`);

// Mostrar un resumen de las carreras encontradas
console.log('\nResumen del calendario:');
races.forEach(race => {
    console.log(`Ronda ${race.round}: ${race.title} - ${race.location} (${race.date})`);
});

// Exportar las carreras para usar en otros módulos
module.exports = { races };
