@extends('layouts.app')

@section('title', 'WooCommerce Sync | Gelia Hub')

@section('content')
<header class="flex items-center justify-between mb-8 border-b border-dark-700 pb-6">
    <div>
        <h1 class="text-4xl font-extrabold text-white tracking-tight">
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-500 to-indigo-500">WooCommerce</span>
            <span class="text-lg text-dark-muted font-normal ml-2">Sync Hub</span>
        </h1>
        <p class="text-dark-muted mt-1 text-sm font-medium">Actualización masiva de precios e inventario para la tienda en línea.</p>
    </div>
    
    <button onclick="abrirModalPin()" class="flex items-center gap-2 px-5 py-2.5 bg-dark-800 border border-dark-600 hover:border-purple-500 rounded-xl text-white font-bold transition-all shadow-sm hover:shadow-purple-500/20">
        <svg class="w-5 h-5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Ajustar Algoritmo
    </button>
</header>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    
    <div class="lg:col-span-5">
        
        <div class="flex gap-2 mb-6 bg-dark-800 p-1.5 rounded-xl border border-dark-700 shadow-sm">
            <button onclick="cambiarPestanaWoo('diario')" id="tab-diario" type="button" class="flex-1 py-2 text-sm font-bold rounded-lg bg-purple-600 text-white transition-all">Carga Diaria</button>
            <button onclick="cambiarPestanaWoo('sync')" id="tab-sync" type="button" class="flex-1 py-2 text-sm font-bold rounded-lg text-gray-400 hover:text-white transition-all">Catálogo Web</button>
        </div>

        <form id="form-diario" class="bg-dark-800 border border-dark-700 rounded-2xl p-6 shadow-xl sticky top-24 transition-opacity">
            @csrf
            <h2 class="text-xl font-bold text-white mb-2 flex items-center">
                <span class="bg-purple-500 w-2 h-6 rounded mr-2"></span> Generar Precios
            </h2>
            <p class="text-sm text-dark-muted mb-6">Cruza el listado actual con la base de datos de WooCommerce.</p>
            
            <div class="mb-8">
                <x-upload-area id="listado-aromas" name="listado_aromas" title="Listado Aromas (Excel) *" colorTheme="purple" accept=".xlsx" />
            </div>

            <button type="submit" class="w-full py-4 bg-gradient-to-r from-purple-600 to-indigo-700 hover:from-purple-500 hover:to-indigo-600 text-white font-bold rounded-xl shadow-lg transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Ejecutar Algoritmo
            </button>
        </form>

        <form id="form-sync" class="hidden bg-dark-800 border border-dark-700 rounded-2xl p-6 shadow-xl sticky top-24 transition-opacity">
            @csrf
            <h2 class="text-xl font-bold text-white mb-2 flex items-center">
                <span class="bg-blue-500 w-2 h-6 rounded mr-2"></span> Sincronizar BD
            </h2>
            <p class="text-sm text-dark-muted mb-6">Sube el CSV de WooCommerce para actualizar los productos internos.</p>
            
            <div class="mb-8">
                <x-upload-area id="woocommerce-csv" name="woocommerce_csv" title="Exportación Woo (CSV) *" colorTheme="blue" accept=".csv,.txt" />
            </div>

            <button type="submit" class="w-full py-4 bg-dark-700 hover:bg-blue-600 text-white font-bold rounded-xl shadow-lg transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2 border border-dark-600">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Actualizar Catálogo Interno
            </button>
        </form>
    </div>

    <div class="lg:col-span-7">
        <h2 class="text-xl font-bold text-white mb-6 flex items-center">
            <span class="bg-indigo-500 w-2 h-6 rounded mr-2"></span> Archivos Listos para Subir
        </h2>

        <div class="bg-dark-800 border border-dark-700 rounded-2xl shadow-xl overflow-hidden">
            <div class="divide-y divide-dark-700 max-h-[600px] overflow-y-auto custom-scroll">
                
                @if($templatesHoy->isNotEmpty())
                    <div class="sticky top-0 bg-dark-800/95 backdrop-blur-md px-4 py-3 border-b border-dark-700 z-10 flex items-center gap-3">
                        <span class="text-sm font-bold text-white tracking-wide">Procesados Hoy</span>
                    </div>

                    @foreach($templatesHoy as $template)
                    <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-dark-700/60 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-dark-900 rounded-lg text-purple-400 border border-dark-600 shadow-sm">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">{{ $template->nombre_archivo }}</h3>
                                <div class="flex items-center gap-3 mt-1 text-xs text-dark-muted">
                                    <span>{{ $template->created_at->format('h:i A') }}</span><span>•</span><span>{{ $template->tamano_kb }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('woocommerce.descargar', $template->id) }}" class="px-4 py-2 bg-dark-600 hover:bg-purple-600 text-white text-sm font-bold rounded-lg transition">Descargar</a>
                            <button onclick="eliminarTemplateWoo({{ $template->id }})" class="px-4 py-2 bg-dark-600 hover:bg-red-600 text-white text-sm font-bold rounded-lg transition">Borrar</button>
                        </div>
                    </div>
                    @endforeach
                @endif

                @if($templatesHistorial->isNotEmpty())
                    <div class="sticky top-0 bg-dark-800/95 backdrop-blur-md px-4 py-3 border-b border-t border-dark-700 z-10 flex items-center gap-3 mt-2">
                        <span class="text-sm font-bold text-gray-300 tracking-wide">Historial Anterior</span>
                    </div>

                    @foreach($templatesHistorial as $template)
                    <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 hover:bg-dark-700/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-dark-900 rounded-lg text-gray-500 border border-dark-600 shadow-sm">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">{{ $template->nombre_archivo }}</h3>
                                <div class="flex items-center gap-3 mt-1 text-xs text-dark-muted">
                                    <span>{{ $template->created_at->format('d/m/Y - h:i A') }}</span><span>•</span><span>{{ $template->tamano_kb }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('woocommerce.descargar', $template->id) }}" class="px-4 py-2 bg-dark-600 hover:bg-purple-600 text-white text-sm font-bold rounded-lg transition">Descargar</a>
                            <button onclick="eliminarTemplateWoo({{ $template->id }})" class="px-4 py-2 bg-dark-600 hover:bg-red-600 text-white text-sm font-bold rounded-lg transition">Borrar</button>
                        </div>
                    </div>
                    @endforeach
                @endif

                @if($templatesHoy->isEmpty() && $templatesHistorial->isEmpty())
                    <div class="p-8 text-center"><p class="text-dark-muted font-bold">No hay archivos procesados.</p></div>
                @endif
            </div>
        </div>
    </div>
</div>

<div id="modal-pin" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-dark-800 border border-dark-700 rounded-2xl p-6 w-full max-w-sm shadow-2xl transform scale-95 transition-transform">
        <h3 class="text-lg font-bold text-white mb-4 text-center">🔐 Seguridad Requerida</h3>
        <p class="text-sm text-dark-muted text-center mb-6">Ingresa el PIN maestro para modificar los algoritmos de precios.</p>
        <input type="password" id="input-pin" class="w-full bg-dark-900 border border-dark-600 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-purple-500 text-center text-xl tracking-widest mb-6" placeholder="••••" maxlength="4">
        <div class="flex gap-3">
            <button onclick="cerrarModalPin()" class="flex-1 py-3 bg-dark-700 hover:bg-dark-600 text-white font-bold rounded-xl transition">Cancelar</button>
            <button onclick="verificarPin()" class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl transition">Ingresar</button>
        </div>
    </div>
</div>

<div id="modal-config" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-dark-800 border border-purple-500/30 rounded-2xl w-full max-w-4xl shadow-2xl flex flex-col max-h-[90vh]">
        
        <div class="p-6 border-b border-dark-700 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                <span class="bg-purple-500 w-2 h-6 rounded"></span> Algoritmo de Precios
            </h3>
            <button onclick="cerrarModalConfig()" class="text-gray-400 hover:text-white transition">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="p-6 overflow-y-auto custom-scroll flex-1">
            <form id="form-config">
                @csrf
                <div class="mb-8 p-4 bg-dark-900 border border-dark-700 rounded-xl flex items-center justify-between">
                    <div>
                        <label class="block text-white font-bold mb-1">Impuesto al Valor Agregado (IVA)</label>
                        <p class="text-xs text-dark-muted">Valor por el que se dividen los precios para obtener el subtotal.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-purple-400 font-bold">÷</span>
                        <input type="number" step="0.01" name="iva" value="{{ $iva }}" class="w-24 bg-dark-800 border border-dark-600 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 text-center font-bold">
                    </div>
                </div>

                <h4 class="text-white font-bold mb-4">Escalones de Ganancia</h4>
                <div class="border border-dark-700 rounded-xl overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-400 bg-dark-900 uppercase">
                            <tr>
                                <th class="px-4 py-3">Rango de Costo ($)</th>
                                <th class="px-4 py-3 text-center">Multiplicador Rebaja</th>
                                <th class="px-4 py-3 text-center">Multiplicador Normal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-dark-700 bg-dark-800">
                            @foreach($margenes as $margen)
                            <tr class="hover:bg-dark-700/30 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-300">
                                    De ${{ number_format($margen->precio_min, 2) }} a ${{ $margen->precio_max >= 99999 ? 'Infinito' : number_format($margen->precio_max, 2) }}
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-dark-muted text-xs">x</span>
                                        <input type="number" step="0.01" name="margenes[{{ $margen->id }}][rebaja]" value="{{ $margen->multiplicador_rebaja }}" class="w-20 bg-dark-900 border border-dark-600 rounded md px-2 py-1 text-white text-center focus:border-purple-500">
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="text-dark-muted text-xs">x</span>
                                        <input type="number" step="0.01" name="margenes[{{ $margen->id }}][normal]" value="{{ $margen->multiplicador_normal }}" class="w-20 bg-dark-900 border border-dark-600 rounded md px-2 py-1 text-white text-center focus:border-purple-500">
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>

        <div class="p-6 border-t border-dark-700 bg-dark-900/50 flex justify-end gap-4 rounded-b-2xl">
            <button onclick="cerrarModalConfig()" class="px-6 py-2.5 bg-dark-700 hover:bg-dark-600 text-white font-bold rounded-xl transition">Cancelar</button>
            <button onclick="guardarConfiguracion()" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl transition flex items-center gap-2 shadow-lg shadow-purple-500/20">
                Guardar Cambios
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/app.js', 'resources/js/woocommerce.js'])
@endpush