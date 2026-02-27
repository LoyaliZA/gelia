<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelia | Plantilla Bellaroma</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/bellaroma.js'])
</head>

<body class="bg-dark-900 text-dark-text min-h-screen font-sans selection:bg-blue-500 selection:text-white pb-20">

    <div id="toast" class="hidden fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold bg-emerald-600 transition-all">
        <span id="toast-msg">Mensaje</span>
    </div>

    <div id="overlay-carga" class="hidden fixed inset-0 bg-dark-900/90 z-40 flex flex-col justify-center items-center backdrop-blur-sm">
        <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-500 mb-4"></div>
        <h2 id="texto-carga" class="text-xl font-bold text-white tracking-widest animate-pulse">Procesando...</h2>
    </div>

    <div class="max-w-6xl mx-auto p-6">

        <header class="flex items-center justify-between mb-8 border-b border-dark-700 pb-6">
            <div>
                <h1 class="text-4xl font-extrabold text-white tracking-tight">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">Generador Bellaroma</span>
                </h1>
                <p class="text-gray-400 mt-1 text-sm font-medium">Cruce de existencias y precios con formato de protección para mayoristas.</p>
            </div>
            <a href="/" class="bg-dark-800 hover:bg-dark-700 border border-dark-700 text-gray-400 hover:text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center gap-2 transition shadow-lg">
                Volver al Inicio
            </a>
        </header>

        <form id="form-bellaroma">
            @csrf

            <h2 class="text-xl font-bold text-white mb-4 flex items-center">
                <span class="bg-blue-600 w-2 h-6 rounded mr-2"></span> Zona de Carga
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                <div class="drop-zone bg-dark-800 border-2 border-dark-700 rounded-xl p-5 hover:border-blue-500/50 transition-all duration-200" id="drop-existencias">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-bold text-md text-blue-400">1. Existencias *</h3>
                    </div>
                    <details class="mb-4 group">
                        <summary class="text-[11px] text-gray-500 hover:text-gray-300 cursor-pointer list-none flex items-center gap-1 transition select-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200 group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            Ver instrucciones
                        </summary>
                        <div class="mt-2 text-[10px] text-gray-400 bg-dark-900 p-2 rounded border border-dark-700 leading-relaxed shadow-inner">
                            <span class="text-blue-400 font-bold">Ruta:</span> Almacenes > Inventarios<br>
                            <span class="text-blue-400 font-bold">Opciones:</span> Exportar en formato Excel o CSV.
                        </div>
                    </details>
                    <input type="file" name="existencias" id="file-existencias" accept=".xlsx,.csv" class="block w-full text-xs text-gray-400 file:bg-blue-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-blue-500 cursor-pointer">
                    <p id="nombre-existencias" class="mt-2 text-[10px] text-gray-500 font-mono truncate"></p>
                </div>

                <div class="drop-zone bg-dark-800 border-2 border-dark-700 rounded-xl p-5 hover:border-emerald-500/50 transition-all duration-200" id="drop-precios">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="font-bold text-md text-emerald-400">2. Precios *</h3>
                    </div>
                    <details class="mb-4 group">
                        <summary class="text-[11px] text-gray-500 hover:text-gray-300 cursor-pointer list-none flex items-center gap-1 transition select-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200 group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                            Ver instrucciones
                        </summary>
                        <div class="mt-2 text-[10px] text-gray-400 bg-dark-900 p-2 rounded border border-dark-700 leading-relaxed shadow-inner">
                            <span class="text-emerald-400 font-bold">Ruta:</span> Almacen > Productos<br>
                            <span class="text-emerald-400 font-bold">Opciones:</span> Exportar lista de precios en Excel o CSV.
                        </div>
                    </details>
                    <input type="file" name="precios" id="file-precios" accept=".xlsx,.csv" class="block w-full text-xs text-gray-400 file:bg-emerald-600 file:text-white file:rounded-full file:px-3 file:py-1 file:border-0 hover:file:bg-emerald-500 cursor-pointer">
                    <p id="nombre-precios" class="mt-2 text-[10px] text-gray-500 font-mono truncate"></p>
                </div>
            </div>

            <div class="border-t border-dark-700 pt-8 max-w-2xl mx-auto">
                <button type="submit" class="w-full bg-gradient-to-r from-yellow-700 to-yellow-900 hover:from-yellow-400 text-white font-bold py-4 rounded-xl border border-dark-700 shadow-lg transition-all duration-300">
                    Generar Plantilla Mayorista
                </button>
            </div>
        </form>
    </div>
</body>

</html>