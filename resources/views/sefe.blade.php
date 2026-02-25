<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S.E.F.E. | Extractor de Facturas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Reutilizamos el scrollbar mamalón de Gelia */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0d1117; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #10b981; } /* Tono esmeralda para SEFE */
        
        .toast-enter { transform: translateY(-20px); opacity: 0; }
        .toast-enter-active { transform: translateY(0); opacity: 1; transition: all 0.3s ease-out; }
    </style>
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
</head>
<body class="bg-dark-900 text-dark-text min-h-screen font-sans selection:bg-emerald-500 selection:text-white pb-20">

    <div id="overlay-carga" class="hidden fixed inset-0 bg-dark-900/90 z-50 flex flex-col justify-center items-center backdrop-blur-sm">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-emerald-500 mb-4"></div>
        <h2 id="texto-carga" class="text-xl font-bold text-white tracking-widest animate-pulse">Procesando Facturas...</h2>
    </div>

    <nav class="border-b border-dark-700 bg-dark-800 sticky top-0 z-30 shadow-lg">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-emerald-600/20 p-2 rounded-lg border border-emerald-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-white tracking-tight">S.E.F.E.</h1>
                    <p class="text-[10px] text-gray-400 uppercase tracking-wider">Sistema Extractor de Facturas</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="{{ route('gelia.index') }}" class="text-sm text-gray-400 hover:text-white transition">Volver a G.E.L.I.A.</a>
                <button onclick="toggleModalProveedor(true)" class="bg-dark-700 hover:bg-dark-600 border border-dark-600 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Catálogo de Proveedores
                </button>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-6 mt-6">
        
        <div class="mb-10">
            <h2 class="text-lg font-bold text-white mb-4 flex items-center">
                <span class="bg-emerald-500 w-2 h-6 rounded mr-2"></span> Subir Nuevas Facturas (XML)
            </h2>
            <form id="form-upload-facturas" enctype="multipart/form-data">
                @csrf
                <div id="dropzone-xml" class="border-2 border-dashed border-dark-600 bg-dark-800 hover:bg-dark-700/50 hover:border-emerald-500 transition-all duration-300 rounded-2xl p-10 text-center cursor-pointer group">
                    <input type="file" name="facturas_xml[]" id="input-xml" multiple accept=".xml" class="hidden">
                    <div class="flex flex-col items-center justify-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-500 group-hover:text-emerald-400 transition-colors mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        <h3 class="text-xl font-bold text-gray-300 group-hover:text-white mb-1">Arrastra tus archivos XML aquí</h3>
                        <p class="text-sm text-gray-500">o haz clic para explorar tu equipo (Puedes seleccionar varios)</p>
                    </div>
                </div>
            </form>
        </div>

        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-white flex items-center">
                    <span class="bg-blue-500 w-2 h-6 rounded mr-2"></span> Mi Unidad / Facturas Procesadas
                </h2>
                <div class="relative">
                    <input type="text" placeholder="Buscar por folio o proveedor..." class="bg-dark-800 border border-dark-600 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-64 pl-10 p-2 text-white outline-none">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/></svg>
                    </div>
                </div>
            </div>

            <div class="bg-dark-800 border border-dark-700 rounded-xl overflow-hidden shadow-xl">
                <table class="w-full text-left text-sm text-gray-400">
                    <thead class="bg-dark-900 border-b border-dark-700 text-xs uppercase font-bold text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Nombre / Folio</th>
                            <th class="px-6 py-4">Proveedor</th>
                            <th class="px-6 py-4">Total</th>
                            <th class="px-6 py-4">Fecha Subida</th>
                            <th class="px-6 py-4 text-right">Descargas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700">
                        @forelse($facturas as $factura)
                        <tr class="hover:bg-dark-700/40 transition-colors group">
                            <td class="px-6 py-4 font-medium text-white flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                <div>
                                    <span class="block text-sm group-hover:text-emerald-400 transition-colors">{{ $factura->folio ?? 'Sin Folio' }}</span>
                                    <span class="block text-[10px] text-gray-500 uppercase">{{ $factura->uuid }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="block text-gray-300 font-bold">{{ $factura->proveedor->nombre ?? 'Desconocido' }}</span>
                                <span class="block text-[10px] text-gray-500">{{ $factura->proveedor->rfc ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4 text-gray-300 font-mono">
                                ${{ number_format($factura->total, 2) }}
                            </td>
                            <td class="px-6 py-4 text-gray-400">
                                {{ $factura->created_at->format('d M Y - H:i') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="#" class="p-2 text-gray-400 hover:text-blue-400 bg-dark-900 rounded-lg border border-dark-600 hover:border-blue-500 transition" title="Ver XML Original">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                    </a>
                                    <a href="#" class="bg-emerald-600/10 text-emerald-500 border border-emerald-500/30 hover:bg-emerald-600 hover:text-white px-4 py-2 rounded-lg font-bold transition flex items-center gap-2 text-xs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        Excel
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-dark-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                    <p>Tu unidad está vacía. Sube tu primer XML arriba.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-proveedor" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="bg-dark-800 border border-dark-600 w-full max-w-3xl rounded-2xl shadow-2xl max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-dark-700 flex justify-between items-center">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01-.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" /></svg>
                    Configurar Mapeo de Proveedor
                </h2>
                <button onclick="toggleModalProveedor(false)" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scroll">
                <form id="form-proveedor" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">RFC del Proveedor</label>
                            <input type="text" id="prov-rfc" required class="w-full bg-dark-900 border border-dark-600 rounded-lg p-3 text-white focus:border-emerald-500 outline-none uppercase placeholder-gray-600" placeholder="Ej. UFR9909228Z9">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Nombre Comercial</label>
                            <input type="text" id="prov-nombre" required class="w-full bg-dark-900 border border-dark-600 rounded-lg p-3 text-white focus:border-emerald-500 outline-none placeholder-gray-600" placeholder="Ej. UNIVERSO DE FRAGANCIAS">
                        </div>
                    </div>

                    <div class="border-t border-dark-700 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-bold text-white">Configuración de Columnas (Excel)</label>
                            <button type="button" onclick="agregarFilaMapeo()" class="text-xs bg-dark-700 hover:bg-dark-600 text-emerald-400 border border-dark-600 px-3 py-1.5 rounded-md font-bold transition flex items-center gap-1">
                                <span>+</span> Añadir Columna
                            </button>
                        </div>
                        
                        <div id="contenedor-mapeo" class="space-y-3">
                            <div class="flex items-center gap-3 mapeo-row">
                                <input type="text" placeholder="Nombre en Excel (Ej. UPC)" class="mapeo-key flex-1 bg-dark-900 border border-dark-600 rounded-lg p-2 text-sm text-white focus:border-emerald-500 outline-none">
                                <span class="text-gray-500">=</span>
                                <select class="mapeo-val flex-1 bg-dark-900 border border-dark-600 rounded-lg p-2 text-sm text-gray-300 focus:border-emerald-500 outline-none">
                                    <option value="NoIdentificacion">Atributo SAT: NoIdentificacion (SKU/UPC)</option>
                                    <option value="Descripcion">Atributo SAT: Descripcion</option>
                                    <option value="Cantidad">Atributo SAT: Cantidad</option>
                                    <option value="ValorUnitario">Atributo SAT: ValorUnitario (Precio)</option>
                                    <option value="Importe">Atributo SAT: Importe (Subtotal)</option>
                                    <option value="ClaveProdServ">Atributo SAT: ClaveProdServ</option>
                                    <option value="Unidad">Atributo SAT: Unidad</option>
                                </select>
                                <button type="button" onclick="this.parentElement.remove()" class="p-2 text-red-400 hover:bg-red-500/10 rounded-lg transition" title="Eliminar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-3">Define exactamente cómo se llamará la columna en el Excel y de qué atributo del CFDI sacaremos el valor.</p>
                    </div>
                </form>
            </div>
            <div class="p-6 border-t border-dark-700 bg-dark-900 rounded-b-2xl flex justify-end gap-3">
                <button type="button" onclick="toggleModalProveedor(false)" class="px-5 py-2.5 text-sm font-bold text-gray-400 hover:text-white transition">Cancelar</button>
                <button type="button" onclick="guardarConfiguracionProveedor()" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold rounded-lg shadow-lg shadow-emerald-900/50 transition">Guardar Configuración</button>
            </div>
        </div>
    </div>

    <script>
        // Lógica visual básica
        function toggleModalProveedor(show) {
            const modal = document.getElementById('modal-proveedor');
            if (show) modal.classList.remove('hidden');
            else modal.classList.add('hidden');
        }

        function agregarFilaMapeo() {
            const container = document.getElementById('contenedor-mapeo');
            const rowHtml = `
                <div class="flex items-center gap-3 mapeo-row">
                    <input type="text" placeholder="Nombre en Excel (Ej. CANTIDAD)" class="mapeo-key flex-1 bg-dark-900 border border-dark-600 rounded-lg p-2 text-sm text-white focus:border-emerald-500 outline-none">
                    <span class="text-gray-500">=</span>
                    <select class="mapeo-val flex-1 bg-dark-900 border border-dark-600 rounded-lg p-2 text-sm text-gray-300 focus:border-emerald-500 outline-none">
                        <option value="NoIdentificacion">Atributo SAT: NoIdentificacion (SKU/UPC)</option>
                        <option value="Descripcion">Atributo SAT: Descripcion</option>
                        <option value="Cantidad">Atributo SAT: Cantidad</option>
                        <option value="ValorUnitario">Atributo SAT: ValorUnitario (Precio)</option>
                        <option value="Importe">Atributo SAT: Importe (Subtotal)</option>
                        <option value="ClaveProdServ">Atributo SAT: ClaveProdServ</option>
                        <option value="Unidad">Atributo SAT: Unidad</option>
                    </select>
                    <button type="button" onclick="this.parentElement.remove()" class="p-2 text-red-400 hover:bg-red-500/10 rounded-lg transition" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        }

        // Aquí meteremos el fetch() para enviar el JSON al controlador y la lógica de upload
    </script>
</body>
</html>