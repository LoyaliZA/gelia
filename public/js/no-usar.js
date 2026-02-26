/* --- gelia.js --- */

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

// Variables Globales
let ordenSeleccionado = []; // Para Manual (Principal)
let ordenCreacion = [];     // Para Crear Nueva Lista (Modal)

// Mapa de dependencias
const camposPorArchivo = {
    'existencias': ['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio'],
    'precios':     ['PG', 'Plataformas', 'Lista3', 'Lista4', 'CostoCalculado'],
    'costos':      ['CostoWizerp']
};

document.addEventListener('DOMContentLoaded', () => {
    verificarArchivos();
    
    // Listener para guardar lista
    const formCrear = document.getElementById('form-crear-lista');
    if(formCrear) {
        formCrear.addEventListener('submit', guardarNuevaLista);
    }
});

// --- LÓGICA MODAL ---
function toggleModal(show) {
    const modal = document.getElementById('modal-nueva-lista');
    if (show) modal.classList.remove('hidden');
    else modal.classList.add('hidden');
}

function actualizarOrdenCreacion(checkbox) {
    const valor = checkbox.value;
    const badge = document.getElementById('badge-creacion-' + valor);
    
    if (checkbox.checked) {
        ordenCreacion.push(valor);
        badge.innerText = ordenCreacion.length;
        badge.classList.remove('hidden');
    } else {
        ordenCreacion = ordenCreacion.filter(item => item !== valor);
        badge.classList.add('hidden');
        // Reindexar visualmente
        ordenCreacion.forEach((item, index) => {
            document.getElementById('badge-creacion-' + item).innerText = index + 1;
        });
    }
    // Actualizar input hidden
    document.getElementById('input-columnas-exportar').value = ordenCreacion.join(',');
}

async function guardarNuevaLista(e) {
    e.preventDefault();
    
    if (ordenCreacion.length === 0) {
        alert("Debes seleccionar al menos una columna para exportar.");
        return;
    }

    const form = e.target;
    const formData = new FormData(form);

    mostrarCarga("Guardando configuración...");

    try {
        const response = await fetch(window.GeliaConfig.routes.guardar, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (!response.ok) {
            if (data.errors) {
                let msg = Object.values(data.errors).flat().join('\n');
                alert("Error de validación:\n" + msg);
            } else {
                alert("Error: " + (data.message || "Error desconocido"));
            }
        } else {
            alert("✅ Lista guardada con éxito. La página se recargará.");
            window.location.reload();
        }

    } catch (error) {
        console.error(error);
        alert("Error de red: " + error.message);
    } finally {
        ocultarCarga();
    }
}


// --- LÓGICA PRINCIPAL ---

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
    const tieneExistencias = document.getElementById('file-existencias').value !== "";
    const tienePrecios = document.getElementById('file-precios').value !== "";
    const tieneCostos = document.getElementById('file-costos').value !== "";

    if (tipo === 'clientes') {
        const fileClientes = document.getElementById('file-clientes').value;
        if (!fileClientes) {
            mostrarToast("⚠️ Sube el archivo CSV de Clientes", "red");
            return;
        }
    } 
    // Si es numérico, es una LISTA PERSONALIZADA (BD)
    else if (!isNaN(tipo)) {
        if (!tieneExistencias) {
            mostrarToast("❌ Existencias es obligatorio para cualquier lista.", "red");
            return;
        }

        // Buscar requisitos en la config inyectada
        const listaConfig = window.GeliaConfig.customLists.find(l => l.id == tipo);
        if (listaConfig) {
            const reqs = listaConfig.archivos_requeridos; // Array ['existencias', 'precios', etc]
            
            if (reqs.includes('precios') && !tienePrecios) {
                mostrarToast(`⚠️ La lista "${listaConfig.titulo_lista}" requiere el archivo de PRECIOS.`, "red");
                return;
            }
            if (reqs.includes('costos') && !tieneCostos) {
                mostrarToast(`⚠️ La lista "${listaConfig.titulo_lista}" requiere el archivo de COSTOS.`, "red");
                return;
            }
        }
    }
    // Listas Hardcoded (Legacy)
    else {
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

    // --- CONFIGURACIÓN ---
    let columnas = [];
    let nombreTipo = "";

    // Si es custom (ID numérico), el backend decide columnas y nombre
    if (!isNaN(tipo)) {
        const listaConfig = window.GeliaConfig.customLists.find(l => l.id == tipo);
        nombreTipo = listaConfig ? listaConfig.titulo_lista : "Lista Personalizada";
        columnas = []; // Se ignora, el backend lo toma de BD
    } 
    else if (tipo === 'clientes') {
        nombreTipo = "Limpieza de Clientes";
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
    
    if (columnas.length > 0) {
        formData.append('orden_final', columnas.join(','));
    }
    formData.append('tipo_lista', tipo);

    mostrarCarga(`Generando: ${nombreTipo}...`);
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

// ... (resto del código)

// --- FUNCIÓN DE ELIMINAR ---
async function eliminarLista(event, id) {
    // IMPORTANTE: Detiene el clic para que no se ejecute el botón padre (Generar lista)
    event.stopPropagation();

    if (!confirm("¿Estás seguro de que deseas eliminar esta lista personalizada?")) {
        return;
    }

    mostrarCarga("Eliminando lista...");

    try {
        // Reemplazamos el placeholder :id por el ID real
        const urlEliminar = window.GeliaConfig.routes.eliminar.replace(':id', id);

        const response = await fetch(urlEliminar, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            alert("🗑️ Lista eliminada.");
            window.location.reload(); // Recargamos para actualizar la vista
        } else {
            alert("❌ Error al eliminar la lista.");
        }

    } catch (error) {
        console.error(error);
        alert("Error de red.");
    } finally {
        ocultarCarga();
    }
}