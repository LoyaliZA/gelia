import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    // =========================================================
    // DRAG AND DROP UNIVERSAL (Funciona en todos los módulos)
    // =========================================================
    const dropZones = document.querySelectorAll('.drop-zone');
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            // Efecto visual genérico y elegante al arrastrar
            zone.classList.add('scale-[1.02]', 'brightness-125', 'border-solid');
            zone.classList.remove('border-dashed');
        });
        
        zone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            zone.classList.remove('scale-[1.02]', 'brightness-125', 'border-solid');
            zone.classList.add('border-dashed');
        });
        
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('scale-[1.02]', 'brightness-125', 'border-solid');
            zone.classList.add('border-dashed');
            
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const input = zone.querySelector('input[type="file"]');
                if (input) {
                    input.files = e.dataTransfer.files;
                    // Actualizamos el texto visual de nuestro componente x-upload-area
                    const label = zone.querySelector(`[id^="label-"]`);
                    if (label) label.textContent = input.files[0].name;
                    
                    // Disparamos evento para que aromas.js o bellaroma.js ejecuten su lógica específica
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });

        // Escuchar el cambio manual (por clic)
        const input = zone.querySelector('input[type="file"]');
        if (input) {
            input.addEventListener('change', () => {
                if (input.files.length > 0) {
                    const label = zone.querySelector(`[id^="label-"]`);
                    if (label) label.textContent = input.files[0].name;
                }
            });
        }
    });
});

// =========================================================
// FUNCIONES GLOBALES DE UI (Disponibles en todo el Layout)
// =========================================================
window.mostrarCarga = function (m) { 
    document.getElementById('overlay-carga').classList.remove('hidden'); 
    document.getElementById('texto-carga').innerText = m; 
}

window.ocultarCarga = function () { 
    document.getElementById('overlay-carga').classList.add('hidden'); 
}

window.mostrarToast = function (m, c) {
    const t = document.getElementById('toast');
    const tm = document.getElementById('toast-msg');
    t.className = `fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold flex items-center transform transition-all duration-300 ${c === 'red' ? 'bg-red-600' : 'bg-emerald-600'}`;
    tm.innerText = m;
    t.classList.remove('hidden', 'toast-enter'); 
    t.classList.add('toast-enter-active');
    setTimeout(() => { t.classList.add('hidden'); }, 4000);
}

window.mostrarError = function (h) { 
    const alertas = document.getElementById('alertas');
    if (alertas) {
        alertas.innerHTML = `<div class="bg-red-900/50 border border-red-500 text-red-200 p-4 rounded-lg mb-6 shadow-md">${h}</div>`; 
    }
}