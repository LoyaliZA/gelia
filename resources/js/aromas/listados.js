let ordenSeleccionado = [];
let ordenCreacion = [];

const camposPorArchivo = {
    'existencias': ['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio'],
    'precios': ['PG', 'Bronce', 'Plata', 'Oro', 'Diamante', 'Plataformas', 'Lista3', 'Lista4', 'ListaBoutique', 'CostoCalculado'],
    'costos': ['CostoWizerp']
};

document.addEventListener('DOMContentLoaded', () => {
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
    document.getElementById('check-filtro-relojes').checked = false;
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

    // POBLADO DE FILTROS: Asignación de valores existentes en la base de datos
    document.getElementById('check-solo-existencia').checked = !!lista.solo_con_existencia;
    document.getElementById('check-filtro-relojes').checked = !!lista.filtro_relojes; // NUEVO

    ordenCreacion = [];
    document.querySelectorAll('[id^="badge-creacion-"]').forEach(b => b.classList.add('hidden'));
    document.querySelectorAll('#form-crear-lista input[type="checkbox"]').forEach(c => {
        // EXCLUSIÓN DE LIMPIEZA: Evitamos que el reset global borre nuestros filtros
        if (c.id !== 'check-solo-existencia' && c.id !== 'check-filtro-relojes' && c.name !== 'archivos_requeridos[]') c.checked = false;
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

// Valores por defecto como salvaguarda
const configDescuentosDefault = {
    bronce: 12.39, plata: 14.14, oro: 15.89,
    diamante: 17.65, lista3: 14.28, lista4: 17.71
};

window.abrirModalConfiguracion = function () {
    const config = JSON.parse(localStorage.getItem('gelia_config_descuentos')) || configDescuentosDefault;

    Object.keys(config).forEach(key => {
        const input = document.getElementById(`input-pct-${key}`);
        if (input) input.value = config[key];
    });

    document.getElementById('modal-configuracion').classList.remove('hidden');
};

window.cerrarModalConfiguracion = function () {
    document.getElementById('modal-configuracion').classList.add('hidden');
};

window.guardarConfiguracionDescuentos = function () {
    const nuevaConfig = {};

    Object.keys(configDescuentosDefault).forEach(key => {
        const inputVal = document.getElementById(`input-pct-${key}`).value;
        nuevaConfig[key] = parseFloat(inputVal) || configDescuentosDefault[key];
    });

    localStorage.setItem('gelia_config_descuentos', JSON.stringify(nuevaConfig));
    window.cerrarModalConfiguracion();
    window.mostrarToast("Configuración de descuentos actualizada exitosamente.", "green");
};

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

    // EXTRACCIÓN EXPLÍCITA DE BOOLEANOS: Garantiza que siempre se envíen '1' o '0'
    formData.set('solo_con_existencia', document.getElementById('check-solo-existencia').checked ? '1' : '0');
    formData.set('filtro_relojes', document.getElementById('check-filtro-relojes').checked ? '1' : '0');

    const idLista = document.getElementById('lista-id').value;
    const url = idLista ? window.GeliaConfig.routes.actualizar.replace(':id', idLista) : window.GeliaConfig.routes.guardar;

    window.mostrarCarga(idLista ? "Actualizando configuración..." : "Guardando configuración...");

    const tokenCSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value;

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin', // Prevención de CORS/Tailscale 419
            headers: {
                'X-CSRF-TOKEN': tokenCSRF,
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

    // Validación unificada inicial
    if (!tieneExistencias) { window.mostrarToast("Existencias es obligatorio.", "red"); return; }

    let columnas = [];
    let nombreTipo = "";

    // Configuración base inmutable por si no existen en localStorage
    const listasDefault = {
        'resurtido': ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Bronce', 'Plata', 'Oro', 'Diamante'],
        'costos': ['Almacen', 'SKU', 'Descripcion', 'Existencia', 'CostoWizerp'],
        'actualizada': ['Folio', 'SKU', 'Descripcion', 'Existencia', 'CostoCalculado', 'Plataformas'],
        'inventario': ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Lista3']
    };

    if (!isNaN(tipo)) {
        const listaConfig = window.GeliaConfig.customLists.find(l => l.id == tipo);
        if (listaConfig) {
            nombreTipo = listaConfig.titulo_lista;
            const reqs = listaConfig.archivos_requeridos || [];
            if (reqs.includes('precios') && !tienePrecios) { window.mostrarToast(`Requiere PRECIOS.`, "red"); return; }
            if (reqs.includes('costos') && !tieneCostos) { window.mostrarToast(`Requiere COSTOS.`, "red"); return; }
        } else {
            nombreTipo = "Lista Personalizada";
        }
    }
    else if (listasDefault[tipo]) {
        if (tipo !== 'costos' && !tienePrecios) { window.mostrarToast("Esta lista requiere: Existencias + Precios", "red"); return; }
        if (tipo === 'costos' && !tieneCostos) { window.mostrarToast("Esta lista requiere: Existencias + Costos", "red"); return; }

        // Comprobación de persistencia local para listas editadas (Flexibilidad sin BD)
        const storageCol = localStorage.getItem('cfg_lista_' + tipo);
        columnas = storageCol ? JSON.parse(storageCol) : listasDefault[tipo];
        nombreTipo = "Lista Predefinida - " + tipo.toUpperCase();
    }
    else if (tipo === 'manual') {
        columnas = ordenSeleccionado;
        nombreTipo = "Lista Personalizada (Manual)";
        if (columnas.length === 0) { window.mostrarToast("Error: Selecciona columnas.", "red"); return; }
    } else {
        return;
    }

    const form = document.getElementById('form-principal');
    const formData = new FormData(form);

    if (columnas.length > 0) formData.append('orden_final', columnas.join(','));
    formData.append('tipo_lista', tipo);

    // --- NUEVO: Inyectar descuentos desde LocalStorage ---
    const configDesc = JSON.parse(localStorage.getItem('gelia_config_descuentos')) || configDescuentosDefault;
    Object.keys(configDesc).forEach(key => {
        formData.append(`pct_${key}`, configDesc[key]);
    });


    window.mostrarCarga(`Generando: ${nombreTipo}...`);
    document.getElementById('alertas').innerHTML = '';

    const tokenCSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value;

    try {
        const urlGenerar = window.GeliaConfig.routes.generar;
        const response = await fetch(urlGenerar, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin', // Prevención de CORS/Tailscale 419
            headers: {
                'X-CSRF-TOKEN': tokenCSRF,
                'Accept': 'application/json'
            }
        });

        if (response.status === 419) {
            window.mostrarToast("Sesión caducada. Recargando el sistema...", "orange");
            setTimeout(() => window.location.reload(), 2000);
            return;
        }

        const contentType = response.headers.get("content-type");

        if (!response.ok && contentType && contentType.includes("text/html")) {
            throw new Error("Error 500 en el servidor. Posible falla de permisos en disco temporal.");
        }

        if (contentType && contentType.includes("application/json")) {
            const data = await response.json();

            if (!response.ok) {
                if (data.errors) {
                    let html = `<ul class='list-disc ml-5'>`;
                    Object.values(data.errors).forEach(err => html += `<li>${err}</li>`);
                    html += `</ul>`;
                    window.mostrarError(html);
                } else { throw new Error(data.error || 'Error en el servidor'); }
                window.ocultarCarga();
                return;
            }

            if (data.requiere_confirmacion) {
                window.ocultarCarga();
                window.mostrarModalInconsistencias(data.inconsistencias, data.temp_file, data.nombre_descarga);
                return;
            }
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

    const tokenCSRF = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value;

    try {
        const urlEliminar = window.GeliaConfig.routes.eliminar.replace(':id', id);
        const response = await fetch(urlEliminar, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': tokenCSRF, 'Accept': 'application/json' }
        });
        if (response.ok) { window.mostrarToast("Eliminada.", "green"); setTimeout(() => window.location.reload(), 1000); }
        else { window.mostrarToast("Error al eliminar.", "red"); }
    } catch (error) { window.mostrarToast("Error de red.", "red"); } finally { window.ocultarCarga(); }
}

// ==========================================
// MANEJO DE INCONSISTENCIAS WIZERP
// ==========================================
window.mostrarModalInconsistencias = function (inconsistencias, tempFile, nombreDescarga) {
    const tbody = document.getElementById('tabla-inconsistencias-body');
    if (!tbody) {
        console.error("El contenedor 'tabla-inconsistencias-body' no existe en el DOM.");
        return;
    }

    tbody.innerHTML = '';

    inconsistencias.forEach(item => {
        tbody.innerHTML += `
            <tr class="hover:bg-dark-700/50 transition">
                <td class="px-4 py-2 font-mono text-aromas-main">${item.sku}</td>
                <td class="px-4 py-2 truncate max-w-xs" title="${item.descripcion}">${item.descripcion}</td>
                <td class="px-4 py-2 text-xs text-gray-400">${item.almacen}</td>
                <td class="px-4 py-2 text-center text-orange-400 font-bold">${item.existencia}</td>
            </tr>
        `;
    });

    const btnForzar = document.getElementById('btn-forzar-descarga');
    if (btnForzar) {
        btnForzar.onclick = () => window.descargarTemporal(tempFile, nombreDescarga);
    }

    const modal = document.getElementById('modal-inconsistencias');
    if (modal) modal.classList.remove('hidden');
}

window.cerrarModalInconsistencias = function () {
    const modal = document.getElementById('modal-inconsistencias');
    if (modal) modal.classList.add('hidden');
}

window.descargarTemporal = function (tempFile, nombreDescarga) {
    window.cerrarModalInconsistencias();
    window.mostrarToast("Iniciando descarga...", "blue");

    const url = new URL(window.GeliaConfig.routes.descargar_temporal, window.location.origin);
    url.searchParams.append('temp_file', tempFile);
    url.searchParams.append('nombre_descarga', nombreDescarga);

    const a = document.createElement("a");
    a.href = url.toString();
    document.body.appendChild(a);
    a.click();
    a.remove();
}

window.copiarTablaInconsistencias = function () {
    const tbody = document.getElementById('tabla-inconsistencias-body');
    if (!tbody) return;

    let texto = "SKU\tDESCRIPCION\tALMACEN\tEXISTENCIA\n";

    Array.from(tbody.rows).forEach(row => {
        const sku = row.cells[0].innerText;
        const desc = row.cells[1].innerText;
        const almacen = row.cells[2].innerText;
        const existencia = row.cells[3].innerText;
        texto += `${sku}\t${desc}\t${almacen}\t${existencia}\n`;
    });

    if (navigator.clipboard) {
        navigator.clipboard.writeText(texto).then(() => {
            window.mostrarToast("Tabla copiada lista para Excel.", "green");
        }).catch(err => {
            window.mostrarToast("Error al copiar: " + err, "red");
        });
    } else {
        window.mostrarToast("API de portapapeles no soportada en este entorno.", "orange");
    }
}