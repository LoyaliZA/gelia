/* --- gelia.js --- */

// Configuración de Tailwind
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                dark: { 900: '#0d1117', 800: '#161b22', 700: '#21262d', text: '#c9d1d9' }
            }
        }
    }
}

let ordenSeleccionado = [];

// Mapa de dependencias
const camposPorArchivo = {
    'existencias': ['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio'],
    'precios':     ['PG', 'Plataformas', 'Lista3', 'Lista4', 'CostoCalculado'],
    'costos':      ['CostoWizerp']
};

// Inicialización automática al cargar
document.addEventListener('DOMContentLoaded', () => {
    verificarArchivos();
});

function actualizarOrden(checkbox) {
    const valor = checkbox.value;
    const badge = document.getElementById('badge-' + valor);

    if (checkbox.checked) {
        ordenSeleccionado.push(valor);
        badge.innerText = ordenSeleccionado.length;
        badge.classList.remove('hidden');
    } else {
        ordenSeleccionado = ordenSeleccionado.filter(item => item !== valor);
        badge.classList.add('hidden');
        ordenSeleccionado.forEach((item, index) => {
            document.getElementById('badge-' + item).innerText = index + 1;
        });
    }
}

function verificarArchivos() {
    const inputs = {
        'existencias': document.getElementById('file-existencias').value !== "",
        'precios':     document.getElementById('file-precios').value !== "",
        'costos':      document.getElementById('file-costos').value !== ""
    };

    for (const [archivo, campos] of Object.entries(camposPorArchivo)) {
        const estaSubido = inputs[archivo];
        campos.forEach(campo => {
            const label = document.getElementById('label-' + campo);
            const checkbox = document.getElementById('check-' + campo);

            if (label && checkbox) {
                if (estaSubido) {
                    label.classList.remove('disabled-option');
                    label.classList.add('hover:bg-dark-700', 'cursor-pointer');
                    checkbox.disabled = false;
                } else {
                    label.classList.add('disabled-option');
                    label.classList.remove('hover:bg-dark-700', 'cursor-pointer');
                    checkbox.disabled = true;
                    if(checkbox.checked) {
                        checkbox.checked = false;
                        actualizarOrden(checkbox);
                    }
                }
            }
        });
    }
}

async function procesarSolicitud(tipo) {
    
    // --- VALIDACIONES ---
    if (tipo === 'clientes') {
        const fileClientes = document.getElementById('file-clientes').value;
        if (!fileClientes) {
            mostrarToast("⚠️ Sube el archivo CSV de Clientes", "red");
            return;
        }
    } 
    else {
        const tienePrecios = document.getElementById('file-precios').value !== "";
        const tieneCostos = document.getElementById('file-costos').value !== "";
        const tieneExistencias = document.getElementById('file-existencias').value !== "";

        if (!tieneExistencias) {
            mostrarToast("❌ Primero sube el archivo de Existencias", "red");
            return;
        }
        if (tipo === 'resurtido' && !tienePrecios) {
            mostrarToast("⚠️ Lista Resurtido requiere: Existencias + Precios", "red");
            return;
        }
        if (tipo === 'actualizada' && !tienePrecios) {
            mostrarToast("⚠️ Lista Actualizada requiere: Existencias + Precios", "red");
            return;
        }
        if (tipo === 'inventario' && !tienePrecios) {
            mostrarToast("⚠️ Inv. Bellaroma requiere: Existencias + Precios", "red");
            return;
        }
        if (tipo === 'costos' && !tieneCostos) {
            mostrarToast("⚠️ Lista Costos requiere: Existencias + Costos", "red");
            return;
        }
    }

    // --- CONFIGURACIÓN DE COLUMNAS ---
    let columnas = [];
    let nombreTipo = "";

    if (tipo === 'clientes') {
        nombreTipo = "Limpieza de Clientes";
        columnas = ['ID', 'NOMBRE']; 
    } else if (tipo === 'resurtido') {
        columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Plataformas', 'Lista3'];
        nombreTipo = "Lista de Resurtido";
    } else if (tipo === 'costos') {
        columnas = ['Almacen', 'SKU', 'Descripcion', 'Existencia', 'CostoWizerp'];
        nombreTipo = "Lista de Costos";
    } else if (tipo === 'actualizada') {
        columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'CostoCalculado', 'Plataformas'];
        nombreTipo = "Lista Actualizada";
    } else if (tipo === 'inventario') {
        columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Lista3'];
        nombreTipo = "Lista de Inventario";
    } else {
        columnas = ordenSeleccionado;
        nombreTipo = "Lista Personalizada";
        if (columnas.length === 0) {
            mostrarToast("❌ Error: Selecciona columnas habilitadas.", "red");
            return;
        }
    }

    const form = document.getElementById('form-principal');
    const formData = new FormData(form);
    formData.append('orden_final', columnas.join(','));
    formData.append('tipo_lista', tipo);

    mostrarCarga(`Generando: ${nombreTipo}...`);
    document.getElementById('alertas').innerHTML = '';

    try {
        // Obtenemos la ruta desde la configuración global inyectada en Blade
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
                mostrarError(html);
            } else {
                throw new Error(data.error || 'Error en el servidor');
            }
            ocultarCarga();
            return;
        }

        const blob = await response.blob();
        const downloadUrl = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = downloadUrl;
        
        const contentDisposition = response.headers.get('Content-Disposition');
        let fileName = `${nombreTipo}.xlsx`;
        if (contentDisposition) {
            const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
            if (fileNameMatch && fileNameMatch.length === 2) fileName = fileNameMatch[1];
        }
        
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        a.remove();
        
        ocultarCarga();
        mostrarToast("✅ ¡Archivo Generado Exitosamente!", "green");

    } catch (error) {
        console.error(error);
        ocultarCarga();
        mostrarToast("❌ Error: " + error.message, "red");
    }
}

// --- UTILIDADES ---
function mostrarCarga(mensaje) {
    document.getElementById('overlay-carga').classList.remove('hidden');
    document.getElementById('texto-carga').innerText = mensaje;
}
function ocultarCarga() {
    document.getElementById('overlay-carga').classList.add('hidden');
}
function mostrarToast(mensaje, color) {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toast-msg');
    toast.className = `fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold flex items-center transform transition-all duration-300 ${color === 'red' ? 'bg-red-600' : 'bg-emerald-600'}`;
    toastMsg.innerText = mensaje;
    toast.classList.remove('hidden', 'toast-enter');
    toast.classList.add('toast-enter-active');
    setTimeout(() => { toast.classList.add('hidden'); }, 4000);
}
function mostrarError(html) {
    const div = document.getElementById('alertas');
    div.innerHTML = `<div class="bg-red-900/50 border border-red-500 text-red-200 p-4 rounded-lg mb-6">${html}</div>`;
}