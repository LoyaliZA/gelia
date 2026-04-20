<?php

namespace App\Http\Controllers\FuncionesContabilidad;

use App\Models\ContabilidadPedido;
use App\Models\Platform;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportesService
{
    /*
     * Obtiene los datos base para la vista principal de contabilidad.
     */
    public static function obtenerDatosIndex($request)
    {
        $platforms = Platform::where('active', true)->get();
        $mesActual = $request->input('mes', date('m'));
        $anioActual = $request->input('anio', date('Y'));

        $pedidos = ContabilidadPedido::with(['detalles', 'platform'])
            ->whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anioActual)
            ->orderBy('fecha_salida', 'desc')
            ->get();

        $comisionesPorPlataforma = $pedidos->groupBy('platform.name')->map(function ($row) {
            return $row->sum('comision_plataforma');
        });

        $datosGrafica = $pedidos->groupBy(function($date) {
            return Carbon::parse($date->fecha_salida)->format('Y-m-d');
        })->map(function ($row) {
            return [
                'utilidad' => $row->sum('utilidad_total'),
                'venta' => $row->sum('venta_total')
            ];
        });

        return compact('platforms', 'pedidos', 'mesActual', 'anioActual', 'comisionesPorPlataforma', 'datosGrafica');
    }

    /*
     * Descarga el reporte mensual en formato Excel.
     */
    public static function exportarExcel($request)
    {
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));

        $pedidos = ContabilidadPedido::with(['detalles', 'platform'])
            ->whereMonth('fecha_salida', $mes)
            ->whereYear('fecha_salida', $anio)
            ->orderBy('fecha_salida', 'desc')
            ->get();

        $nombreArchivo = "Reporte_Contabilidad_Bellaroma_{$mes}_{$anio}.xlsx";

        $datos = $pedidos->map(function ($pedido) {
            return [
                'Fecha' => $pedido->fecha_salida->format('Y-m-d'),
                'Pedido' => $pedido->numero_pedido,
                'Cliente' => $pedido->cliente_nombre ?? 'Sin Nombre',
                'Plataforma' => $pedido->platform->name,
                'Transacción' => ucfirst($pedido->tipo_transaccion),
                'Venta Total ($)' => $pedido->venta_total,
                'Costo Envío ($)' => $pedido->costo_envio,
                'Comisión Plataforma ($)' => $pedido->comision_plataforma,
                'Comisión Retiro Banco ($)' => $pedido->comision_transferencia,
                'Utilidad Final Neta ($)' => $pedido->utilidad_total,
                'Estatus Pago' => strtoupper($pedido->estatus_pago),
                'Total SKUs' => $pedido->detalles->count(),
                'Piezas Vendidas' => $pedido->detalles->sum('piezas'),
            ];
        });

        return (new FastExcel($datos))->download($nombreArchivo);
    }

    /*
     * Genera la plantilla base para cargas masivas históricas.
     */
    public static function descargarPlantillaCsv()
    {
        $headers = [
            'Fecha', 'Pedido', 'Cliente', 'Tipo_Transaccion', 'Plataforma', 
            'SKU', 'Piezas', 'Precio_Pagina', 'Venta_Total', 'Envio_Cobrado', 
            'Comision_Cobrada', 'Envio_Pagado_Cliente'
        ];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, [
                date('d/m/Y'), '28098', 'Ej: Jair Treviño', 'Venta', 'Mercado Pago', 
                '12345', '1', '1500.00', '1800.00', '99.00', '45.50', 'SI'
            ]);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Plantilla_Historico_Bellaroma.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }

    /*
     * Retorna el JSON estructurado para el Dashboard de análisis (Chart.js y KPIs).
     */
    public static function getDashboardData($request)
    {
        $filtro = $request->input('filtro', 'mes');
        $query = ContabilidadPedido::with(['platform', 'detalles']);

        if ($filtro === 'mes') {
            $query->whereMonth('fecha_salida', $request->input('mes', date('m')))
                  ->whereYear('fecha_salida', $request->input('anio', date('Y')));
        } elseif ($filtro === 'dia') {
            $query->whereDate('fecha_salida', $request->input('fecha', date('Y-m-d')));
        } elseif ($filtro === 'anio') {
            $query->whereYear('fecha_salida', $request->input('anio', date('Y')));
        } elseif ($filtro === 'custom') {
            $query->whereBetween('fecha_salida', [$request->input('inicio'), $request->input('fin')]);
        }

        $pedidos = $query->orderBy('fecha_salida', 'asc')->get();

        $ventas = $pedidos->sum('venta_total');
        $comisiones = $pedidos->sum('comision_plataforma');
        $ganancias = $pedidos->where('utilidad_total', '>=', 0)->sum('utilidad_total');
        $perdidas = $pedidos->where('utilidad_total', '<', 0)->sum('utilidad_total');
        $enviosEmpresa = $pedidos->where('envio_pagado_cliente', false)->sum('costo_envio');

        $notasAE = 0;
        foreach ($pedidos as $p) {
            foreach ($p->detalles as $d) {
                $notasAE += ($d->precio_unitario * $d->piezas);
            }
        }
        
        $margen = $ventas > 0 ? ($pedidos->sum('utilidad_total') / $ventas) * 100 : 0;
        $enviosClientesCount = $pedidos->where('envio_pagado_cliente', true)->count();
        $enviosClientesMonto = $pedidos->where('envio_pagado_cliente', true)->sum('costo_envio');

        $comisionesPlataforma = $pedidos->groupBy('platform.name')->map->sum('comision_plataforma');

        $grafica = $pedidos->groupBy(function ($date) {
            return Carbon::parse($date->fecha_salida)->format('d/m/Y');
        })->map(function ($row) {
            return ['venta' => $row->sum('venta_total'), 'utilidad' => $row->sum('utilidad_total')];
        });

        return [
            'kpis' => compact('ventas', 'comisiones', 'ganancias', 'perdidas', 'enviosEmpresa', 'notasAE', 'margen', 'enviosClientesCount', 'enviosClientesMonto'),
            'plataformas' => $comisionesPlataforma,
            'grafica' => $grafica
        ];
    }

    /*
     * Actualiza los porcentajes globales de las comisiones.
     */
    public static function actualizarComisiones($request)
    {
        try {
            DB::transaction(function () use ($request) {
                foreach ($request->plataformas as $plat) {
                    Platform::where('id', $plat['id'])->update(['commission_percent' => $plat['commission_percent']]);
                }
            });
            return response()->json(['success' => true, 'message' => 'Comisiones actualizadas.']);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error al guardar.'], 500);
        }
    }

    /*
     * Genera el PDF con gráficos inyectados en base64 y tabla estructurada.
     */
    public static function generarPdf($request)
    {
        $filtro = $request->input('filtro');
        $query = ContabilidadPedido::with(['platform', 'detalles']); 

        if ($filtro === 'mes') {
            $query->whereMonth('fecha_salida', $request->input('mes'))->whereYear('fecha_salida', $request->input('anio'));
        } elseif ($filtro === 'dia') {
            $query->whereDate('fecha_salida', $request->input('fecha'));
        } elseif ($filtro === 'anio') {
            $query->whereYear('fecha_salida', $request->input('anio'));
        } elseif ($filtro === 'custom') {
            $query->whereBetween('fecha_salida', [$request->input('inicio'), $request->input('fin')]);
        }

        $pedidos = $query->orderBy('fecha_salida', 'desc')->get();

        $kpis = [
            'ventas' => $pedidos->sum('venta_total'),
            'ganancias' => $pedidos->where('utilidad_total', '>=', 0)->sum('utilidad_total'),
            'perdidas' => $pedidos->where('utilidad_total', '<', 0)->sum('utilidad_total'),
            'comisiones' => $pedidos->sum('comision_plataforma'),
            'envios_empresa' => $pedidos->where('envio_pagado_cliente', false)->sum('costo_envio'),
            'envios_clientes_count' => $pedidos->where('envio_pagado_cliente', true)->count(),
            'envios_clientes_monto' => $pedidos->where('envio_pagado_cliente', true)->sum('costo_envio'),
            'notas_ae' => 0
        ];

        foreach ($pedidos as $p) {
            foreach ($p->detalles as $d) {
                $kpis['notas_ae'] += ($d->precio_unitario * $d->piezas);
            }
        }
        $kpis['margen'] = $kpis['ventas'] > 0 ? ($pedidos->sum('utilidad_total') / $kpis['ventas']) * 100 : 0;

        $platStats = [];
        $totalComisiones = $kpis['comisiones'];
        
        foreach($pedidos->groupBy('platform.name') as $nombre => $grupo) {
            $comisionPlataforma = $grupo->sum('comision_plataforma');
            $cantidad = $grupo->count();
            $platStats[$nombre] = [
                'comision' => $comisionPlataforma,
                'cantidad' => $cantidad,
                'porcentaje' => $totalComisiones > 0 ? ($comisionPlataforma / $totalComisiones) * 100 : 0,
                'promedio' => $cantidad > 0 ? ($comisionPlataforma / $cantidad) : 0,
            ];
        }

        $platInsights = [
            'mas_usada' => collect($platStats)->sortByDesc('cantidad')->keys()->first() ?? 'N/A',
            'mas_cara' => collect($platStats)->sortByDesc('promedio')->keys()->first() ?? 'N/A',
            'menos_relevante' => collect($platStats)->sortBy('cantidad')->keys()->first() ?? 'N/A'
        ];

        $anomalias = $pedidos->filter(fn($p) => !str_contains(strtolower($p->tipo_transaccion), 'venta'));
        $ventasNorm = $pedidos->filter(fn($p) => str_contains(strtolower($p->tipo_transaccion), 'venta'));
        $pedidosOrdenados = $anomalias->merge($ventasNorm);

        $data = [
            'fechaImpresion' => date('d/m/Y'),
            'periodo' => $request->periodo,
            'kpis' => $kpis,
            'platStats' => $platStats,
            'platInsights' => $platInsights,
            'imgPlataformas' => $request->img_plataformas,
            'imgVentas' => $request->img_ventas,
            'pedidos' => $pedidosOrdenados,
            'logoBellaroma' => base64_encode(file_get_contents(public_path('assets/BELLAROMA-LOGOTIPO-04.png')))
        ];

        $pdf = Pdf::loadView('contabilidad.partials.reporte_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('Reporte_Bellaroma_'.$request->periodo.'.pdf');
    }
}