// En tu archivo JS (ej. js/subsistema.js o dentro de los módulos)
fetch('modulos/obtener_historial.php')
    .then(response => {
        if (!response.ok) throw new Error('Error de red en el servidor');
        return response.json();
    })
    .then(data => {
        // Renderizar historial
    })
    .catch(error => {
        console.error('Error al cargar el historial:', error);
    });