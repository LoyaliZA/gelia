<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>G.E.L.I.A. v2.3 | Sistema de Listas</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-dark-900 text-dark-text min-h-screen font-sans selection:bg-blue-500 selection:text-white pb-20">

    <div id="toast" class="hidden fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold bg-emerald-600 transition-all">
        <span id="toast-msg">Mensaje</span>
    </div>

    <div id="overlay-carga" class="hidden fixed inset-0 bg-dark-900/90 z-40 flex flex-col justify-center items-center backdrop-blur-sm">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-500 mb-4"></div>
        <h2 id="texto-carga" class="text-xl font-bold text-white tracking-widest animate-pulse">Procesando...</h2>
    </div>

    <div id="modal-nueva-lista" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="bg-dark-800 border border-dark-700 w-full max-w-4xl rounded-xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-dark-700 flex justify-between items-center sticky top-0 bg-dark-800 z-10">
                <h2 id="modal-title" class="text-2xl font-bold text-white">Nueva Lista Personalizada</h2>
                <button onclick="toggleModal(false)" class="text-gray-400 hover:text-white text-2xl">&times;</button>
            </div>

            <form id="form-crear-lista" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" id="lista-id" name="lista_id" value="">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Creado por:</label>
                        <input type="text" name="nombre_creador" required class="w-full bg-dark-900 border border-dark-700 rounded p-2 text-white focus:border-blue-500 outline-none" placeholder="Tu nombre">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Nombre de la Lista (Boton):</label>
                        <input type="text" name="titulo_lista" required class="w-full bg-dark-900 border border-dark-700 rounded p-2 text-white focus:border-blue-500 outline-none" placeholder="Ej: Lista Finanzas">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Color del Tema:</label>
                        <select name="color" class="w-full bg-dark-900 border border-dark-700 rounded p-2 text-white focus:border-blue-500 outline-none">
                            <option value="blue">Azul (Estandar)</option>
                            <option value="emerald">Esmeralda (Exito)</option>
                            <option value="purple">Purpura (Costos)</option>
                            <option value="orange">Naranja (Alerta)</option>
                            <option value="pink">Rosa</option>
                            <option value="red">Rojo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Descripcion:</label>
                        <textarea name="descripcion" rows="2" class="w-full bg-dark-900 border border-dark-700 rounded p-2 text-white focus:border-blue-500 outline-none" placeholder="Para que sirve esta lista?"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1">Nombre Archivo Salida:</label>
                        <div class="flex items-center">
                            <input type="text" name="nombre_archivo_salida" required class="w-full bg-dark-900 border border-dark-700 rounded-l p-2 text-white focus:border-blue-500 outline-none uppercase" placeholder="REPORTE-FINANZAS">
                            <span class="bg-dark-700 text-gray-400 px-3 py-2 rounded-r border border-l-0 border-dark-700 text-sm">-[FECHA].xlsx</span>
                        </div>
                        <p class="text-[10px] text-orange-400 mt-2 font-bold flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            EL NOMBRE DEBE IR SIEMPRE CON GUIONES (Ej: MI-LISTA) o el archivo tendra un nombre erroneo.
                        </p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-white mb-2">1. Archivos Requeridos (Obligatorios):</label>
                        <div class="space-y-2">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="archivos_requeridos[]" value="existencias" checked disabled class="accent-blue-500">
                                <span class="text-gray-300">Existencias (Siempre Obligatorio)</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="archivos_requeridos[]" value="precios" class="accent-emerald-500">
                                <span class="text-gray-300">Precios</span>
                            </label>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="archivos_requeridos[]" value="costos" class="accent-purple-500">
                                <span class="text-gray-300">Costos</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-dark-900 p-3 rounded border border-orange-900/50">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="solo_con_existencia" id="check-solo-existencia" value="1" class="accent-orange-500 w-4 h-4 rounded bg-dark-800 border-dark-600">
                            <span class="text-orange-400 font-bold text-sm">Solo exportar productos con existencia</span>
                        </label>
                        <p class="text-[10px] text-gray-500 mt-1 ml-6">Omite automáticamente del Excel todos los productos que tengan 0 en existencia.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-white mb-2">2. Selecciona Columnas (En orden):</label>
                        <p class="text-xs text-gray-500 mb-2">Haz clic para anadir/quitar. El numero indica el orden.</p>
                        <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-2 custom-scroll">
                            @foreach(['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio', 'PG', 'Plataformas', 'Lista3', 'Lista4', 'ListaBoutique', 'CostoCalculado', 'CostoWizerp'] as $campo)
                            @php
                            // Nombres amigables para mostrar en la interfaz
                            $nombreMostrar = $campo;
                            if($campo == 'ListaBoutique') $nombreMostrar = 'Lista Boutique';
                            if($campo == 'CostoCalculado') $nombreMostrar = 'Costo (L.Resurtido)';
                            if($campo == 'CostoWizerp') $nombreMostrar = 'Costos (L. Costos)';
                            @endphp
                            <label class="relative flex items-center space-x-2 bg-dark-900 p-2 rounded border border-dark-700 cursor-pointer hover:bg-dark-700 transition select-none">
                                <input type="checkbox" value="{{ $campo }}" onchange="actualizarOrdenCreacion(this)" class="w-4 h-4 rounded bg-dark-800 border-dark-600">
                                <span class="text-xs text-gray-300">{{ $nombreMostrar }}</span>
                                <span id="badge-creacion-{{ $campo }}" class="hidden absolute top-1 right-1 bg-blue-600 text-white text-[9px] font-bold w-4 h-4 flex items-center justify-center rounded-full"></span>
                            </label>
                            @endforeach
                        </div>
                        <input type="hidden" name="columnas_exportar" id="input-columnas-exportar">
                    </div>
                </div>

                <div class="md:col-span-2 pt-4 border-t border-dark-700 flex justify-end gap-3">
                    <button type="button" onclick="toggleModal(false)" class="px-4 py-2 text-gray-400 hover:text-white">Cancelar</button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg shadow-lg">Guardar Lista</button>
                </div>
            </form>
        </div>
    </div>

    <div class="max-w-6xl mx-auto p-6">

        <header class="flex items-center justify-between mb-8 border-b border-dark-700 pb-6">
            <div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">G.E.L.I.A.</span>
                    <span class="text-lg text-gray-500 font-normal ml-2">v2.3</span>
                </h1>
                <p class="text-gray-400 mt-1 text-sm font-medium">Sistema Generador de Listas Inteligentes y Automatizadas</p>
            </div>
            <button onclick="abrirModalCrear()" class="bg-dark-800 hover:bg-dark-700 border border-dark-700 text-blue-400 px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition shadow-lg">
                <span>+</span> Crear Nueva Lista
            </button>
        </header>

        <div id="alertas"></div>

        <form id="form-principal" enctype="multipart/form-data">
            @csrf

            <h2 class="text-xl font-bold text-white mb-4 flex items-center">
                <span class="bg-blue-600 w-2 h-6 rounded mr-2"></span> Zona de Carga
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">

                <div class="drop-zone bg-dark-800 border-2 border-dark-700 rounded-xl p-5 hover:border-blue-500/50 transition-all duration-200" id="card-existencias">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-bold text-md text-blue-400">1. Existencias *</h3>
                    </div>
                    <details class="mb-4 group">
                        <summary class="text-[11px] text-gray-500 hover:text-gray-300 cursor-pointer list-none flex items-center gap-1 transition select-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200 group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            Ver instrucciones
                        </summary>
                        <div class="mt-2 text-[10px] text-gray-400 bg-dark-900 p-2 rounded border border-dark-700 leading-relaxed shadow-inner">
                            <span class="text-blue-400 font-bold">Ruta:</span> Almacenes > Inventarios<br>
                            <span class="text-blue-400 font-bold">Filtros:</span> Seleccionar almacen (CEDIS, TIENDA o REMATES), Existencia diferente o igual a 0<br>
                            <span class="text-blue-400 font-bold">Opciones:</span> EXCEL > Exportar en CSV.
                        </div>
                    </details>
                    <input type="file" id="file-existencias" name="existencias" onchange="verificarArchivos()" class="block w-full text-xs text-gray-400 file:bg-blue-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-blue-500 cursor-pointer">
                </div>

                <div class="drop-zone bg-dark-800 border-2 border-dark-700 rounded-xl p-5 hover:border-emerald-500/50 transition-all duration-200" id="card-precios">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-bold text-md text-emerald-400">2. Precios</h3>
                    </div>
                    <details class="mb-4 group">
                        <summary class="text-[11px] text-gray-500 hover:text-gray-300 cursor-pointer list-none flex items-center gap-1 transition select-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200 group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            Ver instrucciones
                        </summary>
                        <div class="mt-2 text-[10px] text-gray-400 bg-dark-900 p-2 rounded border border-dark-700 leading-relaxed shadow-inner">
                            <span class="text-emerald-400 font-bold">Ruta:</span> Almacen > Productos<br>
                            <span class="text-emerald-400 font-bold">Operaciones:</span> Exportar lista de precios<br>
                            <span class="text-emerald-400 font-bold">Opciones:</span> Guardar en carpeta.
                        </div>
                    </details>
                    <input type="file" id="file-precios" name="precios" onchange="verificarArchivos()" class="block w-full text-xs text-gray-400 file:bg-emerald-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-emerald-500 cursor-pointer">
                </div>

                <div class="drop-zone bg-dark-800 border-2 border-dark-700 rounded-xl p-5 hover:border-purple-500/50 transition-all duration-200" id="card-costos">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-bold text-md text-purple-400">3. Costos</h3>
                    </div>
                    <details class="mb-4 group">
                        <summary class="text-[11px] text-gray-500 hover:text-gray-300 cursor-pointer list-none flex items-center gap-1 transition select-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200 group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            Ver instrucciones
                        </summary>
                        <div class="mt-2 text-[10px] text-gray-400 bg-dark-900 p-2 rounded border border-dark-700 leading-relaxed shadow-inner">
                            <span class="text-purple-400 font-bold">Ruta:</span> Almacenes > Costos<br>
                            <span class="text-purple-400 font-bold">Operaciones:</span> Seleccionar Opcion Excel<br>
                            <span class="text-purple-400 font-bold">Opciones:</span> Guardar en CSV.
                        </div>
                    </details>
                    <input type="file" id="file-costos" name="costos" onchange="verificarArchivos()" class="block w-full text-xs text-gray-400 file:bg-purple-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-purple-500 cursor-pointer">
                </div>

                <div class="drop-zone bg-dark-800 border-2 border-yellow-700/50 rounded-xl p-5 hover:border-yellow-500 transition-all duration-200 relative overflow-hidden" id="card-clientes">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-bold text-md text-yellow-400">4. Clientes CSV</h3>
                    </div>
                    <details class="mb-4 group">
                        <summary class="text-[11px] text-gray-500 hover:text-gray-300 cursor-pointer list-none flex items-center gap-1 transition select-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200 group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            Ver instrucciones
                        </summary>
                        <div class="mt-2 text-[10px] text-gray-400 bg-dark-900 p-2 rounded border border-dark-700 leading-relaxed shadow-inner">
                            Archivo CSV de Clientes para limpieza de base de datos.
                        </div>
                    </details>
                    <input type="file" id="file-clientes" name="clientes" class="block w-full text-xs text-gray-400 file:bg-yellow-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-yellow-500 cursor-pointer">
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-10">
                <div>
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center">
                        <span class="bg-emerald-600 w-2 h-6 rounded mr-2"></span> Listas Predeterminadas
                    </h2>
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <button type="button" onclick="procesarSolicitud('resurtido')" class="p-4 bg-dark-800 border border-dark-700 hover:border-blue-500 hover:bg-dark-700 rounded-xl text-left group transition">
                            <span class="block text-blue-400 font-bold mb-1 group-hover:text-blue-300">Lista de Resurtido</span>
                            <span class="block text-[10px] text-gray-500 uppercase">Estandar: PG, Plataformas, Lista3</span>
                        </button>
                        <button type="button" onclick="procesarSolicitud('costos')" class="p-4 bg-dark-800 border border-dark-700 hover:border-purple-500 hover:bg-dark-700 rounded-xl text-left group transition">
                            <span class="block text-purple-400 font-bold mb-1 group-hover:text-purple-300">Lista de Costos</span>
                            <span class="block text-[10px] text-gray-500 uppercase">Estandar: Costos Wizerp</span>
                        </button>
                        <button type="button" onclick="procesarSolicitud('actualizada')" class="p-4 bg-dark-800 border border-dark-700 hover:border-orange-500 hover:bg-dark-700 rounded-xl text-left group transition">
                            <span class="block text-orange-400 font-bold mb-1 group-hover:text-orange-300">Actualizada</span>
                            <span class="block text-[10px] text-gray-500 uppercase">Estandar: Costo Calc + Plataformas</span>
                        </button>
                        <button type="button" onclick="procesarSolicitud('inventario')" class="p-4 bg-dark-800 border border-dark-700 hover:border-teal-500 hover:bg-dark-700 rounded-xl text-left group transition">
                            <span class="block text-teal-400 font-bold mb-1 group-hover:text-teal-300">Inventario Bellaroma</span>
                            <span class="block text-[10px] text-gray-500 uppercase">Estandar: PG + Lista3</span>
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

            <div class="mb-10">
                <h2 class="text-xl font-bold text-white mb-5 flex items-center">
                    <span class="bg-cyan-600 w-2 h-6 rounded mr-2"></span> Filtrar Gastos Comprobables
                </h2>

                <div class="bg-dark-800 border border-dark-700 p-5 rounded-xl flex flex-col md:flex-row gap-6 items-center shadow-lg">
                    <div class="drop-zone border-2 border-green-900/50 border-dashed rounded-lg p-4 w-full md:w-1/3 hover:border-green-500 transition-all bg-dark-900/50" id="card-gastos">
                        <span class="block text-green-400 font-bold mb-2 text-sm">Subir Archivo Excel</span>
                        <input type="file" id="file-gastos" name="archivo_gastos" class="block w-full text-xs text-gray-400 file:bg-green-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-green-500 cursor-pointer">
                    </div>

                    <div class="w-full md:w-1/3">
                        <label class="block text-sm font-medium text-gray-400 mb-2">Filtrar Por tipo</label>
                        <select name="filtro_tipo" id="filtro-tipo" class="w-full bg-dark-900 border border-dark-700 rounded-lg p-3 text-white focus:border-green-500 outline-none cursor-pointer">
                            <option value="TODOS">Ambos (Pedidos y Remisiones)</option>
                            <option value="Remisión">Solo Remisiones</option>
                            <option value="Pedido">Solo Pedidos</option>
                        </select>
                    </div>

                    <div class="w-full md:w-1/3 md:mt-6">
                        <button type="button" onclick="procesarSolicitud('gastos')" class="w-full py-3 bg-green-600/20 border border-green-600/50 text-dark-400 hover:bg-green-600 hover:text-white rounded-lg font-bold transition flex justify-center items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            Generar y Descargar
                        </button>
                    </div>
                </div>
            </div>

            @if(isset($listasPersonalizadas) && count($listasPersonalizadas) > 0)
            <div class="mb-10">
                <h2 class="text-xl font-bold text-white mb-4 flex items-center border-t border-dark-700 pt-6">
                    <span class="bg-purple-600 w-2 h-6 rounded mr-2"></span> Listas Personalizadas Guardadas
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($listasPersonalizadas as $lista)
                    @php
                    $colors = [
                    'blue' => 'text-blue-400 hover:border-blue-500 group-hover:text-blue-300',
                    'emerald' => 'text-emerald-400 hover:border-emerald-500 group-hover:text-emerald-300',
                    'purple' => 'text-purple-400 hover:border-purple-500 group-hover:text-purple-300',
                    'orange' => 'text-orange-400 hover:border-orange-500 group-hover:text-orange-300',
                    'pink' => 'text-pink-400 hover:border-pink-500 group-hover:text-pink-300',
                    'red' => 'text-red-400 hover:border-red-500 group-hover:text-red-300',
                    ];
                    $claseColor = $colors[$lista->color] ?? $colors['blue'];
                    @endphp

                    <button type="button" onclick="procesarSolicitud('{{ $lista->id }}')" class="relative p-4 bg-dark-800 border border-dark-700 hover:bg-dark-700 rounded-xl text-left group transition pr-16 {{ $claseColor }} h-full flex flex-col justify-between">

                        <div>
                            <span class="block font-bold mb-1 text-lg">{{ $lista->titulo_lista }}</span>
                            <span class="block text-[11px] text-gray-500 mb-1 leading-tight">{{ Str::limit($lista->descripcion, 55) }}</span>
                            <span class="block text-[10px] text-gray-600 italic mt-2">Por: {{ $lista->nombre_creador }}</span>
                        </div>

                        <div onclick="abrirModalEdicion(event, '{{ $lista->id }}')" class="absolute top-2 right-10 p-2 rounded-full hover:bg-blue-900/50 text-gray-600 hover:text-blue-500 transition cursor-pointer" title="Editar lista">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>

                        <div onclick="eliminarLista(event, '{{ $lista->id }}')" class="absolute top-2 right-2 p-2 rounded-full hover:bg-red-900/50 text-gray-600 hover:text-red-500 transition cursor-pointer" title="Eliminar lista">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>

                        @if($lista->solo_con_existencia)
                        <span class="absolute bottom-3 right-3 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[9px] font-bold bg-orange-500/10 text-orange-400 border border-orange-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Solo c/ Existencia
                        </span>
                        @endif
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="border-t border-dark-700 pt-8">
                <h2 class="text-lg font-bold text-gray-300 mb-4">Genera una lista personalizada (Manual)</h2>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-8">
                    @foreach(['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio'] as $campo)
                    <label id="label-{{ $campo }}" class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 select-none disabled-option transition-all duration-300">
                        <input type="checkbox" id="check-{{ $campo }}" value="{{ $campo }}" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-blue-600 rounded bg-dark-900 border-dark-700">
                        <span class="text-sm font-medium text-gray-300">{{ $campo }}</span>
                        <span id="badge-{{ $campo }}" class="hidden absolute -top-2 -right-2 bg-blue-600 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
                    </label>
                    @endforeach

                    @foreach(['PG', 'Plataformas', 'Lista3', 'Lista4', 'ListaBoutique'] as $campo)
                    @php $nombreMostrar = $campo == 'ListaBoutique' ? 'Lista Boutique' : $campo; @endphp
                    <label id="label-{{ $campo }}" class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 select-none disabled-option transition-all duration-300">
                        <input type="checkbox" id="check-{{ $campo }}" value="{{ $campo }}" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-blue-600 rounded bg-dark-900 border-dark-700">
                        <span class="text-sm font-medium text-gray-300">{{ $nombreMostrar }}</span>
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
                        <span class="text-sm font-medium text-gray-300">Costo (L. Costos)</span>
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
        // Mantenemos solo el objeto de configuración que necesita la vista de Laravel
        window.GeliaConfig = {
            routes: {
                generar: "{{ route('gelia.generar') }}",
                guardar: "{{ route('gelia.guardar') }}",
                actualizar: "{{ route('gelia.actualizar', ['id' => ':id']) }}",
                eliminar: "{{ route('gelia.eliminar', ['id' => ':id']) }}"
            },
            customLists: {!! json_encode($listasPersonalizadas ?? []) !!}
        };
    </script>
</body>

</html>