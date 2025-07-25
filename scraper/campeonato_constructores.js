// scraper/campeonato_constructores.js
// Extrae la tabla del Campeonato de Constructores y guarda data/constructor_standings.json

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

// Buscar tabla de Constructores por headers exactos
const targetHeaders = [
    'Pos.', 'Constructor', 'Chasis', 'Motor', 'Grandes Premios', 'Victorias',
    'Podios', 'Poles', 'Vueltas rápidas', 'Vueltas lideradas', 'Puntos'
];
let tableConstructores = null;
for (const table of tables) {
    if (!table.html) continue;
    const $ = cheerio.load(table.html);
    let headers = [];
    $('tr').first().find('th,td').each((_, el) => headers.push($(el).text().trim()));
    const headersLower = headers.map(h => h.toLowerCase());
    const match = targetHeaders.every(h => headersLower.includes(h.toLowerCase()));
    if (match) {
        tableConstructores = table;
        break;
    }
}
if (!tableConstructores) {
    console.error('No se encontró la tabla de Campeonato de Constructores');
    process.exit(1);
}

const $ = cheerio.load(tableConstructores.html);
let headers = [];
$('tr').first().find('th,td').each((_, el) => headers.push($(el).text().trim()));
const idxPosition = headers.findIndex(h => h.toLowerCase().includes('pos'));
const idxName = headers.findIndex(h => h.toLowerCase().includes('constructor'));
const idxPoints = headers.findIndex(h => h.toLowerCase().includes('puntos'));

const teams = [];
$('tr').slice(1).each((_, row) => {
    const cells = $(row).find('td, th');
    if (cells.length < 3) return;
    teams.push({
        position: $(cells[idxPosition]).text().trim(),
        name: $(cells[idxName]).text().trim(),
        points: parseInt($(cells[idxPoints]).text().trim().replace(/[^0-9]/g, '')) || 0
    });
});

fs.writeFileSync(path.join(DATA_DIR, 'constructor_standings.json'), JSON.stringify(teams, null, 2));
console.log('Generado data/constructor_standings.json con', teams.length, 'equipos.');
