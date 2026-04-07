<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
        .container { background-color: #ffffff; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; border-top: 5px solid #10B981; } /* Borde Verde */
        h2 { color: #10B981; }
        p { color: #555555; line-height: 1.5; }
        .stats { background-color: #f8fafc; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #e2e8f0; }
        .footer { margin-top: 20px; font-size: 12px; color: #999999; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>✅ Sincronización Completada</h2>
        <p>El proceso de actualización masiva de precios en WooCommerce ha finalizado correctamente en el servidor de <strong>Gelia Hub</strong>.</p>
        
        <div class="stats">
            <p><strong>ID de Proceso:</strong> #{{ $log->id }}</p>
            <p><strong>Productos Procesados:</strong> {{ $log->procesados }} de {{ $log->total_productos }}</p>
            <p><strong>Fecha:</strong> {{ $log->updated_at->format('d/m/Y H:i:s') }}</p>
        </div>

        <p>Adjunto a este correo encontrarás el reporte de auditoría completo en formato CSV detallando todos los cambios realizados.</p>
        <br>
        <p>Saludos,<br><strong>GELIA</strong></p>
    </div>
    <div class="footer">Este es un mensaje automático. Por favor no respondas a este correo.</div>
</body>
</html>