<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\WoocommerceTemplate;
use App\Models\WoocommerceProduct;
use App\Models\WoocommerceMargin;
use App\Models\WoocommerceConfig;

class BellaromaCargaPreciosController extends Controller
{
    /**
     * Carga la vista con el historial y las configuraciones actuales de la BD.
     */
    public function index()
    {
        $hoy = date('Y-m-d');
        
        $templatesHoy = WoocommerceTemplate::whereDate('created_at', $hoy)->orderByDesc('id')->get();
        $templatesHistorial = WoocommerceTemplate::whereDate('created_at', '<', $hoy)->orderByDesc('id')->limit(100)->get();

        $configIva = WoocommerceConfig::where('llave', 'iva')->first();
        $iva = $configIva ? (float) $configIva->valor : 1.16;
        
        // Obtenemos los escalones de precios ordenados de menor a mayor
        $margenes = WoocommerceMargin::orderBy('precio_min')->get();

        return view('woocommerce', compact('templatesHoy', 'templatesHistorial', 'iva', 'margenes'));
    }

    /**
     * Seguridad: Valida el PIN para acceder a los ajustes.
     */
    public function verificarPin(Request $request)
    {
        $request->validate(['pin' => 'required|string']);

        $pinConfig = WoocommerceConfig::where('llave', 'admin_pin')->first();

        if ($pinConfig && Hash::check($request->pin, $pinConfig->valor)) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'PIN de acceso incorrecto.'], 401);
    }

    /**
     * Actualiza el valor del IVA y modifica dinámicamente los escalones de precios.
     */
    public function guardarConfiguracion(Request $request)
    {
        $request->validate([
            'iva' => 'required|numeric|min:1',
            'margenes' => 'required|array'
        ]);

        // Guardar nuevo IVA
        WoocommerceConfig::updateOrCreate(
            ['llave' => 'iva'],
            ['valor' => (string) $request->iva]
        );

        // Actualizar cada escalón individualmente por su ID
        foreach ($request->margenes as $id => $datos) {
            WoocommerceMargin::where('id', $id)->update([
                'multiplicador_rebaja' => $datos['rebaja'],
                'multiplicador_normal' => $datos['normal']
            ]);
        }

        return response()->json(['message' => 'Configuración actualizada correctamente.']);
    }

    /**
     * Sincroniza el catálogo por única vez.
     * Lee el CSV de WooCommerce y llena la tabla woocommerce_products de forma segura.
     */
    public function sincronizarProductos(Request $request)
    {
        $request->validate([
            'woocommerce_csv' => 'required|file|mimes:csv,txt'
        ]);

        $path = $request->file('woocommerce_csv')->getRealPath();
        $fileIn = fopen($path, 'r');
        
        $headersRaw = fgetcsv($fileIn);
        
        // 1. ESCUDO ANTI-BOM: Eliminamos los caracteres invisibles del primer encabezado
        if (isset($headersRaw[0])) {
            $headersRaw[0] = preg_replace('/[\xef\xbb\xbf]+/', '', $headersRaw[0]);
        }

        // 2. BÚSQUEDA INFALIBLE: Limpiamos espacios extra y convertimos todo a minúsculas
        $headers = array_map(function($item) {
            return strtolower(trim((string)$item));
        }, $headersRaw);

        // Buscamos las columnas sin importar si dicen "SKU", "sku" o " Sku "
        $idxSku = array_search('sku', $headers);
        $idxNombre = array_search('nombre', $headers);

        // Fallback de seguridad: por si algún día WooCommerce se actualiza y lo exporta en inglés
        if ($idxNombre === false) {
            $idxNombre = array_search('name', $headers);
        }

        // Si de plano no se encuentran, devolvemos un mensaje diciendo qué columnas vio PHP realmente
        if ($idxSku === false || $idxNombre === false) {
            fclose($fileIn);
            return response()->json([
                'message' => 'No se encontraron las columnas. PHP detectó esto: ' . implode(', ', $headersRaw)
            ], 422);
        }

        // Vaciamos la tabla para meter el catálogo fresco
        WoocommerceProduct::truncate();
        
        $productosNuevos = [];
        $fechaActual = now();

        while (($row = fgetcsv($fileIn)) !== false) {
            $sku = trim($row[$idxSku] ?? '');
            $nombre = trim($row[$idxNombre] ?? '');

            // Filtro de productos en blanco o sin SKU (el caso que mencionaste)
            if ($sku !== '') {
                $productosNuevos[] = [
                    'sku' => $sku,
                    'nombre' => $nombre,
                    'created_at' => $fechaActual,
                    'updated_at' => $fechaActual
                ];
            }
        }
        fclose($fileIn);

        // Inserción masiva en bloques para no saturar el servidor
        foreach (array_chunk($productosNuevos, 500) as $chunk) {
            WoocommerceProduct::insert($chunk);
        }

        return response()->json([
            'message' => 'Catálogo sincronizado. Total de productos válidos guardados: ' . count($productosNuevos)
        ]);
    }

    /**
     * Proceso diario: Solo lee el listado de Wizerp, cruza con la BD,
     * aplica la matemática dinámica y genera el archivo CSV final.
     */
    public function procesar(Request $request)
    {
        $request->validate([
            'listado_aromas' => 'required|file', 
        ]);

        $configIva = WoocommerceConfig::where('llave', 'iva')->first();
        $iva = $configIva ? (float) $configIva->valor : 1.16;
        $margenes = WoocommerceMargin::orderBy('precio_min')->get();

        // 1. Extraer precios de Wizerp
        $diccionarioPrecios = [];
        (new FastExcel)->withoutHeaders()->import($request->file('listado_aromas')->getRealPath(), function ($linea) use (&$diccionarioPrecios) {
            $sku = trim((string)($linea[1] ?? ''));
            $precio = (float)($linea[5] ?? 0);
            
            if ($sku !== '' && $precio > 0) {
                $diccionarioPrecios[$sku] = $precio;
            }
        });

        // 2. Extraer catálogo de WooCommerce desde nuestra BD
        $productosWoo = WoocommerceProduct::all();

        if ($productosWoo->isEmpty()) {
            return response()->json(['message' => 'El catálogo está vacío. Sincroniza WooCommerce primero.'], 422);
        }

        // 3. Preparar el archivo de salida
        $fileName = 'WOOCOMMERCE-SYNC-' . date('d-m-Y_H-i-s') . '.csv';
        $rutaStorage = 'woocommerce/' . $fileName;
        $tempOutPath = sys_get_temp_dir() . '/' . $fileName;
        $fileOut = fopen($tempOutPath, 'w');

        // Columnas estrictamente necesarias para la importación/actualización de WooCommerce
        fputcsv($fileOut, ['SKU', 'Nombre', 'Precio rebajado', 'Precio normal']);

        // 4. Cruce de datos y generación
        foreach ($productosWoo as $producto) {
            if (isset($diccionarioPrecios[$producto->sku])) {
                $precioBase = $diccionarioPrecios[$producto->sku];
                
                $precioRebajado = $this->calcularPrecioDinamico($precioBase, 'rebaja', $margenes, $iva);
                $precioNormal = $this->calcularPrecioDinamico($precioBase, 'normal', $margenes, $iva);

                fputcsv($fileOut, [$producto->sku, $producto->nombre, $precioRebajado, $precioNormal]);
            }
        }
        fclose($fileOut);

        // 5. Guardar físicamente y registrar en BD
        Storage::disk('public')->put($rutaStorage, file_get_contents($tempOutPath));
        $tamanoKb = round(filesize($tempOutPath) / 1024, 2) . ' KB';
        unlink($tempOutPath);

        $template = WoocommerceTemplate::create([
            'nombre_archivo' => $fileName,
            'ruta_fisica' => $rutaStorage,
            'tamano_kb' => $tamanoKb
        ]);

        return response()->json([
            'download_url' => route('woocommerce.descargar', $template->id)
        ]);
    }

    public function descargar($id)
    {
        $template = WoocommerceTemplate::findOrFail($id);
        if (!Storage::disk('public')->exists($template->ruta_fisica)) {
            abort(404, 'El archivo ya no existe en el servidor.');
        }
        return Storage::disk('public')->download($template->ruta_fisica, $template->nombre_archivo);
    }

    public function eliminar($id)
    {
        $template = WoocommerceTemplate::findOrFail($id);
        if (Storage::disk('public')->exists($template->ruta_fisica)) {
            Storage::disk('public')->delete($template->ruta_fisica);
        }
        $template->delete();
        
        return response()->json(['message' => 'Archivo eliminado correctamente.']);
    }

    /**
     * Calcula los precios basado en los datos de la Base de Datos.
     */
    private function calcularPrecioDinamico(float $base, string $tipo, $margenes, float $iva): float
    {
        $multiplicador = 1.0;

        foreach ($margenes as $margen) {
            // Buscamos en qué escalón entra el precio base
            if ($base >= $margen->precio_min && $base <= $margen->precio_max) {
                $multiplicador = ($tipo === 'rebaja') ? $margen->multiplicador_rebaja : $margen->multiplicador_normal;
                break;
            }
        }

        return round(($base * $multiplicador) / $iva, 2);
    }
}