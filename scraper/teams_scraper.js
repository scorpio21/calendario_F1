// scraper/teams_scraper.js
// Genera data/constructor_standings.json a partir de la tabla de clasificación de constructores

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
    if (headers.includes('Constructor') && headers.includes('Puntos')) {
        table = t;
        break;
    }
}
if (!table) {
    console.error('No se encontró tabla de clasificación de constructores (ninguna tabla válida con html y encabezados Constructor + Puntos)');
    process.exit(1);
}

const $ = cheerio.load(table.html);
const rows = $('tr');
const teams = [];
rows.each((i, row) => {
    const th = $(row).children('th');
    const td = $(row).children('td');
    if (th.length === 0 || td.length === 0) return;
    const pos = parseInt($(th[0]).text().trim());
    if (isNaN(pos)) return;
    teams.push({
        position: pos,
        name: $(td[0]).text().trim(),
        points: parseInt($(th[th.length - 1]).text().trim()) || 0,
        nationality: $(td[1]).text().trim(),
        engine: $(td[2]).text().trim(),
        image: $(td[0]).find('img').attr('src') || null
    });
});

fs.writeFileSync(
    path.join(DATA_DIR, 'constructor_standings.json'),
    JSON.stringify(teams, null, 2),
    'utf8'
);
console.log('Generado data/constructor_standings.json con', teams.length, 'equipos.');
