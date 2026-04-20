<dialog id="modalDetalles" class="bg-dark-800 border border-dark-700 rounded-xl shadow-2xl p-6 backdrop:bg-dark-900/80 w-full max-w-lg m-auto">
    <div class="flex justify-between items-center mb-4 border-b border-dark-700 pb-2">
        <h3 class="text-lg font-bold text-white">Productos del Pedido: <span id="detalles_num_pedido" class="text-bella-main"></span></h3>
        <button onclick="document.getElementById('modalDetalles').close()" class="text-dark-muted hover:text-white material-symbols-outlined transition-colors">close</button>
    </div>
    <div class="overflow-y-auto max-h-[300px] custom-scrollbar">
        <table class="w-full text-left text-sm">
            <thead class="text-dark-muted border-b border-dark-700">
                <tr>
                    <th class="py-2">SKU</th>
                    <th class="py-2">Producto</th>
                    <th class="py-2 text-center">Pzas</th>
                    <th class="py-2 text-right">Precio Unit.</th>
                </tr>
            </thead>
            <tbody id="tabla_detalles_body" class="text-white divide-y divide-dark-700/50">
            </tbody>
        </table>
    </div>
</dialog>