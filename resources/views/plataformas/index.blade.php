{{-- resources/views/plataformas/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Control de Plataformas')

@section('content')
<div class="container mx-auto px-4 py-6">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Control de Plataformas
        </h1>
        <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
            Sincronizar Ventas
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">
                Análisis de Utilidad vs Comisiones
            </h2>
            <div id="platform-chart-container" class="w-full h-64 bg-gray-50 border border-gray-200 flex items-center justify-center">
                <span class="text-gray-400">El espacio para la gráfica está listo</span>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">
                Métricas del Mes
            </h2>
            <div class="space-y-4">
                <div class="border-b pb-2">
                    <p class="text-sm text-gray-500">Utilidad Neta Estimada</p>
                    <p class="text-xl font-bold text-green-600">$0.00</p>
                </div>
                <div class="border-b pb-2">
                    <p class="text-sm text-gray-500">Total Comisiones</p>
                    <p class="text-xl font-bold text-red-500">$0.00</p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection