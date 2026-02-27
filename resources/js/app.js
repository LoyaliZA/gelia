import './bootstrap';

let ordenSeleccionado = [];
let ordenCreacion = [];

const camposPorArchivo = {
    'existencias': ['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio'],
    'precios': ['PG', 'Plataformas', 'Lista3', 'Lista4', 'ListaBoutique', 'CostoCalculado'],
    'costos': ['CostoWizerp']
};

document.addEventListener('DOMContentLoaded', () => {
    verificarArchivos();
    const formCrear = document.getElementById('form-crear-lista');
    if (formCrear) formCrear.addEventListener('submit', guardarNuevaLista);

    // Drag and Drop
    const dropZones = document.querySelectorAll('.drop-zone');
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('border-dashed', 'bg-dark-700', 'scale-[1.02]');
            if (zone.id === 'card-existencias') zone.classList.add('border-blue-700');
            if (zone.id === 'card-precios') zone.classList.add('border-emerald-500');
            if (zone.id === 'card-costos') zone.classList.add('border-purple-500');
            if (zone.id === 'card-clientes') zone.classList.add('border-yellow-500');
            if (zone.id === 'card-gastos') zone.classList.add('border-green-500');
            if (zone.id === 'card-transacciones') zone.classList.add('border-orange-500');
        });
        zone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            zone.classList.remove('border-dashed', 'bg-dark-700', 'scale-[1.02]', 'border-blue-500', 'border-emerald-500', 'border-purple-500', 'border-yellow-500');
        });
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('border-dashed', 'bg-dark-700', 'scale-[1.02]', 'border-blue-500', 'border-emerald-500', 'border-purple-500', 'border-yellow-500');
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const input = zone.querySelector('input[type="file"]');
                if (input) {
                    input.files = e.dataTransfer.files;
                    verificarArchivos();
                }
            }
        });
    });
});

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

window.guardarNuevaLista = async function (e) {
    e.preventDefault();
    if (ordenCreacion.length === 0) { alert("Debes seleccionar al menos una columna para exportar."); return; }

    const form = e.target;
    const formData = new FormData(form);
    const idLista = document.getElementById('lista-id').value;
    const url = idLista ? window.GeliaConfig.routes.actualizar.replace(':id', idLista) : window.GeliaConfig.routes.guardar;

    mostrarCarga(idLista ? "Actualizando configuración..." : "Guardando configuración...");

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
                alert("Error de validación:\n" + msg);
            } else {
                alert("Error: " + (data.message || "Error desconocido"));
            }
        } else {
            alert(idLista ? "Lista actualizada con éxito." : "Lista creada con éxito.");
            window.location.reload();
        }

    } catch (error) {
        console.error(error);
        alert("Error de red: " + error.message);
    } finally {
        ocultarCarga();
    }
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

window.verificarArchivos = function () {
    const fileExistencias = document.getElementById('file-existencias');
    if (!fileExistencias) return;

    const inputs = {
        'existencias': fileExistencias.value !== "",
        'precios': document.getElementById('file-precios').value !== "",
        'costos': document.getElementById('file-costos').value !== ""
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

window.procesarSolicitud = async function (tipo) {
    const fileExistencias = document.getElementById('file-existencias');
    const tieneExistencias = fileExistencias && fileExistencias.value !== "";
    const tienePrecios = document.getElementById('file-precios').value !== "";
    const tieneCostos = document.getElementById('file-costos').value !== "";

    if (tipo === 'clientes') {
        const fileClientes = document.getElementById('file-clientes').value;
        if (!fileClientes) { mostrarToast("Sube el archivo CSV de Clientes", "red"); return; }
        // NUEVA VALIDACIÓN: Asegurar que al menos seleccionó 1 columna
        const checks = document.querySelectorAll('.check-col-cliente:checked');
        if (checks.length === 0) { mostrarToast("Selecciona al menos una columna de clientes", "red"); return; }
    }
    else if (tipo === 'gastos') {
        const fileGastos = document.getElementById('file-gastos').value;
        if (!fileGastos) { mostrarToast("⚠️ Sube el archivo de Gastos Comprobables", "red"); return; }
    }
    else if (tipo === 'transacciones') {
        const fileTransacciones = document.getElementById('file-transacciones').value;
        if (!fileTransacciones) { mostrarToast("⚠️ Sube el archivo de Transacciones Bancarias", "red"); return; }
    }
    else if (!isNaN(tipo)) {
        if (!tieneExistencias) { mostrarToast("Existencias es obligatorio.", "red"); return; }
        if (window.GeliaConfig && window.GeliaConfig.customLists) {
            const listaConfig = window.GeliaConfig.customLists.find(l => l.id == tipo);
            if (listaConfig) {
                const reqs = listaConfig.archivos_requeridos || [];
                if (reqs.includes('precios') && !tienePrecios) { mostrarToast(`Requiere PRECIOS.`, "red"); return; }
                if (reqs.includes('costos') && !tieneCostos) { mostrarToast(`Requiere COSTOS.`, "red"); return; }
            }
        }
    }
    else {
        if (!tieneExistencias) { mostrarToast("Primero sube Existencias", "red"); return; }
        if ((tipo === 'resurtido' || tipo === 'actualizada' || tipo === 'inventario') && !tienePrecios) {
            mostrarToast("Esta lista requiere: Existencias + Precios", "red"); return;
        }
        if (tipo === 'costos' && !tieneCostos) {
            mostrarToast("Esta lista requiere: Existencias + Costos", "red"); return;
        }
    }

    let columnas = [];
    let nombreTipo = "";

    if (!isNaN(tipo)) {
        const listaConfig = window.GeliaConfig.customLists.find(l => l.id == tipo);
        nombreTipo = listaConfig ? listaConfig.titulo_lista : "Lista Personalizada";
    }
    else if (tipo === 'clientes') { nombreTipo = "Limpieza de Clientes"; }
    else if (tipo === 'gastos') { nombreTipo = "Gastos Comprobables"; }
    else if (tipo === 'transacciones') { nombreTipo = "Transacciones Bancarias"; }
    else if (tipo === 'resurtido') { columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Plataformas', 'Lista3']; nombreTipo = "Lista de Resurtido"; }
    else if (tipo === 'costos') { columnas = ['Almacen', 'SKU', 'Descripcion', 'Existencia', 'CostoWizerp']; nombreTipo = "Lista de Costos"; }
    else if (tipo === 'actualizada') { columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'CostoCalculado', 'Plataformas']; nombreTipo = "Lista Actualizada"; }
    else if (tipo === 'inventario') { columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Lista3']; nombreTipo = "Lista de Inventario"; }
    else {
        columnas = ordenSeleccionado;
        nombreTipo = "Lista Personalizada";
        if (columnas.length === 0) { mostrarToast("Error: Selecciona columnas.", "red"); return; }
    }

    const form = document.getElementById('form-principal');
    const formData = new FormData(form);

    if (columnas.length > 0) formData.append('orden_final', columnas.join(','));
    formData.append('tipo_lista', tipo);

    // NUEVO BLOQUE: Empaquetar datos específicos de limpieza de clientes
    if (tipo === 'clientes') {
        const checks = document.querySelectorAll('.check-col-cliente:checked');
        const cols = Array.from(checks).map(c => c.value);
        formData.append('columnas_clientes', cols.join(','));
        // Forzamos el envío booleano para sobrescribir el valor de FormData
        formData.set('incluir_sin_id', document.getElementById('check-incluir-sin-id').checked ? '1' : '0');
    }

    mostrarCarga(`Generando: ${nombreTipo}...`);
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
                mostrarError(html);
            } else { throw new Error(data.error || 'Error en el servidor'); }
            ocultarCarga(); return;
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
        mostrarToast("Archivo Generado Exitosamente!", "green");

    } catch (error) {
        console.error(error);
        ocultarCarga();
        mostrarToast("Error: " + error.message, "red");
    }
}

window.eliminarLista = async function (event, id) {
    event.stopPropagation();
    if (!confirm("Eliminar esta lista personalizada?")) return;
    mostrarCarga("Eliminando...");
    try {
        const urlEliminar = window.GeliaConfig.routes.eliminar.replace(':id', id);
        const response = await fetch(urlEliminar, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value, 'Accept': 'application/json' }
        });
        if (response.ok) { alert("Eliminada."); window.location.reload(); } else { alert("Error al eliminar."); }
    } catch (error) { console.error(error); alert("Error de red."); } finally { ocultarCarga(); }
}

window.mostrarCarga = function (m) { document.getElementById('overlay-carga').classList.remove('hidden'); document.getElementById('texto-carga').innerText = m; }
window.ocultarCarga = function () { document.getElementById('overlay-carga').classList.add('hidden'); }
window.mostrarToast = function (m, c) {
    const t = document.getElementById('toast');
    const tm = document.getElementById('toast-msg');
    t.className = `fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold flex items-center transform transition-all duration-300 ${c === 'red' ? 'bg-red-600' : 'bg-emerald-600'}`;
    tm.innerText = m;
    t.classList.remove('hidden', 'toast-enter'); t.classList.add('toast-enter-active');
    setTimeout(() => { t.classList.add('hidden'); }, 4000);
}
window.mostrarError = function (h) { document.getElementById('alertas').innerHTML = `<div class="bg-red-900/50 border border-red-500 text-red-200 p-4 rounded-lg mb-6">${h}</div>`; }

// Función para marcar/desmarcar masivamente las columnas de clientes
window.toggleColumnasClientes = function (btn) {
    const checks = document.querySelectorAll('.check-col-cliente');
    const isSelectingAll = btn.innerText.trim() === 'Seleccionar Todas';
    
    // Cambiamos el estado de todos los checkboxes
    checks.forEach(c => c.checked = isSelectingAll);
    
    // Alternamos el texto del botón
    btn.innerText = isSelectingAll ? 'Desmarcar Todas' : 'Seleccionar Todas';
}