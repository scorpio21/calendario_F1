// scraper/update_races_data.js
// Actualiza el archivo js/races-data.js con los datos del calendario

const fs = require('fs');
const path = require('path');

const DATA_DIR = path.join(__dirname, '../data');
const JS_DIR = path.join(__dirname, '../js');
const CALENDAR_PATH = path.join(DATA_DIR, 'calendar.json');
const RACES_DATA_PATH = path.join(JS_DIR, 'races-data.js');

// Verificar que existan los archivos necesarios
if (!fs.existsSync(CALENDAR_PATH)) {
    console.error('Error: No se encontró el archivo data/calendar.json');
    console.log('Ejecuta primero: node scraper/races_scraper.js');
    process.exit(1);
}

// Cargar los datos del calendario
const calendar = JSON.parse(fs.readFileSync(CALENDAR_PATH, 'utf8'));

// Función para generar un podio simulado
function generateMockPodium(race) {
    // Lista de pilotos ficticios para el podio
    const drivers = [
        { name: 'Max Verstappen', team: 'Red Bull Racing', image: 'max-verstappen' },
        { name: 'Lando Norris', team: 'McLaren F1', image: 'lando-norris' },
        { name: 'Charles Leclerc', team: 'Ferrari', image: 'charles-leclerc' },
        { name: 'Lewis Hamilton', team: 'Mercedes', image: 'lewis-hamilton' },
        { name: 'Carlos Sainz', team: 'Ferrari', image: 'carlos-sainz' },
        { name: 'George Russell', team: 'Mercedes', image: 'george-russell' },
        { name: 'Oscar Piastri', team: 'McLaren F1', image: 'oscar-piastri' },
        { name: 'Fernando Alonso', team: 'Aston Martin', image: 'fernando-alonso' },
        { name: 'Sergio Pérez', team: 'Red Bull Racing', image: 'sergio-perez' },
        { name: 'Pierre Gasly', team: 'Alpine', image: 'pierre-gasly' }
    ];

    // Tomar 3 pilotos aleatorios para el podio
    const shuffled = [...drivers].sort(() => 0.5 - Math.random());
    const podiumDrivers = shuffled.slice(0, 3);

    return podiumDrivers.map((driver, index) => ({
        position: index + 1,
        name: driver.name,
        team: driver.team,
        image: driver.image
    }));
}

// Función para determinar el estado de la carrera basado en la fecha
function getRaceStatus(raceDate) {
    const today = new Date();
    const raceDay = new Date(raceDate);
    
    // Si la fecha de la carrera es anterior a hoy, marcarla como finalizada
    if (raceDay < today) {
        return { text: 'Finalizado', class: 'bsp_sts_green' };
    }
    
    // Si la carrera es hoy, marcarla como en curso
    if (raceDay.toDateString() === today.toDateString()) {
        return { text: 'En curso', class: 'bsp_sts_yellow' };
    }
    
    // Si la carrera es próxima, mostrar la fecha
    return { text: 'Próximo', class: 'bsp_sts_blue' };
}

// Convertir los datos del calendario al formato de la aplicación
const racesData = calendar.map((race, index) => {
    // Generar un podio simulado
    const podium = generateMockPodium(race);
    
    // Obtener el ganador (primer puesto del podio)
    const winner = podium.length > 0 ? {
        name: podium[0].name,
        team: podium[0].team,
        time: '1:30:00.000' // Tiempo simulado
    } : null;
    
    // Determinar el estado de la carrera
    const status = getRaceStatus(race.date);
    
    // Extraer el nombre del circuito de la ubicación
    const locationParts = race.location.split(',');
    const circuitName = locationParts[0].trim();
    
    return {
        title: race.title,
        date: race.date,
        location: circuitName,
        laps: '58', // Número de vueltas por defecto
        status: status,
        winner: status.text === 'Finalizado' ? winner : null,
        podium: status.text === 'Finalizado' ? podium : [],
        coordinates: { lat: 0, lng: 0 }, // Coordenadas por defecto
        weather: { temp: 20, condition: 'Soleado', icon: '01d' } // Clima por defecto
    };
});

// Generar el contenido del archivo races-data.js
const fileContent = `// Datos de las carreras de la temporada 2025
// Actualizado automáticamente el ${new Date().toLocaleString()}

const races = ${JSON.stringify(racesData, null, 4)};
`;

// Guardar el archivo
fs.writeFileSync(RACES_DATA_PATH, fileContent, 'utf8');

console.log(`Se ha actualizado el archivo js/races-data.js con ${racesData.length} carreras.`);
console.log('La aplicación web ahora mostrará el calendario actualizado de la temporada 2025.');
