<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Resurtido | Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Array para guardar el orden exacto de tus clics
        let ordenSeleccionado = [];

        function actualizarOrden(checkbox) {
            const valor = checkbox.value;
            const badgeId = 'badge-' + valor;
            const badge = document.getElementById(badgeId);

            if (checkbox.checked) {
                ordenSeleccionado.push(valor);
                badge.innerText = ordenSeleccionado.length;
                badge.classList.remove('hidden');
            } else {
                ordenSeleccionado = ordenSeleccionado.filter(item => item !== valor);
                badge.classList.add('hidden');
                
                // Recalculamos los números
                ordenSeleccionado.forEach((item, index) => {
                    document.getElementById('badge-' + item).innerText = index + 1;
                });
            }
            document.getElementById('input-orden-final').value = ordenSeleccionado.join(',');
        }

        function mostrarCarga() {
            document.getElementById('btn-texto').classList.add('hidden');
            document.getElementById('btn-carga').classList.remove('hidden');
            document.getElementById('btn-submit').classList.add('cursor-not-allowed', 'opacity-75');
            // Dejamos un pequeño timeout para que el form se envíe antes de deshabilitar
            setTimeout(() => {
                document.getElementById('btn-submit').disabled = true; 
            }, 100);
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen p-8 font-sans text-gray-800">

    <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-8 text-white">
            <h1 class="text-3xl font-extrabold mb-2">Generador Dinámico</h1>
            <p class="text-blue-100 text-sm">El orden en que selecciones las casillas será el orden de las columnas en el Excel.</p>
        </div>

        <form action="{{ route('resurtido.generar') }}" method="POST" enctype="multipart/form-data" class="p-8" onsubmit="mostrarCarga()">
            @csrf
            
            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p class="font-bold">¡Ups! Algo salió mal:</p>
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <input type="hidden" name="orden_final" id="input-orden-final" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                <div class="border-2 border-dashed border-blue-300 bg-blue-50 rounded-xl p-6 text-center">
                    <span class="block text-blue-800 font-bold mb-2">1. Reporte de Existencias</span>
                    <input type="file" name="existencias" required accept=".xlsx,.csv,.txt" class="block w-full text-sm text-slate-500 file:bg-blue-600 file:text-white file:rounded-full file:px-4 file:py-1 cursor-pointer">
                </div>
                <div class="border-2 border-dashed border-green-300 bg-green-50 rounded-xl p-6 text-center">
                    <span class="block text-green-800 font-bold mb-2">2. Lista de Precios</span>
                    <input type="file" name="precios" required accept=".xlsx,.csv,.txt" class="block w-full text-sm text-slate-500 file:bg-green-600 file:text-white file:rounded-full file:px-4 file:py-1 cursor-pointer">
                </div>
            </div>

            <h2 class="text-xl font-bold mb-2 text-gray-700 border-b pb-2">
                2. Selecciona las columnas (En el orden que prefieras)
            </h2>
            <p class="text-sm text-gray-500 mb-4">Dale clic a las casillas en el orden que quieres que aparezcan.</p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                @php
                    $campos = ['Almacen','Folio','SKU', 'Descripcion', 'Marca', 'Existencia', 'Costo', 'PG', 'Plataformas', 'Lista3', 'Lista4'];
                @endphp

                @foreach($campos as $campo)
                <label class="relative flex items-center space-x-3 bg-white p-4 rounded-lg border shadow-sm cursor-pointer hover:border-indigo-400 transition select-none">
                    <input type="checkbox" name="columnas[]" value="{{ $campo }}" onchange="actualizarOrden(this)" class="w-6 h-6 text-indigo-600 rounded focus:ring-indigo-500">
                    <span class="font-medium text-gray-700">{{ $campo }}</span>
                    
                    <span id="badge-{{ $campo }}" class="hidden absolute top-2 right-2 bg-blue-600 text-white text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full shadow-md">
                        1
                    </span>
                </label>
                @endforeach
            </div>

            <div class="flex gap-4 mb-8">
                <label class="flex items-center p-3 border rounded-lg cursor-pointer bg-white hover:bg-gray-50 w-full">
                    <input type="radio" name="formato" value="xlsx" checked class="w-5 h-5 text-blue-600">
                    <span class="ml-2 font-bold text-gray-700">Excel (.xlsx)</span>
                </label>
                <label class="flex items-center p-3 border rounded-lg cursor-pointer bg-white hover:bg-gray-50 w-full">
                    <input type="radio" name="formato" value="csv" class="w-5 h-5 text-green-600">
                    <span class="ml-2 font-bold text-gray-700">CSV (Rápido)</span>
                </label>
            </div>

            <button id="btn-submit" type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg transition-colors">
                <span id="btn-texto">Generar Reporte Ordenado</span>
                <span id="btn-carga" class="hidden">Procesando...</span>
            </button>
        </form>
    </div>
</body>
</html>