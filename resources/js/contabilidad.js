/*
 * Módulo: Contabilidad Bellaroma
 * Descripción: Maneja la lógica de la interfaz, autocompletado en memoria, y gráficas.
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

document.addEventListener('DOMContentLoaded', function () {

    // 2. VARIABLES GLOBALES DEL MÓDULO
    let diccionarioSKU = {};
    const contenedorProductos = document.getElementById('contenedor_productos');
    const config = window.ContabilidadConfig || {};
    const chartData = config.graficaData || {};
    const indicadorNombre = document.getElementById('nombre_archivo_resurtido');
    const btnLimpiarMemoria = document.getElementById('btnLimpiarMemoria');

    // 3. SANITIZACIÓN DE MONEDA
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

    // 4. LÓGICA DE FECHA "PEGAJOSA"
    const inputFecha = document.getElementById('fecha_salida');
    const btnFechaHoy = document.getElementById('btnFechaHoy');
    const fechaLocalActual = new Date().toLocaleDateString('en-CA'); 
    
    const fechaGuardada = sessionStorage.getItem('gelia_fecha_captura');
    const diaRealGuardado = sessionStorage.getItem('gelia_dia_real');

    if (inputFecha) {
        if (fechaGuardada && diaRealGuardado === fechaLocalActual) {
            inputFecha.value = fechaGuardada;
        } else {
            inputFecha.value = fechaLocalActual;
            sessionStorage.removeItem('gelia_fecha_captura');
        }

        inputFecha.addEventListener('change', function() {
            sessionStorage.setItem('gelia_fecha_captura', this.value);
            sessionStorage.setItem('gelia_dia_real', fechaLocalActual);
        });

        if (btnFechaHoy) {
            btnFechaHoy.addEventListener('click', function() {
                inputFecha.value = fechaLocalActual;
                sessionStorage.setItem('gelia_fecha_captura', fechaLocalActual);
                sessionStorage.setItem('gelia_dia_real', fechaLocalActual);
            });
        }
    }

    // 5. RECUPERACIÓN DE MEMORIA DEL EXCEL
    const memoriaGuardada = sessionStorage.getItem('gelia_lista_resurtido');
    const nombreArchivoGuardado = sessionStorage.getItem('gelia_lista_nombre');
    
    if (memoriaGuardada) {
        try {
            diccionarioSKU = JSON.parse(memoriaGuardada);
            const nombreMostrar = nombreArchivoGuardado ? nombreArchivoGuardado : 'Lista en memoria';
            
            // Validamos existencia antes de asignar texto
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

    // 6. PROCESAMIENTO DE NUEVO EXCEL (LISTA DEL DÍA)
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
                    if(typeof window.mostrarToast === 'function') window.mostrarToast('Lista guardada.', 'green');
                }
            } catch (error) {
                console.error(error);
                alert("Error al procesar el archivo.");
            } finally {
                if(typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // 7. FILAS DINÁMICAS Y AUTOCOMPLETADO
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

    // 8. GUARDADO MANUAL DEL PEDIDO
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

            if(errorSKU) {
                if(typeof window.mostrarToast === 'function') window.mostrarToast('Hay SKUs no válidos o sin cargar.', 'red');
                return;
            }

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
                    if (typeof window.mostrarToast === 'function') window.mostrarToast('Pedido guardado con éxito', 'green');
                    setTimeout(() => window.location.reload(), 800);
                } else alert("Error: " + (res.message || "Verifica los datos"));
                
            } catch (error) {
                console.error(error);
                alert("Error de conexión al servidor.");
            } finally {
                if (typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // 9. IMPORTACIÓN HISTÓRICA MASIVA
    const btnToggleMasivo = document.getElementById('btnToggleMasivo');
    const panelCargaMasiva = document.getElementById('panelCargaMasiva');
    const inputHistorico = document.getElementById('archivo_historico');
    const indicadorHistorico = document.getElementById('nombre_archivo_historico');

    if (btnToggleMasivo && panelCargaMasiva) {
        btnToggleMasivo.addEventListener('click', () => panelCargaMasiva.classList.toggle('hidden'));
    }

    if (inputHistorico) {
        inputHistorico.addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if(!file) return;

            // PARCHE DE SEGURIDAD 1
            if (indicadorHistorico) {
                indicadorHistorico.innerText = `Procesando ${file.name}...`;
            }
            
            if(typeof window.mostrarCarga === 'function') window.mostrarCarga('Agrupando pedidos e insertando en base de datos...');

            const formData = new FormData();
            formData.append('archivo_historico', file);
            formData.append('_token', config.token);

            try {
                const response = await fetch('/contabilidad/importar-historico', { method: 'POST', body: formData });
                const res = await response.json();

                if(res.success) {
                    if(typeof window.mostrarToast === 'function') window.mostrarToast(res.message, 'green');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    alert("Error: " + res.message);
                    
                    // PARCHE DE SEGURIDAD 2
                    if (indicadorHistorico) {
                        indicadorHistorico.innerText = "Error en el formato.";
                    }
                }
            } catch (error) {
                console.error(error);
                alert("Fallo de conexión en la carga masiva.");
            } finally {
                if(typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // -----------------------------------------------------------
    // 10. MOTOR DE GRÁFICAS (DASHBOARD & PRINCIPAL)
    // -----------------------------------------------------------
    const btnAbrirDashboard = document.getElementById('btnAbrirDashboard');
    const btnCerrarDashboard = document.getElementById('btnCerrarDashboard');
    const modalDashboard = document.getElementById('modalDashboard');
    
    let chartDona = null;
    let chartBarras = null;

    // --- Lógica de Filtros UI en Dashboard ---
    const selectFiltro = document.getElementById('dash_filtro_tipo');
    const contMes = document.getElementById('filtro_mes_container');
    const contDia = document.getElementById('filtro_dia_container');
    const contCustom = document.getElementById('filtro_custom_container');

    if (selectFiltro) {
        selectFiltro.addEventListener('change', function() {
            contMes.classList.add('hidden');
            contDia.classList.add('hidden');
            contCustom.classList.add('hidden');

            if(this.value === 'mes' || this.value === 'anio') contMes.classList.remove('hidden');
            if(this.value === 'dia') contDia.classList.remove('hidden');
            if(this.value === 'custom') contCustom.classList.remove('hidden');
        });
    }

    // --- Apertura y Petición de Datos ---
    if (btnAbrirDashboard && modalDashboard) {
        btnAbrirDashboard.addEventListener('click', () => {
            modalDashboard.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; 
            cargarDatosDashboard();
        });

        btnCerrarDashboard.addEventListener('click', () => {
            modalDashboard.classList.add('hidden');
            document.body.style.overflow = 'auto';
        });
        
        document.getElementById('btnActualizarDashboard')?.addEventListener('click', cargarDatosDashboard);
    }

    async function cargarDatosDashboard() {
        if(typeof window.mostrarCarga === 'function') window.mostrarCarga('Analizando finanzas...');

        const params = new URLSearchParams({
            filtro: selectFiltro.value,
            mes: document.getElementById('dash_mes').value,
            anio: document.getElementById('dash_anio').value,
            fecha: document.getElementById('dash_fecha').value,
            inicio: document.getElementById('dash_inicio').value,
            fin: document.getElementById('dash_fin').value,
        });

        try {
            const response = await fetch(`/contabilidad/dashboard-data?${params.toString()}`);
            const data = await response.json();
            renderizarDashboardAvanzado(data);
        } catch(e) {
            console.error(e);
            alert("Error al cargar datos del dashboard.");
        } finally {
            if(typeof window.ocultarCarga === 'function') window.ocultarCarga();
        }
    }

    function renderizarDashboardAvanzado(data) {
        // Formateador de moneda rápido
        const formatMoney = (val) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(val);

        // Actualización de KPIs
        document.getElementById('kpi_ventas').innerText = formatMoney(data.kpis.ventas);
        document.getElementById('kpi_ganancias').innerText = formatMoney(data.kpis.ganancias);
        document.getElementById('kpi_perdidas').innerText = formatMoney(data.kpis.perdidas);
        document.getElementById('kpi_comisiones').innerText = formatMoney(data.kpis.comisiones);

        // Destruimos gráficas previas para no sobreponerlas
        if (chartDona) chartDona.destroy();
        if (chartBarras) chartBarras.destroy();

        // Identidad Corporativa de Pasarelas
        const coloresPlataformas = {
            'paypal': '#0079C1',
            'mercadopago': '#F5C20F',
            'kueskipay': '#00E5FF',
            'stripe': '#8271DF'
        };

        const ctxPlataformas = document.getElementById('chartPlataformas');
        if (ctxPlataformas) {
            let labelsPlat = Object.keys(data.plataformas);
            let dataPlat = Object.values(data.plataformas);
            
            if(labelsPlat.length === 0) { labelsPlat = ['Sin Datos']; dataPlat = [1]; }

            const backgroundColors = labelsPlat.map(name => {
                const cleanName = name.toLowerCase().replace(/\s+/g, '');
                return coloresPlataformas[cleanName] || '#64748b'; // Plataformas genéricas van en gris
            });

            chartDona = new Chart(ctxPlataformas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labelsPlat,
                    datasets: [{ data: dataPlat, backgroundColor: backgroundColors, borderWidth: 0 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#94a3b8' } },
                        tooltip: { callbacks: { label: (ctx) => ` $${ctx.raw.toFixed(2)}` } }
                    }
                }
            });
        }

        const ctxComparativa = document.getElementById('chartVentasUtilidad');
        if (ctxComparativa) {
            let labelsFechas = Object.keys(data.grafica);
            let dataVentas = labelsFechas.map(k => data.grafica[k].venta);
            let dataUtilidades = labelsFechas.map(k => data.grafica[k].utilidad);

            if(labelsFechas.length === 0) { labelsFechas = ['Sin Datos']; dataVentas = [0]; dataUtilidades = [0]; }

            chartBarras = new Chart(ctxComparativa.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labelsFechas,
                    datasets: [
                        { 
                            label: 'Ventas Totales (Bruto)', 
                            data: dataVentas, 
                            backgroundColor: 'rgba(59, 130, 246, 0.25)', // Azul opaco para fondo
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        { 
                            label: 'Utilidad Neta', 
                            data: dataUtilidades, 
                            backgroundColor: 'rgba(16, 185, 129, 0.95)', // Verde brillante
                            borderRadius: 4 
                        }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { 
                        y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } }, 
                        x: { grid: { display: false }, ticks: { color: '#94a3b8' } } 
                    },
                    plugins: { legend: { labels: { color: '#f8fafc' } } }
                }
            });
        }
    }

    // Arranque de Gráfica de vista principal
    function inicializarGrafica() {
        const ctx = document.getElementById('utilidadChart');
        if (!ctx) return;
        const chartData = window.ContabilidadConfig.graficaData || {};
        let labels = Object.keys(chartData).reverse();
        let dataPoints = labels.map(k => chartData[k].utilidad); 
        if (labels.length === 0) { labels = ['Sin Datos']; dataPoints = [0]; }
        const bgColors = dataPoints.map(val => val >= 0 ? 'rgba(16, 185, 129, 0.6)' : 'rgba(239, 68, 68, 0.6)');
        const borderColors = dataPoints.map(val => val >= 0 ? 'rgba(16, 185, 129, 1)' : 'rgba(239, 68, 68, 1)');
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: { labels: labels, datasets: [{ label: 'Utilidad por Día', data: dataPoints, backgroundColor: bgColors, borderColor: borderColors, borderWidth: 1, borderRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' } }, x: { grid: { display: false }, ticks: { color: '#94a3b8' } } } }
        });
    }
    inicializarGrafica();
});