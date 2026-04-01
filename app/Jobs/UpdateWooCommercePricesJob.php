<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\WoocommerceProduct;
use App\Models\WoocommerceMargin;
use App\Models\WoocommerceConfig;
use App\Models\WoocommerceSyncLog;

class UpdateWooCommercePricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $syncLogId;
    protected $preciosWizerp;

    public function __construct($syncLogId, $preciosWizerp) {
        $this->syncLogId = $syncLogId;
        $this->preciosWizerp = $preciosWizerp;
    }

    public function handle()
    {
        $log = WoocommerceSyncLog::find($this->syncLogId);
        if (!$log) return;

        $log->update(['estado' => 'en_proceso']);

        $productos = WoocommerceProduct::all();
        $config = WoocommerceConfig::where('llave', 'iva')->first();
        $iva = $config ? (float)$config->valor : 1.16;
        $margenes = WoocommerceMargin::orderBy('precio_min')->get();
        
        $ck = 'ck_dd5b2465b10fb66949a1c1ebde972f7d784abb8c';
        $cs = 'cs_b3654aa5868e953a050186d2118c9a76eb9bdbb7';

        foreach ($productos as $index => $prod) {
            if (isset($this->preciosWizerp[$prod->sku])) {
                $base = $this->preciosWizerp[$prod->sku];
                
                $rebaja = round(($base * $this->getMultiplicador($base, 'rebaja', $margenes)) / $iva, 2);
                $normal = round(($base * $this->getMultiplicador($base, 'normal', $margenes)) / $iva, 2);

                // ENRUTADOR INTELIGENTE
                // Si el producto tiene un parent_id, usamos la ruta de variaciones. 
                // De lo contrario, usamos la ruta de productos simples.
                $url = ($prod->parent_id)
                    ? "https://www.bellaroma.mx/wp-json/wc/v3/products/{$prod->parent_id}/variations/{$prod->id}"
                    : "https://www.bellaroma.mx/wp-json/wc/v3/products/{$prod->id}";

                $response = Http::withBasicAuth($ck, $cs)->put($url, [
                    'regular_price' => (string)$normal,
                    'sale_price' => (string)$rebaja
                ]);

                // AUDITORÍA: Guardamos el registro de lo que acaba de pasar
                // AUDITORÍA: Guardamos el registro de lo que acaba de pasar
                \App\Models\WoocommerceSyncDetail::create([
                    'sync_log_id' => $log->id,
                    'sku' => $prod->sku,
                    'precio_anterior_normal' => $prod->precio_normal,
                    'precio_nuevo_normal' => $normal,
                    'precio_anterior_rebajado' => $prod->precio_rebajado,
                    'precio_nuevo_rebajado' => $rebaja,
                    'estado' => $response->successful() ? 'exito' : 'error',
                    // AQUÍ ESTÁ LA CORRECCIÓN: Extraemos el error descriptivo real de WooCommerce
                    'mensaje' => $response->successful() ? 'Actualizado en Woo' : 'Error Woo: ' . $response->json('message', 'Fallo desconocido')
                ]);

                // También actualizamos la base de datos local para mantenerla sincronizada
                if ($response->successful()) {
                    $prod->update([
                        'precio_normal' => $normal,
                        'precio_rebajado' => $rebaja
                    ]);
                }
            }

            if (($index + 1) % 10 == 0 || ($index + 1) == $productos->count()) {
                $log->update(['procesados' => $index + 1]);
            }
        }

        $log->update(['estado' => 'completado']);
    }

    private function getMultiplicador($base, $tipo, $margenes) {
        foreach ($margenes as $m) {
            if ($base >= $m->precio_min && $base <= $m->precio_max) {
                return ($tipo === 'rebaja') ? $m->multiplicador_rebaja : $m->multiplicador_normal;
            }
        }
        return 1.0;
    }
}