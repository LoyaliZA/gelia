<div id="modalDashboard" class="hidden fixed inset-0 z-50 bg-dark-900/95 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-dark-800 border border-dark-700 rounded-xl shadow-2xl w-full max-w-7xl max-h-[95vh] overflow-y-auto flex flex-col relative" id="pdf_container">

        <div id="pdf_header" class="hidden p-8 border-b border-gray-300 bg-white text-black flex items-center justify-between">
            <div class="flex items-center gap-4">
                <img src="/assets/BELLAROMA-LOGOTIPO-04.png" alt="Bellaroma Logo" class="h-16 object-contain">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Reporte Financiero Contable</h1>
                    <p class="text-sm text-gray-500" id="pdf_rango_fechas">Periodo de análisis</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm font-bold text-gray-800">G.E.L.I.A. System</p>
                <p class="text-xs text-gray-500">Generado el: {{ date('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="p-6 border-b border-dark-700 flex justify-between items-center sticky top-0 bg-dark-800 z-20 shadow-md no-print">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <span class="material-symbols-outlined mr-3 text-bella-main text-3xl">monitoring</span>
                Análisis Financiero
            </h2>
            <button id="btnCerrarDashboard" class="text-dark-muted hover:text-red-500 material-symbols-outlined text-3xl transition-colors">close</button>
        </div>

        <div class="p-6">
            <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 mb-6 flex flex-wrap gap-4 items-end no-print">
                <div>
                    <label class="block text-xs text-dark-muted mb-1">Filtrar por</label>
                    <select id="dash_filtro_tipo" class="bg-dark-800 border border-dark-600 rounded text-white px-3 py-2 outline-none text-sm">
                        <option value="mes" class="bg-dark-900">Mes Específico</option>
                        <option value="dia" class="bg-dark-900">Día Específico</option>
                        <option value="anio" class="bg-dark-900">Todo el Año</option>
                        <option value="custom" class="bg-dark-900">Rango Personalizado</option>
                    </select>
                </div>
                <div id="filtro_mes_container" class="flex gap-2">
                    <select id="dash_mes" class="bg-dark-800 border border-dark-600 rounded text-white px-3 py-2 outline-none text-sm">
                        @foreach(['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'] as $num => $nombre)
                        <option value="{{ $num }}" class="bg-dark-900" {{ date('m') == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <select id="dash_anio" class="bg-dark-800 border border-dark-600 rounded text-white px-3 py-2 outline-none text-sm">
                        <option value="2025" class="bg-dark-900">2025</option>
                        <option value="2026" class="bg-dark-900" selected>2026</option>
                    </select>
                </div>
                <div id="filtro_dia_container" class="hidden">
                    <input type="date" id="dash_fecha" class="bg-dark-800 border border-dark-600 rounded text-white px-3 py-1.5 outline-none text-sm [color-scheme:dark]" value="{{ date('Y-m-d') }}">
                </div>
                <div id="filtro_custom_container" class="hidden flex gap-2 items-center">
                    <input type="date" id="dash_inicio" class="bg-dark-800 border border-dark-600 rounded text-white px-3 py-1.5 outline-none text-sm [color-scheme:dark]">
                    <span class="text-dark-muted">a</span>
                    <input type="date" id="dash_fin" class="bg-dark-800 border border-dark-600 rounded text-white px-3 py-1.5 outline-none text-sm [color-scheme:dark]">
                </div>

                <div class="flex gap-2 ml-auto">
                    <button id="btnActualizarDashboard" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded transition-colors text-sm font-semibold shadow-md flex items-center">
                        <span class="material-symbols-outlined text-sm mr-2">refresh</span> Consultar
                    </button>

                    <a href="{{ route('contabilidad.exportar-reporte', ['mes' => $mesActual, 'anio' => $anioActual]) }}" class="text-sm bg-green-600/20 text-green-400 hover:bg-green-600 hover:text-white px-4 py-2 rounded-lg border border-green-600/50 transition-colors flex items-center">
                        <span class="material-symbols-outlined mr-2 text-base">table_view</span> Excel
                    </a>

                    <button id="btnExportarPDF" class="bg-bella-main hover:bg-red-700 text-white px-4 py-2 rounded transition-colors text-sm font-semibold shadow-md flex items-center">
                        <span class="material-symbols-outlined text-sm mr-2">picture_as_pdf</span> PDF
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 border-l-4 border-l-blue-500">
                    <p class="text-[10px] uppercase text-dark-muted font-bold">Venta Bruta Total</p>
                    <p class="text-xl font-bold text-blue-400 mt-1" id="kpi_ventas">$0.00</p>
                </div>
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 border-l-4 border-l-indigo-500">
                    <p class="text-[10px] uppercase text-dark-muted font-bold" title="Suma de precios de lista x piezas">Notas AE</p>
                    <p class="text-xl font-bold text-indigo-400 mt-1" id="kpi_notas_ae">$0.00</p>
                </div>
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 border-l-4 border-l-green-500">
                    <p class="text-[10px] uppercase text-dark-muted font-bold">Ganancia Neta</p>
                    <p class="text-xl font-bold text-green-500 mt-1" id="kpi_ganancias">$0.00</p>
                </div>
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 border-l-4 border-l-teal-500">
                    <p class="text-[10px] uppercase text-dark-muted font-bold">Margen de Utilidad</p>
                    <p class="text-xl font-bold text-teal-400 mt-1" id="kpi_margen">0.00%</p>
                </div>

                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 border-l-4 border-l-red-500">
                    <p class="text-[10px] uppercase text-dark-muted font-bold">Pérdidas</p>
                    <p class="text-xl font-bold text-red-500 mt-1" id="kpi_perdidas">$0.00</p>
                </div>
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 border-l-4 border-l-orange-500">
                    <p class="text-[10px] uppercase text-dark-muted font-bold">Comisiones Pagadas</p>
                    <p class="text-xl font-bold text-orange-400 mt-1" id="kpi_comisiones">$0.00</p>
                </div>
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 border-l-4 border-l-purple-500">
                    <p class="text-[10px] uppercase text-dark-muted font-bold">Envíos (Costo Empresa)</p>
                    <p class="text-xl font-bold text-purple-400 mt-1" id="kpi_envios_empresa">$0.00</p>
                </div>
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 border-l-4 border-l-gray-500">
                    <p class="text-[10px] uppercase text-gray-500 font-bold">Envíos (Pagó Cliente)</p>
                    <p class="text-lg font-bold text-gray-400 mt-1" id="kpi_envios_clientes">0 ped. <span class="text-sm font-normal italic">(Sin impacto)</span></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1 bg-dark-900 p-4 rounded-lg border border-dark-700">
                    <h3 class="text-sm font-bold text-white mb-4 text-center uppercase tracking-wide">Comisiones por Plataforma</h3>
                    <div class="relative w-full h-64">
                        <canvas id="chartPlataformas"></canvas>
                    </div>
                </div>
                <div class="md:col-span-2 bg-dark-900 p-4 rounded-lg border border-dark-700">
                    <h3 class="text-sm font-bold text-white mb-4 text-center uppercase tracking-wide">Línea de Tiempo: Venta vs Utilidad</h3>
                    <div class="relative w-full h-64">
                        <canvas id="chartVentasUtilidad"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>