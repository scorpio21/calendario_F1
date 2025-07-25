const fs = require('fs');

// Cargar los datos completos
const allTables = JSON.parse(fs.readFileSync('./calendario-F1-2025/data/all_tables_full.json', 'utf8'));

// Busca la tabla de clasificación de pilotos
const driversTable = allTables.find(t => t.type === 'drivers' && t.tableInfo.includes('Clasificación'));

if (!driversTable || !driversTable.rows) {
  console.error('No se encontró la tabla de pilotos o no tiene filas.');
  process.exit(1);
}

// Aquí debes adaptar el parseo según la estructura real de "rows" en tu JSON.
// Este ejemplo es genérico:
const drivers = driversTable.rows.map((row, idx) => {
  // Ajusta los nombres de las propiedades según tu JSON real
  return {
    position: row.position || idx + 1,
    name: row.name,
    team: row.team,
    points: row.points,
    racePoints: row.racePoints, // Debe ser un objeto tipo {bahrain: 25, arabia: 18, ...}
    image: row.image || null
  };
});

// Ordenar por posición
drivers.sort((a, b) => a.position - b.position);

// Guardar el archivo
fs.writeFileSync('./calendario-F1-2025/data/drivers.json', JSON.stringify(drivers, null, 2), 'utf8');
console.log('drivers.json generado correctamente.');