<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\WoocommerceSyncLog;

class WooSyncExitoMail extends Mailable
{
    use Queueable, SerializesModels;

    public $log;
    public $rutaCsvTemporal;

    public function __construct(WoocommerceSyncLog $log, $rutaCsvTemporal)
    {
        $this->log = $log;
        $this->rutaCsvTemporal = $rutaCsvTemporal;
    }

    public function build()
    {
        return $this->subject('✅ Éxito: Sincronización de Precios GELIA')
            ->view('emails.woo_sync_exito');
            
    }
}
