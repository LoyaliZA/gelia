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
use App\Models\WoocommerceSyncDetail;

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

        do {
            $response = Http::withHeaders([
                'User-Agent' => 'GeliaSystem-FetchBot/1.0',
                'Accept' => 'application/json',
            ])
            ->withBasicAuth($ck, $cs)
            ->timeout(60)
            ->get($baseUrl, [
                'per_page' => 100, 
                'page' => $page,
                '_fields' => 'id,sku,regular_price,sale_price' 
            ]);

            if ($response->status() === 403 || $response->status() === 503 || $response->status() === 429) {
                $log->update(['estado' => 'error', 'mensaje' => "Bloqueo perimetral (HTTP {$response->status()})."]);
                throw new \Exception("Conexión rechazada por firewall en lectura.");
            }

            if (!$response->successful()) {
                $log->update(['estado' => 'error']);
                return;
            }

            $productosWoo = $response->json();
            if (empty($productosWoo)) break;

            $this->procesarPaginaLocal($productosWoo, $log);

            $procesados += count($productosWoo);
            $log->update(['procesados' => $procesados]);
            $page++;

            // Protección vital contra Rate Limiting en GET
            usleep(350000); // 0.35 segundos

        } while (count($productosWoo) === 100);

        $log->update(['estado' => 'completado', 'procesados' => $log->total_productos]);
    }

    /**
     * Módulo de persistencia local de datos leídos.
     */
    private function procesarPaginaLocal(array $productosWoo, $log): void
    {
        foreach ($productosWoo as $wp) {
            if (empty($wp['sku'])) continue;

            $productoLocal = WoocommerceProduct::where('sku', $wp['sku'])->first();

            if ($productoLocal) {
                $nuevoNormal = $wp['regular_price'] !== '' ? $wp['regular_price'] : null;
                $nuevoRebajado = $wp['sale_price'] !== '' ? $wp['sale_price'] : null;

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

                $productoLocal->update([
                    'precio_normal' => $nuevoNormal,
                    'precio_rebajado' => $nuevoRebajado,
                ]);
            }
        }
    }
}