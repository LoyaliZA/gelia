<dialog id="modalConfirmarRetiro" class="bg-dark-800 border border-dark-700 rounded-xl shadow-2xl p-6 backdrop:bg-dark-900/80 w-full max-w-sm m-auto">
    <div class="flex justify-between items-center mb-4 border-b border-dark-700 pb-2">
        <h3 class="text-lg font-bold text-white">Confirmar Pago: <span id="conf_num_pedido" class="text-bella-main"></span></h3>
        <button onclick="document.getElementById('modalConfirmarRetiro').close()" class="text-dark-muted hover:text-white material-symbols-outlined transition-colors">close</button>
    </div>
    <form id="formConfirmarRetiro" class="space-y-4">
        <input type="hidden" id="conf_id">
        <div>
            <label class="block text-xs text-dark-muted mb-1">Monto Real Recibido en Banco ($)</label>
            <input type="number" step="0.01" id="conf_monto" class="w-full bg-dark-900 border border-dark-600 rounded px-3 py-2 text-white outline-none focus:border-green-500 font-bold text-lg text-right" required>
            <p class="text-[10px] text-dark-muted mt-1">Esperado por plataforma: <span id="conf_esperado" class="font-bold text-white"></span></p>
        </div>
        <div>
            <label class="block text-xs text-dark-muted mb-1">Fecha de Ingreso a Banco</label>
            <input type="date" id="conf_fecha" required class="w-full bg-dark-900 border border-dark-600 rounded px-3 py-2 text-white text-sm outline-none focus:border-green-500 [color-scheme:dark]" value="{{ date('Y-m-d') }}">
        </div>
        <button type="submit" id="btnConfIndividual" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2.5 rounded transition-colors shadow-lg shadow-green-600/20">
            Guardar y Transferir a Neta
        </button>
    </form>
</dialog>