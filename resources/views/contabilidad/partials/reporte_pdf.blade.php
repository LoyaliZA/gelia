<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero Bellaroma</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #333; font-size: 11px; }
        .header { width: 100%; border-bottom: 2px solid #1e293b; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { width: 140px; float: left; }
        .info-header { float: right; text-align: right; }
        .clear { clear: both; }
        
        /* Sistema de 4 Columnas para KPIs - Optimizado para DomPDF */
        .kpi-container { width: 100%; margin-bottom: 5px; display: block; }
        .kpi-box { 
            float: left; 
            width: 23%; /* 4 cajas del 23% = 92% */
            margin-right: 2.66%; /* 3 espacios de 2.66% = 7.98% (Total 99.98%) */
            margin-bottom: 15px; 
            padding: 12px 5px; 
            background-color: #f8fafc; 
            border: 1px solid #cbd5e1; 
            box-sizing: border-box; 
            text-align: center; 
        }
        .kpi-box.last { margin-right: 0; } /* La 4ta y 8va caja no tienen margen derecho */
        
        .kpi-title { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 6px; }
        .kpi-value { font-size: 13px; font-weight: bold; color: #0f172a; }
        
        /* Colores de texto para KPIs */
        .text-green { color: #16a34a; } 
        .text-red { color: #dc2626; } 
        .text-indigo { color: #4f46e5; }
        .text-blue { color: #2563eb; }
        .text-orange { color: #ea580c; }
        .text-purple { color: #9333ea; }
        
        /* Gráficas y Análisis de Plataformas */
        .chart-container { width: 100%; text-align: center; margin-bottom: 25px; clear: both; }
        .chart-title { font-size: 12px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; text-align: left; }
        
        .plat-section { width: 100%; margin-top: 20px; }
        .plat-chart { float: left; width: 45%; text-align: center; }
        .plat-stats { float: right; width: 50%; }
        
        .stats-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 10px; }
        .stats-table th { background-color: #f1f5f9; padding: 5px; border-bottom: 1px solid #cbd5e1; text-align: left; }
        .stats-table td { padding: 5px; border-bottom: 1px solid #e2e8f0; }
        .insights-box { background-color: #f8fafc; border-left: 3px solid #3b82f6; padding: 8px; font-size: 9px; }

        /* Tabla de Desglose */
        .page-break { page-break-before: always; }
        table.operaciones { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.operaciones th { background-color: #1e293b; color: white; padding: 6px; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.operaciones td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        .row-pedido { background-color: #f8fafc; font-weight: bold; }
        .td-detalles { padding: 4px 6px 12px 6px; border-bottom: 2px solid #94a3b8; }
        .lista-productos { margin: 2px 0 0 0; padding-left: 15px; color: #475569; font-size: 9px; font-weight: normal; }
        .text-right { text-align: right; }
        .badge { padding: 2px 4px; border-radius: 2px; font-size: 8px; text-transform: uppercase; color: white; }
        .bg-venta { background-color: #22c55e; } .bg-reembolso { background-color: #eab308; } .bg-contracargo { background-color: #ef4444; }
    </style>
</head>
<body>

    <div class="header">
        <img src="data:image/png;base64,{{ $logoBellaroma }}" class="logo" alt="Bellaroma Logo">
        <div class="info-header">
            <h2>Reporte Financiero Contable</h2>
            <p><strong>Periodo Analizado:</strong> {{ $periodo }}</p>
            <p><strong>Fecha Emisión:</strong> {{ $fechaImpresion }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="kpi-container">
        <div class="kpi-box">
            <div class="kpi-title">Venta Bruta</div>
            <div class="kpi-value text-blue">${{ number_format($kpis['ventas'], 2) }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Notas AE (Lista x Pza)</div>
            <div class="kpi-value text-indigo">${{ number_format($kpis['notas_ae'], 2) }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Ganancia Neta</div>
            <div class="kpi-value text-green">${{ number_format($kpis['ganancias'], 2) }}</div>
        </div>
        <div class="kpi-box last">
            <div class="kpi-title">Margen Utilidad</div>
            <div class="kpi-value">{{ number_format($kpis['margen'], 2) }}%</div>
        </div>
        <div class="clear"></div>

        <div class="kpi-box">
            <div class="kpi-title">Pérdidas</div>
            <div class="kpi-value text-red">$-{{ number_format(abs($kpis['perdidas']), 2) }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Comisiones Pagadas</div>
            <div class="kpi-value text-orange">${{ number_format($kpis['comisiones'], 2) }}</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Envíos (Costo Empresa)</div>
            <div class="kpi-value text-purple">${{ number_format($kpis['envios_empresa'], 2) }}</div>
        </div>
        <div class="kpi-box last">
            <div class="kpi-title">Envíos (Pagó Cliente)</div>
            <div class="kpi-value" style="font-size: 11px;">
                {{ $kpis['envios_clientes_count'] }} ped. / ${{ number_format($kpis['envios_clientes_monto'], 2) }}
            </div>
            <div style="font-size: 8px; color: #64748b; margin-top: 4px;">(Sin impacto en utilidad)</div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="chart-container">
        <div class="chart-title">Comportamiento: Venta vs Utilidad Neta</div>
        <img src="{{ $imgVentas }}" class="chart" style="max-height: 200px;">
    </div>

    <div class="plat-section">
        <div class="plat-chart">
            <div class="chart-title">Distribución de Comisiones</div>
            <img src="{{ $imgPlataformas }}" class="chart" style="max-height: 180px;">
        </div>
        <div class="plat-stats">
            <div class="chart-title">Análisis de Pasarelas</div>
            <table class="stats-table">
                <tr><th>Plataforma</th><th class="text-right">Comisión Cobrada</th><th class="text-right">% del Total</th></tr>
                @foreach($platStats as $nombre => $stat)
                <tr>
                    <td><strong>{{ $nombre }}</strong> ({{ $stat['cantidad'] }} ped)</td>
                    <td class="text-right">${{ number_format($stat['comision'], 2) }}</td>
                    <td class="text-right">{{ number_format($stat['porcentaje'], 1) }}%</td>
                </tr>
                @endforeach
            </table>
            <div class="insights-box">
                <p style="margin: 0 0 4px 0;"><strong>Mayor volumen de pagos:</strong> {{ $platInsights['mas_usada'] }}</p>
                <p style="margin: 0 0 4px 0;"><strong>Comisión más cara (promedio):</strong> {{ $platInsights['mas_cara'] }}</p>
                <p style="margin: 0;"><strong>Menor relevancia:</strong> {{ $platInsights['menos_relevante'] }}</p>
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="page-break"></div>
    <div class="header">
        <div class="info-header" style="float: left; text-align: left;">
            <h2 style="margin-bottom: 2px;">Desglose de Operaciones ({{ $periodo }})</h2>
            <p style="margin: 0; font-size:10px; color:#64748b;">* Los contracargos y reembolsos se visualizan al inicio de la tabla.</p>
        </div>
        <div class="clear"></div>
    </div>

    <table class="operaciones">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Pedido</th>
                <th>Plataforma / Transacción</th>
                <th class="text-right">Comisión</th>
                <th class="text-right">Venta</th>
                <th class="text-right">Utilidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pedidos as $pedido)
                @php
                    $esVenta = str_contains(strtolower($pedido->tipo_transaccion), 'venta');
                    $esReem = str_contains(strtolower($pedido->tipo_transaccion), 'reembolso');
                    $claseBadge = $esVenta ? 'bg-venta' : ($esReem ? 'bg-reembolso' : 'bg-contracargo');
                @endphp
                <tr class="row-pedido">
                    <td>{{ $pedido->fecha_salida->format('d/m/Y') }}</td>
                    <td style="color: #0f172a;">{{ $pedido->numero_pedido }}</td>
                    <td>
                        {{ $pedido->platform->name }} <br>
                        <span class="badge {{ $claseBadge }}">{{ $esVenta ? 'Venta Normal' : $pedido->tipo_transaccion }}</span>
                    </td>
                    <td class="text-right">${{ number_format($pedido->comision_plataforma, 2) }}</td>
                    <td class="text-right">${{ number_format($pedido->venta_total, 2) }}</td>
                    <td class="text-right" style="{{ $pedido->utilidad_total < 0 ? 'color: #dc2626;' : 'color: #16a34a;' }}">
                        ${{ number_format($pedido->utilidad_total, 2) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="6" class="td-detalles">
                        <span style="font-weight: bold; font-size: 9px;">Productos Facturados:</span>
                        <ul class="lista-productos">
                            @foreach($pedido->detalles as $prod)
                                <li>{{ $prod->piezas }} pz | <strong>{{ $prod->sku }}</strong> - {{ $prod->nombre_producto }} 
                                    <em>(${{ number_format($prod->precio_unitario, 2) }} c/u) = ${{ number_format($prod->subtotal, 2) }}</em>
                                </li>
                            @endforeach
                        </ul>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No hay registros en el periodo filtrado.</td></tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>