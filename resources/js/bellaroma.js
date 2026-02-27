document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-bellaroma');
    const fileExistencias = document.getElementById('file-existencias');
    const filePrecios = document.getElementById('file-precios');

    // Usamos las dropzones con la estética de app.js
    setupDropzone('drop-existencias', fileExistencias, 'nombre-existencias', 'border-blue-700');
    setupDropzone('drop-precios', filePrecios, 'nombre-precios', 'border-emerald-500');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!fileExistencias.files.length || !filePrecios.files.length) {
                // Usamos el Toast global de app.js (color rojo)
                window.mostrarToast('Sube ambos archivos primero', 'red');
                return;
            }

            const formData = new FormData(form);
            // Usamos el Overlay de carga global de app.js
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
                a.download = `PLANTILLA-MAYOREO-${new Date().toLocaleDateString().replace(/\//g, '-')}.xlsx`;
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

    function setupDropzone(id, input, nameId, colorClass) {
        const zone = document.getElementById(id);
        const nameDisplay = document.getElementById(nameId);

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('scale-[1.02]', 'bg-dark-700', colorClass);
        });

        zone.addEventListener('dragleave', () => {
            zone.classList.remove('scale-[1.02]', 'bg-dark-700', colorClass);
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('scale-[1.02]', 'bg-dark-700', colorClass);
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                nameDisplay.textContent = e.dataTransfer.files[0].name;
            }
        });

        input.addEventListener('change', () => {
            if (input.files.length) nameDisplay.textContent = input.files[0].name;
        });
    }
});