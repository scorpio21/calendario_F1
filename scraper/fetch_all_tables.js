// scraper/fetch_all_tables.js
// Script para descargar todas las tablas WikiTable de la temporada 2025 y guardarlas en data/all_tables.json
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Función para detectar el tipo de tabla
function detectTableType(table) {
    try {
        // Añadir información de depuración
        const headers = table.headers ? table.headers.map(h => h.toLowerCase().trim()) : [];
        const text = (table.text || '').toLowerCase();
        
        // Inicializar variables para la detección
        let type = 'unknown';
        let confidence = 0;
        let tableInfo = 'Tabla no identificada';
        
        // Tabla del calendario
        const calendarKeywords = ['ronda', 'gran premio', 'circuito', 'fecha', 'fecha de carrera'];
        const calendarScore = calendarKeywords.filter(kw => 
            text.includes(kw) || headers.some(h => h && h.includes(kw))
        ).length;
        
        if (calendarScore >= 2) {
            type = 'calendar';
            confidence = calendarScore / calendarKeywords.length;
            tableInfo = 'Calendario de carreras';
        }
        
        // Tabla de clasificación de pilotos
        const driversKeywords = ['pos', 'piloto', 'escudería', 'constructor', 'puntos', 'puntos totales'];
        const driversScore = driversKeywords.filter(kw => 
            text.includes(kw) || headers.some(h => h && h.includes(kw))
        ).length;
        
        if (driversScore > confidence * driversKeywords.length) {
            type = 'drivers';
            confidence = driversScore / driversKeywords.length;
            tableInfo = 'Clasificación de pilotos';
        }
        
        // Tabla de clasificación de constructores
        const teamsKeywords = ['pos', 'equipo', 'constructor', 'puntos', 'escudería'];
        const teamsScore = teamsKeywords.filter(kw => 
            text.includes(kw) || headers.some(h => h && h.includes(kw))
        ).length;
        
        if (teamsScore > confidence * teamsKeywords.length) {
            type = 'teams';
            confidence = teamsScore / teamsKeywords.length;
            tableInfo = 'Clasificación de constructores';
        }
        
        // Tabla de resultados
        const resultsKeywords = ['ronda', 'carrera', 'fecha', 'ganador', 'constructor', 'podio', 'puntos'];
        const resultsScore = resultsKeywords.filter(kw => 
            text.includes(kw) || headers.some(h => h && h.includes(kw))
        ).length;
        
        if (resultsScore > confidence * resultsKeywords.length) {
            type = 'results';
            confidence = resultsScore / resultsKeywords.length;
            tableInfo = 'Resultados de carreras';
        }
        
        // Si la confianza es muy baja, marcar como desconocida
        if (confidence < 0.3) {
            type = 'unknown';
            tableInfo = 'Tabla no identificada';
        }
        
        return {
            ...table,
            type,
            confidence: Math.round(confidence * 100) / 100,
            tableInfo,
            headers: headers.slice(0, 10), // Limitar el número de encabezados
            text: text.substring(0, 200) + '...' // Limitar el tamaño del texto
        };
    } catch (error) {
        console.error('Error detectando tipo de tabla:', error);
        return {
            ...table,
            type: 'error',
            confidence: 0,
            tableInfo: 'Error al procesar la tabla',
            error: error.message
        };
    }
}

async function fetchAllTables() {
    const browser = await puppeteer.launch({ headless: 'new' });
    const page = await browser.newPage();
    
    try {
        // URL de la temporada 2025 de F1 en Wikipedia
        const url = 'https://es.wikipedia.org/wiki/Temporada_2025_de_F%C3%B3rmula_1';
        
        console.log(`Navegando a ${url}...`);
        await page.goto(url, { waitUntil: 'networkidle2' });
        
        // Guardar el HTML completo de la página
        const htmlContent = await page.content();
        const dataDir = path.join(__dirname, '../data');
        if (!fs.existsSync(dataDir)) {
            fs.mkdirSync(dataDir, { recursive: true });
        }
        fs.writeFileSync(path.join(dataDir, 'wikipedia_page.html'), htmlContent);
        console.log('HTML de la página guardado en data/wikipedia_page.html');
        
        // Extraer todas las tablas de la página
        console.log('Extrayendo tablas...');
        const tables = await page.evaluate(() => {
            const tables = Array.from(document.querySelectorAll('table.wikitable'));
            return tables.map((table, index) => {
                // Extraer la PRIMERA FILA de la tabla (cabecera)
                const firstRow = table.querySelector('tr');
                const headers = Array.from(firstRow ? firstRow.children : []).map(cell => {
                    let text = cell.textContent.trim();
                    text = text.replace(/\[\d+\]/g, '').trim();
                    return text;
                });
                
                // Extraer el texto completo de la tabla para análisis
                let text = table.textContent.trim();
                // Limpiar el texto de referencias
                text = text.replace(/\[\d+\]/g, '').trim();
                
                // Obtener el HTML de la tabla
                const html = table.outerHTML;
                
                return {
                    index: index + 1,
                    html,
                    headers,
                    text: text.substring(0, 1000) // Limitar el tamaño para evitar archivos demasiado grandes
                };
            });
        });
        
        // Procesar tablas para identificar su tipo
        console.log('Procesando tablas...');
        const tablesWithType = [];
        const tableTypes = [];
        
        for (const table of tables) {
            try {
                const processedTable = detectTableType(table);
                tableTypes.push(processedTable.type);
                
                tablesWithType.push({
                    index: table.index,
                    type: processedTable.type,
                    confidence: processedTable.confidence,
                    tableInfo: processedTable.tableInfo,
                    html: table.html,
                    headers: table.headers,
                    text: table.text
                });
                
                console.log(`Tabla ${table.index}: ${processedTable.type} (${Math.round(processedTable.confidence * 100)}%)`);
            } catch (error) {
                console.error(`Error procesando tabla ${table.index}:`, error);
                tableTypes.push('error');
            }
        }
        
        // Guardar las tablas completas en un archivo JSON
        const outputPath = path.join(dataDir, 'all_tables_full.json');
        fs.writeFileSync(outputPath, JSON.stringify(tablesWithType, null, 2), 'utf8');
        console.log(`\nSe han guardado ${tablesWithType.length} tablas en ${outputPath}`);
        
        // Guardar también solo los tipos para referencia
        fs.writeFileSync(
            path.join(dataDir, 'all_tables.json'), 
            JSON.stringify(tableTypes, null, 2), 
            'utf8'
        );
        
        console.log('\nTipos de tablas detectados:');
        console.log(tableTypes.map((t, i) => `  ${i + 1}. ${t}`).join('\n'));
        
        // Guardar las tablas relevantes por separado
        const relevantTables = tablesWithType.filter(t => t.type !== 'unknown' && t.type !== 'error');
        fs.writeFileSync(
            path.join(dataDir, 'relevant_tables.json'),
            JSON.stringify(relevantTables, null, 2),
            'utf8'
        );
        console.log(`\nSe han guardado ${relevantTables.length} tablas relevantes en relevant_tables.json`);
        
        return {
            allTables: tablesWithType,
            relevantTables,
            tableTypes
        };
    } catch (error) {
        console.error('Error al obtener tablas:', error);
        throw error;
    } finally {
        // Cerrar el navegador
        await browser.close();
    }
}

// Ejecutar la función principal
if (require.main === module) {
    fetchAllTables()
        .then(() => console.log('Proceso completado exitosamente'))
        .catch(error => {
            console.error('Error en el proceso:', error);
            process.exit(1);
        });
}

module.exports = { fetchAllTables, detectTableType };
