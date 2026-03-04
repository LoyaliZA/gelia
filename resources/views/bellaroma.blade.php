@extends('layouts.app')

@section('title', 'Bellaroma | Gelia Hub')

@section('content')
<header class="flex items-center justify-between mb-8 border-b border-dark-700 pb-6">
    <div>
        <h1 class="text-4xl font-extrabold text-white tracking-tight">
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-bella-main to-pink-500">Bellaroma</span>
            <span class="text-lg text-dark-muted font-normal ml-2">Gelia Hub</span>
        </h1>
        <p class="text-dark-muted mt-1 text-sm font-medium">Generador de plantillas de pedido y ofuscación de inventario.</p>
    </div>
</header>

<div class="max-w-4xl mx-auto">
    <form id="form-bellaroma" enctype="multipart/form-data" class="bg-dark-800 border border-dark-700 rounded-2xl p-8 shadow-xl">
        @csrf
        
        <h2 class="text-xl font-bold text-white mb-6 flex items-center">
            <span class="bg-bella-main w-2 h-6 rounded mr-2"></span> Archivos Requeridos
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <x-upload-area 
                id="existencias" 
                name="existencias" 
                title="1. Existencias *" 
                colorTheme="bella" 
                accept=".xlsx,.csv"
                instructions="<span class='text-bella-main font-bold'>Ruta:</span> Almacenes > Inventarios<br><span class='text-bella-main font-bold'>Filtros:</span> CEDIS o TIENDA<br><span class='text-bella-main font-bold'>Opciones:</span> Exportar en CSV." 
            />

            <x-upload-area 
                id="precios" 
                name="precios" 
                title="2. Precios *" 
                colorTheme="green" 
                accept=".xlsx,.csv"
                instructions="<span class='text-emerald-400 font-bold'>Ruta:</span> Almacen > Productos<br><span class='text-emerald-400 font-bold'>Opciones:</span> Exportar lista de precios en Excel o CSV." 
            />
        </div>

        <div class="border-t border-dark-700 pt-8 flex justify-end">
            <button type="submit" class="w-full md:w-auto px-8 py-4 bg-gradient-to-r from-bella-main to-rose-700 hover:from-rose-600 hover:to-rose-800 text-white font-bold rounded-xl shadow-lg transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                Generar Plantilla
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@vite(['resources/js/app.js', 'resources/js/bellaroma.js'])
@endpush