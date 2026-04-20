@extends('layouts.app')
@section('content')
<div class="container mx-auto px-4 py-6 max-w-6xl">
    <div class="flex justify-between items-center mb-6 bg-dark-800 p-4 rounded-xl border border-dark-700">
        <h1 class="text-xl font-bold text-white flex items-center">
            <span class="material-symbols-outlined mr-2 text-green-500">history_edu</span>
            Auditoría de Pagos Confirmados
        </h1>
        <a href="{{ route('contabilidad.retiros') }}" class="text-sm text-dark-muted hover:text-white flex items-center">
            <span class="material-symbols-outlined mr-1">arrow_back</span> Volver
        </a>
    </div>

    <div class="bg-dark-800 rounded-xl p-4 border border-dark-700 shadow-lg mb-6">
        <form method="GET" action="{{ route('contabilidad.historial') }}" class="flex flex-wrap items-center gap-4">
            <div class="flex bg-dark-900 rounded-lg border border-dark-700 overflow-hidden shadow-md">
                <select name="mes" class="bg-transparent text-white border-none px-3 py-2 outline-none text-sm cursor-pointer">
                    @foreach(['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'] as $num => $nombre)
                    <option value="{{ $num }}" class="bg-dark-900" {{ $mesActual == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
                <select name="anio" class="bg-transparent text-white border-l border-dark-700 px-3 py-2 outline-none text-sm cursor-pointer">
                    <option value="2025" {{ $anioActual == '2025' ? 'selected' : '' }}>2025</option>
                    <option value="2026" {{ $anioActual == '2026' ? 'selected' : '' }}>2026</option>
                </select>
            </div>

            <div class="flex bg-dark-900 rounded-lg border border-dark-700 overflow-hidden shadow-md">
                <span class="bg-dark-700 px-3 py-2 text-dark-muted material-symbols-outlined text-base">payments</span>
                <select name="platform_id" class="bg-transparent text-white px-3 py-2 outline-none text-sm cursor-pointer min-w-[150px]">
                    <option value="todas" class="bg-dark-900">Todas las plataformas</option>
                    @foreach($platforms as $plat)
                    <option value="{{ $plat->id }}" class="bg-dark-900" {{ request('platform_id') == $plat->id ? 'selected' : '' }}>{{ $plat->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="bg-bella-main hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors font-bold text-sm shadow-lg shadow-bella-main/20 flex items-center">
                <span class="material-symbols-outlined mr-2 text-base">filter_list</span> Aplicar Filtros
            </button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse($lotes as $lote)
        <div class="bg-dark-800 rounded-xl border border-dark-700 overflow-hidden shadow-lg">
            <div class="p-4 bg-dark-900/50 flex justify-between items-center border-b border-dark-700">
                <div>
                    <span class="text-[10px] uppercase text-dark-muted font-bold">Lote de Pago #{{ $lote->id }}</span>
                    <h3 class="text-white font-bold">{{ $lote->platform->name }} - {{ $lote->fecha_deposito_real->format('d/m/Y') }}</h3>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-dark-muted uppercase">Total Real en Banco</p>
                    <p class="text-lg font-black text-green-500">${{ number_format($lote->monto_real_banco, 2) }}</p>
                </div>
            </div>

            <div class="p-4">
                <table class="w-full text-xs text-left">
                    <thead class="text-dark-muted border-b border-dark-700">
                        <tr>
                            <th class="py-2">Pedido</th>
                            <th class="py-2">Cliente</th>
                            <th class="py-2 text-right">Venta</th>
                            <th class="py-2 text-right">Com. Plataforma</th>
                            <th class="py-2 text-right">Com. Banco</th>
                            <th class="py-2 text-right">Utilidad Neta</th>
                            <th class="py-2 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-700/50">
                        @foreach($lote->pedidos as $pedido)
                        <tr class="text-white hover:bg-dark-700/20">
                            <td class="py-3 font-bold text-bella-main">{{ $pedido->numero_pedido }}</td>
                            <td class="py-3">{{ $pedido->cliente_nombre }}</td>
                            <td class="py-3 text-right">${{ number_format($pedido->venta_total, 2) }}</td>
                            <td class="py-3 text-right text-red-400">-${{ number_format($pedido->comision_plataforma, 2) }}</td>
                            <td class="py-3 text-right text-orange-400">-${{ number_format($pedido->comision_transferencia, 2) }}</td>
                            <td class="py-3 text-right font-bold text-green-400">${{ number_format($pedido->utilidad_total, 2) }}</td>
                            <td class="py-3 text-center">
                                <button onclick="abrirModalEmergencia({{ $pedido->id }}, '{{ $pedido->numero_pedido }}', {{ $pedido->venta_total }}, {{ $pedido->venta_total - $pedido->comision_plataforma - $pedido->comision_transferencia }})"
                                    class="text-dark-muted hover:text-yellow-500 transition-colors material-symbols-outlined text-sm">
                                    edit_note
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @empty
        <div class="p-8 text-center bg-dark-800 rounded-xl border border-dark-700 shadow-lg">
            <span class="material-symbols-outlined text-4xl text-dark-muted mb-2">inbox</span>
            <p class="text-white font-semibold">No se encontraron pagos confirmados.</p>
            <p class="text-sm text-dark-muted">Intenta cambiar los filtros de búsqueda.</p>
        </div>
        @endforelse
    </div>
</div>

<dialog id="modalEmergencia" class="bg-dark-800 border border-yellow-600/50 rounded-xl shadow-2xl p-6 backdrop:bg-dark-900/90 w-full max-w-md m-auto">
    <div class="flex items-center text-yellow-500 mb-4 border-b border-dark-700 pb-2">
        <span class="material-symbols-outlined mr-2">warning</span>
        <h3 class="text-lg font-bold">Edición de Emergencia: <span id="em_pedido"></span></h3>
    </div>
    <form id="formEmergencia" class="space-y-4">
        <input type="hidden" id="em_id">
        <div>
            <label class="block text-xs text-dark-muted mb-1">Monto Venta Total ($)</label>
            <input type="number" step="0.01" id="em_venta" class="w-full bg-dark-900 border border-dark-600 rounded px-3 py-2 text-white outline-none focus:border-yellow-500">
        </div>
        <div>
            <label class="block text-xs text-dark-muted mb-1">Monto Real recibido en Banco ($)</label>
            <input type="number" step="0.01" id="em_monto_banco" class="w-full bg-dark-900 border border-dark-600 rounded px-3 py-2 text-white outline-none focus:border-yellow-500 font-bold">
        </div>
        <div class="bg-yellow-500/5 p-3 rounded-lg border border-yellow-500/20">
            <label class="block text-xs text-yellow-500 font-bold mb-1">Motivo del Cambio (Log de Auditoría)</label>
            <textarea id="em_motivo" required placeholder="Explica por qué se corrige este registro ya autorizado..." class="w-full bg-dark-900 border border-dark-700 rounded p-2 text-xs text-white h-20 outline-none"></textarea>
        </div>
        <div>
            <label class="block text-xs text-red-400 font-bold mb-1">Contraseña de Seguridad</label>
            <input type="password" id="em_pass" required class="w-full bg-dark-900 border border-red-500/50 rounded px-3 py-2 text-white text-sm outline-none">
        </div>
        <div class="flex gap-2 pt-2">
            <button type="button" onclick="document.getElementById('modalEmergencia').close()" class="flex-1 bg-dark-700 text-white py-2 rounded text-sm">Cancelar</button>
            <button type="submit" class="flex-[2] bg-yellow-600 hover:bg-yellow-500 text-white font-bold py-2 rounded text-sm shadow-lg shadow-yellow-600/20">Autorizar y Registrar Cambio</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
    window.ContabilidadConfig = {
        token: "{{ csrf_token() }}"
    };
</script>
@vite(['resources/js/contabilidad.js']) @endpush