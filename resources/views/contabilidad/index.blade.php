@extends('layouts.app')

@section('title', 'Bellaroma | Contabilidad y Utilidades')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">

    <div class="bg-dark-800 rounded-xl p-4 border border-dark-700 shadow-lg mb-8 flex flex-col lg:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Contabilidad <span class="text-bella-main">Bellaroma</span></h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('contabilidad.index') }}" class="flex bg-dark-900 rounded-lg border border-dark-700">
                <select name="mes" class="bg-transparent text-white border-none px-3 py-2 outline-none cursor-pointer text-sm">
                    @foreach(['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'] as $num => $nombre)
                    <option value="{{ $num }}" class="bg-dark-900" {{ $mesActual == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>

                <input type="date" id="fecha_salida" required class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2 text-white outline-none focus:ring-2 focus:ring-bella-main [color-scheme:dark]">

                <select name="anio" class="bg-transparent text-white border-l border-dark-700 px-3 py-2 outline-none cursor-pointer text-sm">
                    <option value="2025" {{ $anioActual == '2025' ? 'selected' : '' }}>2025</option>
                    <option value="2026" {{ $anioActual == '2026' ? 'selected' : '' }}>2026</option>
                </select>
                <button type="submit" class="bg-bella-main hover:bg-red-700 text-white px-3 py-2 rounded-r-lg transition-colors material-symbols-outlined text-base">search</button>
            </form>

            <div class="h-8 w-px bg-dark-700 hidden lg:block"></div>

            <button type="button" id="btnToggleMasivo" class="text-sm bg-dark-700 hover:bg-dark-600 text-white px-4 py-2 rounded-lg border border-dark-600 transition-colors flex items-center">
                <span class="material-symbols-outlined mr-2 text-base">cloud_upload</span> Carga Masiva
            </button>

            <a href="{{ route('contabilidad.exportar-reporte', ['mes' => $mesActual, 'anio' => $anioActual]) }}" class="text-sm bg-green-600/20 text-green-400 hover:bg-green-600 hover:text-white px-4 py-2 rounded-lg border border-green-600/50 transition-colors flex items-center">
                <span class="material-symbols-outlined mr-2 text-base">table_view</span> Reporte Excel
            </a>

            <button type="button" id="btnAbrirDashboard" class="text-sm bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg border border-blue-600/50 transition-colors flex items-center">
                <span class="material-symbols-outlined mr-2 text-base">monitoring</span> Dashboard
            </button>
        </div>
    </div>

    <div id="panelCargaMasiva" class="hidden bg-dark-800 rounded-xl p-6 border border-bella-main/50 shadow-lg mb-8 shadow-bella-main/10 transition-all">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-xl font-semibold text-white mb-1">Carga Masiva de Pedidos Anteriores</h2>
                <p class="text-xs text-dark-muted">Descombina las filas en Excel. GELIA agrupará automáticamente los SKUs que compartan el mismo Número de Pedido.</p>
            </div>
            <a href="{{ route('contabilidad.descargar-plantilla') }}" class="flex items-center text-sm bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg transition-colors border border-blue-600/50">
                <span class="material-symbols-outlined mr-2">download</span> Descargar Plantilla
            </a>
        </div>
        <div class="mt-6 flex items-center space-x-4">
            <input type="file" id="archivo_historico" accept=".xlsx, .csv" class="hidden">
            <label for="archivo_historico" class="cursor-pointer bg-bella-main hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors flex items-center font-bold">
                <span class="material-symbols-outlined mr-2">publish</span> Subir Plantilla Llenada
            </label>
            <span id="nombre_archivo_historico" class="text-sm text-dark-muted italic"></span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-dark-800 rounded-xl p-6 border border-dark-700 shadow-lg">
                <h2 class="text-xl font-semibold text-white mb-4 flex items-center">
                    <span class="material-symbols-outlined mr-2 text-bella-main">upload_file</span>
                    1. Cargar Lista del Día
                </h2>
                <div class="flex items-center space-x-4">
                    <input type="file" id="archivo_resurtido" accept=".xlsx, .csv" class="hidden">
                    <label for="archivo_resurtido" class="cursor-pointer bg-dark-700 hover:bg-dark-600 text-white px-4 py-2 rounded-lg border border-dark-600 transition-colors flex items-center shadow-md">
                        <span class="material-symbols-outlined mr-2 text-sm">attach_file</span> Seleccionar Excel
                    </label>
                    <button type="button" id="btnLimpiarMemoria" class="hidden text-sm text-red-500 hover:text-red-400 font-medium transition-colors underline decoration-red-500/30">
                        Cerrar Día (Borrar Lista)
                    </button>
                    <span id="nombre_archivo_resurtido" class="text-sm text-dark-muted italic">Ningún archivo cargado.</span>
                </div>
            </div>

            <div class="bg-dark-800 rounded-xl p-6 border border-dark-700 shadow-lg relative">
                <div id="bloqueo_formulario" class="absolute inset-0 bg-dark-900/80 z-10 flex flex-col justify-center items-center rounded-xl backdrop-blur-sm">
                    <span class="material-symbols-outlined text-4xl text-bella-main mb-2">lock</span>
                    <p class="text-white font-semibold">Carga la Lista de Resurtido primero</p>
                </div>

                <form id="formPedido" class="space-y-6">
                    <h2 class="text-xl font-semibold text-white mb-4 border-b border-dark-700 pb-2">2. Datos del Pedido</h2>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-dark-muted mb-1 flex justify-between items-center">
                                Fecha <button type="button" id="btnFechaHoy" class="text-xs bg-dark-700 hover:bg-dark-600 text-white px-2 py-0.5 rounded">Hoy</button>
                            </label>
                            <input type="date" id="fecha_salida" required class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2 text-white outline-none focus:ring-2 focus:ring-bella-main [color-scheme:dark]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark-muted mb-1">Pedido</label>
                            <input type="text" id="numero_pedido" required placeholder="28098" class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2 text-white outline-none focus:ring-2 focus:ring-bella-main uppercase">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-dark-muted mb-1">Tipo de Registro</label>
                            <select id="tipo_transaccion" required class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2 text-white outline-none focus:ring-2 focus:ring-bella-main">
                                <option value="venta">Venta Normal</option>
                                <option value="reembolso">Reembolso</option>
                                <option value="contracargo">Contracargo</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-dark-muted mb-1">Plataforma</label>
                            <select id="platform_id" required class="w-full bg-dark-900 border border-dark-700 rounded-lg px-4 py-2 text-white outline-none focus:ring-2 focus:ring-bella-main">
                                <option value="">Selecciona...</option>
                                @foreach($platforms as $plat)
                                <option value="{{ $plat->id }}">{{ $plat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-dark-muted mb-1">Venta Total</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-dark-muted">$</span>
                                <input type="text" id="venta_total" required placeholder="0.00" class="input-moneda w-full bg-dark-900 border border-dark-700 rounded-lg pl-8 pr-4 py-2 text-white outline-none focus:ring-2 focus:ring-bella-main">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-dark-muted mb-1">Costo Envío</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-dark-muted">$</span>
                                <input type="text" id="costo_envio" required placeholder="0.00" class="input-moneda w-full bg-dark-900 border border-dark-700 rounded-lg pl-8 pr-4 py-2 text-white outline-none focus:ring-2 focus:ring-bella-main">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-bella-main mb-1">Com. Factura <span class="text-xs text-dark-muted">(Opc.)</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-bella-main">$</span>
                                <input type="text" id="comision_real" placeholder="0.00" class="input-moneda w-full bg-dark-900 border border-bella-main/50 rounded-lg pl-8 pr-4 py-2 text-white outline-none focus:ring-1 focus:ring-bella-main">
                            </div>
                        </div>
                        <div class="flex items-center mt-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" id="envio_pagado_cliente" class="form-checkbox h-5 w-5 text-bella-main rounded border-dark-700 bg-dark-900">
                                <span class="ml-2 text-sm text-white">Cliente pagó envío</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-dark-700">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-white">Productos (SKUs)</h3>
                            <button type="button" id="btnAgregarProducto" class="text-sm bg-dark-700 hover:bg-dark-600 text-white px-3 py-1 rounded border border-dark-600 transition-colors">+ Añadir Fila</button>
                        </div>
                        <div id="contenedor_productos" class="space-y-2"></div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="bg-bella-main hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-colors shadow-lg shadow-bella-main/20">
                            Guardar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-dark-800 rounded-xl p-6 border border-dark-700 shadow-lg flex flex-col">
            <h2 class="text-xl font-semibold text-white mb-4">Utilidad del Periodo</h2>
            <div class="flex-grow relative w-full" style="min-height: 300px;">
                <canvas id="utilidadChart"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-4 border-t border-dark-700 pt-4">
                <div>
                    <p class="text-xs text-dark-muted">Total Ventas</p>
                    <p class="text-lg font-bold text-white">${{ number_format($pedidos->sum('venta_total'), 2) }}</p>
                </div>
                <div>
                    <p class="text-xs text-dark-muted">Utilidad Neta</p>
                    @php $utilidadMes = $pedidos->sum('utilidad_total'); @endphp
                    <p class="text-lg font-bold {{ $utilidadMes >= 0 ? 'text-green-500' : 'text-red-500' }}">
                        ${{ number_format($utilidadMes, 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-dark-800 rounded-xl border border-dark-700 shadow-lg overflow-hidden flex flex-col">
        <div class="p-6 border-b border-dark-700 bg-dark-800/50 flex-shrink-0">
            <h2 class="text-xl font-semibold text-white">Registros del Periodo Seleccionado</h2>
        </div>

        <div class="overflow-y-auto max-h-[500px] custom-scrollbar">
            <table class="w-full text-left border-collapse relative">
                <thead class="sticky top-0 bg-dark-900 shadow-md z-10">
                    <tr class="text-dark-muted text-xs uppercase tracking-wider">
                        <th class="p-4 font-medium">Fecha</th>
                        <th class="p-4 font-medium">Pedido</th>
                        <th class="p-4 font-medium">Plataforma</th>
                        <th class="p-4 font-medium">Productos</th>
                        <th class="p-4 font-medium text-right">Comisión</th>
                        <th class="p-4 font-medium text-right">Venta Total</th>
                        <th class="p-4 font-medium text-right">Utilidad</th>
                        <th class="p-4 font-medium text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-dark-700">
                    @forelse($pedidos as $pedido)
                    <tr class="hover:bg-dark-700/50 transition-colors {{ $pedido->bloqueado ? 'opacity-75 bg-dark-900/50' : '' }}">
                        <td class="p-4 text-white">{{ $pedido->fecha_salida->format('d/m/Y') }}</td>
                        <td class="p-4 font-semibold">
                            <span class="text-bella-main">{{ $pedido->numero_pedido }}</span>
                            @if($pedido->tipo_transaccion === 'reembolso')
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-500/20 text-yellow-500 border border-yellow-500/30">Reembolso</span>
                            @elseif($pedido->tipo_transaccion === 'contracargo')
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-500/20 text-red-500 border border-red-500/30">Contracargo</span>
                            @endif
                        </td>
                        <td class="p-4 text-dark-muted">{{ $pedido->platform->name }}</td>
                        <td class="p-4">
                            <div class="max-w-xs truncate text-dark-muted" title="{{ implode(', ', $pedido->detalles->pluck('nombre_producto')->toArray()) }}">
                                {{ $pedido->detalles->sum('piezas') }} pzas ({{ $pedido->detalles->count() }} SKUs)
                            </div>
                        </td>
                        <td class="p-4 text-right text-dark-muted">${{ number_format($pedido->comision_plataforma, 2) }}</td>
                        <td class="p-4 text-right text-white font-medium">${{ number_format($pedido->venta_total, 2) }}</td>
                        <td class="p-4 text-right font-bold {{ $pedido->utilidad_total >= 0 ? 'text-green-500' : 'text-red-500' }}">
                            ${{ number_format($pedido->utilidad_total, 2) }}
                        </td>
                        <td class="p-4 text-center">
                            @if(!$pedido->bloqueado)
                            <button onclick="borrarPedido({{ $pedido->id }})" class="text-red-500 hover:text-red-400 material-symbols-outlined text-xl transition-colors">delete_forever</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-dark-muted">No hay pedidos registrados en este periodo.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@include('contabilidad.partials.dashboard')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@php
    $datosGrafica = $pedidos->groupBy(function($date) {
        return \Carbon\Carbon::parse($date->fecha_salida)->format('d/M');
    })->map(function ($row) {
        return [
            'utilidad' => $row->sum('utilidad_total'),
            'venta' => $row->sum('venta_total')
        ];
    });
@endphp

<script>
    window.ContabilidadConfig = {
        rutas: {
            procesarLista: "{{ route('contabilidad.procesar-lista') }}",
            guardarPedido: "{{ route('contabilidad.guardar-pedido') }}"
        },
        graficaData: @json($datosGrafica),
        comisionesPlataforma: @json($comisionesPorPlataforma),
        token: "{{ csrf_token() }}"
    };
</script>

@vite(['resources/js/contabilidad.js'])
@endpush