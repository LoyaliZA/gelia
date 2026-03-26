@extends('layouts.aromas') @section('aromas-content')

@section('title', 'Limpieza de Clientes | Gelia Hub')

@section('aromas-content')
<header class="flex items-center justify-between mb-6 border-b border-dark-700 pb-6">
    <div>
        <h1 class="text-4xl font-extrabold text-white tracking-tight">
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-600">Limpieza de Clientes</span>
        </h1>
        <p class="text-dark-muted mt-1 text-sm font-medium">Corrección de codificación y formateo de base de datos Wizerp.</p>
    </div>
    <a href="{{ route('gelia.index') }}" class="bg-dark-800 hover:bg-dark-700 border border-dark-700 text-dark-muted hover:text-white px-4 py-2 rounded-lg text-sm font-bold transition shadow-lg flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Volver a Aromas
    </a>
</header>

<div class="mb-6 bg-dark-800 border border-dark-700 border-l-4 border-l-yellow-500 rounded-lg p-5 shadow-sm">
    <h3 class="text-sm font-bold text-yellow-500 mb-2 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
        Instrucciones del Módulo
    </h3>
    <ol class="list-decimal list-inside text-xs text-dark-muted space-y-1">
        <li>Sube el archivo CSV o TXT crudo de clientes obtenido del sistema Wizerp.</li>
        <li>Selecciona si deseas incluir o excluir prospectos (clientes sin ID asignado).</li>
        <li>Selecciona las columnas que requieres exportar para armar tu reporte personalizado.</li>
        <li>Haz clic en <strong class="text-gray-300">Procesar Archivo</strong> para descargar el Excel sanitizado con codificación UTF-8.</li>
    </ol>
</div>

<div id="alertas"></div>

<form id="form-principal" enctype="multipart/form-data">
    @csrf

    <div class="bg-dark-800 border border-dark-700 p-6 rounded-xl flex flex-col lg:flex-row gap-8 shadow-lg">

        <div class="w-full lg:w-1/3 flex flex-col gap-6">
            <x-upload-area
                id="clientes"
                name="clientes"
                title="Subir CSV Clientes"
                colorTheme="yellow"
                accept=".csv,.txt" />

            <div class="bg-dark-900 border border-dark-700 p-4 rounded-lg">
                <label class="flex items-start space-x-3 cursor-pointer group">
                    <input type="checkbox" id="check-incluir-sin-id" checked class="mt-2 accent-yellow-500 w-5 h-5 rounded bg-dark-800 border-dark-600">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-gray-300 group-hover:text-white transition">Incluir Prospectos</span>
                        <span class="text-[10px] text-dark-muted">Exporta clientes que aún no tienen ID asignado.</span>
                    </div>
                </label>
            </div>
            <div class="mt-4 flex items-center space-x-2 bg-dark-900 p-3 rounded border border-dark-700 w-fit">
                <input type="checkbox" id="check-filtro-especial" class="accent-yellow-500 w-5 h-5 cursor-pointer">
                <label for="check-filtro-especial" class="text-sm text-gray-300 font-bold cursor-pointer select-none">
                    Exclusivo: Sin Grupo Descuento y Con Tags
                </label>
            </div>

            <div class="bg-dark-900 border border-dark-700 p-4 rounded-lg">
                <label class="block text-sm font-bold text-gray-300 mb-2">Ordenar listado por:</label>
                <select name="orden_clientes" class="w-full bg-dark-800 border border-dark-600 rounded p-2 text-white text-sm focus:border-yellow-500 outline-none transition cursor-pointer">
                    <option value="">Predeterminado (Orden original del archivo)</option>
                    <option value="id_asc">ID (Menor a Mayor)</option>
                    <option value="id_desc">ID (Mayor a Menor)</option>
                    <option value="nombre_asc">Nombre (A - Z)</option>
                    <option value="nombre_desc">Nombre (Z - A)</option>
                </select>
            </div>

            <button type="button" onclick="procesarSolicitud()" class="mt-auto py-4 bg-yellow-600/20 border border-yellow-600/50 text-yellow-400 hover:bg-yellow-600 hover:text-white rounded-lg font-bold transition flex justify-center items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd" />
                </svg>
                Procesar Archivo
            </button>
        </div>

        <div class="w-full lg:w-2/3 border-t lg:border-t-0 lg:border-l border-dark-700 pt-6 lg:pt-0 lg:pl-8 flex flex-col">
            <div class="flex justify-between items-end mb-4">
                <div>
                    <h3 class="text-lg font-bold text-white">Columnas a Exportar</h3>
                    <p class="text-xs text-dark-muted">Selecciona los campos que necesitas en el Excel final.</p>
                </div>
                <button type="button" onclick="toggleColumnasClientes(this)" class="text-xs text-yellow-500 hover:text-yellow-400 font-bold underline transition px-2 py-1 hover:bg-yellow-500/10 rounded">
                    Seleccionar Todas
                </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 overflow-y-auto custom-scroll pr-2 flex-1 max-h-[400px]">
                @foreach(['ID', 'NOMBRE', 'DIRECCION_FISCAL', 'COLONIA_FISCAL', 'MUNICIPIO_FISCAL', 'CP_FISCAL', 'ESTADO_FISCAL', 'PAIS_FISCAL', 'DIRECCION_CONTACTO', 'COLONIA_CONTACTO', 'MUNICIPIO_CONTACTO', 'ESTADO_CONTACTO', 'PAIS_CONTACTO', 'CP_CONTACTO', 'RFC', 'TELEFONO', 'EMAIL', 'LIMITE_CREDITO', 'CREDITO_DISPONIBLE', 'DIAS_CHEQUE_POSTFECHADO', 'DIAS_VENCIMIENTO', 'PARTE_RELACIONAL', 'REGIMEN_FISCAL', 'USO_DE_CFDI', 'GRUPO_DESCUENTO', 'VARIABLE_CONTABLE', 'TAGS', 'TIPO'] as $col)
                <label class="flex items-center space-x-2 bg-dark-900 p-2.5 rounded border border-dark-700 cursor-pointer hover:border-yellow-500/50 transition select-none group">
                    <input type="checkbox" value="{{ $col }}" class="check-col-cliente accent-yellow-500 w-4 h-4" {{ in_array($col, ['ID', 'NOMBRE']) ? 'checked' : '' }}>
                    <span class="text-xs text-gray-400 group-hover:text-gray-200 transition">{{ str_replace('_', ' ', $col) }}</span>
                </label>
                @endforeach
            </div>
        </div>

    </div>
</form>
@endsection

@push('scripts')
@vite(['resources/js/app.js', 'resources/js/aromas/clientes.js'])
<script>
    window.GeliaConfig = {
        routes: {
            generar: "{{ route('aromas.clientes.procesar') }}"
        }
    };
</script>
@endpush