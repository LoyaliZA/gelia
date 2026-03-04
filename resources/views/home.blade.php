@extends('layouts.app')

@section('title', 'Gelia Hub | Menú Principal')

@section('content')
<div class="min-h-[80vh] flex flex-col items-center justify-center">
    
    <div class="text-center mb-16">
        <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-4">
            Bienvenido a <span class="text-transparent bg-clip-text bg-gradient-to-r from-aromas-main to-bella-main">Gelia Hub</span>
        </h1>
        <p class="text-lg text-dark-muted max-w-2xl mx-auto">
            Selecciona el módulo con el que deseas trabajar hoy.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-5xl">
        
        <a href="{{ route('gelia.index') }}" class="group relative bg-dark-800 rounded-2xl p-10 border border-dark-700 hover:border-aromas-main/50 transition-all duration-300 hover:shadow-2xl hover:shadow-aromas-main/10 overflow-hidden flex flex-col items-center text-center">
            <div class="absolute inset-0 bg-gradient-to-br from-aromas-main/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            
            <div class="relative z-10">
                <div class="w-32 h-32 mb-6 mx-auto bg-dark-900 rounded-full flex items-center justify-center border-4 border-dark-800 group-hover:border-aromas-main transition-colors shadow-inner">
                    <span class="text-3xl font-bold text-aromas-main">A</span>
                </div>
                
                <h2 class="text-2xl font-bold text-white mb-3 group-hover:text-aromas-main transition-colors">Aromas</h2>
                <p class="text-dark-muted text-sm leading-relaxed">
                    Generación de listas automatizadas, control de resurtido, cruce de costos Wizerp y limpieza de base de datos de clientes.
                </p>
            </div>
            
            <div class="mt-8 flex items-center text-aromas-main font-semibold text-sm group-hover:translate-x-2 transition-transform">
                Ingresar al módulo 
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </div>
        </a>

        <a href="{{ route('bellaroma.index') }}" class="group relative bg-dark-800 rounded-2xl p-10 border border-dark-700 hover:border-bella-main/50 transition-all duration-300 hover:shadow-2xl hover:shadow-bella-main/10 overflow-hidden flex flex-col items-center text-center">
            <div class="absolute inset-0 bg-gradient-to-br from-bella-main/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            
            <div class="relative z-10">
                <div class="w-32 h-32 mb-6 mx-auto bg-dark-900 rounded-full flex items-center justify-center border-4 border-dark-800 group-hover:border-bella-main transition-colors shadow-inner">
                    <span class="text-3xl font-bold text-bella-main">B</span>
                </div>
                
                <h2 class="text-2xl font-bold text-white mb-3 group-hover:text-bella-main transition-colors">Bellaroma</h2>
                <p class="text-dark-muted text-sm leading-relaxed">
                    Sistema de creación de plantillas de pedido bloqueadas e inyección de lógicas de ofuscación de inventario.
                </p>
            </div>

            <div class="mt-8 flex items-center text-bella-main font-semibold text-sm group-hover:translate-x-2 transition-transform">
                Ingresar al módulo 
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </div>
        </a>

    </div>
</div>
@endsection