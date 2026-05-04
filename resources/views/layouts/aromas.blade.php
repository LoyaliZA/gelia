@extends('layouts.app')

@section('content')
@php
    // Definición centralizada del menú para mantener el código DRY
    $menus = [
        [
            'route' => 'gelia.index',
            'active' => request()->routeIs('gelia.*'),
            'title' => 'Listados e Inventario',
            'colorClass' => 'text-aromas-main',
            'bgHover' => 'hover:bg-aromas-main/10 hover:border-aromas-main/30',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />'
        ],
        [
            'route' => 'aromas.clientes.index',
            'active' => request()->routeIs('aromas.clientes.*'),
            'title' => 'Limpieza Clientes',
            'colorClass' => 'text-yellow-500',
            'bgHover' => 'hover:bg-yellow-500/10 hover:border-yellow-500/30',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />'
        ],
        [
            'route' => 'aromas.gastos.index',
            'active' => request()->routeIs('aromas.gastos.*'),
            'title' => 'Gastos Comprobables',
            'colorClass' => 'text-emerald-500',
            'bgHover' => 'hover:bg-emerald-500/10 hover:border-emerald-500/30',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />'
        ],
        [
            'route' => 'aromas.transacciones.index',
            'active' => request()->routeIs('aromas.transacciones.*'),
            'title' => 'Transacciones Bancarias',
            'colorClass' => 'text-orange-500',
            'bgHover' => 'hover:bg-orange-500/10 hover:border-orange-500/30',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />'
        ],
        // NUEVO MÓDULO: Aviso Mercancía
        [
            'route' => 'aromas.avisos.index',
            'active' => request()->routeIs('aromas.avisos.*'),
            'title' => 'Aviso Mercancía',
            'colorClass' => 'text-cyan-400',
            'bgHover' => 'hover:bg-cyan-400/10 hover:border-cyan-400/30',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />'
        ],
        // Modulo Asistencia
        [
            'route' => 'aromas.asistencia.index',
            'active' => request()->routeIs('aromas.asistencia.*'),
            'title' => 'Asistencia (Checadora)',
            'colorClass' => 'text-indigo-400',
            'bgHover' => 'hover:bg-indigo-500/10 hover:border-indigo-500/30',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />'
        ],
        [
            'route' => 'aromas.limpieza.index',
            'active' => request()->routeIs('aromas.limpieza.*'),
            'title' => 'Limpiar Productos',
            'colorClass' => 'text-blue-400',
            'bgHover' => 'hover:bg-blue-400/10 hover:border-blue-400/30',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />'
        ],
    ];
    
    // Obtenemos el menú actual para mostrarlo en el botón principal
    $activeMenu = collect($menus)->firstWhere('active', true) ?? $menus[0];
@endphp

<div class="mb-8 relative z-30">
    
    <button onclick="toggleAromasMenu()" 
            class="w-full sm:w-80 flex items-center justify-between px-5 py-4 bg-dark-800 border border-dark-700 rounded-xl shadow-lg transition-all hover:bg-dark-700 focus:outline-none focus:ring-2 focus:ring-dark-500 cursor-pointer group">
        <div class="flex items-center gap-3 {{ $activeMenu['colorClass'] }} font-bold">
            <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $activeMenu['icon'] !!}</svg>
            <span class="text-sm tracking-wide">{{ $activeMenu['title'] }}</span>
        </div>
        <svg id="aromas-chevron" class="w-5 h-5 text-dark-muted transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div id="aromas-submenu" 
         class="absolute top-full left-0 mt-3 w-full sm:w-80 bg-dark-800 border border-dark-700 rounded-xl shadow-2xl overflow-hidden max-h-0 opacity-0 transition-all duration-300 ease-out origin-top">
        <nav class="flex flex-col p-2 gap-1">
            @foreach($menus as $menu)
            <a href="{{ route($menu['route']) }}" 
               class="flex items-center gap-3 px-4 py-3 rounded-lg border border-transparent text-sm font-semibold transition-all duration-200 
               {{ $menu['active'] ? $menu['colorClass'] . ' bg-dark-900 shadow-inner pointer-events-none' : 'text-dark-muted hover:text-white ' . $menu['bgHover'] }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $menu['icon'] !!}</svg>
                {{ $menu['title'] }}
                
                @if($menu['active'])
                <svg class="w-4 h-4 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                @endif
            </a>
            @endforeach
        </nav>
    </div>
</div>

<script>
    function toggleAromasMenu() {
        const submenu = document.getElementById('aromas-submenu');
        const chevron = document.getElementById('aromas-chevron');
        
        // Alternamos las clases para crear el efecto acordeón con fade-in
        if (submenu.classList.contains('max-h-0')) {
            submenu.classList.remove('max-h-0', 'opacity-0');
            submenu.classList.add('max-h-96', 'opacity-100');
            chevron.classList.add('rotate-180');
        } else {
            submenu.classList.remove('max-h-96', 'opacity-100');
            submenu.classList.add('max-h-0', 'opacity-0');
            chevron.classList.remove('rotate-180');
        }
    }

    // Cierra el menú automáticamente si el usuario hace clic fuera de él
    document.addEventListener('click', function(event) {
        const submenu = document.getElementById('aromas-submenu');
        const button = submenu.previousElementSibling;
        
        if (!button.contains(event.target) && !submenu.contains(event.target)) {
            submenu.classList.remove('max-h-96', 'opacity-100');
            submenu.classList.add('max-h-0', 'opacity-0');
            document.getElementById('aromas-chevron').classList.remove('rotate-180');
        }
    });
</script>

@yield('aromas-content')

@endsection