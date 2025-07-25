// Función para registrar mensajes en un archivo de log
function logToFile(message) {
    const timestamp = new Date().toISOString();
    const logEntry = `[${timestamp}] ${message}\n`;
    
    // Mostrar en consola
    console.log(logEntry);
    
    // Intentar guardar en un archivo (esto solo funcionará si el servidor está configurado correctamente)
    try {
        // Usar el API de FileSystem si está disponible (solo Chrome)
        if (window.showSaveFilePicker) {
            const blob = new Blob([logEntry], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'debug-log.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else {
            // Método alternativo: almacenar en localStorage
            const logs = JSON.parse(localStorage.getItem('debugLogs') || '[]');
            logs.push(logEntry);
            localStorage.setItem('debugLogs', JSON.stringify(logs));
        }
    } catch (error) {
        console.error('Error al guardar el log:', error);
    }
}

// Función para descargar todos los logs
document.addEventListener('DOMContentLoaded', function() {
    const downloadLogs = () => {
        const logs = JSON.parse(localStorage.getItem('debugLogs') || '[]').join('');
        const blob = new Blob([logs], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'debug-logs.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

    // Añadir botón de descarga de logs
    if (!document.getElementById('downloadLogsBtn')) {
        const btn = document.createElement('button');
        btn.id = 'downloadLogsBtn';
        btn.textContent = 'Descargar Logs';
        btn.style.position = 'fixed';
        btn.style.bottom = '20px';
        btn.style.right = '20px';
        btn.style.zIndex = '9999';
        btn.style.padding = '10px';
        btn.style.backgroundColor = '#e10600';
        btn.style.color = 'white';
        btn.style.border = 'none';
        btn.style.borderRadius = '5px';
        btn.style.cursor = 'pointer';
        btn.onclick = downloadLogs;
        document.body.appendChild(btn);
    }
});
