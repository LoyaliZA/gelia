<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
// Importación de Modelos
use App\Models\WoocommerceTemplate;
use App\Models\WoocommerceProduct;
use App\Models\WoocommerceMargin;
use App\Models\WoocommerceConfig;
use App\Models\WoocommerceSyncLog;
// Importación del Job
use App\Jobs\UpdateWooCommercePricesJob;
use App\Jobs\FetchWooCommercePricesJob;

class BellaromaCargaPreciosController extends Controller
{
    /**
     * Vista principal: Historial, Configuración e Inventario Paginado.
     */
    public function index(Request $request)
    {
        $hoy = date('Y-m-d');
        $query = $request->input('search');

        // Variables para los filtros
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'desc');

        $templatesHoy = WoocommerceTemplate::whereDate('created_at', $hoy)->orderByDesc('id')->get();
        $templatesHistorial = WoocommerceTemplate::whereDate('created_at', '<', $hoy)->orderByDesc('id')->limit(50)->get();

        $configIva = WoocommerceConfig::where('llave', 'iva')->first();
        $iva = $configIva ? (float) $configIva->valor : 1.16;
        $margenes = WoocommerceMargin::orderBy('precio_min')->get();

        // Detección de proceso en background para persistencia de UI
        // Detección de proceso en background con SEGURO ANTI-ZOMBIES (10 minutos de inactividad máxima)
        $procesoActivo = WoocommerceSyncLog::whereIn('estado', ['pendiente', 'en_proceso'])
            ->where('updated_at', '>=', now()->subMinutes(10))
            ->latest()
            ->first();

        // Búsqueda, Filtro y Paginación
        $productos = WoocommerceProduct::when($query, function ($q) use ($query) {
            return $q->where('sku', 'LIKE', "%{$query}%")
                ->orWhere('nombre', 'LIKE', "%{$query}%");
        })->orderBy($sort, $order)->paginate(15)->withQueryString();

        return view('woocommerce', compact('templatesHoy', 'templatesHistorial', 'iva', 'margenes', 'productos', 'procesoActivo', 'sort', 'order'));
    }

    /**
     * Seguridad: Validación de PIN.
     */
    public function verificarPin(Request $request)
    {
        $request->validate(['pin' => 'required|string']);
        $pinConfig = WoocommerceConfig::where('llave', 'admin_pin')->first();

        if ($pinConfig && Hash::check($request->pin, $pinConfig->valor)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'PIN incorrecto.'], 401);
    }

    /**
     * Configuración: Guarda IVA y multiplicadores.
     */
    public function guardarConfiguracion(Request $request)
    {
        $request->validate([
            'iva' => 'required|numeric|min:1',
            'margenes' => 'required|array'
        ]);

        WoocommerceConfig::updateOrCreate(['llave' => 'iva'], ['valor' => (string) $request->iva]);

        foreach ($request->margenes as $id => $datos) {
            WoocommerceMargin::where('id', $id)->update([
                'multiplicador_rebaja' => $datos['rebaja'],
                'multiplicador_normal' => $datos['normal']
            ]);
        }

        return response()->json(['message' => 'Algoritmo actualizado correctamente.']);
    }

    public function sincronizarProductos(Request $request)
    {
        $request->validate(['woocommerce_csv' => 'required|file']);
        $path = $request->file('woocommerce_csv')->getRealPath();

        // 1. Mapeo Inicial: Guardamos SKU -> ID para encontrar padres después
        $skuToIdMap = [];
        $fileIn = fopen($path, 'r');
        $headersRaw = fgetcsv($fileIn);
        $headers = array_map(fn($i) => strtolower(trim((string)$i)), $headersRaw);

        $idxSku = array_search('sku', $headers);
        $idxId = array_search('id', $headers);
        $idxTipo = array_search('tipo', $headers);
        $idxNombre = array_search('nombre', $headers);
        $idxSuperior = array_search('superior', $headers);

        while (($row = fgetcsv($fileIn)) !== false) {
            $sku = trim($row[$idxSku] ?? '');
            $idReal = trim($row[$idxId] ?? '');
            if ($sku !== '' && $idReal !== '') {
                $skuToIdMap[$sku] = (int)$idReal;
            }
        }
        fclose($fileIn);

        // 2. Procesamiento Final: Insertar con jerarquía
        WoocommerceProduct::truncate();
        $nuevos = [];
        $fileIn = fopen($path, 'r');
        fgetcsv($fileIn); // Saltar cabeceras

        while (($row = fgetcsv($fileIn)) !== false) {
            $sku = trim($row[$idxSku] ?? '');
            $idReal = trim($row[$idxId] ?? '');

            if ($sku !== '' && $idReal !== '') {
                $parentSku = trim($row[$idxSuperior] ?? '');
                // Si existe un SKU superior, buscamos su ID en nuestro mapa
                $parentId = ($parentSku !== '' && isset($skuToIdMap[$parentSku]))
                    ? $skuToIdMap[$parentSku]
                    : null;

                $nuevos[] = [
                    'id' => (int) $idReal,
                    'sku' => $sku,
                    'nombre' => trim($row[$idxNombre] ?? 'Sin Nombre'),
                    'tipo' => strtolower(trim($row[$idxTipo] ?? 'simple')),
                    'parent_id' => $parentId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }
        fclose($fileIn);

        foreach (array_chunk($nuevos, 500) as $chunk) {
            WoocommerceProduct::insert($chunk);
        }

        return response()->json(['message' => 'Catálogo sincronizado con éxito. Soporte de variaciones activo.']);
    }

    /**
     * Generación: Crea el archivo CSV de resurtido para descarga manual.
     */
    public function procesar(Request $request)
    {
        $request->validate(['listado_aromas' => 'required|file']);

        $configIva = WoocommerceConfig::where('llave', 'iva')->first();
        $iva = $configIva ? (float) $configIva->valor : 1.16;
        $margenes = WoocommerceMargin::orderBy('precio_min')->get();

        $preciosWizerp = [];
        (new FastExcel)->withoutHeaders()->import($request->file('listado_aromas')->getRealPath(), function ($linea) use (&$preciosWizerp) {
            $sku = trim((string)($linea[1] ?? ''));
            $precio = (float)($linea[5] ?? 0);
            if ($sku !== '' && $precio > 0) $preciosWizerp[$sku] = $precio;
        });

        $productosWoo = WoocommerceProduct::all();
        $fileName = 'WOOCOMMERCE-SYNC-' . date('d-m-Y_H-i-s') . '.csv';
        $ruta = 'woocommerce/' . $fileName;

        $tempPath = tempnam(sys_get_temp_dir(), 'woo');
        $fileOut = fopen($tempPath, 'w');
        fputcsv($fileOut, ['SKU', 'Nombre', 'Precio rebajado', 'Precio normal']);

        foreach ($productosWoo as $prod) {
            if (isset($preciosWizerp[$prod->sku])) {
                $base = $preciosWizerp[$prod->sku];
                $rebaja = $this->calcular($base, 'rebaja', $margenes, $iva);
                $normal = $this->calcular($base, 'normal', $margenes, $iva);
                fputcsv($fileOut, [$prod->sku, $prod->nombre, $rebaja, $normal]);
            }
        }
        fclose($fileOut);

        Storage::disk('public')->put($ruta, file_get_contents($tempPath));
        $size = round(filesize($tempPath) / 1024, 2) . ' KB';
        unlink($tempPath);

        $template = WoocommerceTemplate::create([
            'nombre_archivo' => $fileName,
            'ruta_fisica' => $ruta,
            'tamano_kb' => $size
        ]);

        return response()->json(['download_url' => route('woocommerce.descargar', $template->id)]);
    }

    /**
     * API: Inicia la actualización masiva en segundo plano (Job).
     */
    public function iniciarCargaMasiva(Request $request)
    {
        $request->validate(['listado_aromas' => 'required|file']);

        $preciosWizerp = [];
        (new FastExcel)->withoutHeaders()->import($request->file('listado_aromas')->getRealPath(), function ($linea) use (&$preciosWizerp) {
            $sku = trim((string)($linea[1] ?? ''));
            $precio = (float)($linea[5] ?? 0);
            if ($sku !== '' && $precio > 0) $preciosWizerp[$sku] = $precio;
        });

        $total = WoocommerceProduct::count();

        // Usamos el Modelo en lugar de \DB para evitar errores de Intelephense
        $log = WoocommerceSyncLog::create([
            'total_productos' => $total,
            'procesados' => 0,
            'estado' => 'pendiente'
        ]);

        UpdateWooCommercePricesJob::dispatch($log->id, $preciosWizerp);

        return response()->json(['success' => true, 'log_id' => $log->id]);
    }

    public function consultarProgreso($id)
    {
        return response()->json(WoocommerceSyncLog::findOrFail($id));
    }

    public function descargar($id)
    {
        $t = WoocommerceTemplate::findOrFail($id);
        return Storage::disk('public')->download($t->ruta_fisica, $t->nombre_archivo);
    }

    public function eliminar($id)
    {
        $t = WoocommerceTemplate::findOrFail($id);
        Storage::disk('public')->delete($t->ruta_fisica);
        $t->delete();
        return response()->json(['success' => true]);
    }

    private function calcular($base, $tipo, $margenes, $iva)
    {
        $mult = 1.0;
        foreach ($margenes as $m) {
            if ($base >= $m->precio_min && $base <= $m->precio_max) {
                $mult = ($tipo === 'rebaja') ? $m->multiplicador_rebaja : $m->multiplicador_normal;
                break;
            }
        }
        return round(($base * $mult) / $iva, 2);
    }

    public function descargarPreciosApi()
    {
        $total = WoocommerceProduct::count();

        if ($total === 0) {
            return response()->json([
                'success' => false,
                'message' => 'El catálogo local está vacío. Sube el CSV primero.'
            ], 400);
        }

        // Creamos el log para que la barra de progreso sepa cuánto falta
        $log = WoocommerceSyncLog::create([
            'total_productos' => $total,
            'procesados' => 0,
            'estado' => 'pendiente'
        ]);

        // Disparamos el Job que creaste en el paso anterior
        \App\Jobs\FetchWooCommercePricesJob::dispatch($log->id);

        return response()->json(['success' => true, 'log_id' => $log->id]);
    }

    /**
     * Vista de Auditoría: Muestra el historial de sincronizaciones con filtros.
     */
    public function auditoriaIndex(Request $request)
    {
        $search = $request->input('search');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $logs = WoocommerceSyncLog::query()
            ->when($search, function ($q) use ($search) {
                return $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('estado', 'LIKE', "%{$search}%");
            })
            ->when($fechaInicio && $fechaFin, function ($q) use ($fechaInicio, $fechaFin) {
                // Rango de fechas
                return $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            })
            ->when($fechaInicio && !$fechaFin, function ($q) use ($fechaInicio) {
                // Fecha única
                return $q->whereDate('created_at', $fechaInicio);
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('woocommerce.auditoria', compact('logs', 'search', 'fechaInicio', 'fechaFin'));
    }

    /**
     * Descarga el CSV de Auditoría de un proceso específico.
     */
    public function descargarAuditoria($id)
    {
        $detalles = \App\Models\WoocommerceSyncDetail::where('sync_log_id', $id)->get();

        if ($detalles->isEmpty()) {
            return back()->withErrors(['error' => 'No hay detalles de auditoría para este proceso.']);
        }

        $fileName = 'AUDITORIA-PRECIOS-' . $id . '-' . date('Ymd_Hi') . '.csv';

        // Usamos FastExcel para generar el archivo limpiamente mapeando las columnas
        return (new \Rap2hpoutre\FastExcel\FastExcel($detalles))->download($fileName, function ($detalle) {
            return [
                'SKU' => $detalle->sku,
                'Normal Anterior' => $detalle->precio_anterior_normal ? '$' . $detalle->precio_anterior_normal : '---',
                'Normal Nuevo' => '$' . $detalle->precio_nuevo_normal,
                'Rebaja Anterior' => $detalle->precio_anterior_rebajado ? '$' . $detalle->precio_anterior_rebajado : '---',
                'Rebaja Nueva' => '$' . $detalle->precio_nuevo_rebajado,
                'Estado' => strtoupper($detalle->estado),
                'Mensaje / Error' => $detalle->mensaje,
                'Fecha Ejecución' => $detalle->created_at->format('d/m/Y H:i:s'),
            ];
        });
    }

    public function actualizarPrecioIndividual(Request $request, $id)
    {
        $request->validate([
            'precio_normal' => 'required|numeric|min:0',
            'precio_rebajado' => 'required|numeric|min:0'
        ]);

        $producto = WoocommerceProduct::findOrFail($id);

        $ck = 'ck_dd5b2465b10fb66949a1c1ebde972f7d784abb8c';
        $cs = 'cs_b3654aa5868e953a050186d2118c9a76eb9bdbb7';

        $response = Http::withBasicAuth($ck, $cs)->put("https://www.bellaroma.mx/wp-json/wc/v3/products/{$producto->id}", [
            'regular_price' => (string) $request->precio_normal,
            'sale_price' => (string) $request->precio_rebajado
        ]);

        if ($response->successful()) {
            $producto->update([
                'precio_normal' => $request->precio_normal,
                'precio_rebajado' => $request->precio_rebajado
            ]);
            return response()->json(['success' => true, 'message' => 'Precio sincronizado en WooCommerce y GELIA.']);
        }

        return response()->json(['success' => false, 'message' => $response->json('message', 'Error desconocido en API')], 400);
    }
}
