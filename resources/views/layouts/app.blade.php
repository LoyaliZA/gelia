<!DOCTYPE html>
<html lang="es" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gelia Hub')</title>

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=drive_export" />

    @stack('scripts')
</head>

<body
    class="bg-dark-900 text-dark-text min-h-screen font-sans selection:bg-aromas-main selection:text-white pb-20 antialiased">

    <div id="toast"
        class="hidden fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold transition-all">
        <span id="toast-msg">Mensaje</span>
    </div>

    <div id="overlay-carga"
        class="hidden fixed inset-0 bg-dark-900/95 z-50 flex flex-col justify-center items-center backdrop-blur-md">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-aromas-main mb-4"></div>
        <h2 id="texto-carga" class="text-sm font-semibold text-dark-muted tracking-widest animate-pulse uppercase">
            Procesando...</h2>
    </div>

    <nav class="sticky top-0 z-40 w-full backdrop-blur-md bg-dark-900/80 border-b border-dark-800 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex-shrink-0">
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
                        <span class="text-2xl font-light tracking-tight text-white">G.E.L.I.A.<span
                                class="font-bold text-aromas-main">HUB</span></span>
                    </a>
                </div>

                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-6">
                        <a href="{{ route('home') }}"
                            class="text-dark-muted hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">Inicio</a>
                        <a href="{{ route('gelia.index') }}"
                            class="text-dark-muted hover:text-aromas-main px-3 py-2 rounded-md text-sm font-medium transition-colors">Aromas</a>
                        <a href="{{ route('bellaroma.index') }}"
                            class="text-dark-muted hover:text-bella-main px-3 py-2 rounded-md text-sm font-medium transition-colors">Bellaroma</a>
                        <a href="{{ route('woocommerce.index') }}"
                            class="text-dark-muted hover:text-woocommerce-main px-3 py-2 rounded-md text-sm font-medium transition-colors">WooCommerce</a>
                        
                    </div>
                </div>

                <div class="-mr-2 flex md:hidden">
                    <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')"
                        class="inline-flex items-center justify-center p-2 rounded-md text-dark-muted hover:text-white hover:bg-dark-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-dark-900 focus:ring-white">
                        <span class="sr-only">Abrir menu principal</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="hidden md:hidden border-t border-dark-800 bg-dark-900" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="#"
                    class="text-dark-muted hover:text-white block px-3 py-2 rounded-md text-base font-medium">Inicio</a>
                <a href="#"
                    class="text-dark-muted hover:text-aromas-main block px-3 py-2 rounded-md text-base font-medium">Aromas</a>
                <a href="{{ route('bellaroma.index') }}"
                    class="text-dark-muted hover:text-bella-main block px-3 py-2 rounded-md text-base font-medium">Bellaroma</a>
                <a href="{{ route('woocommerce.index') }}"
                    class="text-dark-muted hover:text-woocommerce-main block px-3 py-2 rounded-md text-base font-medium">WooCommerce</a>
                
    
                    
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <script>
        // 1. Función para encender la pantalla de carga
        window.mostrarCarga = function(mensaje = 'Cargando...') {
            const overlay = document.getElementById('overlay-carga');
            const texto = document.getElementById('texto-carga');
            if (texto) texto.innerText = mensaje;
            if (overlay) overlay.classList.remove('hidden');
        };

        // 2. Función para apagar la pantalla de carga
        window.ocultarCarga = function() {
            const overlay = document.getElementById('overlay-carga');
            if (overlay) overlay.classList.add('hidden');
        };

        // 3. Función para mostrar notificaciones (Toasts)
        window.mostrarToast = function(mensaje, color = 'green') {
            const toast = document.getElementById('toast');
            const toastMsg = document.getElementById('toast-msg');

            if (!toast || !toastMsg) return;

            // Reconstruimos las clases para aplicar el color dinámico (green o red)
            toast.className = `fixed top-5 right-5 z-50 px-6 py-4 rounded-lg shadow-xl text-white font-bold transition-all bg-${color}-600`;
            toastMsg.innerText = mensaje;

            toast.classList.remove('hidden');

            // Ocultar automáticamente después de 3.5 segundos
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3500);
        };

        // Tu código de menú móvil que ya tenías...
        const btnMenu = document.getElementById('user-menu-button');
        if (btnMenu) {
            btnMenu.addEventListener('click', function() {
                document.getElementById('user-menu')?.classList.toggle('hidden');
            });
        }
    </script>

</body>

</html>