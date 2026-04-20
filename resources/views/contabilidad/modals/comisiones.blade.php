<dialog id="modalComisiones" class="bg-dark-800 border border-dark-700 rounded-xl shadow-2xl p-6 backdrop:bg-dark-900/80 w-full max-w-sm m-auto">
    <div class="flex justify-between items-center mb-4 border-b border-dark-700 pb-2">
        <h3 class="text-lg font-bold text-white">Comisiones</h3>
        <button onclick="document.getElementById('modalComisiones').close()" class="text-dark-muted hover:text-white material-symbols-outlined transition-colors">close</button>
    </div>
    <form id="formUpdateComisiones" class="space-y-4">
        @foreach($platforms as $plat)
        <div class="flex justify-between items-center bg-dark-900 p-2 rounded border border-dark-700">
            <label class="text-sm text-white">{{ $plat->name }}</label>
            <div class="flex items-center gap-1">
                <input type="number" step="0.01" value="{{ $plat->commission_percent }}"
                    class="input-config-comision w-20 bg-dark-800 border border-dark-700 rounded px-2 py-1.5 text-white text-sm text-right outline-none focus:border-bella-main"
                    data-id="{{ $plat->id }}">
                <span class="text-xs text-dark-muted">%</span>
            </div>
        </div>
        @endforeach
        <button type="submit" class="w-full bg-bella-main hover:bg-red-700 text-white py-2.5 rounded font-bold text-sm mt-4 transition-colors">Guardar Configuración</button>
    </form>
</dialog>