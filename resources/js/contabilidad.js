/*
 * Módulo: Contabilidad Bellaroma
 * Descripción: Maneja la lógica de la interfaz, autocompletado en memoria y gráficas.
 */

// 1. FUNCIONES GLOBALES (Borrado)
window.borrarPedido = async function(id) {
    if(!confirm('¿Estás seguro de eliminar este registro para corregirlo?')) return;
    
    if(typeof window.mostrarCarga === 'function') window.mostrarCarga('Eliminando...');
    try {
        const response = await fetch(`/contabilidad/eliminar-pedido/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.ContabilidadConfig.token }
        });
        const res = await response.json();
        if(res.success) window.location.reload();
        else alert('Error al eliminar: ' + res.message);
    } catch(e) {
        console.error(e);
        alert('Fallo de conexión.');
    } finally {
        if(typeof window.ocultarCarga === 'function') window.ocultarCarga();
    }
};

window.verDetallesPedido = function(numPedido, detalles) {
    document.getElementById('detalles_num_pedido').innerText = numPedido;
    const tbody = document.getElementById('tabla_detalles_body');
    tbody.innerHTML = '';

    if (!detalles || detalles.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-dark-muted">No hay detalles registrados.</td></tr>';
    } else {
        detalles.forEach(prod => {
            tbody.innerHTML += `
                <tr class="hover:bg-dark-700/30">
                    <td class="py-2 text-xs text-dark-muted">${prod.sku}</td>
                    <td class="py-2 text-xs truncate max-w-[150px]" title="${prod.nombre_producto}">${prod.nombre_producto}</td>
                    <td class="py-2 text-xs text-center">${prod.piezas}</td>
                    <td class="py-2 text-xs text-right text-green-400">$${parseFloat(prod.precio_unitario).toFixed(2)}</td>
                </tr>
            `;
        });
    }
    document.getElementById('modalDetalles').showModal();
};

window.abrirModalEdicion = function(id, numPedido, tipo, platformId, venta, envio, comision) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_num_pedido').innerText = numPedido;
    
    // Normalizar el tipo de transacción para el select
    let tipoNormalizado = 'venta';
    if(tipo.includes('reembolso')) tipoNormalizado = 'reembolso';
    if(tipo.includes('contracargo')) tipoNormalizado = 'contracargo';
    
    document.getElementById('edit_tipo').value = tipoNormalizado;
    document.getElementById('edit_plataforma').value = platformId;
    document.getElementById('edit_venta').value = parseFloat(venta).toFixed(2);
    document.getElementById('edit_envio').value = parseFloat(envio).toFixed(2);
    document.getElementById('edit_comision').value = parseFloat(comision).toFixed(2);
    
    document.getElementById('modalEditar').showModal();
};

document.addEventListener('DOMContentLoaded', function () {

    // --- CONTROL DEL MODAL DASHBOARD (ANÁLISIS FINANCIERO) ---
    const btnAbrirDashboard = document.getElementById('btnAbrirDashboard');
    const modalDashboard = document.getElementById('modalDashboard');
    const btnCerrarDashboard = document.getElementById('btnCerrarDashboard');

    if (btnAbrirDashboard && modalDashboard) {
        btnAbrirDashboard.addEventListener('click', function() {
            modalDashboard.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    }

    if (btnCerrarDashboard && modalDashboard) {
        btnCerrarDashboard.addEventListener('click', function() {
            modalDashboard.classList.add('hidden');
            document.body.style.overflow = '';
        });
    }

    const formEditar = document.getElementById('formEditarPedido');
    if (formEditar) {
        formEditar.addEventListener('submit', async function(e) {
            e.preventDefault();
            const id = document.getElementById('edit_id').value;
            const payload = {
                tipo_transaccion: document.getElementById('edit_tipo').value,
                platform_id: document.getElementById('edit_plataforma').value,
                venta_total: document.getElementById('edit_venta').value,
                costo_envio: document.getElementById('edit_envio').value,
                comision_plataforma: document.getElementById('edit_comision').value
            };

            if(typeof window.mostrarCarga === 'function') window.mostrarCarga('Actualizando montos...');

            try {
                const response = await fetch(`${window.ContabilidadConfig.rutas.actualizarPedidoBase}/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.ContabilidadConfig.token },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                
                if(res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            } catch(error) {
                console.error(error);
                alert('Fallo de conexión.');
            } finally {
                if(typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // Lógica para togglear el panel de carga masiva (Restaurado)
    const btnToggleMasivo = document.getElementById('btnToggleMasivo');
    const panelCargaMasiva = document.getElementById('panelCargaMasiva');
    if (btnToggleMasivo && panelCargaMasiva) {
        btnToggleMasivo.addEventListener('click', () => panelCargaMasiva.classList.toggle('hidden'));
    }

    const config = window.ContabilidadConfig || {};
    let diccionarioSKU = {};
    const contenedorProductos = document.getElementById('contenedor_productos');
    const indicadorNombre = document.getElementById('nombre_archivo_resurtido');
    const btnLimpiarMemoria = document.getElementById('btnLimpiarMemoria');
    // --- FUNCIÓN PARA BORRAR LA LISTA DE LA MEMORIA ---
    if (btnLimpiarMemoria) {
        btnLimpiarMemoria.addEventListener('click', function() {
            if(!confirm('¿Estás seguro de cerrar la sesión de esta lista? Se bloqueará el formulario de nuevo.')) return;

            // 1. Limpiar el almacenamiento del navegador y variables
            sessionStorage.removeItem('gelia_lista_resurtido');
            sessionStorage.removeItem('gelia_lista_nombre');
            diccionarioSKU = {};

            // 2. Restaurar la interfaz (Textos y Botones)
            if (indicadorNombre) {
                indicadorNombre.innerText = 'Sin archivo cargado.';
                indicadorNombre.classList.replace('text-green-400', 'text-dark-muted');
            }
            this.classList.add('hidden');
            
            const inputArchivo = document.getElementById('archivo_resurtido');
            if (inputArchivo) inputArchivo.value = '';

            // 3. Volver a bloquear el formulario
            const bloqueoForm = document.getElementById('bloqueo_formulario');
            if (bloqueoForm) bloqueoForm.style.display = 'flex';

            // 4. Limpiar los productos que estaban en el formulario
            if (contenedorProductos) contenedorProductos.innerHTML = '';
        });
    }

    // --- SANITIZACIÓN DE MONEDA ---
    function limpiarMoneda(valor) {
        if (!valor) return '';
        return valor.replace(/[^0-9.]/g, '');
    }

    document.querySelectorAll('.input-moneda').forEach(input => {
        input.addEventListener('blur', function () {
            let valorLimpio = limpiarMoneda(this.value);
            if (valorLimpio !== '') this.value = parseFloat(valorLimpio).toFixed(2);
        });
    });

    // --- RECUPERACIÓN DE MEMORIA DEL EXCEL ---
    const memoriaGuardada = sessionStorage.getItem('gelia_lista_resurtido');
    const nombreArchivoGuardado = sessionStorage.getItem('gelia_lista_nombre');
    
    if (memoriaGuardada) {
        try {
            diccionarioSKU = JSON.parse(memoriaGuardada);
            const nombreMostrar = nombreArchivoGuardado ? nombreArchivoGuardado : 'Lista en memoria';
            
            if (indicadorNombre) {
                indicadorNombre.innerText = `[${nombreMostrar}] - ${Object.keys(diccionarioSKU).length} productos.`;
                indicadorNombre.classList.replace('text-dark-muted', 'text-green-400');
            }
            
            const bloqueoForm = document.getElementById('bloqueo_formulario');
            if (bloqueoForm) bloqueoForm.style.display = 'none';
            
            if (btnLimpiarMemoria) btnLimpiarMemoria.classList.remove('hidden');
            if (contenedorProductos && contenedorProductos.children.length === 0) agregarFilaProducto();
        } catch (e) {
            console.error("Error al recuperar memoria:", e);
        }
    }

    // --- PROCESAMIENTO DE NUEVO EXCEL ---
    const inputArchivo = document.getElementById('archivo_resurtido');
    if (inputArchivo) {
        inputArchivo.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if(!file) return;

            indicadorNombre.innerText = "Procesando...";
            if(typeof window.mostrarCarga === 'function') window.mostrarCarga('Leyendo precios...');

            const formData = new FormData();
            formData.append('lista_resurtido', file);
            formData.append('_token', config.token);

            try {
                const response = await fetch(config.rutas.procesarLista, { method: 'POST', body: formData });
                const res = await response.json();

                if(res.success) {
                    diccionarioSKU = res.data;
                    sessionStorage.setItem('gelia_lista_resurtido', JSON.stringify(diccionarioSKU));
                    sessionStorage.setItem('gelia_lista_nombre', file.name);
                    
                    indicadorNombre.innerText = `[${file.name}] - ${Object.keys(diccionarioSKU).length} productos.`;
                    indicadorNombre.classList.replace('text-dark-muted', 'text-green-400');
                    document.getElementById('bloqueo_formulario').style.display = 'none';
                    
                    if (btnLimpiarMemoria) btnLimpiarMemoria.classList.remove('hidden');
                    if (contenedorProductos && contenedorProductos.children.length === 0) agregarFilaProducto();
                }
            } catch (error) {
                console.error(error);
                alert("Error al procesar el archivo.");
            } finally {
                if(typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // --- FILAS DINÁMICAS Y AUTOCOMPLETADO ---
    function agregarFilaProducto() {
        const div = document.createElement('div');
        div.className = "grid grid-cols-12 gap-3 items-end producto-fila bg-dark-800/30 p-2 rounded-lg border border-dark-700/50 mb-2";
        div.innerHTML = `
            <div class="col-span-12 md:col-span-3">
                <label class="text-xs text-dark-muted">SKU</label>
                <input type="text" class="input-sku w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm focus:border-bella-main outline-none" required>
            </div>
            <div class="col-span-4 md:col-span-2">
                <label class="text-xs text-dark-muted">Pzas</label>
                <input type="number" min="1" value="1" class="input-piezas w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none" required>
            </div>
            <div class="col-span-8 md:col-span-4">
                <label class="text-xs text-dark-muted">Producto</label>
                <input type="text" class="input-nombre w-full bg-dark-800 border border-dark-700 rounded px-2 py-1.5 text-dark-muted text-sm cursor-not-allowed" readonly required placeholder="...">
            </div>
            <div class="col-span-10 md:col-span-2">
                <label class="text-xs text-dark-muted">P. Página</label>
                <input type="number" class="input-precio w-full bg-dark-800 border border-dark-700 rounded px-2 py-1.5 text-dark-muted text-sm cursor-not-allowed" readonly required>
            </div>
            <div class="col-span-2 md:col-span-1 flex justify-center">
                <button type="button" class="btn-eliminar w-full bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white rounded py-1.5 transition-colors material-symbols-outlined text-lg" title="Eliminar fila">delete</button>
            </div>
        `;
        contenedorProductos.appendChild(div);

        const inputSku = div.querySelector('.input-sku');
        const inputNombre = div.querySelector('.input-nombre');
        const inputPrecio = div.querySelector('.input-precio');

        inputSku.addEventListener('input', function() {
            const sku = this.value.trim().replace(/^0+/, '');
            if(diccionarioSKU[sku]) {
                inputNombre.value = diccionarioSKU[sku].nombre;
                inputPrecio.value = diccionarioSKU[sku].precio;
                this.classList.replace('border-red-500', 'border-green-500');
            } else {
                inputNombre.value = ''; inputPrecio.value = '';
                if(this.value.length > 3) this.classList.add('border-red-500');
            }
        });

        div.querySelector('.btn-eliminar').addEventListener('click', function() {
            if(contenedorProductos.children.length > 1) div.remove();
        });
    }

    const btnAgregar = document.getElementById('btnAgregarProducto');
    if (btnAgregar) btnAgregar.addEventListener('click', agregarFilaProducto);

    // --- GUARDADO MANUAL DEL PEDIDO ---
    const formPedido = document.getElementById('formPedido');
    if (formPedido) {
        formPedido.addEventListener('submit', async function(e) {
            e.preventDefault();
            const filas = document.querySelectorAll('.producto-fila');
            let productosData = [];
            let errorSKU = false;

            filas.forEach(fila => {
                const sku = fila.querySelector('.input-sku').value.trim();
                const nombre = fila.querySelector('.input-nombre').value;
                const precio = fila.querySelector('.input-precio').value;
                const piezas = fila.querySelector('.input-piezas').value;

                if(!nombre || !precio) errorSKU = true;
                productosData.push({ sku, nombre, precio: parseFloat(precio), piezas: parseInt(piezas) });
            });

            if(errorSKU) return alert('Hay SKUs no válidos.');

            if(typeof window.mostrarCarga === 'function') window.mostrarCarga('Guardando registro...');

            const payload = {
                _token: config.token,
                fecha_salida: document.getElementById('fecha_salida').value,
                numero_pedido: document.getElementById('numero_pedido').value,
                tipo_transaccion: document.getElementById('tipo_transaccion').value,
                platform_id: document.getElementById('platform_id').value,
                venta_total: parseFloat(limpiarMoneda(document.getElementById('venta_total').value)) || 0,
                costo_envio: parseFloat(limpiarMoneda(document.getElementById('costo_envio').value)) || 0,
                comision_real: document.getElementById('comision_real').value ? parseFloat(limpiarMoneda(document.getElementById('comision_real').value)) : null,
                envio_pagado_cliente: document.getElementById('envio_pagado_cliente').checked ? 1 : 0,
                productos: productosData
            };

            try {
                const response = await fetch(config.rutas.guardarPedido, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();

                if (res.success) {
                    setTimeout(() => window.location.reload(), 500);
                } else alert("Error: " + (res.message || "Verifica los datos"));
            } catch (error) {
                console.error(error);
            } finally {
                if (typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // --- BUSCADOR Y FILTROS DE TABLA CORREGIDOS ---
    const inputBuscador = document.getElementById('inputBuscador');
    const tbodyPedidos = document.querySelector('#tablaPedidos tbody');

    if (inputBuscador && tbodyPedidos) {
        inputBuscador.addEventListener('input', function() {
            const termino = this.value.toLowerCase().trim();
            const filas = document.querySelectorAll('.registro-fila');
            
            filas.forEach(fila => {
                const numPedido = fila.getAttribute('data-pedido').toLowerCase();
                fila.style.display = numPedido.includes(termino) ? '' : 'none';
            });
        });
    }

    document.querySelectorAll('.th-sort').forEach(header => {
        header.addEventListener('click', () => {
            const criterio = header.getAttribute('data-sort');
            const filas = Array.from(document.querySelectorAll('.registro-fila'));
            const esAsc = header.classList.contains('asc');
            
            // Iconos UI
            document.querySelectorAll('.th-sort span').forEach(s => s.innerText = 'unfold_more');
            header.querySelector('span').innerText = esAsc ? 'expand_more' : 'expand_less';

            filas.sort((a, b) => {
                let valA = a.getAttribute(`data-${criterio}`);
                let valB = b.getAttribute(`data-${criterio}`);

                if (['comision', 'venta', 'utilidad'].includes(criterio)) {
                    return esAsc ? parseFloat(valA) - parseFloat(valB) : parseFloat(valB) - parseFloat(valA);
                }
                
                return esAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
            });

            header.classList.toggle('asc', !esAsc);
            filas.forEach(fila => tbodyPedidos.appendChild(fila));
        });
    });

    // --- ACTUALIZACIÓN DE COMISIONES (MODAL) ---
    const formConfig = document.getElementById('formUpdateComisiones');
    if (formConfig) {
        formConfig.addEventListener('submit', async function(e) {
            e.preventDefault();
            const payload = Array.from(document.querySelectorAll('.input-config-comision')).map(input => ({
                id: input.getAttribute('data-id'),
                percent: input.value
            }));

            try {
                const response = await fetch(config.rutas.updateComisiones, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token },
                    body: JSON.stringify({ plataformas: payload })
                });
                const res = await response.json();
                if(res.success) window.location.reload();
            } catch (e) { console.error(e); }
        });
    }

    // --- GRÁFICA PRINCIPAL ---
    function renderGraficaUtilidad() {
        const ctx = document.getElementById('utilidadChart');
        if (!ctx) return;

        const dataRaw = config.graficaData || {};
        const labels = Object.keys(dataRaw).reverse();
        const values = labels.map(k => dataRaw[k].utilidad);

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Utilidad Neta',
                    data: values,
                    backgroundColor: values.map(v => v >= 0 ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.4)'),
                    borderColor: values.map(v => v >= 0 ? 'rgba(16, 185, 129, 1)' : 'rgba(239, 68, 68, 1)'),
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` $${ctx.raw.toLocaleString('es-MX', {minimumFractionDigits: 2})}`
                        }
                    }
                },
                scales: {
                    y: {
                        grid: { color: '#334155' },
                        ticks: {
                            color: '#94a3b8',
                            callback: (val) => `$${val.toLocaleString()}`
                        }
                    },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    }

    renderGraficaUtilidad();
});