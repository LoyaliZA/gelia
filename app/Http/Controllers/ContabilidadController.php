<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PlatformCalculationService;
use App\Http\Controllers\FuncionesContabilidad\PedidosService;
use App\Http\Controllers\FuncionesContabilidad\CargaMasivaService;
use App\Http\Controllers\FuncionesContabilidad\RetirosService;
use App\Http\Controllers\FuncionesContabilidad\ReportesService;
use App\Http\Controllers\FuncionesContabilidad\HistorialService;

class ContabilidadController extends Controller
{
    protected $calcService;

    public function __construct(PlatformCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    // ==========================================
    // 1. VISTAS PRINCIPALES Y CONFIGURACIÓN
    // ==========================================
    public function index(Request $request)
    {
        return view('contabilidad.index', ReportesService::obtenerDatosIndex($request));
    }

    public function gestionRetiros()
    {
        return view('contabilidad.retiros', ['datosPlataformas' => RetirosService::obtenerDatosVista()]);
    }

    public function actualizarComisiones(Request $request)
    {
        $request->validate(['plataformas' => 'required|array']);
        return ReportesService::actualizarComisiones($request);
    }

    // ==========================================
    // 2. CRUD DE PEDIDOS (Operaciones Manuales)
    // ==========================================
    public function guardarPedido(Request $request)
    {
        $request->validate(['fecha_salida' => 'required|date', 'numero_pedido' => 'required', 'venta_total' => 'required|numeric']);
        return PedidosService::guardar($request, $this->calcService);
    }

    public function actualizarPedidoRapido(Request $request, $id)
    {
        return PedidosService::actualizarRapido($request, $id);
    }

    public function eliminarPedido($id)
    {
        return PedidosService::eliminar($id);
    }

    // ==========================================
    // 3. CARGA MASIVA Y MEMORIA (Excel)
    // ==========================================
    public function procesarLista(Request $request)
    {
        return CargaMasivaService::procesarLista($request);
    }

    public function importarHistorico(Request $request)
    {
        return CargaMasivaService::importarHistorico($request);
    }

    public function descargarPlantilla()
    {
        return ReportesService::descargarPlantillaCsv();
    }

    // ==========================================
    // 4. RETIROS Y CONFIRMACIONES BANCARIAS
    // ==========================================
    public function confirmarIndividual(Request $request, $id)
    {
        return RetirosService::confirmarIndividual($request, $id);
    }

    public function confirmarLote(Request $request)
    {
        return RetirosService::confirmarLote($request);
    }

    // ==========================================
    // 5. ANÁLISIS, REPORTES Y DASHBOARD
    // ==========================================
    public function getDashboardData(Request $request)
    {
        return response()->json(ReportesService::getDashboardData($request));
    }

    public function exportarReporte(Request $request)
    {
        return ReportesService::exportarExcel($request);
    }

    public function generarReportePDF(Request $request)
    {
        return ReportesService::generarPdf($request);
    }

    // ==========================================
    // 6. HISTORIAL Y AUDITORÍA DE CAMBIOS
    // ==========================================
    public function historialAutorizados(Request $request)
    {
        // FIX: Debemos traer las plataformas para que el filtro del Blade funcione
        $platforms = \App\Models\Platform::where('active', true)->get();

        // Usamos nuestro servicio para traer los lotes filtrados
        $lotes = \App\Http\Controllers\FuncionesContabilidad\HistorialService::obtenerHistorial($request);

        $mesActual = $request->input('mes', date('m'));
        $anioActual = $request->input('anio', date('Y'));

        // Enviamos 'platforms' a la vista
        return view('contabilidad.historial', compact(
            'lotes',
            'mesActual',
            'anioActual',
            'platforms'
        ));
    }

    public function procesarEdicionEmergencia(Request $request, $id)
    {
        return HistorialService::edicionEmergencia($request, $id);
    }
}
