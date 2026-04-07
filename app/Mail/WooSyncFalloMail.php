<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\WoocommerceSyncLog;

class WooSyncFalloMail extends Mailable
{
    use Queueable, SerializesModels;

    public $log;

    public function __construct(WoocommerceSyncLog $log)
    {
        $this->log = $log;
    }

    public function build()
    {
        return $this->subject('ERROR: Sincronización de Precios GELIA')
                    ->view('emails.woo_sync_fallo');
    }
}