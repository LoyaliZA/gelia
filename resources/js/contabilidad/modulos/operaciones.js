/*
 * Módulo: Operaciones y APIs (GELIA Contabilidad)
 * Descripción: Maneja CRUD, subida de Excel, persistencia en SessionStorage y peticiones al Backend.
 */

// ==========================================
// 1. FUNCIONES GLOBALES DE CRUD
// ==========================================

window.borrarPedido = async function (id) {
    if (!confirm('¿Estás seguro de eliminar este registro para corregirlo?')) return;

    if (typeof window.mostrarCarga === 'function') window.mostrarCarga('Eliminando...');
    try {
        const response = await fetch(`/contabilidad/eliminar-pedido/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': window.ContabilidadConfig.token }
        });
        const res = await response.json();
        if (res.success) window.location.reload();
        else alert('Error al eliminar: ' + res.message);
    } catch (e) {
        console.error(e);
        alert('Fallo de conexión.');
    } finally {
        if (typeof window.ocultarCarga === 'function') window.ocultarCarga();
    }
};

// ==========================================
// 2. INICIALIZACIÓN DE FORMULARIOS Y MEMORIA
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const config = window.ContabilidadConfig || {};
    let diccionarioSKU = {};

    function limpiarMonedaStr(valor) {
        if (!valor) return '';
        return valor.replace(/[^0-9.]/g, '');
    }

    // 2.1 Actualización Rápida de Pedidos
    const formEditar = document.getElementById('formEditarPedido');
    if (formEditar) {
        formEditar.addEventListener('submit', async function (e) {
            e.preventDefault();
            const id = document.getElementById('edit_id').value;

            let productosEditados = [];
            document.querySelectorAll('.edit-producto-item').forEach(item => {
                productosEditados.push({
                    id: item.querySelector('.edit-prod-id').value,
                    piezas: parseInt(item.querySelector('.edit-prod-piezas').value)
                });
            });

            const payload = {
                tipo_transaccion: document.getElementById('edit_tipo').value,
                platform_id: document.getElementById('edit_plataforma').value,
                venta_total: document.getElementById('edit_venta').value,
                costo_envio: document.getElementById('edit_envio').value,
                comision_plataforma: document.getElementById('edit_comision').value,
                cliente_nombre: document.getElementById('edit_cliente').value,
                envio_pagado_cliente: document.getElementById('edit_envio_pagado_cliente').checked ? 1 : 0,
                productos: productosEditados
            };

            if (typeof window.mostrarCarga === 'function') window.mostrarCarga('Actualizando montos y cantidades...');

            try {
                const response = await fetch(`${config.rutas.actualizarPedidoBase}/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                if (res.success) window.location.reload();
                else alert('Error: ' + res.message);
            } catch (error) {
                console.error(error);
                alert('Fallo de conexión al actualizar.');
            } finally {
                if (typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // 2.2 Confirmación Individual Bancaria
    const formConfirmarRetiro = document.getElementById('formConfirmarRetiro');
    if (formConfirmarRetiro) {
        formConfirmarRetiro.addEventListener('submit', async function (e) {
            e.preventDefault();
            const id = document.getElementById('conf_id').value;
            const btn = document.getElementById('btnConfIndividual');

            btn.disabled = true;
            btn.innerText = 'Procesando...';

            try {
                const response = await fetch(`/contabilidad/confirmar-individual/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token },
                    body: JSON.stringify({
                        monto_real_banco: document.getElementById('conf_monto').value,
                        fecha_deposito: document.getElementById('conf_fecha').value
                    })
                });

                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + res.message);
                }
            } catch (err) {
                console.error(err);
                alert('Fallo de conexión.');
            } finally {
                btn.disabled = false;
                btn.innerText = 'Guardar y Transferir a Neta';
            }
        });
    }

    // 2.3 Actualización de Comisiones Globales
    const formConfig = document.getElementById('formUpdateComisiones');
    if (formConfig) {
        formConfig.addEventListener('submit', async function (e) {
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
                if (res.success) window.location.reload();
            } catch (e) { console.error(e); }
        });
    }

    // 2.4 Memoria de Excel y Lectura Local
    const contenedorProductos = document.getElementById('contenedor_productos');
    const indicadorNombre = document.getElementById('nombre_archivo_resurtido');
    const btnLimpiarMemoria = document.getElementById('btnLimpiarMemoria');

    if (btnLimpiarMemoria) {
        btnLimpiarMemoria.addEventListener('click', function () {
            if (!confirm('¿Estás seguro de cerrar la sesión de esta lista? Se bloqueará el formulario de nuevo.')) return;
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
        inputArchivo.addEventListener('change', async function (e) {
            const file = e.target.files[0];
            if (!file) return;

            indicadorNombre.innerText = "Procesando...";
            if (typeof window.mostrarCarga === 'function') window.mostrarCarga('Leyendo precios...');

            const formData = new FormData();
            formData.append('lista_resurtido', file);
            formData.append('_token', config.token);

            try {
                const response = await fetch(config.rutas.procesarLista, { method: 'POST', body: formData });
                const res = await response.json();

                if (res.success) {
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
                if (typeof window.ocultarCarga === 'function') window.ocultarCarga();
            }
        });
    }

    // 2.5 Inserción Dinámica de Filas de Productos
    function agregarFilaProducto() {
        if (!contenedorProductos) return;
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

        inputSku.addEventListener('input', function () {
            const sku = this.value.trim().replace(/^0+/, '');
            if (diccionarioSKU[sku]) {
                inputNombre.value = diccionarioSKU[sku].nombre;
                inputPrecio.value = diccionarioSKU[sku].precio;
                this.classList.replace('border-red-500', 'border-green-500');
            } else {
                inputNombre.value = ''; inputPrecio.value = '';
                if (this.value.length > 3) this.classList.add('border-red-500');
            }
        });

        div.querySelector('.btn-eliminar').addEventListener('click', function () {
            if (contenedorProductos.children.length > 1) div.remove();
        });
    }

    const btnAgregar = document.getElementById('btnAgregarProducto');
    if (btnAgregar) btnAgregar.addEventListener('click', agregarFilaProducto);

    // 2.6 Guardado de Pedido Nuevo
    const formPedido = document.getElementById('formPedido');
    if (formPedido) {
        formPedido.addEventListener('submit', async function (e) {
            e.preventDefault();
            const filas = document.querySelectorAll('.producto-fila');
            let productosData = [];
            let errorSKU = false;

            filas.forEach(fila => {
                const sku = fila.querySelector('.input-sku').value.trim();
                const nombre = fila.querySelector('.input-nombre').value;
                const precio = fila.querySelector('.input-precio').value;
                const piezas = fila.querySelector('.input-piezas').value;
                if (!nombre || !precio) errorSKU = true;
                productosData.push({ sku, nombre, precio: parseFloat(precio), piezas: parseInt(piezas) });
            });

            if (errorSKU) return alert('Hay SKUs no válidos o no encontrados en la memoria.');

            if (typeof window.mostrarCarga === 'function') window.mostrarCarga('Guardando registro...');

            const payload = {
                _token: config.token,
                fecha_salida: document.getElementById('fecha_salida').value,
                numero_pedido: document.getElementById('numero_pedido').value,
                cliente_nombre: document.getElementById('cliente_nombre').value,
                tipo_transaccion: document.getElementById('tipo_transaccion').value,
                platform_id: document.getElementById('platform_id').value,
                venta_total: parseFloat(limpiarMonedaStr(document.getElementById('venta_total').value)) || 0,
                costo_envio: parseFloat(limpiarMonedaStr(document.getElementById('costo_envio').value)) || 0,
                comision_real: document.getElementById('comision_real').value ? parseFloat(limpiarMonedaStr(document.getElementById('comision_real').value)) : null,
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
});