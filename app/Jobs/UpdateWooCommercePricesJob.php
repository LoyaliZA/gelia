<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\WoocommerceProduct;
use App\Models\WoocommerceMargin;
use App\Models\WoocommerceConfig;
use App\Models\WoocommerceSyncLog;
use App\Models\WoocommerceSyncDetail;
use App\Mail\WooSyncExitoMail;
use App\Mail\WooSyncFalloMail;
use Rap2hpoutre\FastExcel\FastExcel;

class UpdateWooCommercePricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $syncLogId;
    protected $preciosWizerp;
    protected $ck = 'ck_dd5b2465b10fb66949a1c1ebde972f7d784abb8c';
    protected $cs = 'cs_b3654aa5868e953a050186d2118c9a76eb9bdbb7';

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

        $iva = (float)(WoocommerceConfig::where('llave', 'iva')->value('valor') ?? 1.16);
        $margenes = WoocommerceMargin::orderBy('precio_min')->get();

        $skusProcesados = WoocommerceSyncDetail::where('sync_log_id', $this->syncLogId)->pluck('sku')->toArray();
        $index = count($skusProcesados);

        WoocommerceProduct::chunk(100, function ($productosLocales) use ($log, &$index, $margenes, $iva, $skusProcesados) {
            if ($log->fresh()->estado === 'cancelado') return false;

            $loteSimples = [];
            $loteVariaciones = [];

            foreach ($productosLocales as $prod) {
                $sku = $prod->sku;

                if (!isset($this->preciosWizerp[$sku]) || in_array($sku, $skusProcesados)) {
                    continue;
                }

                $precioBase = $this->preciosWizerp[$sku];
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

                $payload = [
                    'id' => $prod->id,
                    'regular_price' => (string) $normal,
                    'sale_price' => (string) $rebaja
                ];

                if ($prod->tipo === 'variation') {
                    $loteVariaciones[$prod->parent_id][] = $payload;
                } else {
                    $loteSimples[] = $payload;
                }

                $prod->update(['precio_normal' => $normal, 'precio_rebajado' => $rebaja]);
                $this->registrarDetalleAuditoria($log->id, $sku, $prod, $normal, $rebaja, 'exito', 'Enviado en lote a Woo');
                $index++;
            }

            $this->enviarLotesWooCommerce($loteSimples, $loteVariaciones);
            $log->update(['procesados' => $index]);
        });

        if ($log->fresh()->estado !== 'cancelado') {
            $log->update(['estado' => 'completado']);
        }

        $this->enviarNotificaciones($log->fresh());
    }

    /**
     * Módulo de envío estructurado a WooCommerce.
     */
    private function enviarLotesWooCommerce(array $simples, array $variaciones): void
    {
        if (!empty($simples)) {
            $this->ejecutarPeticionBatch("https://www.bellaroma.mx/wp-json/wc/v3/products/batch", $simples);
        }

        if (!empty($variaciones)) {
            foreach ($variaciones as $parentId => $variacionesHijas) {
                $url = "https://www.bellaroma.mx/wp-json/wc/v3/products/{$parentId}/variations/batch";
                $this->ejecutarPeticionBatch($url, $variacionesHijas);
            }
        }
    }

    /**
     * Ejecuta la petición HTTP con prevención de bloqueos.
     */
    private function ejecutarPeticionBatch(string $url, array $datosUpdate): void
    {
        $response = Http::withHeaders([
            'User-Agent' => 'GeliaSystem-SyncBot/1.0',
            'Accept' => 'application/json'
        ])
        ->withBasicAuth($this->ck, $this->cs)
        ->timeout(45)
        ->post($url, ['update' => $datosUpdate]);

        $this->validarRespuestaRed($response);
        usleep(500000); // Pausa de 0.5s por lote
    }

    private function validarRespuestaRed($response): void
    {
        $status = $response->status();
        if ($status === 429 || $status === 403 || $status === 503) {
            throw new \Exception("Bloqueo de seguridad detectado en destino (HTTP {$status}). Lote abortado.");
        }
        if (!$response->successful()) {
            throw new \Exception("Error en sincronización: " . $response->body());
        }
    }

    private function calcularPrecioFinal($base, $tipo, $margenes, $iva)
    {
        $multiplicador = 1.0;
        foreach ($margenes as $m) {
            if ($base >= $m->precio_min && $base <= $m->precio_max) {
                $multiplicador = ($tipo === 'rebaja') ? $m->multiplicador_rebaja : $m->multiplicador_normal;
                break;
            }
        }
        return round(($base * $multiplicador) / $iva, 2);
    }

    private function registrarDetalleAuditoria($logId, $sku, $prod, $normal, $rebaja, $estado, $mensaje)
    {
        WoocommerceSyncDetail::create([
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
