<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\WoocommerceProduct;
use App\Models\WoocommerceSyncLog;
use App\Models\WoocommerceSyncDetail; // Importación obligatoria para el espía

class FetchWooCommercePricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $syncLogId;

    public function __construct($syncLogId)
    {
        $this->syncLogId = $syncLogId;
    }

    public function handle()
    {
        $log = WoocommerceSyncLog::find($this->syncLogId);
        if (!$log) return;

        $log->update(['estado' => 'en_proceso']);

        $ck = 'ck_dd5b2465b10fb66949a1c1ebde972f7d784abb8c';
        $cs = 'cs_b3654aa5868e953a050186d2118c9a76eb9bdbb7';
        $baseUrl = 'https://www.bellaroma.mx/wp-json/wc/v3/products';

        $page = 1;
        $procesados = 0;

        // Bucle que recorre la API por páginas
        do {
            $response = Http::withBasicAuth($ck, $cs)->timeout(60)->get($baseUrl, [
                'per_page' => 100, 
                'page' => $page,
                '_fields' => 'id,sku,regular_price,sale_price' 
            ]);

            if (!$response->successful()) {
                $log->update(['estado' => 'error']);
                return;
            }

            $productosWoo = $response->json();

            if (empty($productosWoo)) break;

            foreach ($productosWoo as $wp) {
                if (!empty($wp['sku'])) {
                    $productoLocal = WoocommerceProduct::where('sku', $wp['sku'])->first();

                    if ($productoLocal) {
                        $nuevoNormal = $wp['regular_price'] !== '' ? $wp['regular_price'] : null;
                        $nuevoRebajado = $wp['sale_price'] !== '' ? $wp['sale_price'] : null;

                        // 1. AUDITORÍA: Guardamos el "antes y después"
                        WoocommerceSyncDetail::create([
                            'sync_log_id' => $log->id,
                            'sku' => $wp['sku'],
                            'precio_anterior_normal' => $productoLocal->precio_normal,
                            'precio_nuevo_normal' => $nuevoNormal,
                            'precio_anterior_rebajado' => $productoLocal->precio_rebajado,
                            'precio_nuevo_rebajado' => $nuevoRebajado,
                            'estado' => 'exito',
                            'mensaje' => 'Descargado desde WooCommerce'
                        ]);

                        // 2. Actualizamos la base de datos local
                        $productoLocal->update([
                            'precio_normal' => $nuevoNormal,
                            'precio_rebajado' => $nuevoRebajado,
                        ]);
                    }
                }
                $procesados++;
            }

            $log->update(['procesados' => $procesados]);
            $page++;

        } while (count($productosWoo) === 100);

        $log->update(['estado' => 'completado', 'procesados' => $log->total_productos]);
    }
}