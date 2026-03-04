@props(['id', 'name', 'title', 'colorTheme' => 'aromas', 'instructions' => '', 'accept' => ''])

@php
    // Paleta de colores dinámica para el borde, texto y hover
    $themeColors = [
        'aromas' => 'border-aromas-main/30 hover:border-aromas-main text-aromas-main',
        'bella'  => 'border-bella-main/30 hover:border-bella-main text-bella-main',
        'purple' => 'border-purple-500/30 hover:border-purple-500 text-purple-400',
        'yellow' => 'border-amber-500/30 hover:border-amber-500 text-amber-400',
        'green'  => 'border-emerald-500/30 hover:border-emerald-500 text-emerald-400',
        'orange' => 'border-orange-500/30 hover:border-orange-500 text-orange-400',
    ];
    
    $colorClass = $themeColors[$colorTheme] ?? $themeColors['aromas'];
@endphp

<div class="relative drop-zone bg-dark-800 border-2 border-dashed rounded-xl p-6 transition-all duration-300 flex flex-col items-center justify-center text-center group {{ $colorClass }}" id="card-{{ $id }}">
    
    <div class="w-14 h-14 rounded-full bg-dark-900 flex items-center justify-center mb-4 group-hover:-translate-y-1 transition-transform duration-300 shadow-inner">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
    </div>

    <h3 class="font-bold text-md mb-2 tracking-wide">{{ $title }}</h3>
    
    @if($instructions)
    <details class="mb-5 group/details w-full relative z-20">
        <summary class="text-xs text-dark-muted hover:text-white cursor-pointer list-none flex items-center justify-center gap-1 transition select-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200 group-open/details:rotate-90" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            Ver instrucciones
        </summary>
        <div class="mt-3 text-[11px] text-gray-400 bg-dark-900 p-3 rounded-lg border border-dark-700 leading-relaxed shadow-inner text-left">
            {!! $instructions !!}
        </div>
    </details>
    @else
    <div class="mb-5"></div>
    @endif

    <input type="file" id="file-{{ $id }}" name="{{ $name }}" {{ $accept ? "accept=$accept" : "" }} class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 file-input-custom">
    
    <div class="bg-dark-900/50 border border-dark-700 rounded-lg py-2 px-4 text-xs text-dark-muted w-full truncate pointer-events-none" id="label-{{ $id }}">
        Click o arrastrar archivo aquí
    </div>
</div>