@extends('layouts.aromas') @section('aromas-content')

@section('title', 'Listados e Inventario | Gelia Hub')

@section('aromas-content')


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
                    <label class="block text-sm font-medium text-dark-muted mb-1">Creado por:</label>
                    <input type="text" name="nombre_creador" required class="w-full bg-dark-900 border border-dark-700 rounded p-2 text-white focus:border-aromas-main outline-none" placeholder="Tu nombre">
                </div>
                <div>
                    <label class="block text-sm font-medium text-dark-muted mb-1">Nombre de la Lista (Boton):</label>
                    <input type="text" name="titulo_lista" required class="w-full bg-dark-900 border border-dark-700 rounded p-2 text-white focus:border-aromas-main outline-none" placeholder="Ej: Lista Finanzas">
                </div>
                <div>
                    <label class="block text-sm font-medium text-dark-muted mb-1">Color del Tema:</label>
                    <select name="color" class="w-full bg-dark-900 border border-dark-700 rounded p-2 text-white focus:border-aromas-main outline-none">
                        <option value="blue">Azul (Estandar)</option>
                        <option value="emerald">Esmeralda (Exito)</option>
                        <option value="purple">Purpura (Costos)</option>
                        <option value="orange">Naranja (Alerta)</option>
                        <option value="pink">Rosa</option>
                        <option value="red">Rojo</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-dark-muted mb-1">Descripcion:</label>
                    <textarea name="descripcion" rows="2" class="w-full bg-dark-900 border border-dark-700 rounded p-2 text-white focus:border-aromas-main outline-none" placeholder="Para que sirve esta lista?"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-dark-muted mb-1">Nombre Archivo Salida:</label>
                    <div class="flex items-center">
                        <input type="text" name="nombre_archivo_salida" required class="w-full bg-dark-900 border border-dark-700 rounded-l p-2 text-white focus:border-aromas-main outline-none uppercase" placeholder="REPORTE-FINANZAS">
                        <span class="bg-dark-700 text-dark-muted px-3 py-2 rounded-r border border-l-0 border-dark-700 text-sm">-[FECHA].xlsx</span>
                    </div>
                    <p class="text-[10px] text-orange-400 mt-2 font-bold flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        EL NOMBRE DEBE IR SIEMPRE CON GUIONES (Ej: MI-LISTA).
                    </p>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-white mb-2">1. Archivos Requeridos (Obligatorios):</label>
                    <div class="space-y-2">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input type="checkbox" name="archivos_requeridos[]" value="existencias" checked disabled class="accent-aromas-main">
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
                    <p class="text-[10px] text-dark-muted mt-1 ml-6">Omite automáticamente del Excel todos los productos que tengan 0 en existencia.</p>
                </div>
                <div class="col-span-1 md:col-span-2 bg-dark-900 p-4 rounded-xl border border-dark-700 flex flex-col sm:flex-row gap-4">
                    <label class="flex items-center space-x-3 cursor-pointer group">
                        <input type="checkbox" name="filtro_relojes" id="check-filtro-relojes" value="1" class="w-5 h-5 text-purple-500 bg-dark-800 border-dark-600 rounded focus:ring-purple-500 focus:ring-2">
                        <span class="text-sm font-medium text-gray-300 group-hover:text-white transition">Solo Relojes (Nombre inicia con 'R')</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-bold text-white mb-2">2. Selecciona Columnas (En orden):</label>
                    <p class="text-xs text-dark-muted mb-2">Haz clic para anadir/quitar. El numero indica el orden.</p>
                    <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-2 custom-scroll">
                        @foreach(['SKU', 'Descripcion', 'Marca', 'Existencia', 'Almacen', 'Folio', 'PG', 'Bronce', 'Plata', 'Oro', 'Diamante', 'Plataformas', 'Lista3', 'Lista4', 'ListaBoutique', 'CostoCalculado', 'CostoWizerp'] as $campo)
                        @php
                        $nombreMostrar = $campo;
                        if($campo == 'ListaBoutique') $nombreMostrar = 'Lista Boutique';
                        if($campo == 'CostoCalculado') $nombreMostrar = 'Costo (L.Resurtido)';
                        if($campo == 'CostoWizerp') $nombreMostrar = 'Costos (L. Costos)';
                        @endphp
                        <label class="relative flex items-center space-x-2 bg-dark-900 p-2 rounded border border-dark-700 cursor-pointer hover:bg-dark-700 transition select-none">
                            <input type="checkbox" value="{{ $campo }}" onchange="actualizarOrdenCreacion(this)" class="w-4 h-4 rounded bg-dark-800 border-dark-600">
                            <span class="text-xs text-gray-300">{{ $nombreMostrar }}</span>
                            <span id="badge-creacion-{{ $campo }}" class="hidden absolute top-1 right-1 bg-aromas-main text-white text-[9px] font-bold w-4 h-4 flex items-center justify-center rounded-full"></span>
                        </label>
                        @endforeach
                    </div>
                    <input type="hidden" name="columnas_exportar" id="input-columnas-exportar">
                </div>
            </div>

            <div class="md:col-span-2 pt-4 border-t border-dark-700 flex justify-end gap-3">
                <button type="button" onclick="toggleModal(false)" class="px-4 py-2 text-dark-muted hover:text-white">Cancelar</button>
                <button type="submit" class="px-6 py-2 bg-aromas-main hover:bg-aromas-light text-dark-900 font-bold rounded-lg shadow-lg">Guardar Lista</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-inconsistencias" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
    <div class="bg-dark-800 border border-orange-500/50 w-full max-w-5xl rounded-xl shadow-2xl max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-dark-700 flex justify-between items-center bg-dark-800 rounded-t-xl z-10">
            <h2 class="text-2xl font-bold text-orange-400 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Advertencia: Inconsistencias en Wizerp Detectadas
            </h2>
            <button onclick="cerrarModalInconsistencias()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto custom-scroll flex-1">
            <p class="text-gray-300 mb-4 text-sm">Se han detectado productos con <span class="text-orange-400 font-bold">existencia mayor a 0 pero con Precio/Margen 0</span> en los archivos base. Por favor, verifica estos datos en el sistema para evitar fugas.</p>

            <div class="bg-dark-900 border border-dark-700 rounded-lg overflow-hidden">
                <table class="w-full text-left text-sm text-gray-300">
                    <thead class="bg-dark-800 text-dark-muted uppercase text-[10px] font-bold">
                        <tr>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Descripción</th>
                            <th class="px-4 py-3">Almacén</th>
                            <th class="px-4 py-3 text-center">Existencia</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-inconsistencias-body" class="divide-y divide-dark-700">
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-4 border-t border-dark-700 flex justify-between items-center bg-dark-800 rounded-b-xl">
            <button type="button" onclick="copiarTablaInconsistencias()" class="px-4 py-2 bg-dark-700 hover:bg-dark-600 text-white font-bold rounded-lg transition flex items-center gap-2 text-sm border border-dark-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Copiar Tabla
            </button>
            <div class="flex gap-3">
                <button type="button" onclick="cerrarModalInconsistencias()" class="px-4 py-2 text-dark-muted hover:text-white text-sm transition">Cancelar</button>
                <button type="button" id="btn-forzar-descarga" class="px-6 py-2 bg-orange-600 hover:bg-orange-500 text-white font-bold rounded-lg shadow-lg transition text-sm">Descargar de todos modos</button>
            </div>
        </div>
    </div>
</div>

<header class="flex items-center justify-between mb-8 pb-6">
    <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-aromas-main to-emerald-400">Cruce de Inventarios</span>
        </h1>
        <p class="text-dark-muted mt-1 text-sm font-medium">Gestión de resurtidos, costos y generación de listas maestras.</p>
    </div>
    <button onclick="abrirModalCrear()" class="bg-dark-800 hover:bg-dark-700 border border-dark-700 text-aromas-main px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition shadow-lg">
        <span>+</span> Crear Nueva Lista
    </button>
</header>

<div id="alertas"></div>

<form id="form-principal" enctype="multipart/form-data">
    @csrf

    <div class="bg-dark-900 border border-dark-700 rounded-xl p-5 mb-8 shadow-lg">
        <h2 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
            <span class="bg-blue-500 w-2 h-4 rounded"></span> Configuración de Descuentos (%)
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            @foreach(['bronce' => 12.39, 'plata' => 14.14, 'oro' => 15.89, 'diamante' => 17.65, 'lista3' => 14.28, 'lista4' => 17.71] as $key => $val)
            <div>
                <label class="block text-xs font-bold text-dark-muted mb-1 uppercase">{{ $key }}</label>
                <div class="flex items-center">
                    <input type="number" step="0.01" name="pct_{{ $key }}" value="{{ $val }}" class="w-full bg-dark-800 border border-dark-700 rounded-l p-2 text-white focus:border-aromas-main outline-none text-sm text-center">
                    <span class="bg-dark-700 text-dark-muted px-2 py-2 rounded-r border border-l-0 border-dark-700 text-sm">%</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <h2 class="text-xl font-bold text-white mb-4 flex items-center">
        <span class="bg-aromas-main w-2 h-6 rounded mr-2"></span> Zona de Carga Principal
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <x-upload-area
            id="existencias"
            name="existencias"
            title="1. Existencias *"
            colorTheme="aromas"
            instructions="<span class='text-aromas-main font-bold'>Ruta:</span> Almacenes > Inventarios<br><span class='text-aromas-main font-bold'>Filtros:</span> Seleccionar almacen (CEDIS, TIENDA o REMATES), Existencia diferente o igual a 0<br><span class='text-aromas-main font-bold'>Opciones:</span> EXCEL > Exportar en CSV." />

        <x-upload-area
            id="precios"
            name="precios"
            title="2. Precios"
            colorTheme="green"
            instructions="<span class='text-blue-400 font-bold'>Ruta:</span> Almacen > Productos<br><span class='text-blue-400 font-bold'>Operaciones:</span> Exportar lista de precios<br><span class='text-blue-400 font-bold'>Opciones:</span> Guardar en carpeta." />

        <x-upload-area
            id="costos"
            name="costos"
            title="3. Costos"
            colorTheme="purple"
            instructions="<span class='text-purple-400 font-bold'>Ruta:</span> Almacenes > Costos<br><span class='text-purple-400 font-bold'>Operaciones:</span> Seleccionar Opcion Excel<br><span class='text-purple-400 font-bold'>Opciones:</span> Guardar en CSV." />
    </div>

    <div class="mb-10">
        <h2 class="text-xl font-bold text-white mb-4 flex items-center">
            <span class="bg-emerald-600 w-2 h-6 rounded mr-2"></span> Listas Predeterminadas
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <button type="button" onclick="procesarSolicitud('resurtido')" class="p-4 bg-dark-800 border border-dark-700 hover:border-aromas-main hover:bg-dark-700 rounded-xl text-left group transition">
                <span class="block text-aromas-main font-bold mb-1 group-hover:text-aromas-light">Lista de Resurtido</span>
                <span class="block text-[10px] text-dark-muted uppercase">Estandar: PG, Plataformas, Lista3</span>
            </button>
            <button type="button" onclick="procesarSolicitud('costos')" class="p-4 bg-dark-800 border border-dark-700 hover:border-purple-500 hover:bg-dark-700 rounded-xl text-left group transition">
                <span class="block text-purple-400 font-bold mb-1 group-hover:text-purple-300">Lista de Costos</span>
                <span class="block text-[10px] text-dark-muted uppercase">Estandar: Costos Wizerp</span>
            </button>
            <button type="button" onclick="procesarSolicitud('actualizada')" class="p-4 bg-dark-800 border border-dark-700 hover:border-orange-500 hover:bg-dark-700 rounded-xl text-left group transition">
                <span class="block text-orange-400 font-bold mb-1 group-hover:text-orange-300">Actualizada</span>
                <span class="block text-[10px] text-dark-muted uppercase">Estandar: Costo Calc + Plataformas</span>
            </button>
            <button type="button" onclick="procesarSolicitud('inventario')" class="p-4 bg-dark-800 border border-dark-700 hover:border-teal-500 hover:bg-dark-700 rounded-xl text-left group transition">
                <span class="block text-teal-400 font-bold mb-1 group-hover:text-teal-300">Inventario Bellaroma</span>
                <span class="block text-[10px] text-dark-muted uppercase">Estandar: PG + Lista3</span>
            </button>
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
                    <span class="block text-[11px] text-dark-muted mb-1 leading-tight">{{ Str::limit($lista->descripcion, 55) }}</span>
                    <span class="block text-[10px] text-dark-600 italic mt-2">Por: {{ $lista->nombre_creador }}</span>
                </div>

                <div onclick="abrirModalEdicion(event, '{{ $lista->id }}')" class="absolute top-2 right-10 p-2 rounded-full hover:bg-blue-900/50 text-dark-muted hover:text-blue-500 transition cursor-pointer" title="Editar lista">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>

                <div onclick="eliminarLista(event, '{{ $lista->id }}')" class="absolute top-2 right-2 p-2 rounded-full hover:bg-red-900/50 text-dark-muted hover:text-red-500 transition cursor-pointer" title="Eliminar lista">
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
                <input type="checkbox" id="check-{{ $campo }}" value="{{ $campo }}" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-aromas-main rounded bg-dark-900 border-dark-700">
                <span class="text-sm font-medium text-gray-300">{{ $campo }}</span>
                <span id="badge-{{ $campo }}" class="hidden absolute -top-2 -right-2 bg-aromas-main text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
            </label>
            @endforeach

            @foreach(['PG', 'Bronce', 'Plata', 'Oro', 'Diamante', 'Plataformas', 'Lista3', 'Lista4', 'ListaBoutique'] as $campo)
            @php $nombreMostrar = $campo == 'ListaBoutique' ? 'Lista Boutique' : $campo; @endphp
            <label id="label-{{ $campo }}" class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 select-none disabled-option transition-all duration-300">
                <input type="checkbox" id="check-{{ $campo }}" value="{{ $campo }}" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-aromas-main rounded bg-dark-900 border-dark-700">
                <span class="text-sm font-medium text-gray-300">{{ $nombreMostrar }}</span>
                <span id="badge-{{ $campo }}" class="hidden absolute -top-2 -right-2 bg-aromas-main text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
            </label>
            @endforeach

            <label id="label-CostoCalculado" class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 select-none disabled-option transition-all duration-300">
                <input type="checkbox" id="check-CostoCalculado" value="CostoCalculado" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-aromas-main rounded bg-dark-900 border-dark-700">
                <span class="text-sm font-medium text-gray-300">Costo (L.Resurtido)</span>
                <span id="badge-CostoCalculado" class="hidden absolute -top-2 -right-2 bg-aromas-main text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
            </label>

            <label id="label-CostoWizerp" class="relative flex items-center space-x-2 bg-dark-800 p-3 rounded-lg border border-dark-700 select-none disabled-option transition-all duration-300">
                <input type="checkbox" id="check-CostoWizerp" value="CostoWizerp" onchange="actualizarOrden(this)" disabled class="w-4 h-4 text-aromas-main rounded bg-dark-900 border-dark-700">
                <span class="text-sm font-medium text-gray-300">Costo (L. Costos)</span>
                <span id="badge-CostoWizerp" class="hidden absolute -top-2 -right-2 bg-aromas-main text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full"></span>
            </label>
        </div>
        <button type="button" onclick="procesarSolicitud('manual')" class="w-full bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 text-white font-bold py-4 rounded-xl border border-dark-700 shadow-lg transition">
            Generar Manualmente
        </button>
    </div>
</form>
@endsection

@push('scripts')
@vite(['resources/js/app.js', 'resources/js/aromas/listados.js'])

<script>
    window.GeliaConfig = {
        routes: {
            // Asegúrate de que los nombres de las rutas coincidan con tu archivo web.php
            generar: "{{ route('gelia.generar') }}",
            guardar: "{{ route('gelia.guardar') }}",
            actualizar: "{{ route('gelia.actualizar', ['id' => ':id']) }}",
            eliminar: "{{ route('gelia.eliminar', ['id' => ':id']) }}"
        },
        customLists: @json($listasPersonalizadas ?? [])
    };
</script>
@endpush