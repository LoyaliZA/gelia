<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GELIA v2.1 | Sistema de Listas</title>
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
        
        .toast-enter { transform: translateY(-20px); opacity: 0; }
        .toast-enter-active { transform: translateY(0); opacity: 1; transition: all 0.3s ease-out; }
        
        .disabled-option { 
            opacity: 0.3; 
            pointer-events: none; 
            filter: grayscale(100%);
            border-color: #374151;
        }
    </style>
</head>
<body class="bg-dark-900 text-dark-text min-h-screen font-sans selection:bg-blue-500 selection:text-white pb-20" onload="verificarArchivos()">

    <div id="toast" class="hidden fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold bg-emerald-600 transition-all">
        <span id="toast-msg">Mensaje</span>
    </div>

    <div id="overlay-carga" class="hidden fixed inset-0 bg-dark-900/90 z-40 flex flex-col justify-center items-center backdrop-blur-sm">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-500 mb-4"></div>
        <h2 id="texto-carga" class="text-xl font-bold text-white tracking-widest animate-pulse">Procesando...</h2>
    </div>

    <div class="max-w-6xl mx-auto p-6">
        
        <header class="flex items-center justify-between mb-8 border-b border-dark-700 pb-6">
            <div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">GELIA</span>
                    <span class="text-lg text-gray-500 font-normal ml-2">v2.1</span>
                </h1>
                <p class="text-gray-400 mt-1 text-sm font-medium">Sistema Generador de Listas Inteligentes y Automatizadas</p>
            </div>
        </header>

        <div id="alertas"></div>

        <form id="form-principal" enctype="multipart/form-data">
            @csrf
            
            <h2 class="text-xl font-bold text-white mb-4 flex items-center">
                <span class="bg-blue-600 w-2 h-6 rounded mr-2"></span> Zona de Carga
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-5 hover:border-blue-500/50 transition">
                    <h3 class="font-bold text-md text-blue-400 mb-3">1. Existencias *</h3>
                    <input type="file" id="file-existencias" name="existencias" onchange="verificarArchivos()" class="block w-full text-xs text-gray-400 file:bg-blue-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-blue-500 cursor-pointer">
                </div>
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-5 hover:border-emerald-500/50 transition">
                    <h3 class="font-bold text-md text-emerald-400 mb-3">2. Precios</h3>
                    <input type="file" id="file-precios" name="precios" onchange="verificarArchivos()" class="block w-full text-xs text-gray-400 file:bg-emerald-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-emerald-500 cursor-pointer">
                </div>
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-5 hover:border-purple-500/50 transition">
                    <h3 class="font-bold text-md text-purple-400 mb-3">3. Costos</h3>
                    <input type="file" id="file-costos" name="costos" onchange="verificarArchivos()" class="block w-full text-xs text-gray-400 file:bg-purple-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-purple-500 cursor-pointer">
                </div>
                 <div class="bg-dark-800 border border-yellow-700/50 rounded-xl p-5 hover:border-yellow-500 transition relative overflow-hidden">
                    <h3 class="font-bold text-md text-yellow-400 mb-3">4. Clientes CSV</h3>
                    <input type="file" id="file-clientes" name="clientes" class="block w-full text-xs text-gray-400 file:bg-yellow-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-yellow-500 cursor-pointer">
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-10">
                
                <div>
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center">
                        <span class="bg-emerald-600 w-2 h-6 rounded mr-2"></span> Listas de Producto
                    </h2>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" onclick="procesarSolicitud('resurtido')" class="p-4 bg-dark-800 border border-dark-700 hover:border-blue-500 hover:bg-dark-700 rounded-xl text-left group transition">
                            <span class="block text-blue-400 font-bold mb-1 group-hover:text-blue-300">Lista de Resurtido</span>
                            <span class="block text-[10px] text-gray-500 uppercase">Muestra los campos base de Existencias, PG, Plataformas y Lista 3</span>
                        </button>
                        <button type="button" onclick="procesarSolicitud('costos')" class="p-4 bg-dark-800 border border-dark-700 hover:border-purple-500 hover:bg-dark-700 rounded-xl text-left group transition">
                            <span class="block text-purple-400 font-bold mb-1 group-hover:text-purple-300">Lista de Costos</span>
                            <span class="block text-[10px] text-gray-500 uppercase">Muestra los campos base de Existencias y Costos</span>
                        </button>
                        <button type="button" onclick="procesarSolicitud('actualizada')" class="p-4 bg-dark-800 border border-dark-700 hover:border-orange-500 hover:bg-dark-700 rounded-xl text-left group transition">
                            <span class="block text-orange-400 font-bold mb-1 group-hover:text-orange-300">Actualizada</span>
                            <span class="block text-[10px] text-gray-500 uppercase">Muestra los campos base de Existencias, Costo y Plataformas</span>
                        </button>
                        <button type="button" onclick="procesarSolicitud('inventario')" class="p-4 bg-dark-800 border border-dark-700 hover:border-teal-500 hover:bg-dark-700 rounded-xl text-left group transition">
                            <span class="block text-teal-400 font-bold mb-1 group-hover:text-teal-300">Inventario Bellaroma</span>
                            <span class="block text-[10px] text-gray-500 uppercase">Muestra los campos base de Existencias y PG y Lista 3</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center">
                        <span class="bg-yellow-600 w-2 h-6 rounded mr-2"></span> Procesar Lista Clientes
                    </h2>
                    <div class="bg-dark-800 border border-dark-700 p-5 rounded-xl">
                        <p class="text-gray-400 text-sm mb-4">
                            Genera una lista limpia de IDs y Nombres. Limpia y corrige el archivo CSV.
                        </p>
                        <button type="button" onclick="procesarSolicitud('clientes')" class="w-full py-3 bg-yellow-600/20 border border-yellow-600/50 text-yellow-400 hover:bg-yellow-600 hover:text-white rounded-lg font-bold transition flex justify-center items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
                            </svg>
                            Procesar Lista Clientes
                        </button>
                    </div>
                </div>
            </div>

            <div class="border-t border-dark-700 pt-8">
                <h2 class="text-lg font-bold text-gray-300 mb-4">🛠️ Genera una lista personalizada</h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-8">
                    @foreach(['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio'] as $campo)
                        <label id="label-{{ $campo }}" class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 select-none disabled-option transition-all duration-300">
                            <input type="checkbox" id="check-{{ $campo }}" value="{{ $campo }}" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-blue-600 rounded bg-dark-900 border-dark-700">
                            <span class="text-sm font-medium text-gray-300">{{ $campo }}</span>
                            <span id="badge-{{ $campo }}" class="hidden absolute -top-2 -right-2 bg-blue-600 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
                        </label>
                    @endforeach

                    @foreach(['PG', 'Plataformas', 'Lista3', 'Lista4'] as $campo)
                        <label id="label-{{ $campo }}" class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 select-none disabled-option transition-all duration-300">
                            <input type="checkbox" id="check-{{ $campo }}" value="{{ $campo }}" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-blue-600 rounded bg-dark-900 border-dark-700">
                            <span class="text-sm font-medium text-gray-300">{{ $campo }}</span>
                            <span id="badge-{{ $campo }}" class="hidden absolute -top-2 -right-2 bg-blue-600 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
                        </label>
                    @endforeach

                     <label id="label-CostoCalculado" class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 select-none disabled-option transition-all duration-300">
                        <input type="checkbox" id="check-CostoCalculado" value="CostoCalculado" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-blue-600 rounded bg-dark-900 border-dark-700">
                        <span class="text-sm font-medium text-gray-300">Costo (L.Resurtido)</span>
                        <span id="badge-CostoCalculado" class="hidden absolute -top-2 -right-2 bg-blue-600 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
                    </label>

                     <label id="label-CostoWizerp" class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 select-none disabled-option transition-all duration-300">
                        <input type="checkbox" id="check-CostoWizerp" value="CostoWizerp" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-blue-600 rounded bg-dark-900 border-dark-700">
                        <span class="text-sm font-medium text-gray-300">Costo (L.Costos)</span>
                        <span id="badge-CostoWizerp" class="hidden absolute -top-2 -right-2 bg-blue-600 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
                    </label>

                </div>
                <button type="button" onclick="procesarSolicitud('manual')" class="w-full bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 text-white font-bold py-4 rounded-xl border border-dark-700 shadow-lg">
                    Generar Manualmente
                </button>
            </div>
        </form>
    </div>

    <script>
        let ordenSeleccionado = [];

        const camposPorArchivo = {
            'existencias': ['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio'],
            'precios':     ['PG', 'Plataformas', 'Lista3', 'Lista4', 'CostoCalculado'],
            'costos':      ['CostoWizerp']
        };

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

        function verificarArchivos() {
            const inputs = {
                'existencias': document.getElementById('file-existencias').value !== "",
                'precios':     document.getElementById('file-precios').value !== "",
                'costos':      document.getElementById('file-costos').value !== ""
            };

            for (const [archivo, campos] of Object.entries(camposPorArchivo)) {
                const estaSubido = inputs[archivo];
                campos.forEach(campo => {
                    const label = document.getElementById('label-' + campo);
                    const checkbox = document.getElementById('check-' + campo);

                    if (label && checkbox) {
                        if (estaSubido) {
                            label.classList.remove('disabled-option');
                            label.classList.add('hover:bg-dark-700', 'cursor-pointer');
                            checkbox.disabled = false;
                        } else {
                            label.classList.add('disabled-option');
                            label.classList.remove('hover:bg-dark-700', 'cursor-pointer');
                            checkbox.disabled = true;
                            if(checkbox.checked) {
                                checkbox.checked = false;
                                actualizarOrden(checkbox);
                            }
                        }
                    }
                });
            }
        }

        async function procesarSolicitud(tipo) {
            
            if (tipo === 'clientes') {
                const fileClientes = document.getElementById('file-clientes').value;
                if (!fileClientes) {
                    mostrarToast("⚠️ Sube el archivo CSV de Clientes", "red");
                    return;
                }
            } 
            else {
                const tienePrecios = document.getElementById('file-precios').value !== "";
                const tieneCostos = document.getElementById('file-costos').value !== "";
                const tieneExistencias = document.getElementById('file-existencias').value !== "";

                if (!tieneExistencias) {
                    mostrarToast("❌ Primero sube el archivo de Existencias", "red");
                    return;
                }
                if (tipo === 'resurtido' && !tienePrecios) {
                    mostrarToast("⚠️ Lista Resurtido requiere: Existencias + Precios", "red");
                    return;
                }
                if (tipo === 'actualizada' && !tienePrecios) {
                    mostrarToast("⚠️ Lista Actualizada requiere: Existencias + Precios", "red");
                    return;
                }
                if (tipo === 'inventario' && !tienePrecios) {
                    mostrarToast("⚠️ Inv. Bellaroma requiere: Existencias + Precios", "red");
                    return;
                }
                if (tipo === 'costos' && !tieneCostos) {
                    mostrarToast("⚠️ Lista Costos requiere: Existencias + Costos", "red");
                    return;
                }
            }

            let columnas = [];
            let nombreTipo = "";

            if (tipo === 'clientes') {
                nombreTipo = "Limpieza de Clientes";
                columnas = ['ID', 'NOMBRE']; 
            } else if (tipo === 'resurtido') {
                columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Plataformas', 'Lista3'];
                nombreTipo = "Lista de Resurtido";
            } else if (tipo === 'costos') {
                columnas = ['Almacen', 'SKU', 'Descripcion', 'Existencia', 'CostoWizerp'];
                nombreTipo = "Lista de Costos";
            } else if (tipo === 'actualizada') {
                columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'CostoCalculado', 'Plataformas'];
                nombreTipo = "Lista Actualizada";
            } else if (tipo === 'inventario') {
                columnas = ['Folio', 'SKU', 'Descripcion', 'Existencia', 'PG', 'Lista3'];
                nombreTipo = "Lista de Inventario";
            } else {
                columnas = ordenSeleccionado;
                nombreTipo = "Lista Personalizada";
                if (columnas.length === 0) {
                    mostrarToast("❌ Error: Selecciona columnas habilitadas.", "red");
                    return;
                }
            }

            const form = document.getElementById('form-principal');
            const formData = new FormData(form);
            formData.append('orden_final', columnas.join(','));
            formData.append('tipo_lista', tipo);

            mostrarCarga(`Generando: ${nombreTipo}...`);
            document.getElementById('alertas').innerHTML = '';

            try {
                const response = await fetch("{{ route('gelia.generar') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
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

                const blob = await response.blob();
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = downloadUrl;
                
                const contentDisposition = response.headers.get('Content-Disposition');
                let fileName = `${nombreTipo}.xlsx`;
                if (contentDisposition) {
                    const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/);
                    if (fileNameMatch && fileNameMatch.length === 2) fileName = fileNameMatch[1];
                }
                
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                a.remove();
                
                ocultarCarga();
                mostrarToast("✅ ¡Archivo Generado Exitosamente!", "green");

            } catch (error) {
                console.error(error);
                ocultarCarga();
                mostrarToast("❌ Error: " + error.message, "red");
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
            setTimeout(() => { toast.classList.add('hidden'); }, 4000);
        }
        function mostrarError(html) {
            const div = document.getElementById('alertas');
            div.innerHTML = `<div class="bg-red-900/50 border border-red-500 text-red-200 p-4 rounded-lg mb-6">${html}</div>`;
        }
    </script>
</body>
</html>