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
use Illuminate\Support\Facades\Mail;
use App\Mail\WooSyncExitoMail;
use App\Mail\WooSyncFalloMail;
use Rap2hpoutre\FastExcel\FastExcel;

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

        $skusProcesados = \App\Models\WoocommerceSyncDetail::where('sync_log_id', $this->syncLogId)
            ->pluck('sku')
            ->toArray();

        $index = count($skusProcesados);

        // OPTIMIZACIÓN DE ARQUITECTURA: Usamos chunk() para traer nuestros 1764 productos 
        // de 100 en 100. Esto no satura la memoria RAM del servidor.
        WoocommerceProduct::chunk(100, function ($productosLocales) use (
            $log,
            &$index,
            $margenes,
            $iva,
            $ck,
            $cs,
            $skusProcesados
        ) {
            // SEGURO ANTI-ZOMBIS (Kill Switch): Verificamos en BD si el usuario canceló el proceso
            if ($log->fresh()->estado === 'cancelado') {
                return false; // Retornar false en un chunk() rompe el ciclo instantáneamente
            }

            foreach ($productosLocales as $prod) {
                $sku = $prod->sku;

                // Si el SKU de nuestra BD no viene en el Excel gigante, o ya se procesó, lo ignoramos
                if (!isset($this->preciosWizerp[$sku]) || in_array($sku, $skusProcesados)) {
                    continue; // Buscar en un array (isset) toma 0.0001 segundos
                }

                $precioBase = $this->preciosWizerp[$sku];

                // Delegamos el cálculo completo a la función modular
                $normal = $this->calcularPrecioFinal($precioBase, 'normal', $margenes, $iva);
                $rebaja = $this->calcularPrecioFinal($precioBase, 'rebaja', $margenes, $iva);

                if (empty($normal) || $normal <= 0) {
                    $this->registrarDetalleAuditoria($log->id, $sku, $prod, $normal, $rebaja, 'error', 'Precio calculado inválido.');
                    continue;
                }

                if ($prod->precio_normal == $normal && $prod->precio_rebajado == $rebaja) {
                    $this->registrarDetalleAuditoria($log->id, $sku, $prod, $normal, $rebaja, 'exito', 'Omitido: Sin cambios.');
                    $index++;
                    continue;
                }

                $url = $prod->tipo === 'variation'
                    ? "https://www.bellaroma.mx/wp-json/wc/v3/products/{$prod->parent_id}/variations/{$prod->id}"
                    : "https://www.bellaroma.mx/wp-json/wc/v3/products/{$prod->id}";

                $response = Http::withBasicAuth($ck, $cs)->put($url, [
                    'regular_price' => (string) $normal,
                    'sale_price' => (string) $rebaja
                ]);

                $estado = $response->successful() ? 'exito' : 'error';
                $mensaje = $response->successful() ? 'Actualizado en Woo' : 'Error Woo: ' . $response->json('message', 'Fallo');

                $this->registrarDetalleAuditoria($log->id, $sku, $prod, $normal, $rebaja, $estado, $mensaje);

                if ($response->successful()) {
                    $prod->update([
                        'precio_normal' => $normal,
                        'precio_rebajado' => $rebaja
                    ]);
                }

                $index++;
            }

            // Actualizamos la base de datos de 100 en 100 para no saturarla
            $log->update(['procesados' => $index]);
        });

        // Solo marcamos como completado si no fue cancelado a la mitad
        if ($log->fresh()->estado !== 'cancelado') {
            $log->update(['estado' => 'completado']);
        }

        $this->enviarNotificaciones($log->fresh());
    }

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

    /**
     * Calcula el precio aplicando el margen correspondiente y dividiendo entre el IVA.
     * Esta función unifica la obtención del multiplicador y el cálculo final.
     */
    private function calcularPrecioFinal($base, $tipo, $margenes, $iva)
    {
        $multiplicador = 1.0;

        foreach ($margenes as $m) {
            if ($base >= $m->precio_min && $base <= $m->precio_max) {
                $multiplicador = ($tipo === 'rebaja') ? $m->multiplicador_rebaja : $m->multiplicador_normal;
                break; // Optimización: Terminamos el ciclo al encontrar el rango
            }
        }

        return round(($base * $multiplicador) / $iva, 2);
    }

    private function enviarNotificaciones($log)
    {
        $adminEmail = WoocommerceConfig::where('llave', 'admin_email')->value('valor') ?? 'tu_correo_admin@dominio.com';
        
        if ($log->estado === 'completado') {
            // ÉXITO
            $notifyStr = WoocommerceConfig::where('llave', 'notify_emails')->value('valor');
            $destinatarios = $notifyStr ? array_filter(array_map('trim', explode(',', $notifyStr))) : [];
            $destinatarios[] = $adminEmail; 
            $destinatarios = array_unique($destinatarios);

            // Generar el archivo CSV temporal
            $detalles = \App\Models\WoocommerceSyncDetail::where('sync_log_id', $log->id)->get();
            $tempPath = tempnam(sys_get_temp_dir(), 'woo_auditoria_');
            
            (new FastExcel($detalles))->export($tempPath, function ($detalle) {
                return [
                    'SKU' => $detalle->sku,
                    'Normal Anterior' => $detalle->precio_anterior_normal ? '$' . $detalle->precio_anterior_normal : '---',
                    'Normal Nuevo' => '$' . $detalle->precio_nuevo_normal,
                    'Rebaja Anterior' => $detalle->precio_anterior_rebajado ? '$' . $detalle->precio_anterior_rebajado : '---',
                    'Rebaja Nueva' => '$' . $detalle->precio_nuevo_rebajado,
                    'Estado' => strtoupper($detalle->estado),
                    'Mensaje' => $detalle->mensaje,
                ];
            });

            // Usar la nueva clase Mailable
            Mail::to($destinatarios)->send(new WooSyncExitoMail($log, $tempPath));

            // Eliminar archivo temporal
            unlink($tempPath); 

        } else {
            // FALLO (Solo enviamos al admin usando la clase Mailable)
            Mail::to($adminEmail)->send(new WooSyncFalloMail($log));
        }
    }
}
