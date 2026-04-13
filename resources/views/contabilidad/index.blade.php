@extends('layouts.app')

@section('title', 'Bellaroma | Dashboard Contable')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">

    <div class="bg-dark-800 rounded-xl p-4 border border-dark-700 shadow-lg mb-8 flex flex-col lg:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-white">Contabilidad <span class="text-bella-main">Bellaroma</span></h1>

            <div class="group relative inline-block cursor-help">
                <span class="material-symbols-outlined text-dark-muted hover:text-white transition-colors">help</span>
                <div class="absolute left-0 bottom-full mb-2 w-72 p-3 bg-dark-900 border border-dark-600 rounded-lg text-xs text-dark-muted opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 shadow-2xl">
                    <p class="font-bold text-white mb-1">Guía del Sistema:</p>
                    <ul class="space-y-1">
                        <li>• <b class="text-bella-main">Carga Lista:</b> Sube el Excel de precios para activar el formulario.</li>
                        <li>• <b class="text-bella-main">Buscador:</b> Filtra registros por número de pedido al instante.</li>
                        <li>• <b class="text-bella-main">Ordenamiento:</b> Clic en los títulos de la tabla para organizar datos.</li>
                        <li>• <b class="text-bella-main">Comisiones:</b> Ajusta los % globales en el icono de engrane.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('contabilidad.index') }}" class="flex bg-dark-900 rounded-lg border border-dark-700 overflow-hidden">
                <select name="mes" class="bg-transparent text-white border-none px-3 py-2 outline-none cursor-pointer text-sm">
                    @foreach(['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'] as $num => $nombre)
                    <option value="{{ $num }}" class="bg-dark-900" {{ $mesActual == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
                <select name="anio" class="bg-transparent text-white border-l border-dark-700 px-3 py-2 outline-none cursor-pointer text-sm">
                    <option value="2025" {{ $anioActual == '2025' ? 'selected' : '' }}>2025</option>
                    <option value="2026" {{ $anioActual == '2026' ? 'selected' : '' }}>2026</option>
                </select>
                <button type="submit" class="bg-bella-main hover:bg-red-700 text-white px-4 py-2 transition-colors material-symbols-outlined text-base">search</button>
            </form>

            <div class="h-8 w-px bg-dark-700 hidden lg:block"></div>

            <button type="button" id="btnToggleMasivo" class="text-sm bg-dark-700 hover:bg-dark-600 text-white px-4 py-2 rounded-lg border border-dark-600 transition-colors flex items-center">
                <span class="material-symbols-outlined mr-2 text-base">cloud_upload</span> Carga Masiva
            </button>

            <a href="{{ route('contabilidad.exportar-reporte', ['mes' => $mesActual, 'anio' => $anioActual]) }}" class="text-sm bg-green-600/20 text-green-400 hover:bg-green-600 hover:text-white px-4 py-2 rounded-lg border border-green-600/50 transition-colors flex items-center">
                <span class="material-symbols-outlined mr-2 text-base">table_view</span> Excel
            </a>

            <button type="button" id="btnAbrirDashboard" class="text-sm bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg border border-blue-600/50 transition-colors flex items-center">
                <span class="material-symbols-outlined mr-2 text-base">monitoring</span> Análisis
            </button>

            <button type="button" onclick="document.getElementById('modalComisiones').showModal()" class="text-sm bg-purple-600/20 text-purple-400 hover:bg-purple-600 hover:text-white px-3 py-2 rounded-lg border border-purple-600/50 transition-colors flex items-center" title="Configurar Comisiones">
                <span class="material-symbols-outlined text-base">settings</span>
            </button>
        </div>
    </div>

    <div id="panelCargaMasiva" class="hidden bg-dark-800 rounded-xl p-6 border border-bella-main/50 shadow-lg mb-8 transition-all">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-xl font-semibold text-white mb-1">Carga Masiva Histórica</h2>
                <p class="text-xs text-dark-muted">Sube los registros pasados siguiendo el formato estricto.</p>
            </div>
            <a href="{{ route('contabilidad.descargar-plantilla') }}" class="flex items-center text-sm bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-lg border border-blue-600/50 transition-colors">
                <span class="material-symbols-outlined mr-2">download</span> Descargar Plantilla
            </a>
        </div>
        <div class="mt-6 flex items-center">
            <input type="file" id="archivo_historico" accept=".xlsx, .csv" class="hidden">
            <label for="archivo_historico" class="cursor-pointer bg-bella-main hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors inline-flex items-center font-bold text-sm">
                <span class="material-symbols-outlined mr-2">publish</span> Subir Excel Llenado
            </label>
            <span id="nombre_archivo_historico" class="ml-4 text-sm text-dark-muted italic"></span>
        </div>
    </div>

    <div class="space-y-6 mb-8">
        <div class="bg-dark-800 rounded-xl p-6 border border-dark-700 shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
                <span class="material-symbols-outlined mr-2 text-bella-main">upload_file</span>
                1. Lista de Precios del Día
            </h2>
            <div class="flex items-center space-x-4">
                <input type="file" id="archivo_resurtido" accept=".xlsx, .csv" class="hidden">
                <label for="archivo_resurtido" class="cursor-pointer bg-dark-700 hover:bg-dark-600 text-white px-4 py-2 rounded-lg border border-dark-600 transition-colors flex items-center shadow-md text-sm">
                    <span class="material-symbols-outlined mr-2 text-base">attach_file</span> Seleccionar Excel
                </label>
                <button type="button" id="btnLimpiarMemoria" class="hidden text-xs text-red-500 hover:text-red-400 font-medium transition-colors underline decoration-red-500/30">
                    Cerrar Sesión (Borrar)
                </button>
                <span id="nombre_archivo_resurtido" class="text-xs text-dark-muted italic">Sin archivo cargado.</span>
            </div>
        </div>

        <div class="bg-dark-800 rounded-xl p-6 border border-dark-700 shadow-lg relative">
            <div id="bloqueo_formulario" class="absolute inset-0 bg-dark-900/80 z-10 flex flex-col justify-center items-center rounded-xl backdrop-blur-sm">
                <span class="material-symbols-outlined text-4xl text-bella-main mb-2">lock</span>
                <p class="text-white font-semibold">Carga la Lista primero</p>
            </div>

            <form id="formPedido" class="space-y-4">
                <h2 class="text-lg font-semibold text-white mb-2 border-b border-dark-700 pb-2">2. Nuevo Registro</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-dark-muted mb-1">Fecha</label>
                        <input type="date" id="fecha_salida" required class="w-full bg-dark-900 border border-dark-700 rounded-lg px-3 py-1.5 text-white text-sm outline-none focus:ring-1 focus:ring-bella-main [color-scheme:dark]">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-dark-muted mb-1">Pedido #</label>
                        <input type="text" id="numero_pedido" required placeholder="Ej: 28098" class="w-full bg-dark-900 border border-dark-700 rounded-lg px-3 py-1.5 text-white text-sm outline-none focus:ring-1 focus:ring-bella-main uppercase">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-dark-muted mb-1">Transacción</label>
                        <select id="tipo_transaccion" class="w-full bg-dark-900 border border-dark-700 rounded-lg px-3 py-1.5 text-white text-sm outline-none focus:ring-1 focus:ring-bella-main">
                            <option value="venta">Venta Normal</option>
                            <option value="reembolso">Reembolso</option>
                            <option value="contracargo">Contracargo</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-dark-muted mb-1">Plataforma</label>
                        <select id="platform_id" required class="w-full bg-dark-900 border border-dark-700 rounded-lg px-3 py-1.5 text-white text-sm outline-none focus:ring-1 focus:ring-bella-main">
                            <option value="">Seleccione...</option>
                            @foreach($platforms as $plat)
                            <option value="{{ $plat->id }}">{{ $plat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-dark-muted mb-1">Venta Total</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1.5 text-dark-muted text-xs">$</span>
                            <input type="text" id="venta_total" required class="input-moneda w-full bg-dark-900 border border-dark-700 rounded-lg pl-6 pr-3 py-1.5 text-white text-sm outline-none focus:ring-1 focus:ring-bella-main">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-dark-muted mb-1">Costo Envío</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1.5 text-dark-muted text-xs">$</span>
                            <input type="text" id="costo_envio" required class="input-moneda w-full bg-dark-900 border border-dark-700 rounded-lg pl-6 pr-3 py-1.5 text-white text-sm outline-none focus:ring-1 focus:ring-bella-main">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-bella-main mb-1">Comisión Real (Opcional)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1.5 text-bella-main text-xs">$</span>
                            <input type="text" id="comision_real" placeholder="0.00" class="input-moneda w-full bg-dark-900 border border-bella-main/30 rounded-lg pl-6 pr-3 py-1.5 text-white text-sm outline-none focus:ring-1 focus:ring-bella-main">
                        </div>
                    </div>
                    <div class="flex items-center pt-5">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="envio_pagado_cliente" class="form-checkbox h-4 w-4 text-bella-main rounded border-dark-700 bg-dark-900">
                            <span class="ml-2 text-sm text-white">Cliente Pagó Envío</span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-dark-700">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-bold text-white uppercase tracking-tighter">Productos Incluidos</h3>
                        <button type="button" id="btnAgregarProducto" class="text-xs bg-dark-700 hover:bg-dark-600 text-white px-3 py-1.5 rounded border border-dark-600 transition-colors uppercase">+ Agregar SKU</button>
                    </div>
                    <div id="contenedor_productos" class="space-y-2 max-h-[250px] overflow-y-auto custom-scrollbar pr-2"></div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="bg-bella-main hover:bg-red-700 text-white font-bold py-2.5 px-8 rounded-lg transition-colors shadow-lg shadow-bella-main/20 text-sm">
                        Guardar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-dark-800 rounded-xl border border-dark-700 shadow-lg overflow-hidden flex flex-col mb-8 w-full">
        <div class="p-4 border-b border-dark-700 bg-dark-800/50 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-white">Historial del Periodo</h2>
            <div class="relative w-64 flex items-center">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-dark-muted text-[18px]">search</span>
                <input type="text" id="inputBuscador" placeholder="Buscar por pedido..." class="w-full bg-dark-900 border border-dark-700 rounded-lg pl-9 pr-3 py-2 text-white text-sm outline-none focus:ring-1 focus:ring-bella-main transition-all">
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-auto flex-grow custom-scrollbar max-h-[600px]">
            <table class="w-full text-left border-collapse whitespace-nowrap" id="tablaPedidos">
                <thead class="sticky top-0 bg-dark-900 shadow-sm z-10">
                    <tr class="text-dark-muted text-[11px] uppercase tracking-widest select-none">
                        <th class="p-4 font-bold cursor-pointer hover:text-white transition-colors th-sort" data-sort="fecha">Fecha <span class="material-symbols-outlined text-[14px] align-middle ml-1">unfold_more</span></th>
                        <th class="p-4 font-bold cursor-pointer hover:text-white transition-colors th-sort" data-sort="plataforma">Plataforma <span class="material-symbols-outlined text-[14px] align-middle ml-1">unfold_more</span></th>
                        <th class="p-4 font-bold cursor-pointer hover:text-white transition-colors th-sort" data-sort="pedido">Pedido <span class="material-symbols-outlined text-[14px] align-middle ml-1">unfold_more</span></th>
                        <th class="p-4 font-bold cursor-pointer hover:text-white transition-colors th-sort" data-sort="tipo">Tipo <span class="material-symbols-outlined text-[14px] align-middle ml-1">unfold_more</span></th>
                        <th class="p-4 font-bold text-right cursor-pointer hover:text-white transition-colors th-sort" data-sort="comision">Comisión <span class="material-symbols-outlined text-[14px] align-middle ml-1">unfold_more</span></th>
                        <th class="p-4 font-bold text-right cursor-pointer hover:text-white transition-colors th-sort" data-sort="venta">Venta <span class="material-symbols-outlined text-[14px] align-middle ml-1">unfold_more</span></th>
                        <th class="p-4 font-bold text-right cursor-pointer hover:text-white transition-colors th-sort" data-sort="utilidad">Utilidad <span class="material-symbols-outlined text-[14px] align-middle ml-1">unfold_more</span></th>
                        <th class="p-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-dark-700">
                    @forelse($pedidos as $pedido)
                    <tr class="hover:bg-dark-700/50 transition-colors registro-fila"
                        data-fecha="{{ $pedido->fecha_salida->format('Y-m-d') }}"
                        data-plataforma="{{ $pedido->platform->name }}"
                        data-pedido="{{ $pedido->numero_pedido }}"
                        data-tipo="{{ $pedido->tipo_transaccion }}"
                        data-comision="{{ $pedido->comision_plataforma }}"
                        data-venta="{{ $pedido->venta_total }}"
                        data-utilidad="{{ $pedido->utilidad_total }}">

                        <td class="p-4 text-white">{{ $pedido->fecha_salida->format('d/m/Y') }}</td>
                        <td class="p-4 text-dark-muted">{{ $pedido->platform->name }}</td>
                        <td class="p-4 font-semibold text-bella-main">{{ $pedido->numero_pedido }}</td>
                        <td class="p-4">
                            @php
                            $esVenta = str_contains(strtolower($pedido->tipo_transaccion), 'venta');
                            $esReembolso = str_contains(strtolower($pedido->tipo_transaccion), 'reembolso');
                            $badgeBg = $esVenta ? 'bg-green-500/10 text-green-400' : ($esReembolso ? 'bg-yellow-500/10 text-yellow-500' : 'bg-red-500/10 text-red-500');
                            $badgeText = $esVenta ? 'Venta Normal' : $pedido->tipo_transaccion;
                            @endphp
                            <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider {{ $badgeBg }}">
                                {{ $badgeText }}
                            </span>
                        </td>
                        <td class="p-4 text-right text-dark-muted">${{ number_format($pedido->comision_plataforma, 2) }}</td>
                        <td class="p-4 text-right text-white font-medium">${{ number_format($pedido->venta_total, 2) }}</td>
                        <td class="p-4 text-right font-bold {{ $pedido->utilidad_total >= 0 ? 'text-green-500' : 'text-red-500' }}">
                            ${{ number_format($pedido->utilidad_total, 2) }}
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex justify-center gap-2 items-center">
                                <button onclick="verDetallesPedido('{{ $pedido->numero_pedido }}', {{ json_encode($pedido->detalles) }})" class="text-blue-500/50 hover:text-blue-400 material-symbols-outlined text-lg transition-colors" title="Ver Productos">visibility</button>
                                @if(!$pedido->bloqueado)
                                <button onclick="abrirModalEdicion({{ $pedido->id }}, '{{ $pedido->numero_pedido }}', '{{ strtolower($pedido->tipo_transaccion) }}', {{ $pedido->platform_id }}, {{ $pedido->venta_total }}, {{ $pedido->costo_envio }}, {{ $pedido->comision_plataforma }}, {{ json_encode($pedido->detalles) }})" class="text-yellow-500/50 hover:text-yellow-400 material-symbols-outlined text-lg transition-colors" title="Editar Valores">edit</button>
                                <button onclick="borrarPedido({{ $pedido->id }})" class="text-red-500/50 hover:text-red-500 material-symbols-outlined text-lg transition-colors" title="Eliminar Registro">delete</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-dark-muted italic">Sin registros en este periodo.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-dark-800 rounded-xl p-6 border border-dark-700 shadow-lg w-full">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <h2 class="text-xl font-semibold text-white">Análisis de Utilidad del Periodo</h2>
            <div class="flex gap-6">
                <div class="text-right">
                    <p class="text-[10px] text-dark-muted uppercase font-bold">Ventas Totales</p>
                    <p class="text-xl font-bold text-white">${{ number_format($pedidos->sum('venta_total'), 2) }}</p>
                </div>
                <div class="text-right border-l border-dark-700 pl-6">
                    <p class="text-[10px] text-dark-muted uppercase font-bold">Utilidad Neta</p>
                    @php $utilidadMes = $pedidos->sum('utilidad_total'); @endphp
                    <p class="text-xl font-bold {{ $utilidadMes >= 0 ? 'text-green-500' : 'text-red-500' }}">
                        ${{ number_format($utilidadMes, 2) }}
                    </p>
                </div>
            </div>
        </div>
        <div class="w-full h-[400px]">
            <canvas id="utilidadChart"></canvas>
        </div>
    </div>

</div>

<dialog id="modalComisiones" class="bg-dark-800 border border-dark-700 rounded-xl shadow-2xl p-6 backdrop:bg-dark-900/80 w-full max-w-sm m-auto">
    <div class="flex justify-between items-center mb-4 border-b border-dark-700 pb-2">
        <h3 class="text-lg font-bold text-white">Comisiones</h3>
        <button onclick="document.getElementById('modalComisiones').close()" class="text-dark-muted hover:text-white material-symbols-outlined transition-colors">close</button>
    </div>
    <form id="formUpdateComisiones" class="space-y-4">
        @foreach($platforms as $plat)
        <div class="flex justify-between items-center bg-dark-900 p-2 rounded border border-dark-700">
            <label class="text-sm text-white">{{ $plat->name }}</label>
            <div class="flex items-center gap-1">
                <input type="number" step="0.01" value="{{ $plat->commission_percent }}"
                    class="input-config-comision w-20 bg-dark-800 border border-dark-700 rounded px-2 py-1.5 text-white text-sm text-right outline-none focus:border-bella-main"
                    data-id="{{ $plat->id }}">
                <span class="text-xs text-dark-muted">%</span>
            </div>
        </div>
        @endforeach
        <button type="submit" class="w-full bg-bella-main hover:bg-red-700 text-white py-2.5 rounded font-bold text-sm mt-4 transition-colors">Guardar Configuración</button>
    </form>
</dialog>

<dialog id="modalDetalles" class="bg-dark-800 border border-dark-700 rounded-xl shadow-2xl p-6 backdrop:bg-dark-900/80 w-full max-w-lg m-auto">
    <div class="flex justify-between items-center mb-4 border-b border-dark-700 pb-2">
        <h3 class="text-lg font-bold text-white">Productos del Pedido: <span id="detalles_num_pedido" class="text-bella-main"></span></h3>
        <button onclick="document.getElementById('modalDetalles').close()" class="text-dark-muted hover:text-white material-symbols-outlined transition-colors">close</button>
    </div>
    <div class="overflow-y-auto max-h-[300px] custom-scrollbar">
        <table class="w-full text-left text-sm">
            <thead class="text-dark-muted border-b border-dark-700">
                <tr>
                    <th class="py-2">SKU</th>
                    <th class="py-2">Producto</th>
                    <th class="py-2 text-center">Pzas</th>
                    <th class="py-2 text-right">Precio Unit.</th>
                </tr>
            </thead>
            <tbody id="tabla_detalles_body" class="text-white divide-y divide-dark-700/50">
                </tbody>
        </table>
    </div>
</dialog>
<dialog id="modalEditar" class="bg-dark-800 border border-dark-700 rounded-xl shadow-2xl p-6 backdrop:bg-dark-900/80 w-full max-w-lg m-auto">
    <div class="flex justify-between items-center mb-4 border-b border-dark-700 pb-2">
        <h3 class="text-lg font-bold text-white">Corregir Pedido: <span id="edit_num_pedido" class="text-bella-main"></span></h3>
        <button onclick="document.getElementById('modalEditar').close()" class="text-dark-muted hover:text-white material-symbols-outlined transition-colors">close</button>
    </div>
    <form id="formEditarPedido" class="space-y-4">
        <input type="hidden" id="edit_id">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-dark-muted mb-1">Transacción</label>
                <select id="edit_tipo" class="w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none">
                    <option value="venta">Venta Normal</option>
                    <option value="reembolso">Reembolso</option>
                    <option value="contracargo">Contracargo</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-dark-muted mb-1">Plataforma</label>
                <select id="edit_plataforma" class="w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none">
                    @foreach($platforms as $plat)
                    <option value="{{ $plat->id }}">{{ $plat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-dark-muted mb-1">Venta Total</label>
                <input type="number" step="0.01" id="edit_venta" class="w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none text-right" required>
            </div>
            <div>
                <label class="block text-xs text-dark-muted mb-1">Costo Envío</label>
                <input type="number" step="0.01" id="edit_envio" class="w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none text-right" required>
            </div>
            <div>
                <label class="block text-xs text-bella-main mb-1">Com. Cobrada</label>
                <input type="number" step="0.01" id="edit_comision" class="w-full bg-dark-900 border border-bella-main/50 rounded px-2 py-1.5 text-white text-sm outline-none text-right" required>
            </div>
        </div>
        
        <div class="mt-4 border-t border-dark-700 pt-3">
            <label class="block text-xs font-bold text-white uppercase mb-2">Ajustar Cantidad de Productos</label>
            <div id="edit_productos_container" class="space-y-2 max-h-[150px] overflow-y-auto custom-scrollbar">
                </div>
            <p class="text-[10px] text-dark-muted mt-1 italic">*El precio unitario original se mantiene y el subtotal se calculará automáticamente.</p>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="bg-yellow-600 hover:bg-yellow-500 text-white font-bold py-2 px-6 rounded transition-colors text-sm">Actualizar Registro</button>
        </div>
    </form>
</dialog>

@include('contabilidad.partials.dashboard')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.ContabilidadConfig = {
        rutas: {
            procesarLista: "{{ route('contabilidad.procesar-lista') }}",
            guardarPedido: "{{ route('contabilidad.guardar-pedido') }}",
            updateComisiones: "{{ route('contabilidad.actualizar-comisiones') }}",
            actualizarPedidoBase: "{{ url('/contabilidad/actualizar-pedido') }}",
            // NUEVA RUTA PARA EL DASHBOARD
            dashboardData: "{{ url('/contabilidad/dashboard-data') }}" 
        },
        graficaData: @json($datosGrafica),
        token: "{{ csrf_token() }}"
    };
</script>
@vite(['resources/js/contabilidad.js'])
@endpush