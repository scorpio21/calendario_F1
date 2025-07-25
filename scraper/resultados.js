// scraper/resultados.js
// Extrae la tabla de resultados de carreras y guarda data/results.json

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

// --- NUEVO PARSER ÚNICO: extrae desde el campo 'text' de la tabla ---
const resultHeaders = ["Ronda", "Fecha", "Gran Premio", "Mapa del circuito", "Resultados"];
let tableResultados = null;
for (const table of tables) {
    if (!table.headers) continue;
    const headers = table.headers;
    const headersLower = headers.map(h => h.toLowerCase());
    const match = resultHeaders.every(h => headersLower.includes(h.toLowerCase()));
    if (match) {
        tableResultados = table;
        break;
    }
}
if (!tableResultados || !tableResultados.text) {
    console.error('No se encontró la tabla de calendario/resultados por ronda con campo text.');
    process.exit(1);
}

const text = tableResultados.text;
// Parsear el bloque de texto por rondas
const bloques = text.split(/\n\n(?=\d+\n)/).slice(1); // Quita encabezados
const results = [];
for (const bloque of bloques) {
    // Extraer ronda, fecha, gran premio, mapa y resultados de cada bloque
    const lineas = bloque.split(/\n+/).map(l => l.trim()).filter(Boolean);
    const ronda = lineas.shift();
    const fecha = lineas.shift();
    let gran_premio = lineas.shift() || '';
    // Extraer nombre de circuito como 'mapa' si está pegado al gran premio
    let mapa = '';
    let mapa_img = 'default-circuit.jpg';
    const circuitoMatch = gran_premio.match(/(Albert Park|Baréin|Sakhir|Jeddah|Yeda|Shanghai|China|Suzuka|Japón|Miami|Imola|Emilia-Romagna|Mónaco|Montmeló|Barcelona|Canadá|Montreal|Red Bull Ring|Austria|Silverstone|Gran Bretaña|Hungaroring|Hungría|Spa-Francorchamps|Países Bajos|Zandvoort|Monza|Singapur|Marina Bay|Austin|COTA|México|Hermanos Rodríguez|Interlagos|Sao Paulo|Las Vegas|Qatar|Losail|Abu Dabi|Yas Marina|Bakú|Azerbaiyán)/i);
    if (circuitoMatch) {
        mapa = circuitoMatch[0].trim();
        // Mapear a archivo local
        const circuitMap = {
          'Albert Park': 'Australia_Circuit.avif',
          'Baréin': 'Bahrain_Circuit.avif',
          'Sakhir': 'Bahrain_Circuit.avif',
          'Jeddah': 'Saudi_Arabia_Circuit.avif',
          'Yeda': 'Saudi_Arabia_Circuit.avif',
          'Shanghai': 'China_Circuit.png',
          'China': 'China_Circuit.png',
          'Suzuka': 'Japan_Circuit.avif',
          'Japón': 'Japan_Circuit.avif',
          'Miami': 'Miami_Circuit.avif',
          'Imola': 'Emilia_Romagna_Circuit.avif',
          'Emilia-Romagna': 'Emilia_Romagna_Circuit.avif',
          'Mónaco': 'Monaco_Circuit.avif',
          'Montmeló': 'Barcelona.avif',
          'Barcelona': 'Barcelona.avif',
          'Canadá': 'default-circuit.jpg',
          'Montreal': 'default-circuit.jpg',
          'Red Bull Ring': 'redbull.avif',
          'Austria': 'redbull.avif',
          'Silverstone': 'silverstone.avif',
          'Gran Bretaña': 'silverstone.avif',
          'Hungaroring': 'Hungaroring.avif',
          'Hungría': 'Hungaroring.avif',
          'Spa-Francorchamps': 'Spa-Francorchamps.avif',
          'Países Bajos': 'Zandvoort.avif',
          'Zandvoort': 'Zandvoort.avif',
          'Monza': 'Monza.avif',
          'Singapur': 'Marina_Bay.avif',
          'Marina Bay': 'Marina_Bay.avif',
          'Austin': 'COTA_Circuit.png',
          'COTA': 'COTA_Circuit.png',
          'México': 'Mexico_Circuit.png',
          'Hermanos Rodríguez': 'Mexico_Circuit.png',
          'Interlagos': 'default-circuit.jpg',
          'Sao Paulo': 'default-circuit.jpg',
          'Las Vegas': 'default-circuit.jpg',
          'Qatar': 'Losail.avif',
          'Losail': 'Losail.avif',
          'Abu Dabi': 'Yas Marina.avif',
          'Yas Marina': 'Yas Marina.avif',
          'Bakú': 'Baku_Circuit.png',
          'Azerbaiyán': 'Baku_Circuit.png',
        };
        for (const key in circuitMap) {
          if (mapa.toLowerCase().includes(key.toLowerCase())) {
            mapa_img = circuitMap[key];
            break;
          }
        }
    }
    // El resto es el bloque de resultados (solo texto, sin imagen ni enlace)
    const resultadosRaw = lineas.filter(l => !l.match(/^https?:\/\//)).join('\n');
    // Parsear subcampos
    const result = { ronda, fecha, gran_premio, mapa, mapa_img };

    // Intentar extraer desde array estructurado si existe en la tabla original
    let segundo = null, tercero = null;
    // Buscar la tabla de la ronda actual en all_tables_full.json
    let tablaRonda = null;
    if (tables && Array.isArray(tables)) {
        tablaRonda = tables.find(t => (t.ronda === ronda || (t.ronda && t.ronda.toString() === ronda.toString())) && t.resultados && Array.isArray(t.resultados));
    }
    if (tablaRonda && tablaRonda.resultados) {
        for (const fila of tablaRonda.resultados) {
            for (let i = 0; i < fila.length; i++) {
                if (fila[i] === "2.º puesto" && fila[i+1]) {
                    segundo = fila[i+1].replace(/\(.*?\)/, '').trim();
                }
                if (fila[i] === "3.er puesto" && fila[i+1]) {
                    tercero = fila[i+1].replace(/\(.*?\)/, '').trim();
                }
            }
        }
    }

    // Si no se encontró en array, usar método antiguo de texto plano
    const regexps = {
        libres_1: /Libres 1\n([^\n]+)/i,
        libres_2: /Libres 2\n([^\n]+)/i,
        libres_3: /Libres 3\n([^\n]+)/i,
        ganador: /Ganador\n([^\n]+)/i,
        segundo: /2\.º puesto\n([^\n]+)/i,
        tercero: /3\.er puesto\n([^\n]+)/i,
        pole: /Pole position\n([^\n]+)/i,
        vuelta_rapida: /Vuelta r[aá]pida\n([^\n]+)/i,
        ganador_sprint: /Ganador sprint\n([^\n]+)/i,
        pole_sprint: /Pole positionsprint\n([^\n]+)/i
    };
    for (const [campo, re] of Object.entries(regexps)) {
        const m = resultadosRaw.match(re);
        if (m && !result[campo]) result[campo] = m[1].trim();
    }
    // Prioridad: si se extrajo desde array, usarlo
    if (segundo) result.segundo = segundo;
    if (tercero) result.tercero = tercero;
    result.resultados_raw = resultadosRaw;
    results.push(result);
}

fs.writeFileSync(path.join(DATA_DIR, 'results.json'), JSON.stringify(results, null, 2));
console.log('Generado data/results.json estructurado con', results.length, 'rondas (desde campo text).');
