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

    public function __construct($syncLogId, $preciosWizerp)
    {
        $this->syncLogId = $syncLogId;
        $this->preciosWizerp = $preciosWizerp;
    }

    // Dentro de UpdateWooCommercePricesJob.php

    public function handle()
    {
        $log = WoocommerceSyncLog::find($this->syncLogId);
        if (!$log) return;

        $log->update(['estado' => 'en_proceso']);

        $config = WoocommerceConfig::where('llave', 'iva')->first();
        $iva = $config ? (float)$config->valor : 1.16;
        $margenes = WoocommerceMargin::orderBy('precio_min')->get();

        $ck = 'ck_dd5b2465b10fb66949a1c1ebde972f7d784abb8c';
        $cs = 'cs_b3654aa5868e953a050186d2118c9a76eb9bdbb7';

        // CHECKPOINT: Obtenemos los SKUs que ya fueron procesados en este log específico
        $skusProcesados = \App\Models\WoocommerceSyncDetail::where('sync_log_id', $this->syncLogId)
            ->pluck('sku')
            ->toArray();

        $index = count($skusProcesados); // Retomamos el conteo visual donde se quedó

        foreach ($this->preciosWizerp as $sku => $precioBase) {

            // MODO SEGURO: Si el SKU ya está en la lista de procesados, lo saltamos instantáneamente
            if (in_array($sku, $skusProcesados)) {
                continue;
            }

            // Buscamos el producto directo en BD, evitando cargar toda la tabla en memoria
            $prod = WoocommerceProduct::where('sku', $sku)->first();

            if ($prod) {
                $baseConIva = $precioBase * $iva;
                $normal = round($baseConIva * $this->getMultiplicador($baseConIva, 'normal', $margenes), 2);
                $rebaja = round($baseConIva * $this->getMultiplicador($baseConIva, 'rebaja', $margenes), 2);

                // VALIDACIÓN ESTRICTA: Prevenir actualización si el precio calculado es 0 o inválido
                if (empty($normal) || $normal <= 0) {
                    $this->registrarDetalleAuditoria($log->id, $sku, $prod, $normal, $rebaja, 'error', 'Precio calculado inválido (0 o nulo). Protegido por sistema.');
                    continue;
                }

                // DIRTY CHECKING (Selectivo): Si los precios son idénticos, no tocamos la API
                if ($prod->precio_normal == $normal && $prod->precio_rebajado == $rebaja) {
                    $this->registrarDetalleAuditoria($log->id, $sku, $prod, $normal, $rebaja, 'exito', 'Omitido: El precio no requería cambios.');
                    $index++;
                    continue;
                }

                // Si llegamos aquí, requiere actualización en WooCommerce
                $url = $prod->tipo === 'variation'
                    ? "https://www.bellaroma.mx/wp-json/wc/v3/products/{$prod->parent_id}/variations/{$prod->id}"
                    : "https://www.bellaroma.mx/wp-json/wc/v3/products/{$prod->id}";

                $response = Http::withBasicAuth($ck, $cs)->put($url, [
                    'regular_price' => (string) $normal,
                    'sale_price' => (string) $rebaja
                ]);

                $estado = $response->successful() ? 'exito' : 'error';
                $mensaje = $response->successful() ? 'Actualizado en Woo' : 'Error Woo: ' . $response->json('message', 'Fallo desconocido');

                $this->registrarDetalleAuditoria($log->id, $sku, $prod, $normal, $rebaja, $estado, $mensaje);

                if ($response->successful()) {
                    $prod->update([
                        'precio_normal' => $normal,
                        'precio_rebajado' => $rebaja
                    ]);
                }
            } else {
                // El producto está en el CSV pero no en nuestra BD
                $this->registrarDetalleAuditoria($log->id, $sku, null, null, null, 'error', 'SKU no encontrado en base de datos local.');
            }

            $index++;
            if ($index % 10 == 0 || $index == count($this->preciosWizerp)) {
                $log->update(['procesados' => $index]);
            }
        }

        $log->update(['estado' => 'completado']);
    }

    // Función modular para mantener limpio el bucle principal
    private function registrarDetalleAuditoria($logId, $sku, $prod, $normal, $rebaja, $estado, $mensaje)
    {
        \App\Models\WoocommerceSyncDetail::create([
            'sync_log_id' => $logId,
            'sku' => $sku,
            'precio_anterior_normal' => $prod ? $prod->precio_normal : null,
            'precio_nuevo_normal' => $normal,
            'precio_anterior_rebajado' => $prod ? $prod->precio_rebajado : null,
            'precio_nuevo_rebajado' => $rebaja,
            'estado' => $estado,
            'mensaje' => $mensaje
        ]);
    }

    private function getMultiplicador($base, $tipo, $margenes)
    {
        foreach ($margenes as $m) {
            if ($base >= $m->precio_min && $base <= $m->precio_max) {
                return ($tipo === 'rebaja') ? $m->multiplicador_rebaja : $m->multiplicador_normal;
            }
        }
        return 1.0;
    }
}
