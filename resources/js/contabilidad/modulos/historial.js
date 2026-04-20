/*
 * Módulo: Auditoría e Historial (GELIA)
 * Descripción: Maneja la lógica de edición de emergencia y visualización de lotes.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Función para abrir el modal y precargar datos
    window.abrirModalEmergencia = function(id, num, venta, montoBanco) {
        document.getElementById('em_id').value = id;
        document.getElementById('em_pedido').innerText = num;
        document.getElementById('em_venta').value = parseFloat(venta).toFixed(2);
        document.getElementById('em_monto_banco').value = parseFloat(montoBanco).toFixed(2);
        
        // Limpiar campos de seguridad cada vez que se abre
        document.getElementById('em_motivo').value = '';
        document.getElementById('em_pass').value = '';
        
        document.getElementById('modalEmergencia').showModal();
    };

    // 2. Procesar la actualización de emergencia
    const formEmergencia = document.getElementById('formEmergencia');
    if (formEmergencia) {
        formEmergencia.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const id = document.getElementById('em_id').value;
            const btn = this.querySelector('button[type="submit"]');

            if(!confirm('ATENCIÓN: Esta acción alterará registros contables autorizados y dejará una huella permanente en el log de auditoría. ¿Confirmas la autorización?')) return;

            btn.disabled = true;
            btn.innerText = 'Autorizando...';

            const payload = {
                venta_total: document.getElementById('em_venta').value,
                monto_real_banco: document.getElementById('em_monto_banco').value,
                motivo_cambio: document.getElementById('em_motivo').value,
                password_emergencia: document.getElementById('em_pass').value,
                _token: window.ContabilidadConfig.token
            };

            try {
                const response = await fetch(`/contabilidad/edicion-emergencia/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const res = await response.json();
                
                if(res.success) {
                    window.location.reload();
                } else {
                    alert('ERROR DE SEGURIDAD: ' + res.message);
                    btn.disabled = false;
                    btn.innerText = 'Autorizar y Registrar Cambio';
                }
            } catch(error) {
                console.error(error);
                alert('Error crítico de comunicación con el servidor.');
                btn.disabled = false;
                btn.innerText = 'Autorizar y Registrar Cambio';
            }
        });
    }
});