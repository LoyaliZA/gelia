let ordenSeleccionado = [];
let ordenCreacion = [];

const camposPorArchivo = {
    'existencias': ['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio'],
    'precios': ['PG', 'Plataformas', 'Lista3', 'Lista4', 'ListaBoutique', 'CostoCalculado'],
    'costos': ['CostoWizerp']
};

document.addEventListener('DOMContentLoaded', () => {
    // Escuchar cambios en los inputs para habilitar botones dinámicamente
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', window.verificarArchivos);
    });
    
    window.verificarArchivos();
    
    const formCrear = document.getElementById('form-crear-lista');
    if (formCrear) formCrear.addEventListener('submit', guardarNuevaLista);
});

// ==========================================
// MODALES Y EDICIÓN DE LISTAS
// ==========================================
window.toggleModal = function (show) {
    const modal = document.getElementById('modal-nueva-lista');
    if (show) modal.classList.remove('hidden');
    else modal.classList.add('hidden');
}

window.abrirModalCrear = function () {
    document.getElementById('form-crear-lista').reset();
    document.getElementById('lista-id').value = '';
    document.getElementById('modal-title').innerText = 'Nueva Lista Personalizada';

    ordenCreacion = [];
    document.querySelectorAll('[id^="badge-creacion-"]').forEach(b => b.classList.add('hidden'));
    document.querySelectorAll('#form-crear-lista input[type="checkbox"]').forEach(c => {
        if (c.name !== 'archivos_requeridos[]' || c.value !== 'existencias') c.checked = false;
    });
    document.getElementById('check-solo-existencia').checked = false;
    document.getElementById('input-columnas-exportar').value = '';
    toggleModal(true);
}

window.abrirModalEdicion = function (event, id) {
    event.stopPropagation();
    const lista = window.GeliaConfig.customLists.find(l => l.id == id);
    if (!lista) return;

    document.getElementById('form-crear-lista').reset();
    document.getElementById('lista-id').value = lista.id;
    document.getElementById('modal-title').innerText = 'Editar Lista: ' + lista.titulo_lista;

    document.querySelector('input[name="nombre_creador"]').value = lista.nombre_creador;
    document.querySelector('input[name="titulo_lista"]').value = lista.titulo_lista;
    document.querySelector('select[name="color"]').value = lista.color;
    document.querySelector('textarea[name="descripcion"]').value = lista.descripcion || '';
    document.querySelector('input[name="nombre_archivo_salida"]').value = lista.nombre_archivo_salida;

    const reqs = lista.archivos_requeridos || [];
    document.querySelector('input[name="archivos_requeridos[]"][value="precios"]').checked = reqs.includes('precios');
    document.querySelector('input[name="archivos_requeridos[]"][value="costos"]').checked = reqs.includes('costos');

    document.getElementById('check-solo-existencia').checked = lista.solo_con_existencia;

    ordenCreacion = [];
    document.querySelectorAll('[id^="badge-creacion-"]').forEach(b => b.classList.add('hidden'));
    document.querySelectorAll('#form-crear-lista input[type="checkbox"]').forEach(c => {
        if (c.id !== 'check-solo-existencia' && c.name !== 'archivos_requeridos[]') c.checked = false;
    });

    const colExportar = lista.columnas_exportar || [];
    colExportar.forEach(col => {
        const cb = document.querySelector(`input[value="${col}"][onchange="actualizarOrdenCreacion(this)"]`);
        if (cb) {
            cb.checked = true;
            actualizarOrdenCreacion(cb);
        }
    });

    toggleModal(true);
}

// ==========================================
// ORDENAMIENTO DE COLUMNAS
// ==========================================
window.actualizarOrdenCreacion = function (checkbox) {
    const valor = checkbox.value;
    const badge = document.getElementById('badge-creacion-' + valor);
    if (checkbox.checked) {
        ordenCreacion.push(valor);
        badge.innerText = ordenCreacion.length;
        badge.classList.remove('hidden');
    } else {
        ordenCreacion = ordenCreacion.filter(item => item !== valor);
        badge.classList.add('hidden');
        ordenCreacion.forEach((item, index) => {
            document.getElementById('badge-creacion-' + item).innerText = index + 1;
        });
    }
    document.getElementById('input-columnas-exportar').value = ordenCreacion.join(',');
}

window.actualizarOrden = function (checkbox) {
    const valor = checkbox.value;
    const badge = document.getElementById('badge-' + valor);
    if (checkbox.checked) {
        ordenSeleccionado.push(valor);
        badge.innerText = ordenSeleccionado.length;
        badge.classList.remove('hidden');
    } else {
        ordenSeleccionado = ordenSeleccionado.filter(item => item !== valor);
        badge.classList.add('hidden');
        ordenSeleccionado.forEach((item, index) => { document.getElementById('badge-' + item).innerText = index + 1; });
    }
}

// ==========================================
// VALIDACIONES Y HABILITACIÓN DE SECCIONES
// ==========================================
window.verificarArchivos = function () {
    const fileExistencias = document.getElementById('file-existencias');
    if (!fileExistencias) return;

    const inputs = {
        'existencias': fileExistencias.files.length > 0,
        'precios': document.getElementById('file-precios').files.length > 0,
        'costos': document.getElementById('file-costos').files.length > 0
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
                    if (checkbox.checked) {
                        checkbox.checked = false;
                        actualizarOrden(checkbox);
                    }
                }
            }
        });
    }
}

// ==========================================
// PETICIONES AL BACKEND (API REST)
// ==========================================
window.guardarNuevaLista = async function (e) {
    e.preventDefault();
    if (ordenCreacion.length === 0) { window.mostrarToast("Debes seleccionar al menos una columna.", "red"); return; }

    const form = e.target;
    const formData = new FormData(form);
    const idLista = document.getElementById('lista-id').value;
    const url = idLista ? window.GeliaConfig.routes.actualizar.replace(':id', idLista) : window.GeliaConfig.routes.guardar;

    window.mostrarCarga(idLista ? "Actualizando configuración..." : "Guardando configuración...");

    try {
        const response = await fetch(url, {
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
                window.mostrarToast("Error de validación:\n" + msg, "red");
            } else {
                window.mostrarToast("Error: " + (data.message || "Error desconocido"), "red");
            }
        } else {
            window.mostrarToast(idLista ? "Lista actualizada con éxito." : "Lista creada con éxito.", "green");
            setTimeout(() => window.location.reload(), 1000);
        }

    } catch (error) {
        window.mostrarToast("Error de red: " + error.message, "red");
    } finally {
        window.ocultarCarga();
    }
}

window.procesarSolicitud = async function (tipo) {
    const fileExistencias = document.getElementById('file-existencias');
    const tieneExistencias = fileExistencias && fileExistencias.files.length > 0;
    const tienePrecios = document.getElementById('file-precios').files.length > 0;
    const tieneCostos = document.getElementById('file-costos').files.length > 0;

    // Validación de obligatoriedad de archivos según el tipo de lista
    if (!isNaN(tipo)) {
        if (!tieneExistencias) { window.mostrarToast("Existencias es obligatorio.", "red"); return; }
        if (window.GeliaConfig && window.GeliaConfig.customLists) {
            const listaConfig = window.GeliaConfig.customLists.find(l => l.id == tipo);
            if (listaConfig) {
                const reqs = listaConfig.archivos_requeridos || [];
                if (reqs.includes('precios') && !tienePrecios) { window.mostrarToast(`Requiere PRECIOS.`, "red"); return; }
                if (reqs.includes('costos') && !tieneCostos) { window.mostrarToast(`Requiere COSTOS.`, "red"); return; }
            }
        }
    } else {
        if (!tieneExistencias) { window.mostrarToast("Primero sube Existencias", "red"); return; }
        if ((tipo === 'resurtido' || tipo === 'actualizada' || tipo === 'inventario') && !tienePrecios) {
            window.mostrarToast("Esta lista requiere: Existencias + Precios", "red"); return;
        }
        if (tipo === 'costos' && !tieneCostos) {
            window.mostrarToast("Esta lista requiere: Existencias + Costos", "red"); return;
        }
    }

    let columnas = [];
    let nombreTipo = "";

    // Mapeo del nombre del archivo y columnas estándar
    if (!isNaN(tipo)) {
        const listaConfig = window.GeliaConfig.customLists.find(l => l.id == tipo);
        nombreTipo = listaConfig ? listaConfig.titulo_lista : "Lista Personalizada";
    }
    else if (tipo === 'resurtido') { columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Plataformas', 'Lista3']; nombreTipo = "Lista de Resurtido"; }
    else if (tipo === 'costos') { columnas = ['Almacen', 'SKU', 'Descripcion', 'Existencia', 'CostoWizerp']; nombreTipo = "Lista de Costos"; }
    else if (tipo === 'actualizada') { columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'CostoCalculado', 'Plataformas']; nombreTipo = "Lista Actualizada"; }
    else if (tipo === 'inventario') { columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Lista3']; nombreTipo = "Lista de Inventario"; }
    else {
        columnas = ordenSeleccionado;
        nombreTipo = "Lista Personalizada";
        if (columnas.length === 0) { window.mostrarToast("Error: Selecciona columnas.", "red"); return; }
    }

    const form = document.getElementById('form-principal');
    const formData = new FormData(form);

    if (columnas.length > 0) formData.append('orden_final', columnas.join(','));
    formData.append('tipo_lista', tipo);

    window.mostrarCarga(`Generando: ${nombreTipo}...`);
    document.getElementById('alertas').innerHTML = '';

    try {
        const urlGenerar = window.GeliaConfig.routes.generar;
        const response = await fetch(urlGenerar, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' }
        });

        if (!response.ok) {
            const data = await response.json();
            if (data.errors) {
                let html = `<ul class='list-disc ml-5'>`;
                Object.values(data.errors).forEach(err => html += `<li>${err}</li>`);
                html += `</ul>`;
                window.mostrarError(html);
            } else { throw new Error(data.error || 'Error en el servidor'); }
            window.ocultarCarga(); return;
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

        window.ocultarCarga();
        window.mostrarToast("Archivo Generado Exitosamente!", "green");

    } catch (error) {
        window.ocultarCarga();
        window.mostrarToast("Error: " + error.message, "red");
    }
}

window.eliminarLista = async function (event, id) {
    event.stopPropagation();
    if (!confirm("Eliminar esta lista personalizada?")) return;
    window.mostrarCarga("Eliminando...");
    try {
        const urlEliminar = window.GeliaConfig.routes.eliminar.replace(':id', id);
        const response = await fetch(urlEliminar, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' }
        });
        if (response.ok) { window.mostrarToast("Eliminada.", "green"); setTimeout(() => window.location.reload(), 1000); } 
        else { window.mostrarToast("Error al eliminar.", "red"); }
    } catch (error) { window.mostrarToast("Error de red.", "red"); } finally { window.ocultarCarga(); }
}