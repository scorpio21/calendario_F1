// scraper/campeonato_pilotos.js
// Extrae la tabla del Campeonato de Pilotos y guarda data/drivers.json

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

// Buscar tabla de Campeonato de Pilotos por headers exactos
const targetHeaders = [
    'Pos.', 'Piloto', 'Escudería', 'Grandes Premios', 'Victorias',
    'Podios', 'Poles', 'Vueltas rápidas', 'Vueltas lideradas', 'Puntos'
];
let tablePilotos = null;
for (const table of tables) {
    if (!table.html) continue;
    const $ = cheerio.load(table.html);
    let headers = [];
    $('tr').first().find('th,td').each((_, el) => headers.push($(el).text().trim()));
    const headersLower = headers.map(h => h.toLowerCase());
    const match = targetHeaders.every(h => headersLower.includes(h.toLowerCase()));
    if (match) {
        tablePilotos = table;
        break;
    }
}
if (!tablePilotos) {
    console.error('No se encontró la tabla de Campeonato de Pilotos');
    process.exit(1);
}

const $ = cheerio.load(tablePilotos.html);
let headers = [];
$('tr').first().find('th,td').each((_, el) => headers.push($(el).text().trim()));
const idxPosition = headers.findIndex(h => h.toLowerCase().includes('pos'));
const idxName = headers.findIndex(h => h.toLowerCase().includes('piloto'));
const idxTeam = headers.findIndex(h => h.toLowerCase().includes('escudería'));
const idxPoints = headers.findIndex(h => h.toLowerCase().includes('puntos'));

const drivers = [];
$('tr').slice(1).each((_, row) => {
    const cells = $(row).find('td, th');
    if (cells.length < 4) return;
    let position = $(cells[idxPosition]).text().trim();
    let name = $(cells[idxName]).text().trim();
    let team = $(cells[idxTeam]).text().trim();
    let points = parseInt($(cells[idxPoints]).text().trim().replace(/[^0-9]/g, '')) || 0;
    // Buscar enlace a Wikipedia
    let wikiLink = '';
    const nameCell = $(cells[idxName]);
    const link = nameCell.find('a');
    if (link.length) {
        wikiLink = link.attr('href') || '';
        if (wikiLink && wikiLink.startsWith('/wiki/')) {
            wikiLink = 'https://es.wikipedia.org' + wikiLink;
        }
    }
    drivers.push({
        position,
        name,
        team,
        points,
        wikiLink
    });
});

fs.writeFileSync(path.join(DATA_DIR, 'drivers.json'), JSON.stringify(drivers, null, 2));
console.log('Generado data/drivers.json con', drivers.length, 'pilotos.');
