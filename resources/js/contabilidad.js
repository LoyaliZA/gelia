/*
 * Módulo: Contabilidad Bellaroma
 * Descripción: Maneja la captura de datos, autocompletado en memoria, CRUD rápido y Dashboards de Análisis.
 */

// ==========================================
// 1. FUNCIONES GLOBALES (MODALES Y ACCIONES)
// ==========================================

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

// ==========================================
// 2. INICIALIZACIÓN DEL DOM Y LÓGICA PRINCIPAL
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const config = window.ContabilidadConfig || {};
    let diccionarioSKU = {};

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

    // --- ACTUALIZACIÓN RÁPIDA DE PEDIDO (MODAL EDITAR) ---
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
                const response = await fetch(`${config.rutas.actualizarPedidoBase}/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                if(res.success) window.location.reload();
                else alert('Error: ' + res.message);
            } catch(error) {
                console.error(error);
                alert('Fallo de conexión al actualizar.');
            } finally {
                if(typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // --- PANELES Y NAVEGACIÓN ---
    const btnToggleMasivo = document.getElementById('btnToggleMasivo');
    const panelCargaMasiva = document.getElementById('panelCargaMasiva');
    if (btnToggleMasivo && panelCargaMasiva) {
        btnToggleMasivo.addEventListener('click', () => panelCargaMasiva.classList.toggle('hidden'));
    }

    const btnAbrirDashboard = document.getElementById('btnAbrirDashboard');
    const modalDashboard = document.getElementById('modalDashboard');
    const btnCerrarDashboard = document.getElementById('btnCerrarDashboard');

    if (btnAbrirDashboard && modalDashboard) {
        btnAbrirDashboard.addEventListener('click', function() {
            modalDashboard.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Evita scroll de fondo
        });
    }

    if (btnCerrarDashboard && modalDashboard) {
        btnCerrarDashboard.addEventListener('click', function() {
            modalDashboard.classList.add('hidden');
            document.body.style.overflow = '';
        });
    }

    // --- MEMORIA DE ARCHIVO EXCEL ---
    const contenedorProductos = document.getElementById('contenedor_productos');
    const indicadorNombre = document.getElementById('nombre_archivo_resurtido');
    const btnLimpiarMemoria = document.getElementById('btnLimpiarMemoria');

    if (btnLimpiarMemoria) {
        btnLimpiarMemoria.addEventListener('click', function() {
            if(!confirm('¿Estás seguro de cerrar la sesión de esta lista? Se bloqueará el formulario de nuevo.')) return;
            sessionStorage.removeItem('gelia_lista_resurtido');
            sessionStorage.removeItem('gelia_lista_nombre');
            diccionarioSKU = {};
            if (indicadorNombre) {
                indicadorNombre.innerText = 'Sin archivo cargado.';
                indicadorNombre.classList.replace('text-green-400', 'text-dark-muted');
            }
            this.classList.add('hidden');
            const inputArchivo = document.getElementById('archivo_resurtido');
            if (inputArchivo) inputArchivo.value = '';
            const bloqueoForm = document.getElementById('bloqueo_formulario');
            if (bloqueoForm) bloqueoForm.style.display = 'flex';
            if (contenedorProductos) contenedorProductos.innerHTML = '';
        });
    }

    const memoriaGuardada = sessionStorage.getItem('gelia_lista_resurtido');
    const nombreArchivoGuardado = sessionStorage.getItem('gelia_lista_nombre');
    
    if (memoriaGuardada) {
        try {
            diccionarioSKU = JSON.parse(memoriaGuardada);
            if (indicadorNombre) {
                indicadorNombre.innerText = `[${nombreArchivoGuardado || 'Lista en memoria'}] - ${Object.keys(diccionarioSKU).length} productos.`;
                indicadorNombre.classList.replace('text-dark-muted', 'text-green-400');
            }
            const bloqueoForm = document.getElementById('bloqueo_formulario');
            if (bloqueoForm) bloqueoForm.style.display = 'none';
            if (btnLimpiarMemoria) btnLimpiarMemoria.classList.remove('hidden');
            if (contenedorProductos && contenedorProductos.children.length === 0) agregarFilaProducto();
        } catch (e) { console.error("Error al recuperar memoria:", e); }
    }

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
                alert("Error al procesar el archivo.");
            } finally {
                if(typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // --- FILAS DINÁMICAS DE PRODUCTOS ---
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

    // --- GUARDADO MANUAL DEL PEDIDO NUEVO ---
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

            if(errorSKU) return alert('Hay SKUs no válidos o no encontrados en la memoria.');

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
                if (res.success) setTimeout(() => window.location.reload(), 500);
                else alert("Error: " + (res.message || "Verifica los datos"));
            } catch (error) { console.error(error); } 
            finally { if (typeof window.ocultarCarga === 'function') window.ocultarCarga(); }
        });
    }

    // --- BUSCADOR Y ORDENAMIENTO DE TABLA ---
    const inputBuscador = document.getElementById('inputBuscador');
    const tbodyPedidos = document.querySelector('#tablaPedidos tbody');

    if (inputBuscador && tbodyPedidos) {
        inputBuscador.addEventListener('input', function() {
            const termino = this.value.toLowerCase().trim();
            document.querySelectorAll('.registro-fila').forEach(fila => {
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

    // --- CONFIGURACIÓN DE COMISIONES GLOBALES ---
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

    // --- GRÁFICA PRINCIPAL (VISTA GENERAL) ---
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
                    borderWidth: 1, borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => ` $${ctx.raw.toLocaleString('es-MX', {minimumFractionDigits: 2})}` } }
                },
                scales: {
                    y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8', callback: (val) => `$${val.toLocaleString()}` } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    }
    renderGraficaUtilidad();

    // ==========================================
    // 3. ANÁLISIS FINANCIERO (DASHBOARD MODAL)
    // ==========================================
    
    // Filtros UI del Dashboard
    const dashFiltroTipo = document.getElementById('dash_filtro_tipo');
    if (dashFiltroTipo) {
        dashFiltroTipo.addEventListener('change', function() {
            document.getElementById('filtro_mes_container').classList.add('hidden');
            document.getElementById('filtro_dia_container').classList.add('hidden');
            document.getElementById('filtro_custom_container').classList.add('hidden');

            if (this.value === 'mes') document.getElementById('filtro_mes_container').classList.remove('hidden');
            if (this.value === 'dia') document.getElementById('filtro_dia_container').classList.remove('hidden');
            if (this.value === 'custom') document.getElementById('filtro_custom_container').classList.remove('hidden', 'flex');
        });
    }

    // Variables globales para destruir gráficas antiguas antes de dibujar nuevas
    let chartPlataformasInst = null;
    let chartVentasUtilidadInst = null;

    const btnActualizarDash = document.getElementById('btnActualizarDashboard');
    if (btnActualizarDash) {
        btnActualizarDash.addEventListener('click', async function() {
            const filtro = dashFiltroTipo.value;
            let queryUrl = `${config.rutas.dashboardData}?filtro=${filtro}`;
            
            if (filtro === 'mes') {
                queryUrl += `&mes=${document.getElementById('dash_mes').value}&anio=${document.getElementById('dash_anio').value}`;
            } else if (filtro === 'dia') {
                queryUrl += `&fecha=${document.getElementById('dash_fecha').value}`;
            } else if (filtro === 'anio') {
                queryUrl += `&anio=${document.getElementById('dash_anio').value}`; // Aunque uses el mismo select
            } else if (filtro === 'custom') {
                queryUrl += `&inicio=${document.getElementById('dash_inicio').value}&fin=${document.getElementById('dash_fin').value}`;
            }

            const btnOriginalText = this.innerText;
            this.innerText = 'Generando...';
            this.disabled = true;

            try {
                const response = await fetch(queryUrl);
                const data = await response.json();
                
                // 1. Actualizar KPIs con formato Moneda
                const formatMoney = (val) => `$${parseFloat(val).toLocaleString('es-MX', {minimumFractionDigits: 2})}`;
                document.getElementById('kpi_ventas').innerText = formatMoney(data.kpis.ventas);
                document.getElementById('kpi_ganancias').innerText = formatMoney(data.kpis.ganancias);
                document.getElementById('kpi_perdidas').innerText = formatMoney(data.kpis.perdidas);
                document.getElementById('kpi_comisiones').innerText = formatMoney(data.kpis.comisiones);

                // 2. Gráfica Doughnut (Plataformas)
                const ctxPlat = document.getElementById('chartPlataformas');
                if (ctxPlat) {
                    if (chartPlataformasInst) chartPlataformasInst.destroy();
                    const platLabels = Object.keys(data.plataformas);
                    const platValues = Object.values(data.plataformas);
                    
                    chartPlataformasInst = new Chart(ctxPlat.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: platLabels,
                            datasets: [{
                                data: platValues,
                                backgroundColor: ['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 10 } } },
                                tooltip: { callbacks: { label: (ctx) => ` $${ctx.raw.toLocaleString('es-MX')}` } }
                            }
                        }
                    });
                }

                // 3. Gráfica de Barras (Venta vs Utilidad)
                const ctxVentasUt = document.getElementById('chartVentasUtilidad');
                if (ctxVentasUt) {
                    if (chartVentasUtilidadInst) chartVentasUtilidadInst.destroy();
                    const chartData = data.grafica || {};
                    const dates = Object.keys(chartData);
                    const dataVentas = dates.map(d => chartData[d].venta);
                    const dataUtilidad = dates.map(d => chartData[d].utilidad);

                    chartVentasUtilidadInst = new Chart(ctxVentasUt.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: dates,
                            datasets: [
                                { label: 'Venta Bruta', data: dataVentas, backgroundColor: 'rgba(59, 130, 246, 0.7)' },
                                { label: 'Utilidad Neta', data: dataUtilidad, backgroundColor: 'rgba(16, 185, 129, 0.8)' }
                            ]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top', labels: { color: '#94a3b8' } },
                                tooltip: { callbacks: { label: (ctx) => ` $${ctx.raw.toLocaleString('es-MX', {minimumFractionDigits: 2})}` } }
                            },
                            scales: {
                                y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8', callback: (val) => `$${val.toLocaleString()}` } },
                                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                            }
                        }
                    });
                }

            } catch (error) {
                console.error("Error cargando dashboard:", error);
                alert("Ocurrió un problema al obtener los datos del reporte.");
            } finally {
                this.innerText = btnOriginalText;
                this.disabled = false;
            }
        });
    }
});