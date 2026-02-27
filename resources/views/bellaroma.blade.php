<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelia | Plantilla Bellaroma</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/bellaroma.js'])
</head>
<body class="bg-dark-900 text-dark-text min-h-screen font-sans selection:bg-blue-500 selection:text-white">

    <div id="toast" class="hidden fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold bg-emerald-600 transition-all">
        <span id="toast-msg">Mensaje</span>
    </div>

    <div id="overlay-carga" class="hidden fixed inset-0 bg-dark-900/90 z-40 flex flex-col justify-center items-center backdrop-blur-sm">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-500 mb-4"></div>
        <h2 id="texto-carga" class="text-xl font-bold text-white tracking-widest animate-pulse">Procesando...</h2>
    </div>

    <div class="max-w-4xl mx-auto p-8">
        <div class="mb-8 border-b border-dark-700 pb-4">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-transparent">Generador Bellaroma</h1>
            <p class="text-gray-400 mt-2 italic text-sm">Cruce de existencias y precios con formato de protección.</p>
        </div>

        <form id="form-bellaroma" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div id="drop-existencias" class="drop-zone border-2 border-dark-700 rounded-2xl p-8 text-center bg-dark-800/50 hover:bg-dark-700/50 transition-all duration-300 group cursor-pointer">
                    <label class="cursor-pointer block">
                        <span class="text-4xl block mb-2 group-hover:scale-110 transition-transform">📦</span>
                        <span class="block text-lg font-semibold text-blue-400">Existencias</span>
                        <input type="file" name="existencias" id="file-existencias" class="hidden" accept=".xlsx,.csv">
                    </label>
                    <p id="nombre-existencias" class="mt-3 text-xs text-gray-500 font-mono truncate"></p>
                </div>

                <div id="drop-precios" class="drop-zone border-2 border-dark-700 rounded-2xl p-8 text-center bg-dark-800/50 hover:bg-dark-700/50 transition-all duration-300 group cursor-pointer">
                    <label class="cursor-pointer block">
                        <span class="text-4xl block mb-2 group-hover:scale-110 transition-transform">💲</span>
                        <span class="block text-lg font-semibold text-emerald-400">Precios</span>
                        <input type="file" name="precios" id="file-precios" class="hidden" accept=".xlsx,.csv">
                    </label>
                    <p id="nombre-precios" class="mt-3 text-xs text-gray-500 font-mono truncate"></p>
                </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-gray-700 to-gray-800 hover:from-blue-600 hover:to-emerald-600 text-white font-bold py-4 rounded-xl border border-dark-700 shadow-lg transition-all duration-500">
                GENERAR PLANTILLA MAYORISTA
            </button>
        </form>
    </div>
</body>
</html>