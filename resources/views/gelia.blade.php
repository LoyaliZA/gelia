<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GELIA v2.0 | Sistema de Listas</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 900: '#0d1117', 800: '#161b22', 700: '#21262d', text: '#c9d1d9' }
                    }
                }
            }
        }
    </script>

    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0d1117; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #58a6ff; }
        
        /* Animación de Toast */
        .toast-enter { transform: translateY(-20px); opacity: 0; }
        .toast-enter-active { transform: translateY(0); opacity: 1; transition: all 0.3s ease-out; }
    </style>

    <script>
        let ordenSeleccionado = [];

        function actualizarOrden(checkbox) {
            const valor = checkbox.value;
            const badge = document.getElementById('badge-' + valor);

            if (checkbox.checked) {
                ordenSeleccionado.push(valor);
                badge.innerText = ordenSeleccionado.length;
                badge.classList.remove('hidden');
            } else {
                ordenSeleccionado = ordenSeleccionado.filter(item => item !== valor);
                badge.classList.add('hidden');
                ordenSeleccionado.forEach((item, index) => {
                    document.getElementById('badge-' + item).innerText = index + 1;
                });
            }
        }

        // --- FUNCIÓN AJAX PRINCIPAL ---
        async function procesarSolicitud(tipo) {
            // 1. Definir columnas según el tipo
            let columnas = [];
            let nombreTipo = "";

            if (tipo === 'resurtido') {
                columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Plataformas', 'Lista3'];
                nombreTipo = "Lista de Resurtido";
            } else if (tipo === 'costos') {
                columnas = ['Almacen', 'SKU', 'Descripcion', 'Existencia', 'CostoWizerp'];
                nombreTipo = "Lista de Costos";
            } else if (tipo === 'actualizada') {
                columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'CostoCalculado', 'Plataformas'];
                nombreTipo = "Lista Actualizada";
            } else if (tipo === 'inventario') {
                // TUS NUEVOS CAMPOS
                columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Lista3'];
                nombreTipo = "Lista de Inventario";
            } else {
                // Manual
                columnas = ordenSeleccionado;
                nombreTipo = "Lista Personalizada";
            }

            // Validar que haya columnas
            if (columnas.length === 0) {
                mostrarToast("❌ Error: Selecciona columnas o un botón rápido.", "red");
                return;
            }

            // 2. Preparar datos (FormData)
            const form = document.getElementById('form-principal');
            const formData = new FormData(form);
            
            formData.append('orden_final', columnas.join(','));
            formData.append('tipo_lista', tipo); // Enviamos el tipo para el nombre del archivo

            // 3. UI: Mostrar carga
            mostrarCarga(`Generando: ${nombreTipo}...`);
            document.getElementById('alertas').innerHTML = ''; // Limpiar errores previos

            try {
                // 4. FETCH (Envío sin recarga)
                const response = await fetch("{{ route('gelia.generar') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json' // Para que Laravel devuelva JSON si falla validación
                    }
                });

                if (!response.ok) {
                    // Si hay error (ej. validación 422)
                    const data = await response.json();
                    if (data.errors) {
                        let html = `<ul class='list-disc ml-5'>`;
                        Object.values(data.errors).forEach(err => html += `<li>${err}</li>`);
                        html += `</ul>`;
                        mostrarError(html);
                    } else {
                        throw new Error(data.error || 'Error en el servidor');
                    }
                    ocultarCarga();
                    return;
                }

                // 5. ÉXITO: Descargar el archivo BLOB
                const blob = await response.blob();
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = downloadUrl;
                
                // Intentar sacar el nombre del archivo del header o usar default
                const contentDisposition = response.headers.get('Content-Disposition');
                let fileName = `${nombreTipo}.xlsx`;
                if (contentDisposition) {
                    const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
                    if (fileNameMatch.length === 2) fileName = fileNameMatch[1];
                }
                
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                
                // 6. UI: Listo
                ocultarCarga();
                mostrarToast("✅ ¡Archivo Generado Exitosamente!", "green");

            } catch (error) {
                console.error(error);
                ocultarCarga();
                mostrarToast("❌ Error crítico: " + error.message, "red");
            }
        }

        function mostrarCarga(mensaje) {
            document.getElementById('overlay-carga').classList.remove('hidden');
            document.getElementById('texto-carga').innerText = mensaje;
        }

        function ocultarCarga() {
            document.getElementById('overlay-carga').classList.add('hidden');
        }

        function mostrarToast(mensaje, color) {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-msg');
            
            toast.className = `fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold flex items-center transform transition-all duration-300 ${color === 'red' ? 'bg-red-600' : 'bg-emerald-600'}`;
            toastMsg.innerText = mensaje;
            
            toast.classList.remove('hidden', 'toast-enter');
            toast.classList.add('toast-enter-active');

            setTimeout(() => {
                toast.classList.add('hidden');
            }, 4000);
        }

        function mostrarError(html) {
            const div = document.getElementById('alertas');
            div.innerHTML = `<div class="bg-red-900/50 border border-red-500 text-red-200 p-4 rounded-lg mb-6">${html}</div>`;
        }
    </script>
</head>
<body class="bg-dark-900 text-dark-text min-h-screen font-sans selection:bg-blue-500 selection:text-white pb-20">

    <div id="toast" class="hidden fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold bg-emerald-600 transition-all">
        <span id="toast-msg">Mensaje</span>
    </div>

    <div id="overlay-carga" class="hidden fixed inset-0 bg-dark-900/90 z-40 flex flex-col justify-center items-center backdrop-blur-sm">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-500 mb-4"></div>
        <h2 id="texto-carga" class="text-xl font-bold text-white tracking-widest animate-pulse">Procesando...</h2>
    </div>

    <div class="max-w-6xl mx-auto p-6">
        
        <header class="flex items-center justify-between mb-6 border-b border-dark-700 pb-6">
            <div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">GELIA</span>
                    <span class="text-lg text-gray-500 font-normal ml-2">v2.0</span>
                </h1>
                <p class="text-gray-400 mt-1 text-sm">Sistema Generador de Listas Inteligente y Automatizado</p>
            </div>
        </header>

        <div id="alertas"></div>

        <form id="form-principal" enctype="multipart/form-data">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                    <h3 class="font-bold text-lg text-blue-400 mb-4">1. Existencias</h3>
                    <input type="file" name="existencias" class="block w-full text-xs text-gray-400 file:bg-blue-600 file:text-white file:rounded-full file:px-4 file:py-2 file:border-0 hover:file:bg-blue-500 cursor-pointer">
                </div>
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                    <h3 class="font-bold text-lg text-emerald-400 mb-4">2. Precios</h3>
                    <input type="file" name="precios" class="block w-full text-xs text-gray-400 file:bg-emerald-600 file:text-white file:rounded-full file:px-4 file:py-2 file:border-0 hover:file:bg-emerald-500 cursor-pointer">
                </div>
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-6">
                    <h3 class="font-bold text-lg text-purple-400 mb-4">3. Costos</h3>
                    <input type="file" name="costos" class="block w-full text-xs text-gray-400 file:bg-purple-600 file:text-white file:rounded-full file:px-4 file:py-2 file:border-0 hover:file:bg-purple-500 cursor-pointer">
                </div>
            </div>

            <div class="mb-10">
                <h2 class="text-xl font-bold text-white mb-4">🚀 Generación Rápida</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    
                    <button type="button" onclick="procesarSolicitud('resurtido')" class="p-4 bg-dark-800 border border-dark-700 hover:border-blue-500 hover:bg-dark-700 rounded-xl text-left group transition">
                        <span class="block text-blue-400 font-bold mb-1 group-hover:text-blue-300">Lista Resurtido</span>
                        <span class="block text-xs text-gray-500">LISTA DE RESURTIDO...</span>
                    </button>

                    <button type="button" onclick="procesarSolicitud('costos')" class="p-4 bg-dark-800 border border-dark-700 hover:border-purple-500 hover:bg-dark-700 rounded-xl text-left group transition">
                        <span class="block text-purple-400 font-bold mb-1 group-hover:text-purple-300">Lista de Costos</span>
                        <span class="block text-xs text-gray-500">LISTA DE COSTOS...</span>
                    </button>

                    <button type="button" onclick="procesarSolicitud('actualizada')" class="p-4 bg-dark-800 border border-dark-700 hover:border-orange-500 hover:bg-dark-700 rounded-xl text-left group transition">
                        <span class="block text-orange-400 font-bold mb-1 group-hover:text-orange-300">Lista Actualizada</span>
                        <span class="block text-xs text-gray-500">LISTA ACTUALIZADA...</span>
                    </button>

                    <button type="button" onclick="procesarSolicitud('inventario')" class="p-4 bg-dark-800 border border-dark-700 hover:border-teal-500 hover:bg-dark-700 rounded-xl text-left group transition">
                        <span class="block text-teal-400 font-bold mb-1 group-hover:text-teal-300">Inv. Bellaroma</span>
                        <span class="block text-xs text-gray-500">Folio, SKU, Exist, PG...</span>
                    </button>

                </div>
            </div>

            <div class="border-t border-dark-700 pt-8">
                <h2 class="text-lg font-bold text-gray-300 mb-4">🛠️ O Personaliza tus columnas</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-8">
                    @foreach(['SKU', 'Descripcion', 'Marca', 'Existencia', 'CostoWizerp' => 'Costo (W)', 'CostoCalculado' => 'Costo (C)', 'PG', 'Plataformas', 'Lista3', 'Lista4', 'Almacen', 'Folio'] as $key => $label)
                        @php $val = is_string($key)?$key:$label; $txt = is_string($key)?$label:$label; @endphp
                        <label class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 cursor-pointer hover:bg-dark-700 select-none">
                            <input type="checkbox" value="{{ $val }}" onchange="actualizarOrden(this)" class="w-4 h-4 text-blue-600 rounded bg-dark-900 border-dark-700">
                            <span class="text-sm font-medium text-gray-300">{{ $txt }}</span>
                            <span id="badge-{{ $val }}" class="hidden absolute -top-2 -right-2 bg-blue-600 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
                        </label>
                    @endforeach
                </div>
                <button type="button" onclick="procesarSolicitud('manual')" class="w-full bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 text-white font-bold py-4 rounded-xl border border-dark-700 shadow-lg">
                    Generar Manualmente
                </button>
            </div>
        </form>
    </div>
</body>
</html>