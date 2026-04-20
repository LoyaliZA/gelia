/*
 * Módulo: Interfaz y Modales (GELIA Contabilidad)
 * Descripción: Maneja la experiencia de usuario, interacciones del DOM, ordenamiento y modales.
 */

// ==========================================
// 1. FUNCIONES GLOBALES DE APERTURA DE MODALES
// ==========================================

window.verDetallesPedido = function (numPedido, detalles) {
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

window.abrirModalEdicion = function (id, numPedido, tipo, platformId, venta, envio, comision, clienteNombre, detalles, envioPagadoCliente) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_num_pedido').innerText = numPedido;

    const inputCliente = document.getElementById('edit_cliente');
    if (inputCliente) inputCliente.value = clienteNombre !== 'null' ? clienteNombre : '';

    let tipoNormalizado = 'venta';
    if (tipo.includes('reembolso')) tipoNormalizado = 'reembolso';
    if (tipo.includes('contracargo')) tipoNormalizado = 'contracargo';

    document.getElementById('edit_tipo').value = tipoNormalizado;
    document.getElementById('edit_plataforma').value = platformId;
    document.getElementById('edit_venta').value = parseFloat(venta).toFixed(2);
    document.getElementById('edit_envio').value = parseFloat(envio).toFixed(2);
    document.getElementById('edit_comision').value = parseFloat(comision).toFixed(2);

    const checkEnvio = document.getElementById('edit_envio_pagado_cliente');
    if (checkEnvio) {
        checkEnvio.checked = (envioPagadoCliente === true || envioPagadoCliente === 1 || envioPagadoCliente === '1');
    }

    const containerProductos = document.getElementById('edit_productos_container');
    containerProductos.innerHTML = '';

    if (detalles && detalles.length > 0) {
        detalles.forEach(prod => {
            containerProductos.innerHTML += `
                <div class="flex items-center justify-between bg-dark-900 p-2 rounded border border-dark-700 edit-producto-item">
                    <input type="hidden" class="edit-prod-id" value="${prod.id}">
                    <div class="truncate max-w-[200px]">
                        <p class="text-xs text-white font-bold">${prod.sku}</p>
                        <p class="text-[10px] text-dark-muted truncate" title="${prod.nombre_producto}">${prod.nombre_producto}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-green-400">$${parseFloat(prod.precio_unitario).toFixed(2)} c/u</span>
                        <input type="number" min="1" class="edit-prod-piezas w-16 bg-dark-800 border border-dark-600 rounded px-2 py-1 text-white text-sm text-center outline-none focus:border-bella-main" value="${prod.piezas}">
                    </div>
                </div>
            `;
        });
    } else {
        containerProductos.innerHTML = '<p class="text-xs text-dark-muted italic">Sin productos en este registro.</p>';
    }

    document.getElementById('modalEditar').showModal();
};

window.abrirModalConfirmarRetiro = function (id, numPedido, montoEsperado) {
    document.getElementById('conf_id').value = id;
    document.getElementById('conf_num_pedido').innerText = numPedido;
    document.getElementById('conf_esperado').innerText = '$' + parseFloat(montoEsperado).toFixed(2);
    document.getElementById('conf_monto').value = parseFloat(montoEsperado).toFixed(2);

    document.getElementById('modalConfirmarRetiro').showModal();
};

// ==========================================
// 2. INICIALIZACIÓN DE EVENTOS UI
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    
    // 2.1 Sanitización de campos de moneda
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

    // 2.2 Control de Paneles Superiores
    const btnToggleMasivo = document.getElementById('btnToggleMasivo');
    const panelCargaMasiva = document.getElementById('panelCargaMasiva');
    if (btnToggleMasivo && panelCargaMasiva) {
        btnToggleMasivo.addEventListener('click', () => panelCargaMasiva.classList.toggle('hidden'));
    }

    const btnAbrirDashboard = document.getElementById('btnAbrirDashboard');
    const modalDashboard = document.getElementById('modalDashboard');
    const btnCerrarDashboard = document.getElementById('btnCerrarDashboard');

    if (btnAbrirDashboard && modalDashboard) {
        btnAbrirDashboard.addEventListener('click', function () {
            modalDashboard.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    }

    if (btnCerrarDashboard && modalDashboard) {
        btnCerrarDashboard.addEventListener('click', function () {
            modalDashboard.classList.add('hidden');
            document.body.style.overflow = '';
        });
    }

    // 2.3 Buscador en Tiempo Real de la Tabla
    const inputBuscador = document.getElementById('inputBuscador');
    const tbodyPedidos = document.querySelector('#tablaPedidos tbody');

    if (inputBuscador && tbodyPedidos) {
        inputBuscador.addEventListener('input', function () {
            const termino = this.value.toLowerCase().trim();
            document.querySelectorAll('.registro-fila').forEach(fila => {
                const numPedido = (fila.getAttribute('data-pedido') || '').toLowerCase();
                const cliente = (fila.getAttribute('data-cliente') || '').toLowerCase();
                const tipo = (fila.getAttribute('data-tipo') || '').toLowerCase();

                if (numPedido.includes(termino) || cliente.includes(termino) || tipo.includes(termino)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });
    }

    // 2.4 Ordenamiento Dinámico de Columnas
    document.querySelectorAll('.th-sort').forEach(header => {
        header.addEventListener('click', () => {
            const criterio = header.getAttribute('data-sort');
            const filas = Array.from(document.querySelectorAll('.registro-fila'));
            const esAsc = header.classList.contains('asc');

            document.querySelectorAll('.th-sort span').forEach(s => { if (s.innerText !== 'info') s.innerText = 'unfold_more'; });
            const icon = header.querySelector('span');
            if (icon && icon.innerText !== 'info') icon.innerText = esAsc ? 'expand_more' : 'expand_less';

            filas.sort((a, b) => {
                let valA = a.getAttribute(`data-${criterio}`);
                let valB = b.getAttribute(`data-${criterio}`);

                if (['venta', 'preretiro', 'neta'].includes(criterio)) {
                    return esAsc ? parseFloat(valA) - parseFloat(valB) : parseFloat(valB) - parseFloat(valA);
                }
                return esAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
            });

            header.classList.toggle('asc', !esAsc);
            filas.forEach(fila => tbodyPedidos.appendChild(fila));
        });
    });

    // 2.5 Lógica de Menús Flotantes (Acciones)
    document.addEventListener('click', function(e) {
        const isMenuBtn = e.target.closest('.btn-action-menu');
        const isDropdown = e.target.closest('.action-dropdown');
        
        if (!isMenuBtn && !isDropdown) {
            document.querySelectorAll('.action-dropdown').forEach(menu => {
                menu.classList.add('hidden');
            });
        }

        if (isMenuBtn) {
            e.preventDefault();
            e.stopPropagation();

            const dropdown = isMenuBtn.nextElementSibling;
            const isHidden = dropdown.classList.contains('hidden');

            document.querySelectorAll('.action-dropdown').forEach(m => m.classList.add('hidden'));

            if (isHidden) {
                const rect = isMenuBtn.getBoundingClientRect();
                dropdown.classList.remove('hidden');
                
                const dropdownHeight = dropdown.offsetHeight;
                const dropdownWidth = 192;
                
                let topPos = rect.top;
                let leftPos = rect.left - dropdownWidth - 8;

                if (topPos + dropdownHeight > window.innerHeight) {
                    topPos = rect.bottom - dropdownHeight;
                }

                dropdown.style.top = `${topPos}px`;
                dropdown.style.left = `${leftPos}px`;
            }
        }
    });

    window.addEventListener('scroll', function() {
        document.querySelectorAll('.action-dropdown:not(.hidden)').forEach(menu => {
            menu.classList.add('hidden');
        });
    }, true);
});