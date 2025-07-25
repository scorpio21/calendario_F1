// scraper/update_data.js
// Script para actualizar todos los datos de la aplicación

const fs = require('fs');
const path = require('path');

// Función para ejecutar un script y mostrar su salida
async function runScript(script) {
    try {
        const { exec } = require('child_process');
        console.log(`Ejecutando ${script}...`);
        await new Promise((resolve, reject) => {
            exec(`node ${script}`, (error, stdout, stderr) => {
                if (error) {
                    console.error(`Error al ejecutar ${script}:`, error);
                    reject(error);
                } else {
                    console.log(stdout);
                    resolve();
                }
            });
        });
    } catch (error) {
        console.error(`Error al ejecutar ${script}:`, error);
        throw error;
    }
}

// Función principal
async function updateAllData() {
    try {
        // Obtener y procesar las tablas de Wikipedia
        await runScript(path.join(__dirname, 'fetch_all_tables.js'));
        await runScript(path.join(__dirname, 'process_tables.js'));

        // Actualizar los datos de la aplicación
        await runScript(path.join(__dirname, 'races_scraper.js'));
        await runScript(path.join(__dirname, 'drivers_scraper.js'));
        await runScript(path.join(__dirname, 'teams_scraper.js'));

        console.log('Todos los datos han sido actualizados exitosamente.');
    } catch (error) {
        console.error('Error al actualizar datos:', error);
    }
}

// Ejecutar la actualización
updateAllData();
