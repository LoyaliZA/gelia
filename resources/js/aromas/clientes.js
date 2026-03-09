document.addEventListener('DOMContentLoaded', () => {
    // Escuchar el evento change del input file si necesitas alguna validación inmediata
    const fileInput = document.getElementById('file-clientes');
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            if(fileInput.files.length > 0) {
                window.mostrarToast("Archivo cargado, listo para procesar.", "green");
            }
        });
    }
});

// Función para marcar/desmarcar masivamente
window.toggleColumnasClientes = function (btn) {
    const checks = document.querySelectorAll('.check-col-cliente');
    const isSelectingAll = btn.innerText.trim() === 'Seleccionar Todas';
    checks.forEach(c => c.checked = isSelectingAll);
    btn.innerText = isSelectingAll ? 'Desmarcar Todas' : 'Seleccionar Todas';
}

// Procesamiento principal de la solicitud
window.procesarSolicitud = async function () {
    const fileInput = document.getElementById('file-clientes');
    
    // 1. Validaciones
    if (!fileInput || fileInput.files.length === 0) { 
        window.mostrarToast("Sube el archivo CSV o TXT de Clientes", "red"); 
        return; 
    }
    
    const checks = document.querySelectorAll('.check-col-cliente:checked');
    if (checks.length === 0) { 
        window.mostrarToast("Selecciona al menos una columna para exportar", "red"); 
        return; 
    }

    // 2. Preparar los datos
    const form = document.getElementById('form-principal');
    const formData = new FormData(form);

    const cols = Array.from(checks).map(c => c.value);
    formData.append('columnas_clientes', cols.join(','));
    formData.set('incluir_sin_id', document.getElementById('check-incluir-sin-id').checked ? '1' : '0');

    // 3. Ejecutar Petición
    window.mostrarCarga(`Limpiando Base de Datos de Clientes...`);
    document.getElementById('alertas').innerHTML = '';

    try {
        const urlGenerar = window.GeliaConfig.routes.generar;
        const response = await fetch(urlGenerar, {
            method: 'POST',
            body: formData,
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 
                'Accept': 'application/json' 
            }
        });

        if (!response.ok) {
            const data = await response.json();
            if (data.errors) {
                let html = `<ul class='list-disc ml-5'>`;
                Object.values(data.errors).forEach(err => html += `<li>${err}</li>`);
                html += `</ul>`;
                window.mostrarError(html);
            } else { 
                throw new Error(data.error || 'Error en el servidor al procesar el archivo.'); 
            }
            window.ocultarCarga(); 
            return;
        }

        // 4. Descargar el Excel generado
        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = downloadUrl;

        // Intentar obtener el nombre del archivo desde los headers del backend
        const contentDisposition = response.headers.get('Content-Disposition');
        let fileName = `CLIENTES-SANITIZADOS.xlsx`;
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (fileNameMatch && fileNameMatch.length === 2) fileName = fileNameMatch[1];
        }

        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        a.remove();

        window.ocultarCarga();
        window.mostrarToast("¡Archivo de Clientes Generado Exitosamente!", "green");

    } catch (error) {
        console.error(error);
        window.ocultarCarga();
        window.mostrarToast("Error: " + error.message, "red");
    }
}