<div id="modalDashboard" class="hidden fixed inset-0 z-50 bg-dark-900/95 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-dark-800 border border-dark-700 rounded-xl shadow-2xl w-full max-w-7xl max-h-[95vh] overflow-y-auto flex flex-col">
        
        <div class="p-6 border-b border-dark-700 flex justify-between items-center sticky top-0 bg-dark-800 z-20 shadow-md">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <span class="material-symbols-outlined mr-3 text-bella-main text-3xl">monitoring</span>
                Análisis Financiero
            </h2>
            <button id="btnCerrarDashboard" class="text-dark-muted hover:text-red-500 material-symbols-outlined text-3xl transition-colors">close</button>
        </div>

        <div class="p-6">
            <div class="bg-dark-900 p-4 rounded-lg border border-dark-700 mb-6 flex flex-wrap gap-4 items-end">
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

                <button id="btnActualizarDashboard" class="bg-bella-main hover:bg-red-700 text-white px-4 py-2 rounded transition-colors text-sm font-semibold shadow-md">
                    Generar Reporte
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700">
                    <p class="text-xs text-dark-muted font-medium">Venta Bruta Total</p>
                    <p class="text-2xl font-bold text-blue-400 mt-1" id="kpi_ventas">$0.00</p>
                </div>
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700">
                    <p class="text-xs text-dark-muted font-medium">Ganancia Neta</p>
                    <p class="text-2xl font-bold text-green-500 mt-1" id="kpi_ganancias">$0.00</p>
                </div>
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700">
                    <p class="text-xs text-dark-muted font-medium">Pérdidas</p>
                    <p class="text-2xl font-bold text-red-500 mt-1" id="kpi_perdidas">$0.00</p>
                </div>
                <div class="bg-dark-900 p-4 rounded-lg border border-dark-700">
                    <p class="text-xs text-dark-muted font-medium">Comisiones Pagadas</p>
                    <p class="text-2xl font-bold text-orange-400 mt-1" id="kpi_comisiones">$0.00</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1 bg-dark-900 p-4 rounded-lg border border-dark-700">
                    <h3 class="text-lg font-medium text-white mb-4 text-center">Gasto por Plataforma</h3>
                    <div class="relative w-full h-64">
                        <canvas id="chartPlataformas"></canvas>
                    </div>
                </div>
                <div class="md:col-span-2 bg-dark-900 p-4 rounded-lg border border-dark-700">
                    <h3 class="text-lg font-medium text-white mb-4 text-center">Venta vs Utilidad Neta</h3>
                    <div class="relative w-full h-64">
                        <canvas id="chartVentasUtilidad"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>