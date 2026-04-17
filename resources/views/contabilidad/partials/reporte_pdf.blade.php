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
        
        /* 8 KPIs con Altura Fija y Bordes Redondeados */
        .kpi-wrapper { width: 100%; margin-bottom: 15px; }
        .kpi-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .kpi-table td { padding: 5px; vertical-align: top; width: 25%; }
        .kpi-box { 
            background-color: #f8fafc; 
            border: 1px solid #cbd5e1; 
            border-radius: 6px; /* BORDES REDONDEADOS */
            padding: 10px 5px; 
            text-align: center; 
            height: 65px; /* ALTURA FIJA PARA EVITAR QUE SE DESBORDEN */
        }
        
        .kpi-title { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 4px; }
        .kpi-value { font-size: 13px; font-weight: bold; color: #0f172a; }
        
        .text-green { color: #16a34a; } 
        .text-red { color: #dc2626; } 
        .text-indigo { color: #4f46e5; }
        .text-blue { color: #2563eb; }
        .text-orange { color: #ea580c; }
        .text-purple { color: #9333ea; }
        
        /* Gráficas y Análisis */
        .chart-container { width: 100%; text-align: center; margin-bottom: 25px; clear: both; }
        .chart-title { font-size: 12px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; text-align: left; }
        
        .plat-section { width: 100%; margin-top: 20px; }
        .plat-chart { float: left; width: 45%; text-align: center; }
        .plat-stats { float: right; width: 50%; }
        
        .stats-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 10px; }
        .stats-table th { background-color: #f1f5f9; padding: 5px; border-bottom: 1px solid #cbd5e1; text-align: left; }
        .stats-table td { padding: 5px; border-bottom: 1px solid #e2e8f0; }
        .insights-box { background-color: #f8fafc; border-left: 3px solid #3b82f6; padding: 8px; font-size: 9px; border-radius: 4px; }

        /* Tabla de Desglose */
        .page-break { page-break-before: always; }
        table.operaciones { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        table.operaciones th { background-color: #1e293b; color: white; padding: 6px; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.operaciones td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; font-size: 10px; word-wrap: break-word; }
        .row-pedido { background-color: #f8fafc; font-weight: bold; }
        .td-detalles { padding: 4px 6px 12px 6px; border-bottom: 2px solid #94a3b8; }
        .lista-productos { margin: 2px 0 0 0; padding-left: 15px; color: #475569; font-size: 9px; font-weight: normal; }
        .text-right { text-align: right; }
        .badge { padding: 2px 4px; border-radius: 3px; font-size: 8px; text-transform: uppercase; color: white; display: inline-block; margin-top: 2px; }
        
        .bg-venta { background-color: #22c55e; } 
        .bg-reembolso { background-color: #eab308; } 
        .bg-contracargo { background-color: #ef4444; }
        
        /* Encabezados Separadores */
        .separador-grupo td { background-color: #cbd5e1; color: #0f172a; text-align: center; font-weight: bold; font-size: 9px; padding: 6px; border-bottom: 2px solid #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
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

    <div class="kpi-wrapper">
        <table class="kpi-table">
            <tr>
                <td style="padding-left: 0;">
                    <div class="kpi-box">
                        <div class="kpi-title">Venta Bruta</div>
                        <div class="kpi-value text-blue">${{ number_format($kpis['ventas'], 2) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-title">Notas AE (Lista x Pza)</div>
                        <div class="kpi-value text-indigo">${{ number_format($kpis['notas_ae'], 2) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-title">Ganancia Neta</div>
                        <div class="kpi-value text-green">${{ number_format($kpis['ganancias'], 2) }}</div>
                    </div>
                </td>
                <td style="padding-right: 0;">
                    <div class="kpi-box">
                        <div class="kpi-title">Margen Utilidad</div>
                        <div class="kpi-value">{{ number_format($kpis['margen'], 2) }}%</div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding-left: 0;">
                    <div class="kpi-box">
                        <div class="kpi-title">Pérdidas</div>
                        <div class="kpi-value text-red">$-{{ number_format(abs($kpis['perdidas']), 2) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-title">Comisiones Pagadas</div>
                        <div class="kpi-value text-orange">${{ number_format($kpis['comisiones'], 2) }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi-box">
                        <div class="kpi-title">Envíos (Costo Empresa)</div>
                        <div class="kpi-value text-purple">${{ number_format($kpis['envios_empresa'], 2) }}</div>
                    </div>
                </td>
                <td style="padding-right: 0;">
                    <div class="kpi-box">
                        <div class="kpi-title">Envíos (Pagó Cliente)</div>
                        <div class="kpi-value" style="font-size: 11px;">{{ $kpis['envios_clientes_count'] }} ped. / ${{ number_format($kpis['envios_clientes_monto'], 2) }}</div>
                        <div style="font-size: 8px; color: #64748b; line-height: 1.2; margin-top: 2px;">(Sin impacto en utilidad)</div>
                    </div>
                </td>
            </tr>
        </table>
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
                <p style="margin: 0 0 4px 0;"><strong>Mayor volumen:</strong> {{ $platInsights['mas_usada'] }}</p>
                <p style="margin: 0 0 4px 0;"><strong>Más cara (promedio):</strong> {{ $platInsights['mas_cara'] }}</p>
                <p style="margin: 0;"><strong>Menor relevancia:</strong> {{ $platInsights['menos_relevante'] }}</p>
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="page-break"></div>
    <div class="header">
        <div class="info-header" style="float: left; text-align: left;">
            <h2 style="margin-bottom: 2px;">Desglose de Operaciones ({{ $periodo }})</h2>
        </div>
        <div class="clear"></div>
    </div>

    @php
        // Agrupación automática usando Laravel Collections directamente en la vista
        $grupos = [
            '--- Contracargos y Reembolsos ---' => $pedidos->filter(fn($p) => !str_contains(strtolower($p->tipo_transaccion), 'venta')),
            '--- Pedidos Confirmados (En Banco) ---' => $pedidos->filter(fn($p) => str_contains(strtolower($p->tipo_transaccion), 'venta') && $p->estatus_pago === 'transferido'),
            '--- Pedidos Pendientes (En Plataforma) ---' => $pedidos->filter(fn($p) => str_contains(strtolower($p->tipo_transaccion), 'venta') && $p->estatus_pago !== 'transferido'),
        ];
    @endphp

    <table class="operaciones">
        <thead>
            <tr>
                <th style="width: 12%;">Fecha</th>
                <th style="width: 13%;">Pedido</th>
                <th style="width: 22%;">Cliente</th>
                <th style="width: 18%;">Plataforma</th>
                <th style="width: 10%;" class="text-right">Venta</th>
                <th style="width: 10%;" class="text-right">Comisión</th>
                <th style="width: 15%;" class="text-right">Utilidad Neta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grupos as $titulo => $grupoPedidos)
                @if($grupoPedidos->count() > 0)
                    <tr class="separador-grupo">
                        <td colspan="7">{{ $titulo }}</td>
                    </tr>
                    
                    @foreach($grupoPedidos as $pedido)
                        @php
                            $esVenta = str_contains(strtolower($pedido->tipo_transaccion), 'venta');
                            $esReem = str_contains(strtolower($pedido->tipo_transaccion), 'reembolso');
                            $claseBadge = $esVenta ? 'bg-venta' : ($esReem ? 'bg-reembolso' : 'bg-contracargo');
                            $esTransferido = $pedido->estatus_pago === 'transferido';
                        @endphp
                        <tr class="row-pedido">
                            <td>{{ $pedido->fecha_salida->format('d/m/Y') }}</td>
                            <td style="color: #0f172a;">{{ $pedido->numero_pedido }}</td>
                            <td>{{ $pedido->cliente_nombre ?? 'Sin Nombre' }}</td>
                            <td>
                                {{ $pedido->platform->name }}<br>
                                <span class="badge {{ $claseBadge }}">{{ $esVenta ? 'Venta' : $pedido->tipo_transaccion }}</span>
                            </td>
                            <td class="text-right">${{ number_format($pedido->venta_total, 2) }}</td>
                            <td class="text-right text-orange">-${{ number_format($pedido->comision_plataforma, 2) }}</td>
                            
                            <td class="text-right" style="{{ $pedido->utilidad_total < 0 ? 'color: #dc2626;' : 'color: #16a34a;' }}">
                                ${{ number_format($pedido->utilidad_total, 2) }}<br>
                                <span style="font-size: 8px; font-weight: normal; color: {{ $esTransferido ? '#16a34a' : '#eab308' }};">
                                    {{ $esTransferido ? '✅ Confirmado' : '⏳ Pendiente' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" class="td-detalles">
                                <span style="font-weight: bold; font-size: 9px;">Productos Facturados:</span>
                                <ul class="lista-productos">
                                    @foreach($pedido->detalles as $prod)
                                        <li>{{ $prod->piezas }} pz | <strong>{{ $prod->sku }}</strong> - {{ $prod->nombre_producto }} 
                                            <em>(${{ number_format($prod->precio_unitario, 2) }} c/u)</em>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
</body>
</html>