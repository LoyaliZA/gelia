@extends('layouts.app')
@section('title', 'Bellaroma | Conciliación de Pagos')

@php
$coloresPlataformas = [
'paypal' => '#3b82f6', // Azul
'stripe' => '#8b5cf6', // Morado
'mercado pago' => '#eab308', // Amarillo
'kueskipay' => '#22c55e', // Verde
'open pay' => '#14b8a6', // Teal
'openpay' => '#14b8a6'
];
@endphp

@section('content')
<div class="px-4 py-4 min-h-screen xl:h-[calc(100vh-2rem)] flex flex-col max-w-[1600px] mx-auto xl:overflow-hidden">

    <div class="bg-dark-800 rounded-xl p-4 border border-dark-700 shadow-lg mb-4 flex flex-col lg:flex-row justify-between items-center gap-4 flex-shrink-0">
        <div class="w-full lg:w-auto text-center lg:text-left">
            <h1 class="text-xl font-bold text-white flex items-center justify-center lg:justify-start">
                <span class="material-symbols-outlined mr-3 text-bella-main text-3xl">account_balance</span>
                Conciliación y Retiros
            </h1>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-dark-muted text-[18px]">search</span>
                <input type="text" id="buscadorRetiros" placeholder="Buscar pedido o cliente..." class="w-full bg-dark-900 border border-dark-700 rounded-lg pl-9 pr-3 py-1.5 text-white text-sm outline-none focus:ring-1 focus:ring-bella-main transition-all">
            </div>
            
            <button id="btnOrdenarRetiros" class="text-sm bg-dark-700 hover:bg-dark-600 text-white px-4 py-1.5 rounded-lg transition-colors border border-dark-600 flex items-center justify-center shadow-md w-full sm:w-auto" title="Invertir orden de fechas">
                <span class="material-symbols-outlined mr-2 text-[18px]" id="iconOrden">arrow_downward</span> 
                <span id="textoOrden">Más antiguos</span>
            </button>

            <a href="{{ route('contabilidad.historial') }}" class="text-sm bg-dark-700 hover:bg-dark-600 text-white px-4 py-1.5 rounded-lg transition-colors border border-dark-600 flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined mr-2">history</span> Historial de Pagos
            </a>

            <a href="{{ route('contabilidad.index') }}" class="text-sm bg-dark-700 hover:bg-dark-600 text-white px-4 py-1.5 rounded-lg transition-colors border border-dark-600 flex items-center justify-center shadow-md">
                <span class="material-symbols-outlined mr-2">arrow_back</span> Volver
            </a>
        </div>
    </div>

    <div class="flex flex-col xl:flex-row gap-6 flex-grow xl:overflow-hidden">

        <div class="w-full xl:w-2/3 flex flex-col xl:h-full">

            <div class="flex gap-2 overflow-x-auto pb-2 flex-shrink-0 custom-scrollbar">
                @foreach($datosPlataformas as $index => $data)
                @php
                $color = $coloresPlataformas[strtolower($data['plataforma']->name)] ?? '#94a3b8';
                $pendientes = $data['total_pendientes'];
                @endphp
                <button class="tab-btn px-6 py-2.5 rounded-lg font-bold text-sm transition-all flex items-center whitespace-nowrap gap-2 border shadow-md {{ $index === 0 ? 'activo' : 'inactivo bg-dark-800 border-dark-700 text-dark-muted hover:bg-dark-700' }}"
                    data-target="tab-{{ $data['plataforma']->id }}"
                    style="{{ $index === 0 ? 'background-color: '.$color.'15; border-color: '.$color.'; color: '.$color.';' : '' }}"
                    data-color="{{ $color }}">
                    {{ $data['plataforma']->name }}
                    @if($pendientes > 0)
                    <span class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full count-badge">{{ $pendientes }}</span>
                    @endif
                </button>
                @endforeach
            </div>

            <div id="contenedorTablasScroll" class="bg-dark-800 border border-dark-700 rounded-xl shadow-lg p-3 flex-grow overflow-y-auto custom-scrollbar max-h-[500px] xl:max-h-none">
                @foreach($datosPlataformas as $index => $data)
                @php $color = $coloresPlataformas[strtolower($data['plataforma']->name)] ?? '#94a3b8'; @endphp

                <div id="tab-{{ $data['plataforma']->id }}" class="tab-content {{ $index !== 0 ? 'hidden' : '' }}" data-plat-id="{{ $data['plataforma']->id }}">

                    @forelse($data['grupos'] as $nombreGrupo => $pedidos)
                    <div class="mb-4 border border-dark-600 rounded-lg overflow-hidden grupo-lote" data-grupo-nombre="{{ $nombreGrupo }}">

                        <div class="p-3 flex flex-col sm:flex-row justify-between sm:items-center gap-3 cursor-move" style="background-color: {{ $color }}10; border-bottom: 1px solid {{ $color }}30;">
                            <div>
                                <h3 class="text-white font-bold text-sm flex items-center">
                                    <span class="material-symbols-outlined text-[16px] mr-2 text-dark-muted hidden sm:inline-block">drag_indicator</span>
                                    {{ $nombreGrupo }}
                                </h3>
                                <p class="text-xs text-dark-muted contador-grupo">{{ $pedidos->count() }} operaciones detectadas.</p>
                            </div>
                            <button type="button" class="btn-cargar-grupo text-xs text-white px-3 py-2 rounded transition-colors shadow-sm font-bold flex items-center justify-center w-full sm:w-auto" style="background-color: {{ $color }}90; hover:opacity-80;">
                                <span class="material-symbols-outlined text-[14px] mr-1">checklist</span> Seleccionar
                            </button>
                        </div>

                        <div class="overflow-x-auto min-h-[50px]">
                            <table class="w-full text-left whitespace-nowrap tabla-grupo">
                                <thead class="bg-dark-900/50 text-[10px] uppercase text-dark-muted border-b border-dark-700 select-none">
                                    <tr>
                                        <th class="p-2 text-center w-8">
                                            <input type="checkbox" class="check-grupo-all h-4 w-4 rounded border-dark-600 bg-dark-800 cursor-pointer" style="accent-color: {{ $color }};">
                                        </th>
                                        <th class="p-2 font-bold">Fecha</th>
                                        <th class="p-2 font-bold">Pedido</th>
                                        <th class="p-2 font-bold">Cliente</th>
                                        <th class="p-2 font-bold text-right">Venta</th>
                                        <th class="p-2 font-bold text-right">Retiro Esp.</th>
                                        <th class="p-2 text-center w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="text-xs divide-y divide-dark-700/50 bg-dark-800 dropzone-tbody">
                                    @foreach($pedidos as $pedido)
                                    @php
                                    $esVenta = str_contains(strtolower($pedido->tipo_transaccion), 'venta');
                                    $retiroEsperado = $esVenta ? ($pedido->venta_total - $pedido->comision_plataforma) : -abs($pedido->venta_total + $pedido->comision_plataforma);
                                    @endphp
                                    <tr class="hover:bg-dark-700/50 transition-colors cursor-grab row-draggable {{ !$esVenta ? 'bg-red-500/5' : '' }}" draggable="true" data-filtro="{{ strtolower($pedido->numero_pedido . ' ' . $pedido->cliente_nombre) }}">
                                        <td class="p-2 text-center border-r border-dark-700/50">
                                            <input type="checkbox" value="{{ $pedido->id }}"
                                                data-numpedido="{{ $pedido->numero_pedido }}" data-monto="{{ $pedido->venta_total }}"
                                                data-com="{{ $pedido->comision_plataforma }}"
                                                data-esperado="{{ $retiroEsperado }}"
                                                class="check-pedido h-4 w-4 rounded border-dark-600 bg-dark-800 cursor-pointer"
                                                style="accent-color: {{ $color }};">
                                        </td>
                                        <td class="p-2 text-dark-muted">{{ $pedido->fecha_salida->format('d/m/Y') }}</td>
                                        <td class="p-2">
                                            <span class="font-bold" style="color: {{ $color }}">{{ $pedido->numero_pedido }}</span>
                                            @if(!$esVenta)
                                            <span class="ml-1 text-[9px] bg-red-500/20 text-red-500 px-1 rounded uppercase font-bold">{{ $pedido->tipo_transaccion }}</span>
                                            @endif
                                        </td>
                                        <td class="p-2 text-white truncate max-w-[150px]" title="{{ $pedido->cliente_nombre }}">{{ $pedido->cliente_nombre ?? 'Sin Nombre' }}</td>
                                        <td class="p-2 text-right text-white">${{ number_format($pedido->venta_total, 2) }}</td>
                                        <td class="p-2 text-right font-bold {{ $retiroEsperado >= 0 ? 'text-green-400' : 'text-red-400' }}">${{ number_format($retiroEsperado, 2) }}</td>
                                        <td class="p-2 text-center hidden sm:table-cell">
                                            <span class="material-symbols-outlined text-dark-muted text-[14px] cursor-grab">drag_indicator</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @empty
                    <div class="p-12 text-center text-dark-muted h-full flex flex-col items-center justify-center">
                        <span class="material-symbols-outlined text-5xl mb-3 opacity-50">task_alt</span>
                        <p class="text-lg">Todo al día. No hay retiros pendientes.</p>
                    </div>
                    @endforelse
                </div>
                @endforeach
            </div>
        </div>

        <div class="w-full xl:w-1/3 xl:h-full flex flex-col pb-8 xl:pb-0">
            <div class="bg-dark-800 rounded-xl border border-dark-700 shadow-2xl p-6 flex flex-col h-full">
                
                <h2 class="text-lg font-bold text-white border-b border-dark-700 pb-3 mb-4 flex items-center flex-shrink-0">
                    <span class="material-symbols-outlined mr-2 text-green-500">price_check</span> 
                    Panel de Lote a Procesar
                </h2>
                
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-600 mb-4 flex-shrink-0">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-dark-muted uppercase font-bold">Pedidos Seleccionados</span>
                        <span id="resumen_cantidad" class="text-lg font-black text-white bg-dark-700 px-2 rounded">0</span>
                    </div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-xs text-dark-muted">Retiro Esperado Global:</span>
                        <span id="resumen_esperado" class="text-lg font-bold text-blue-400">$0.00</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 mt-2 border-t border-dark-700">
                        <span class="text-sm font-bold text-white uppercase">Total a Ingresar:</span>
                        <span id="resumen_total_real" class="text-2xl font-black text-green-500">$0.00</span>
                    </div>
                </div>

                <form id="formConfirmarLote" class="flex flex-col flex-grow overflow-hidden">
                    
                    <label class="block text-xs font-bold text-white uppercase mb-2 flex-shrink-0">Desglose Individual</label>
                    
                    <div id="lista_pedidos_confirmar" class="bg-dark-700/30 p-2 rounded-xl border border-dark-600/50 mb-4 overflow-y-auto custom-scrollbar flex-grow space-y-2">
                        <p class="text-xs text-dark-muted text-center italic py-4">Selecciona pedidos para desglosarlos aquí.</p>
                    </div>

                    <div class="mb-4 flex-shrink-0">
                        <label class="block text-xs text-dark-muted mb-1">Fecha de Ingreso a Banco</label>
                        <input type="date" id="input_fecha_banco" required class="w-full bg-dark-900 border border-dark-600 rounded px-3 py-3 text-white text-sm outline-none focus:border-green-500 [color-scheme:dark]" value="{{ date('Y-m-d') }}">
                    </div>

                    <button type="submit" id="btnProcesarLote" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-4 rounded-xl transition-colors shadow-lg shadow-green-600/20 flex justify-center items-center text-lg flex-shrink-0 disabled:opacity-50" disabled>
                        <span class="material-symbols-outlined mr-2">task_alt</span> Aprobar Retiro
                    </button>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    window.RetirosConfig = {
        token: "{{ csrf_token() }}",
        rutas: {
            confirmarLote: "{{ route('contabilidad.confirmar-lote') }}"
        }
    };
</script>
@vite(['resources/js/contabilidad/retiros.js'])
@endpush