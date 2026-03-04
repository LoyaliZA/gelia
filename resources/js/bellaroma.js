document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-bellaroma');
    const fileExistencias = document.getElementById('file-existencias');
    const filePrecios = document.getElementById('file-precios');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Verificamos que los inputs tengan archivos (sin importar si fue por click o drag&drop)
            if (!fileExistencias.files.length || !filePrecios.files.length) {
                window.mostrarToast('Sube ambos archivos primero', 'red');
                return;
            }

            const formData = new FormData(form);
            window.mostrarCarga("Cruzando datos y protegiendo celdas...");

            try {
                const response = await fetch('/bellaroma/generar', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
                });

                if (!response.ok) throw new Error('Error al procesar el Excel.');

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                
                // Formateamos la fecha para el nombre del archivo
                const fecha = new Date().toLocaleDateString('es-MX', {day: '2-digit', month: '2-digit', year: '2-digit'}).replace(/\//g, '-');
                a.download = `PLANTILLA-BELLAROMA-${fecha}.xlsx`;
                
                document.body.appendChild(a);
                a.click();
                a.remove();
                
                window.mostrarToast('Plantilla generada con éxito', 'green');

            } catch (error) {
                window.mostrarToast(error.message, 'red');
            } finally {
                window.ocultarCarga();
            }
        });
    }
});