/*
 * Módulo: Control de Retiros Inteligentes (GELIA)
 * Descripción: Maneja pestañas, cálculos, búsqueda y Drag & Drop nativo HTML5.
 */

document.addEventListener('DOMContentLoaded', function() {
    let platActivaId = document.querySelector('.tab-content:not(.hidden)')?.dataset.platId;
    let montoEsperadoActual = 0;

    // ==========================================
    // 1. SISTEMA DE PESTAÑAS (TABS)
    // ==========================================
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => {
                b.style.backgroundColor = 'transparent';
                b.style.borderColor = '#334155';
                b.style.color = '#94a3b8';
                b.classList.replace('activo', 'inactivo');
            });
            tabContents.forEach(c => c.classList.add('hidden'));

            const color = btn.dataset.color;
            btn.style.backgroundColor = color + '15';
            btn.style.borderColor = color;
            btn.style.color = color;
            btn.classList.replace('inactivo', 'activo');
            
            const content = document.getElementById(btn.dataset.target);
            content.classList.remove('hidden');
            
            platActivaId = content.dataset.platId;
            document.getElementById('buscadorRetiros').value = '';
            filtrarTabla('');
            calcularResumen();
        });
    });

    // ==========================================
    // 2. BUSCADOR EN TIEMPO REAL
    // ==========================================
    const buscador = document.getElementById('buscadorRetiros');
    if (buscador) {
        buscador.addEventListener('input', function() {
            filtrarTabla(this.value.toLowerCase().trim());
        });
    }

    function filtrarTabla(query) {
        const contentActivo = document.getElementById(`tab-${platActivaId}`);
        if(!contentActivo) return;

        contentActivo.querySelectorAll('.row-draggable').forEach(row => {
            const texto = row.dataset.filtro || '';
            row.style.display = texto.includes(query) ? '' : 'none';
        });
    }

    // ==========================================
    // 3. CHECKBOXES Y BOTÓN MÁGICO (CARGAR GRUPO)
    // ==========================================
    document.querySelectorAll('.check-grupo-all').forEach(checkGroup => {
        checkGroup.addEventListener('change', function() {
            const tbody = this.closest('table').querySelector('tbody');
            tbody.querySelectorAll('.check-pedido').forEach(chk => {
                if(chk.closest('tr').style.display !== 'none') chk.checked = this.checked;
            });
            calcularResumen();
        });
    });

    document.querySelectorAll('.btn-cargar-grupo').forEach(btn => {
        btn.addEventListener('click', function() {
            const content = document.getElementById(`tab-${platActivaId}`);
            content.querySelectorAll('.check-pedido, .check-grupo-all').forEach(c => c.checked = false);
            
            const grupoContainer = this.closest('.grupo-lote');
            grupoContainer.querySelectorAll('.check-pedido').forEach(chk => {
                if(chk.closest('tr').style.display !== 'none') chk.checked = true;
            });
            grupoContainer.querySelector('.check-grupo-all').checked = true;
            
            calcularResumen();
            
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="material-symbols-outlined text-[14px] mr-1">done_all</span> Cargado al Panel';
            setTimeout(() => this.innerHTML = originalText, 1500);
        });
    });

    document.querySelectorAll('.check-pedido').forEach(chk => {
        chk.addEventListener('change', calcularResumen);
    });

    // ==========================================
    // 4. DRAG & DROP CON AUTO-SCROLL
    // ==========================================
    let draggedRow = null;
    const scrollContainer = document.getElementById('contenedorTablasScroll');
    let scrollInterval = null;

    document.addEventListener('dragstart', e => {
        if(e.target.classList.contains('row-draggable')) {
            draggedRow = e.target;
            e.target.classList.add('opacity-50', 'bg-dark-700');
            e.dataTransfer.effectAllowed = 'move';
        }
    });

    document.addEventListener('dragend', e => {
        if(draggedRow) {
            draggedRow.classList.remove('opacity-50', 'bg-dark-700');
            draggedRow = null;
        }
        clearInterval(scrollInterval);
        scrollInterval = null;
        document.querySelectorAll('.dropzone-tbody').forEach(tb => tb.classList.remove('ring-2', 'ring-bella-main', 'bg-dark-800/50'));
    });

    // Configurar los Tbodies como zonas de caída
    document.querySelectorAll('.dropzone-tbody').forEach(tbody => {
        tbody.addEventListener('dragover', e => {
            e.preventDefault(); // Permitir el drop
            e.dataTransfer.dropEffect = 'move';
            tbody.classList.add('ring-2', 'ring-bella-main', 'bg-dark-800/50');
        });

        tbody.addEventListener('dragleave', e => {
            tbody.classList.remove('ring-2', 'ring-bella-main', 'bg-dark-800/50');
        });

        tbody.addEventListener('drop', e => {
            e.preventDefault();
            tbody.classList.remove('ring-2', 'ring-bella-main', 'bg-dark-800/50');
            
            if(draggedRow && draggedRow.parentNode !== tbody) {
                // Mover el elemento en el DOM
                tbody.appendChild(draggedRow);
                
                // Actualizar contadores visuales de los grupos afectados
                actualizarContadoresGrupos();
                calcularResumen();
            }
        });
    });

    // Lógica de Auto-scroll al arrastrar cerca de los bordes del contenedor
    scrollContainer.addEventListener('dragover', e => {
        const threshold = 60; // Píxeles desde el borde para activar scroll
        const speed = 15;     // Velocidad de scroll
        const rect = scrollContainer.getBoundingClientRect();

        clearInterval(scrollInterval);
        
        if (e.clientY - rect.top < threshold) {
            scrollInterval = setInterval(() => scrollContainer.scrollTop -= speed, 20);
        } else if (rect.bottom - e.clientY < threshold) {
            scrollInterval = setInterval(() => scrollContainer.scrollTop += speed, 20);
        }
    });

    function actualizarContadoresGrupos() {
        document.querySelectorAll('.grupo-lote').forEach(grupo => {
            const count = grupo.querySelectorAll('.row-draggable').length;
            grupo.querySelector('.contador-grupo').innerText = `${count} operaciones detectadas.`;
            if (count === 0) grupo.style.display = 'none'; // Ocultar si quedó vacío
            else grupo.style.display = 'block';
        });
    }

    // ==========================================
    // 5. CÁLCULOS DEL PANEL LATERAL (INPUTS INDIVIDUALES)
    // ==========================================
    const contenedorDesglose = document.getElementById('lista_pedidos_confirmar');
    const resumenTotalReal = document.getElementById('resumen_total_real');

    function calcularResumen() {
        const tablaActiva = document.getElementById(`tab-${platActivaId}`);
        if(!tablaActiva) return;

        let seleccionados = 0;
        let sumEsperado = 0;
        let htmlDesglose = '';

        tablaActiva.querySelectorAll('.check-pedido:checked').forEach(chk => {
            if(chk.closest('tr').style.display !== 'none') {
                seleccionados++;
                const esperado = parseFloat(chk.dataset.esperado);
                const numPedido = chk.dataset.numpedido;
                sumEsperado += esperado;

                // Generar tarjeta individual
                htmlDesglose += `
                    <div class="bg-dark-900 border border-dark-600 rounded p-2 flex justify-between items-center item-confirmacion">
                        <input type="hidden" class="conf-id" value="${chk.value}">
                        <div>
                            <span class="text-white font-bold text-sm">#${numPedido}</span><br>
                            <span class="text-[10px] text-dark-muted">Esp: $${esperado.toFixed(2)}</span>
                        </div>
                        <div class="w-28 relative">
                            <span class="absolute left-2 top-1.5 text-dark-muted text-xs font-bold">$</span>
                            <input type="number" step="0.01" class="conf-monto w-full bg-dark-800 border border-dark-600 rounded pl-5 pr-2 py-1.5 text-white text-sm outline-none focus:border-green-500 text-right font-bold transition-colors" value="${esperado.toFixed(2)}" required>
                        </div>
                    </div>
                `;
            }
        });

        document.getElementById('resumen_cantidad').innerText = seleccionados;
        document.getElementById('resumen_esperado').innerText = '$' + sumEsperado.toLocaleString('es-MX', {minimumFractionDigits:2});
        
        if (seleccionados > 0) {
            contenedorDesglose.innerHTML = htmlDesglose;
            document.getElementById('btnProcesarLote').disabled = false;
        } else {
            contenedorDesglose.innerHTML = '<p class="text-xs text-dark-muted text-center italic py-4">Selecciona pedidos para desglosarlos aquí.</p>';
            document.getElementById('btnProcesarLote').disabled = true;
            resumenTotalReal.innerText = '$0.00';
            return;
        }

        // Añadir listeners a los nuevos inputs para recalcular el TOTAL REAL en vivo
        contenedorDesglose.querySelectorAll('.conf-monto').forEach(input => {
            input.addEventListener('input', recalcularTotalSumaReal);
        });

        recalcularTotalSumaReal();
    }

    function recalcularTotalSumaReal() {
        let sumaReal = 0;
        contenedorDesglose.querySelectorAll('.conf-monto').forEach(input => {
            sumaReal += parseFloat(input.value) || 0;
        });
        resumenTotalReal.innerText = '$' + sumaReal.toLocaleString('es-MX', {minimumFractionDigits:2});
    }

    // ==========================================
    // 6. ENVIAR A BACKEND (NUEVO PAYLOAD ARRAY)
    // ==========================================
    document.getElementById('formConfirmarLote').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Recopilar el array de objetos individuales
        let pedidosPayload = [];
        contenedorDesglose.querySelectorAll('.item-confirmacion').forEach(item => {
            pedidosPayload.push({
                id: item.querySelector('.conf-id').value,
                monto_real: item.querySelector('.conf-monto').value
            });
        });

        if (pedidosPayload.length === 0) return alert('No hay pedidos para procesar.');

        const btn = document.getElementById('btnProcesarLote');
        btn.disabled = true; btn.innerHTML = '<span class="material-symbols-outlined animate-spin mr-2">sync</span> Procesando...';

        try {
            const res = await fetch(window.RetirosConfig.rutas.confirmarLote, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.RetirosConfig.token },
                body: JSON.stringify({
                    platform_id: platActivaId,
                    pedidos: pedidosPayload, // Mandamos el array detallado
                    fecha_deposito: document.getElementById('input_fecha_banco').value
                })
            }).then(r => r.json());
            
            if(res.success) window.location.reload();
            else throw new Error(res.message);
        } catch(err) {
            alert('Error: ' + err.message);
            btn.disabled = false; btn.innerHTML = '<span class="material-symbols-outlined mr-2">task_alt</span> Aprobar Retiro';
        }
    });

    calcularResumen();
});