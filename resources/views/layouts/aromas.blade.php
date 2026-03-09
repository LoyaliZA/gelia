@extends('layouts.app')

@section('content')
<div class="mb-8 border-b border-dark-700">
    <nav class="-mb-px flex space-x-6 overflow-x-auto custom-scroll pb-2">
        
        <a href="{{ route('gelia.index') }}" 
           class="whitespace-nowrap pb-3 px-2 border-b-2 font-bold text-sm transition-colors {{ request()->routeIs('gelia.*') ? 'border-aromas-main text-aromas-main' : 'border-transparent text-dark-muted hover:text-gray-300 hover:border-dark-500' }}">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
                Listados e Inventario
            </span>
        </a>
        
        <a href="{{ route('aromas.clientes.index') }}" 
           class="whitespace-nowrap pb-3 px-2 border-b-2 font-bold text-sm transition-colors {{ request()->routeIs('aromas.clientes.*') ? 'border-yellow-500 text-yellow-500' : 'border-transparent text-dark-muted hover:text-gray-300 hover:border-dark-500' }}">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                Limpieza Clientes
            </span>
        </a>

        <a href="{{ route('aromas.gastos.index') }}" 
           class="whitespace-nowrap pb-3 px-2 border-b-2 font-bold text-sm transition-colors {{ request()->routeIs('aromas.gastos.*') ? 'border-emerald-500 text-emerald-500' : 'border-transparent text-dark-muted hover:text-gray-300 hover:border-dark-500' }}">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                Gastos Comprobables
            </span>
        </a>

        <a href="{{ route('aromas.transacciones.index') }}" 
           class="whitespace-nowrap pb-3 px-2 border-b-2 font-bold text-sm transition-colors {{ request()->routeIs('aromas.transacciones.*') ? 'border-orange-500 text-orange-500' : 'border-transparent text-dark-muted hover:text-gray-300 hover:border-dark-500' }}">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                Transacciones Bancarias
            </span>
        </a>

    </nav>
</div>

@yield('aromas-content')

@endsection