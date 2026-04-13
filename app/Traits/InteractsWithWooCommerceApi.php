<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

trait InteractsWithWooCommerceApi
{
    /**
     * CAMBIO NUEVO: Centralización de la configuración del cliente HTTP.
     * Reemplaza la instanciación manual en cada Job y lee credenciales seguras.
     */
    protected function getWooClient(string $botName = 'GeliaSystem-Bot/1.0'): PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => $botName,
            'Accept'     => 'application/json',
        ])
        ->withBasicAuth(
            config('services.woocommerce.key'), 
            config('services.woocommerce.secret')
        )
        ->timeout(60);
    }

    /**
     * CAMBIO NUEVO: Centralización de la validación de bloqueos (Cloudflare).
     * Se invoca después de cada petición para abortar si hay códigos de error de WAF.
     */
    protected function validateSecurityResponse($response): void
    {
        $status = $response->status();
        
        if (in_array($status, [403, 429, 503])) {
            throw new \Exception("Bloqueo de seguridad detectado en destino (HTTP {$status}). Proceso abortado.");
        }

        if (!$response->successful()) {
            throw new \Exception("Error de red o de API: " . $response->body());
        }
    }
}