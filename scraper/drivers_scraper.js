// scraper/drivers_scraper.js
// Genera data/championship_drivers.json a partir de la tabla de clasificación de pilotos

const fs = require('fs');
const path = require('path');
const cheerio = require('cheerio');

const DATA_DIR = path.join(__dirname, '../data');
const TABLES_PATH = path.join(DATA_DIR, 'all_tables_full.json');

if (!fs.existsSync(TABLES_PATH)) {
    console.error('Falta data/all_tables_full.json. Ejecuta primero fetch_all_tables.js');
    process.exit(1);
}

const tables = JSON.parse(fs.readFileSync(TABLES_PATH, 'utf8'));
let table = null;
for (const t of tables) {
    if (!t.html || typeof t.html !== 'string') continue;
    const $ = cheerio.load(t.html);
    const headers = $('th').map((i, th) => $(th).text().trim()).get();
    if (headers.includes('Piloto') && headers.includes('Puntos')) {
        table = t;
        break;
    }
}
if (!table) {
    console.error('No se encontró tabla de clasificación de pilotos (ninguna tabla válida con html y encabezados Piloto + Puntos)');
    process.exit(1);
}

const $ = cheerio.load(table.html);
const rows = $('tr');
const drivers = [];

// Detectar los índices de las columnas relevantes
const headerCells = $(rows[0]).find('th');
let idxPos = -1, idxNum = -1, idxName = -1, idxPoints = -1;
headerCells.each((i, el) => {
    const txt = $(el).text().trim();
    if (txt === 'Pos.') idxPos = i;
    if (txt === 'N.º') idxNum = i;
    if (txt === 'Piloto') idxName = i;
    if (txt === 'Puntos') idxPoints = i;
});
if (idxPos === -1 || idxNum === -1 || idxName === -1 || idxPoints === -1) {
    console.error('No se detectaron correctamente los encabezados esperados en la tabla de clasificación de pilotos');
    process.exit(1);
}

rows.each((i, row) => {
    // Saltar la fila de encabezados
    if (i === 0) return;
    const cells = $(row).children('th,td');
    // Solo filas con el número correcto de columnas
    if (cells.length < Math.max(idxPos, idxNum, idxName, idxPoints) + 1) return;
    // La posición debe ser un número
    const pos = $(cells[idxPos]).text().trim();
    if (!/^[0-9]+$/.test(pos)) return;
    const num = $(cells[idxNum]).text().trim();
    // Extraer el nombre del piloto (limpia banderas y espacios)
    let name = $(cells[idxName]).find('a[title]').first().text().trim();
    if (!name) name = $(cells[idxName]).text().trim().replace(/\s+/g, ' ');
    // Extraer puntos
    let points = $(cells[idxPoints]).text().trim();
    drivers.push({
        position: pos,
        number: num,
        name,
        team: '',
        points: Number(points)
    });
});

const outPath = path.join(DATA_DIR, 'drivers.json');
fs.writeFileSync(outPath, JSON.stringify(drivers, null, 2), 'utf8');
console.log('Generado data/drivers.json con', drivers.length, 'pilotos');

fs.writeFileSync(
    path.join(DATA_DIR, 'championship_drivers.json'),
    JSON.stringify(drivers, null, 2),
    'utf8'
);
console.log('Generado data/championship_drivers.json con', drivers.length, 'pilotos.');
