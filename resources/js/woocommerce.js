// ==========================================
// CONTROL DE PESTAÑAS (UI)
// ==========================================
window.cambiarPestanaWoo = function(pestana) {
    const tabDiario = document.getElementById('tab-diario');
    const tabSync = document.getElementById('tab-sync');
    const formDiario = document.getElementById('form-diario');
    const formSync = document.getElementById('form-sync');

    if (pestana === 'diario') {
        tabDiario.className = 'flex-1 py-2 text-sm font-bold rounded-lg bg-purple-600 text-white transition-all';
        tabSync.className = 'flex-1 py-2 text-sm font-bold rounded-lg text-gray-400 hover:text-white transition-all';
        formDiario.classList.remove('hidden');
        formSync.classList.add('hidden');
    } else {
        tabSync.className = 'flex-1 py-2 text-sm font-bold rounded-lg bg-blue-600 text-white transition-all';
        tabDiario.className = 'flex-1 py-2 text-sm font-bold rounded-lg text-gray-400 hover:text-white transition-all';
        formSync.classList.remove('hidden');
        formDiario.classList.add('hidden');
    }
};

// ==========================================
// EVENTOS PRINCIPALES DE FORMULARIOS
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    
    const token = document.querySelector('input[name="_token"]').value;

    // 1. FORMULARIO DE PROCESO DIARIO (Cruce de Precios)
    const formDiario = document.getElementById('form-diario');
    if (formDiario) {
        formDiario.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileAromas = document.getElementById('file-listado-aromas');
            if (!fileAromas?.files.length) {
                window.mostrarToast('Por favor, anexa el listado de Wizerp (Excel).', 'red');
                return;
            }

            window.mostrarCarga('Ejecutando algoritmo de precios...');
            try {
                const response = await fetch('/woocommerce/procesar', {
                    method: 'POST',
                    body: new FormData(formDiario),
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Error en el cálculo.');

                window.mostrarToast('Precios calculados con éxito.', 'green');
                const a = document.createElement('a');
                a.href = data.download_url;
                a.click();
                setTimeout(() => window.location.reload(), 1500);
            } catch (error) {
                window.mostrarToast(error.message, 'red');
            } finally {
                window.ocultarCarga();
            }
        });
    }

    // 2. FORMULARIO DE SINCRONIZACIÓN (Catálogo WooCommerce)
    const formSync = document.getElementById('form-sync');
    if (formSync) {
        formSync.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileWoo = document.getElementById('file-woocommerce-csv');
            if (!fileWoo?.files.length) {
                window.mostrarToast('Sube el CSV exportado de WooCommerce.', 'red');
                return;
            }

            window.mostrarCarga('Sincronizando Base de Datos...');
            try {
                const response = await fetch('/woocommerce/productos/sincronizar', {
                    method: 'POST',
                    body: new FormData(formSync),
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Error de sincronización.');

                window.mostrarToast(data.message, 'green');
                formSync.reset();
            } catch (error) {
                window.mostrarToast(error.message, 'red');
            } finally {
                window.ocultarCarga();
            }
        });
    }
});

// ==========================================
// SEGURIDAD Y CONFIGURACIÓN (MODALES)
// ==========================================
window.abrirModalPin = () => {
    document.getElementById('input-pin').value = '';
    document.getElementById('modal-pin').classList.remove('hidden');
    setTimeout(() => document.getElementById('input-pin').focus(), 100);
};

window.cerrarModalPin = () => document.getElementById('modal-pin').classList.add('hidden');
window.cerrarModalConfig = () => document.getElementById('modal-config').classList.add('hidden');

window.verificarPin = async () => {
    const pin = document.getElementById('input-pin').value;
    if (!pin) return;

    window.mostrarCarga('Verificando acceso...');
    try {
        const response = await fetch('/woocommerce/verificar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ pin })
        });

        const data = await response.json();
        window.ocultarCarga();

        if (response.ok && data.success) {
            cerrarModalPin();
            document.getElementById('modal-config').classList.remove('hidden');
        } else {
            window.mostrarToast(data.message || 'PIN Incorrecto', 'red');
            document.getElementById('input-pin').value = '';
        }
    } catch (error) {
        window.ocultarCarga();
        window.mostrarToast('Error de conexión', 'red');
    }
};

window.guardarConfiguracion = async () => {
    const form = document.getElementById('form-config');
    window.mostrarCarga('Actualizando algoritmo...');
    
    try {
        const response = await fetch('/woocommerce/configuracion/guardar', {
            method: 'POST',
            body: new FormData(form),
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'Error al guardar.');

        window.mostrarToast(data.message, 'green');
        cerrarModalConfig();
        // Recargamos para que los nuevos valores se reflejen si intentan generar un reporte sin salir de la vista
        setTimeout(() => window.location.reload(), 1000); 
    } catch (error) {
        window.mostrarToast(error.message, 'red');
    } finally {
        window.ocultarCarga();
    }
};

// ==========================================
// ELIMINACIÓN DE ARCHIVOS
// ==========================================
window.eliminarTemplateWoo = async function(id) {
    if(!confirm('¿Estás seguro de eliminar este archivo permanentemente?')) return;
    
    window.mostrarCarga('Eliminando...');
    try {
        const response = await fetch(`/woocommerce/eliminar/${id}`, {
            method: 'DELETE',
            headers: { 
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) throw new Error('Error al eliminar del servidor.');
        
        window.mostrarToast('Archivo eliminado', 'green');
        setTimeout(() => window.location.reload(), 800);
    } catch(error) {
        window.ocultarCarga();
        window.mostrarToast(error.message, 'red');
    }
}