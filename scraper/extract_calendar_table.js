const fs = require('fs');
const cheerio = require('cheerio');

// Lee el HTML de la tabla descargado manualmente de Wikipedia
const html = fs.readFileSync('data/calendario_tabla.html', 'utf-8');
const $ = cheerio.load(html);

const tables = $('table.wikitable');
let calendarioTable = null;

// Busca la tabla con los encabezados correctos
// También extrae la imagen del mapa del circuito (si existe)
tables.each((i, table) => {
  const headers = [];
  $(table).find('tr').first().find('th').each((j, th) => {
    headers.push($(th).text().trim());
  });
  if (
    headers.includes('Ronda') &&
    headers.includes('Fecha') &&
    headers.includes('Gran Premio') &&
    headers.includes('Mapa del circuito') &&
    headers.some(h => h.includes('Resultados'))
  ) {
    calendarioTable = table;
  }
});

if (!calendarioTable) {
  console.error('No se encontró la tabla de calendario');
  process.exit(1);
}

// Extrae el bloque de texto completo de la tabla y los mapas
let text = '';
const rounds = [];
let currentRound = null;

$(calendarioTable).find('tr').each((i, row) => {
  const cells = $(row).find('th,td');
  // Detectar inicio de ronda
  if (cells.length > 0 && $(cells[0]).attr('rowspan')) {
    if (currentRound) rounds.push(currentRound);
    currentRound = {
      ronda: $(cells[0]).text().trim(),
      fecha: '',
      gran_premio: '',
      mapa_img: '',
      resultados: []
    };
    // Fecha
    currentRound.fecha = $(cells[1]).text().trim();
    // Gran Premio (con posible HTML)
    currentRound.gran_premio = $(cells[2]).text().trim();
    // Imagen mapa
    const mapaCell = $(cells[3]);
    const img = mapaCell.find('img');
    if (img.length) {
      let src = img.attr('src');
      if (src && src.startsWith('//')) src = 'https:' + src;
      currentRound.mapa_img = src;
    }
    // El resto se ignora aquí, se añade en los siguientes rows
  } else if (currentRound && cells.length > 1) {
    // Agrega los resultados de las subfilas
    currentRound.resultados.push(cells.map((_, c) => $(c).text().trim()).get());
  }
});
if (currentRound) rounds.push(currentRound);

// Genera un campo 'text' resumen para compatibilidad legacy
text = rounds.map(r => {
  let bloque = `${r.ronda}\n${r.fecha}\n${r.gran_premio}`;
  if (r.mapa_img) bloque += `\n${r.mapa_img}`;
  bloque += '\n' + r.resultados.map(res => res.join('\n')).join('\n');
  return bloque;
}).join('\n\n');

// --- NUEVO BLOQUE PARA GUARDAR TODAS LAS TABLAS RELEVANTES ---

const allTables = [];

// 1. Guarda el calendario como hasta ahora
allTables.push({
  tipo: 'calendario',
  headers: ['Ronda', 'Fecha', 'Gran Premio', 'Mapa del circuito', 'Resultados'],
  text: text.trim(),
  rounds: rounds
});

// 2. Añade todas las tablas wikitable con sus encabezados y HTML

// Es importante volver a usar el objeto $ raíz, no el del calendario
$('table.wikitable').each((i, table) => {
  const headers = [];
  $(table).find('tr').first().find('th').each((j, th) => {
    headers.push($(th).text().trim());
  });

  let tipo = 'otro';
  if (headers.includes('Piloto') && headers.includes('Puntos')) {
    tipo = 'clasificacion_pilotos';
  }
  if (headers.includes('Constructor') && headers.includes('Puntos')) {
    tipo = 'clasificacion_constructores';
  }

  allTables.push({
    tipo,
    headers,
    html: $.html(table)
  });
});

fs.writeFileSync('data/all_tables_full.json', JSON.stringify(allTables, null, 2), 'utf-8');
console.log('Generado data/all_tables_full.json con TODAS las tablas relevantes.');
