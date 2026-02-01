@php
    $theme = $content['theme'] ?? [];
    $sections = $content['sections'] ?? [];
    $primaryColor = $theme['primaryColor'] ?? '#d4a574';
    $secondaryColor = $theme['secondaryColor'] ?? '#8b7355';
    $fontFamily = $theme['fontFamily'] ?? 'Playfair Display';
    
    $headerStyle = $sections['header']['style'] ?? [];
    $heroStyle = $sections['hero']['style'] ?? [];
    $footerStyle = $sections['footer']['style'] ?? [];
@endphp

<div class="border rounded-lg overflow-hidden shadow-sm" style="font-family: {{ $fontFamily }}, serif;">
    {{-- Header Preview --}}
    @if($sections['header']['enabled'] ?? true)
    <div 
        class="px-4 py-3 flex items-center justify-between"
        style="background-color: {{ $headerStyle['backgroundColor'] ?? '#ffffff' }}; height: {{ $headerStyle['height'] ?? '80px' }};"
    >
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full" style="background-color: {{ $primaryColor }};"></div>
            <span class="font-semibold text-gray-800">Logo</span>
        </div>
        <nav class="flex gap-4 text-sm text-gray-600">
            <span>Início</span>
            <span>Sobre</span>
            <span>Galeria</span>
            <span>Confirmação de Presença</span>
        </nav>
    </div>
    @endif

    {{-- Hero Preview --}}
    @if($sections['hero']['enabled'] ?? true)
    <div 
        class="relative h-48 flex items-center justify-center"
        style="background: linear-gradient(135deg, {{ $primaryColor }}40, {{ $secondaryColor }}40);"
    >
        <div 
            class="absolute inset-0"
            style="background-color: {{ $heroStyle['overlay']['color'] ?? '#000000' }}; opacity: {{ $heroStyle['overlay']['opacity'] ?? 0.3 }};"
        ></div>
        <div class="relative text-center text-white z-10">
            <h1 class="text-2xl font-bold mb-2">Nome & Nome</h1>
            <p class="text-sm opacity-90">Vamos nos casar!</p>
            <p class="text-xs mt-2 opacity-75">01 de Janeiro de 2026</p>
        </div>
    </div>
    @endif

    {{-- Save the Date Preview --}}
    @if($sections['saveTheDate']['enabled'] ?? true)
    <div 
        class="px-4 py-6 text-center"
        style="background-color: {{ $sections['saveTheDate']['style']['backgroundColor'] ?? '#f5f5f5' }};"
    >
        <h2 class="text-lg font-semibold mb-2" style="color: {{ $primaryColor }};">Save the Date</h2>
        <div class="flex justify-center gap-4 text-sm">
            <div class="text-center">
                <div class="text-2xl font-bold" style="color: {{ $secondaryColor }};">120</div>
                <div class="text-xs text-gray-500">dias</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold" style="color: {{ $secondaryColor }};">08</div>
                <div class="text-xs text-gray-500">horas</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold" style="color: {{ $secondaryColor }};">45</div>
                <div class="text-xs text-gray-500">min</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Gallery Preview --}}
    @if($sections['photoGallery']['enabled'] ?? false)
    <div 
        class="px-4 py-6"
        style="background-color: {{ $sections['photoGallery']['style']['backgroundColor'] ?? '#ffffff' }};"
    >
        <h2 class="text-lg font-semibold mb-3 text-center" style="color: {{ $primaryColor }};">Galeria</h2>
        <div class="grid grid-cols-3 gap-2">
            @for($i = 0; $i < 6; $i++)
            <div class="aspect-square rounded" style="background-color: {{ $primaryColor }}20;"></div>
            @endfor
        </div>
    </div>
    @endif

    {{-- RSVP Preview --}}
    @if($sections['rsvp']['enabled'] ?? false)
    <div 
        class="px-4 py-6 text-center"
        style="background-color: {{ $sections['rsvp']['style']['backgroundColor'] ?? '#f5f5f5' }};"
    >
        <h2 class="text-lg font-semibold mb-2" style="color: {{ $primaryColor }};">Confirme sua Presença</h2>
        <button 
            class="px-4 py-2 rounded text-white text-sm"
            style="background-color: {{ $primaryColor }};"
        >
            Confirmar
        </button>
    </div>
    @endif

    {{-- Footer Preview --}}
    @if($sections['footer']['enabled'] ?? true)
    <div 
        class="px-4 py-4 text-center text-sm"
        style="background-color: {{ $footerStyle['backgroundColor'] ?? '#333333' }}; color: {{ $footerStyle['textColor'] ?? '#ffffff' }};"
    >
        <div class="flex justify-center gap-3 mb-2">
            <span class="w-6 h-6 rounded-full bg-white/20"></span>
            <span class="w-6 h-6 rounded-full bg-white/20"></span>
            <span class="w-6 h-6 rounded-full bg-white/20"></span>
        </div>
        <p class="opacity-75">© 2026 - Feito com ❤️</p>
    </div>
    @endif
</div>

<p class="text-xs text-gray-500 mt-2 text-center">
    Preview ilustrativo das cores e estilos do template
</p>
