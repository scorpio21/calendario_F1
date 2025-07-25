// Script para actualizar el campo podium en js/races-data.js usando data/results.json
// Ejecutar: node scraper/update_podiums.js

const fs = require('fs');
const path = require('path');

const RESULTS_PATH = path.join(__dirname, '../data/results.json');
const RACES_DATA_PATH = path.join(__dirname, '../js/races-data.js');
const BACKUP_PATH = path.join(__dirname, '../js/races-data.js.bak');

function normalizarTitulo(t) {
    // Elimina detalles de circuito y solo deja el nombre del GP
    return t.replace(/\s*Circuito.*$/i, '').replace(/\s*Autódromo.*$/i, '').trim();
}

function slugify(name) {
    return name
        .toLowerCase()
        .normalize('NFD').replace(/\p{Diacritic}/gu, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

// Leer resultados
const results = JSON.parse(fs.readFileSync(RESULTS_PATH, 'utf8'));
// Leer races-data.js como texto
const racesDataSrc = fs.readFileSync(RACES_DATA_PATH, 'utf8');
const racesArrayMatch = racesDataSrc.match(/const races = (\[.*\]);/s);
if (!racesArrayMatch) {
    console.error('No se encontró el array races en races-data.js');
    process.exit(1);
}
const races = JSON.parse(racesArrayMatch[1]);

// Crear un mapa de resultados por GP
const resultByGP = {};
for (const r of results) {
    // Buscar nombre del GP
    const match = r.gran_premio.match(/Gran Premio de [^\d\n]+/);
    if (!match) continue;
    const gp = match[0].trim();
    resultByGP[normalizarTitulo(gp)] = r;
}

// Actualizar el campo podium en cada carrera
for (const race of races) {
    const gp = normalizarTitulo(race.title);
    const res = resultByGP[gp];
    if (!res) continue;
    // Detectar ganadores
    let primero = res.ganador || '';
    if (!primero && res.resultados_raw) {
        const m = res.resultados_raw.match(/Ganador(?: sprint)?\n([A-Za-z .'-]+)/);
        if (m) primero = m[1].trim();
    }
    // Si tampoco, poner vacío
    if (!primero) primero = '';
    let segundo = res.segundo || '';
    let tercero = res.tercero || '';
    // Si no hay segundo o tercero, intentar extraer del texto
    if (!segundo && res.resultados_raw) {
        const m2 = res.resultados_raw.match(/2\.º puesto\n([A-Za-z .'-]+)/);
        if (m2) segundo = m2[1].trim();
    }
    if (!tercero && res.resultados_raw) {
        const m3 = res.resultados_raw.match(/3\.er puesto\n([A-Za-z .'-]+)/);
        if (m3) tercero = m3[1].trim();
    }
    // Actualizar el campo podium
    race.podium = [
        { position: 1, name: primero, team: '', image: slugify(primero) },
        { position: 2, name: segundo, team: '', image: slugify(segundo) },
        { position: 3, name: tercero, team: '', image: slugify(tercero) }
    ];
}

// Hacer backup
fs.writeFileSync(BACKUP_PATH, racesDataSrc);
// Reemplazar el array races en races-data.js
const racesString = JSON.stringify(races, null, 4);
const newSrc = racesDataSrc.replace(/const races = (\[.*\]);/s, `const races = ${racesString};`);
fs.writeFileSync(RACES_DATA_PATH, newSrc);
console.log('Podios actualizados en js/races-data.js. Backup creado en js/races-data.js.bak');
