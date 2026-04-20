<dialog id="modalEditar" class="bg-dark-800 border border-dark-700 rounded-xl shadow-2xl p-6 backdrop:bg-dark-900/80 w-full max-w-lg m-auto">
    <div class="flex justify-between items-center mb-4 border-b border-dark-700 pb-2">
        <h3 class="text-lg font-bold text-white">Corregir Pedido: <span id="edit_num_pedido" class="text-bella-main"></span></h3>
        <button onclick="document.getElementById('modalEditar').close()" class="text-dark-muted hover:text-white material-symbols-outlined transition-colors">close</button>
    </div>
    <form id="formEditarPedido" class="space-y-4">
        <input type="hidden" id="edit_id">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-dark-muted mb-1">Transacción</label>
                <select id="edit_tipo" class="w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none">
                    <option value="venta">Venta Normal</option>
                    <option value="reembolso">Reembolso</option>
                    <option value="contracargo">Contracargo</option>
                </select>
            </div>
            <div class="col-span-2 mb-3">
                <label class="block text-xs text-dark-muted mb-1">Nombre del Cliente</label>
                <input type="text" id="edit_cliente" class="w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none" required>
            </div>
            <div>
                <label class="block text-xs text-dark-muted mb-1">Plataforma</label>
                <select id="edit_plataforma" class="w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none">
                    @foreach($platforms as $plat)
                    <option value="{{ $plat->id }}">{{ $plat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-dark-muted mb-1">Venta Total</label>
                <input type="number" step="0.01" id="edit_venta" class="w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none text-right" required>
            </div>
            <div>
                <label class="block text-xs text-dark-muted mb-1">Costo Envío</label>
                <input type="number" step="0.01" id="edit_envio" class="w-full bg-dark-900 border border-dark-700 rounded px-2 py-1.5 text-white text-sm outline-none text-right" required>
            </div>
            <div>
                <label class="block text-xs text-bella-main mb-1">Com. Cobrada</label>
                <input type="number" step="0.01" id="edit_comision" class="w-full bg-dark-900 border border-bella-main/50 rounded px-2 py-1.5 text-white text-sm outline-none text-right" required>
            </div>
        </div>

        <div class="mt-3 flex items-center bg-dark-900 p-2 rounded border border-dark-700">
            <label class="flex items-center cursor-pointer w-full">
                <input type="checkbox" id="edit_envio_pagado_cliente" class="form-checkbox h-4 w-4 text-bella-main rounded border-dark-600 bg-dark-800">
                <span class="ml-2 text-sm text-white font-semibold">El Cliente Pagó el Envío</span>
            </label>
        </div>
        
        <div class="mt-4 border-t border-dark-700 pt-3">
            <label class="block text-xs font-bold text-white uppercase mb-2">Ajustar Cantidad de Productos</label>
            <div id="edit_productos_container" class="space-y-2 max-h-[150px] overflow-y-auto custom-scrollbar">
            </div>
            <p class="text-[10px] text-dark-muted mt-1 italic">*El precio unitario original se mantiene y el subtotal se calculará automáticamente.</p>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="bg-yellow-600 hover:bg-yellow-500 text-white font-bold py-2 px-6 rounded transition-colors text-sm">Actualizar Registro</button>
        </div>
    </form>
</dialog>