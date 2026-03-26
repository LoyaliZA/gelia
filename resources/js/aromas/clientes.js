document.addEventListener('DOMContentLoaded', () => {
    const fileInput = document.getElementById('file-clientes');
    if (fileInput) {
        fileInput.addEventListener('change', () => {
            if(fileInput.files.length > 0) {
                window.mostrarToast("Archivo cargado, listo para procesar.", "green");
            }
        });
    }
});

window.toggleColumnasClientes = function (btn) {
    const checks = document.querySelectorAll('.check-col-cliente');
    const isSelectingAll = btn.innerText.trim() === 'Seleccionar Todas';
    checks.forEach(c => c.checked = isSelectingAll);
    btn.innerText = isSelectingAll ? 'Desmarcar Todas' : 'Seleccionar Todas';
}

window.procesarSolicitud = async function () {
    const fileInput = document.getElementById('file-clientes');
    
    if (!fileInput || fileInput.files.length === 0) { 
        window.mostrarToast("Sube el archivo CSV o TXT de Clientes", "red"); 
        return; 
    }
    
    const checks = document.querySelectorAll('.check-col-cliente:checked');
    if (checks.length === 0) { 
        window.mostrarToast("Selecciona al menos una columna para exportar", "red"); 
        return; 
    }

    const form = document.getElementById('form-principal');
    const formData = new FormData(form);

    const cols = Array.from(checks).map(c => c.value);
    formData.append('columnas_clientes', cols.join(','));
    formData.set('incluir_sin_id', document.getElementById('check-incluir-sin-id').checked ? '1' : '0');

    // Novedad: Captura y seteo del filtro especial
    const checkFiltroEspecial = document.getElementById('check-filtro-especial');
    if (checkFiltroEspecial) {
        formData.set('filtro_especial', checkFiltroEspecial.checked ? '1' : '0');
    }

    window.mostrarCarga(`Limpiando Base de Datos de Clientes...`);
    document.getElementById('alertas').innerHTML = '';

    try {
        const urlGenerar = window.GeliaConfig.routes.generar;
        
        // Obtención segura del token CSRF
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfInput = document.querySelector('input[name="_token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : (csrfInput ? csrfInput.value : '');

        const response = await fetch(urlGenerar, {
            method: 'POST',
            body: formData,
            headers: { 
                'X-CSRF-TOKEN': csrfToken, 
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

        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = downloadUrl;

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