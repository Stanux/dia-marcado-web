<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $content['meta']['title'] ?? $wedding->title ?? 'Site de Casamento' }}</title>
    
    @if(!empty($content['meta']['description']))
    <meta name="description" content="{{ $content['meta']['description'] }}">
    @endif
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $content['meta']['title'] ?? $wedding->title ?? 'Site de Casamento' }}">
    @if(!empty($content['meta']['description']))
    <meta property="og:description" content="{{ $content['meta']['description'] }}">
    @endif
    @if(!empty($content['meta']['ogImage']))
    <meta property="og:image" content="{{ $content['meta']['ogImage'] }}">
    @endif
    @if(!empty($content['meta']['canonical']))
    <link rel="canonical" href="{{ $content['meta']['canonical'] }}">
    @endif
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&family=Tangerine:wght@400;700&family=Allura&family=Sacramento&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&display=swap" rel="stylesheet">
    
    @php
        // Theme variables
        $primaryColor = $content['theme']['primaryColor'] ?? '#d4a574';
        $secondaryColor = $content['theme']['secondaryColor'] ?? '#8b7355';
        $fontFamily = $content['theme']['fontFamily'] ?? 'Georgia, serif';
        $fontSize = $content['theme']['fontSize'] ?? '16px';
        
        // Header styles
        $headerBg = $content['sections']['header']['style']['backgroundColor'] ?? '#ffffff';
        $headerAlign = $content['sections']['header']['style']['alignment'] ?? 'center';
        $headerHeight = $content['sections']['header']['style']['height'] ?? '80px';
        $headerSticky = $content['sections']['header']['style']['sticky'] ?? false;
        
        // Hero styles
        $heroTextAlign = $content['sections']['hero']['style']['textAlign'] ?? 'center';
        $heroOverlayColor = $content['sections']['hero']['style']['overlay']['color'] ?? '#000000';
        $heroOverlayOpacity = $content['sections']['hero']['style']['overlay']['opacity'] ?? 0.3;
        $heroLayout = $content['sections']['hero']['layout'] ?? 'full-bleed';
        $heroAnimation = $content['sections']['hero']['style']['animation'] ?? 'none';
        $heroAnimationDuration = $content['sections']['hero']['style']['animationDuration'] ?? 500;
    @endphp

    <style>
        :root {
            --primary-color: {{ $primaryColor }};
            --secondary-color: {{ $secondaryColor }};
            --font-family: {{ $fontFamily }};
            --font-size: {{ $fontSize }};
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: var(--font-family);
            font-size: var(--font-size);
            line-height: 1.6;
            color: #333;
        }
        
        .section { padding: 60px 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* Header */
        .header {
            background: {{ $headerBg }};
            min-height: {{ $headerHeight }};
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 20px;
            @if($headerSticky) position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); @endif
        }
        .header-container {
            width: 80%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            gap: 20px;
        }
        .header-brand { 
            display: flex; 
            align-items: center; 
            gap: 10px;
            justify-self: start;
        }
        .header-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            justify-self: center;
        }
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            justify-self: end;
        }
        .header h1 { margin: 0; }
        .header .subtitle { margin: 5px 0 0 0; }
        .header-nav { display: flex; gap: 20px; align-items: center; }
        .header-nav a { color: #333; text-decoration: none; font-size: 0.9rem; transition: color 0.3s; }
        .header-nav a:hover { color: var(--primary-color); }
        .header-action {
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .header-action.primary { background: var(--primary-color); color: white; }
        .header-action.secondary { background: transparent; border: 1px solid var(--primary-color); color: var(--primary-color); }
        .header-action.ghost { background: transparent; color: var(--primary-color); }
        .header-action:hover { opacity: 0.8; }

        /* Hero */
        .hero {
            position: relative;
            min-height: {{ $heroLayout === 'full-bleed' ? '100vh' : '60vh' }};
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: {{ $heroTextAlign }};
            overflow: hidden;
            @if($heroLayout === 'boxed') margin: 20px; border-radius: 10px; @endif
        }
        .hero-background {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-size: cover; background-position: center;
        }
        .hero-video-container {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0; overflow: hidden;
        }
        .hero-video-container iframe, .hero-video-container video {
            position: absolute; top: 50%; left: 50%;
            min-width: 100%; min-height: 100%; width: auto; height: auto;
            transform: translate(-50%, -50%); pointer-events: none;
        }
        .hero-overlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: {{ $heroOverlayColor }}; opacity: {{ $heroOverlayOpacity }}; z-index: 1;
        }
        .hero-content {
            position: relative; z-index: 2; color: white; padding: 40px; max-width: 800px;
            @if($heroAnimation !== 'none')
            animation: hero-{{ $heroAnimation }} {{ $heroAnimationDuration }}ms ease-out;
            @endif
        }
        .hero-split { display: flex; align-items: stretch; min-height: 80vh; }
        .hero-split .hero-media { flex: 1; position: relative; }
        .hero-split .hero-content { flex: 1; display: flex; flex-direction: column; justify-content: center; background: #fff; color: #333; }
        .hero h2 { margin-bottom: 1rem; }
        .hero .subtitle { margin-bottom: 2rem; opacity: 0.9; }
        .cta-button {
            display: inline-block; padding: 15px 30px;
            background: var(--primary-color); color: white;
            text-decoration: none; border-radius: 5px; margin: 10px;
            transition: all 0.3s;
        }
        .cta-button:hover { background: var(--secondary-color); transform: translateY(-2px); }
        .cta-button.secondary { background: transparent; border: 2px solid white; }
        .cta-button.secondary:hover { background: rgba(255,255,255,0.1); }
        
        @keyframes hero-fade { from { opacity: 0; } to { opacity: 1; } }
        @keyframes hero-slide { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes hero-zoom { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }

        /* Save the Date */
        @php
            $stdBg = $content['sections']['saveTheDate']['style']['backgroundColor'] ?? '#f5f5f5';
            $stdLayout = $content['sections']['saveTheDate']['style']['layout'] ?? 'inline';
        @endphp
        .save-the-date { background: {{ $stdBg }}; text-align: center; }
        .save-the-date h2 { color: var(--primary-color); font-size: 2rem; margin-bottom: 1rem; }
        .save-the-date-card {
            @if($stdLayout === 'card')
            background: white; border-radius: 10px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto;
            @endif
        }
        .countdown { display: flex; justify-content: center; gap: 30px; margin: 30px 0; flex-wrap: wrap; }
        .countdown-item { text-align: center; }
        .countdown-number { font-size: 3rem; font-weight: bold; color: var(--primary-color); }
        .countdown-label { font-size: 0.9rem; color: #666; text-transform: uppercase; }
        .map-container { margin: 30px auto; max-width: 600px; height: 300px; border-radius: 10px; overflow: hidden; }
        .map-container iframe { width: 100%; height: 100%; border: 0; }
        .calendar-button {
            display: inline-block; padding: 12px 24px;
            background: var(--primary-color); color: white;
            text-decoration: none; border-radius: 5px; margin-top: 20px;
        }
        .calendar-button:hover { background: var(--secondary-color); }
        
        /* Gift Registry & RSVP */
        .gift-registry, .rsvp { text-align: center; }
        .gift-registry h2, .rsvp h2 { color: var(--primary-color); margin-bottom: 1rem; }
        
        /* Photo Gallery */
        @php
            $galleryBg = $content['sections']['photoGallery']['style']['backgroundColor'] ?? '#ffffff';
            $galleryCols = $content['sections']['photoGallery']['style']['columns'] ?? 3;
            $galleryLayout = $content['sections']['photoGallery']['layout'] ?? 'grid';
        @endphp
        .photo-gallery { background: {{ $galleryBg }}; }
        .photo-gallery h2 { text-align: center; color: var(--primary-color); font-size: 2rem; margin-bottom: 2rem; }
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat({{ $galleryCols }}, 1fr);
            gap: 20px;
        }
        @if($galleryLayout === 'masonry')
        .gallery-grid { column-count: {{ $galleryCols }}; display: block; }
        .gallery-item { break-inside: avoid; margin-bottom: 20px; }
        @endif
        .gallery-item { position: relative; overflow: hidden; border-radius: 8px; cursor: pointer; }
        .gallery-item img { width: 100%; height: {{ $galleryLayout === 'masonry' ? 'auto' : '250px' }}; object-fit: cover; transition: transform 0.3s; }
        .gallery-item:hover img { transform: scale(1.05); }
        .gallery-item .caption { position: absolute; bottom: 0; left: 0; right: 0; padding: 10px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white; font-size: 0.9rem; }
        .gallery-item .download-btn { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.5); color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 0.8rem; opacity: 0; transition: opacity 0.3s; }
        .gallery-item:hover .download-btn { opacity: 1; }

        /* Footer */
        @php
            $footerBg = $content['sections']['footer']['style']['backgroundColor'] ?? '#333333';
            $footerText = $content['sections']['footer']['style']['textColor'] ?? '#ffffff';
            $footerBorder = $content['sections']['footer']['style']['borderTop'] ?? false;
        @endphp
        .footer {
            background: {{ $footerBg }}; color: {{ $footerText }};
            padding: 40px 20px; text-align: center;
            @if($footerBorder) border-top: 1px solid rgba(255,255,255,0.2); @endif
        }
        .social-links { margin-bottom: 20px; display: flex; justify-content: center; gap: 15px; }
        .social-links a {
            display: inline-flex; align-items: center; justify-content: center;
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(255,255,255,0.1); color: inherit;
            text-decoration: none; transition: all 0.3s;
        }
        .social-links a:hover { background: var(--primary-color); transform: translateY(-3px); }
        .back-to-top { display: inline-block; margin-top: 20px; color: inherit; text-decoration: none; opacity: 0.7; }
        .back-to-top:hover { opacity: 1; }
        
        /* Lightbox */
        .lightbox { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.95); z-index: 1000; align-items: center; justify-content: center; }
        .lightbox.active { display: flex; }
        .lightbox img { max-width: 90%; max-height: 90%; object-fit: contain; }
        .lightbox-close { position: absolute; top: 20px; right: 20px; color: white; font-size: 2rem; cursor: pointer; }
        .lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); color: white; font-size: 2rem; cursor: pointer; padding: 20px; }
        .lightbox-prev { left: 20px; }
        .lightbox-next { right: 20px; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header-container { 
                grid-template-columns: 1fr; 
                width: 95%;
                gap: 15px;
            }
            .header-brand { justify-self: center; }
            .header-center { justify-self: center; }
            .header-actions { 
                justify-self: center; 
                flex-direction: column;
            }
            .header-nav { flex-wrap: wrap; justify-content: center; }
            .hero h2 { font-size: 2rem; }
            .hero .subtitle { font-size: 1.2rem; }
            .hero-split { flex-direction: column; }
            .countdown { gap: 15px; }
            .countdown-number { font-size: 2rem; }
            .gallery-grid { grid-template-columns: repeat(2, 1fr); }
            @if($galleryLayout === 'masonry') .gallery-grid { column-count: 2; } @endif
        }
    </style>
</head>
<body>

    @if(($content['sections']['header']['enabled'] ?? true))
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <!-- Logo à esquerda -->
            <div class="header-brand">
                @php
                    $logoType = $content['sections']['header']['logo']['type'] ?? 'image';
                @endphp
                
                @if($logoType === 'image' && !empty($content['sections']['header']['logo']['url']))
                    <img src="{{ $content['sections']['header']['logo']['url'] }}" alt="{{ $content['sections']['header']['logo']['alt'] ?? 'Logo' }}" style="max-height: 50px;">
                @elseif($logoType === 'text')
                    @php
                        $logoText = $content['sections']['header']['logo']['text'] ?? [];
                        $initial1 = strtoupper(substr($logoText['initials'][0] ?? '', 0, 1));
                        $initial2 = strtoupper(substr($logoText['initials'][1] ?? '', 0, 1));
                        $connector = $logoText['connector'] ?? '&';
                        $logoTypography = $logoText['typography'] ?? [
                            'fontFamily' => 'Playfair Display',
                            'fontColor' => '#333333',
                            'fontSize' => 32,
                            'fontWeight' => 700,
                            'fontItalic' => false,
                            'fontUnderline' => false,
                        ];
                    @endphp
                    @if($initial1 || $initial2)
                    <span class="logo-text" style="
                        font-family: {{ $logoTypography['fontFamily'] ?? 'Playfair Display' }};
                        color: {{ $logoTypography['fontColor'] ?? '#333333' }};
                        font-size: {{ $logoTypography['fontSize'] ?? 32 }}px;
                        font-weight: {{ $logoTypography['fontWeight'] ?? 700 }};
                        font-style: {{ ($logoTypography['fontItalic'] ?? false) ? 'italic' : 'normal' }};
                        text-decoration: {{ ($logoTypography['fontUnderline'] ?? false) ? 'underline' : 'none' }};
                        letter-spacing: 0.1em;
                    ">
                        {{ $initial1 }} {{ $connector }} {{ $initial2 }}
                    </span>
                    @endif
                @endif
            </div>
            
            <!-- Texto centralizado -->
            <div class="header-center">
                @if(!empty($content['sections']['header']['title']))
                @php
                    $titleTypography = $content['sections']['header']['titleTypography'] ?? [
                        'fontFamily' => 'Playfair Display',
                        'fontColor' => '#333333',
                        'fontSize' => 48,
                        'fontWeight' => 700,
                        'fontItalic' => false,
                        'fontUnderline' => false,
                    ];
                @endphp
                <h1 style="
                    font-family: {{ $titleTypography['fontFamily'] ?? 'Playfair Display' }};
                    color: {{ $titleTypography['fontColor'] ?? '#333333' }};
                    font-size: {{ $titleTypography['fontSize'] ?? 48 }}px;
                    font-weight: {{ $titleTypography['fontWeight'] ?? 700 }};
                    font-style: {{ ($titleTypography['fontItalic'] ?? false) ? 'italic' : 'normal' }};
                    text-decoration: {{ ($titleTypography['fontUnderline'] ?? false) ? 'underline' : 'none' }};
                    margin: 0;
                ">{{ $content['sections']['header']['title'] }}</h1>
                @endif
                @if(!empty($content['sections']['header']['subtitle']))
                @php
                    $subtitleTypography = $content['sections']['header']['subtitleTypography'] ?? [
                        'fontFamily' => 'Montserrat',
                        'fontColor' => '#666666',
                        'fontSize' => 24,
                        'fontWeight' => 400,
                        'fontItalic' => true,
                        'fontUnderline' => false,
                    ];
                @endphp
                <p class="subtitle" style="
                    font-family: {{ $subtitleTypography['fontFamily'] ?? 'Montserrat' }};
                    color: {{ $subtitleTypography['fontColor'] ?? '#666666' }};
                    font-size: {{ $subtitleTypography['fontSize'] ?? 24 }}px;
                    font-weight: {{ $subtitleTypography['fontWeight'] ?? 400 }};
                    font-style: {{ ($subtitleTypography['fontItalic'] ?? false) ? 'italic' : 'normal' }};
                    text-decoration: {{ ($subtitleTypography['fontUnderline'] ?? false) ? 'underline' : 'none' }};
                    margin: 5px 0 0 0;
                ">{{ $content['sections']['header']['subtitle'] }}</p>
                @endif
            </div>
            
            <!-- Navegação e botão de ação à direita -->
            <div class="header-actions">
                @if(!empty($content['sections']['header']['navigation']))
                <nav class="header-nav">
                    @foreach($content['sections']['header']['navigation'] as $navItem)
                        @if(($navItem['showInMenu'] ?? false) && !empty($navItem['sectionKey']))
                            @php
                                $sectionIds = [
                                    'hero' => 'hero',
                                    'saveTheDate' => 'save-the-date',
                                    'giftRegistry' => 'lista-presentes',
                                    'rsvp' => 'confirmar-presenca',
                                    'photoGallery' => 'galeria',
                                ];
                                $sectionLabels = [
                                    'hero' => 'Hero',
                                    'saveTheDate' => 'Save the Date',
                                    'giftRegistry' => 'Lista de Presentes',
                                    'rsvp' => 'Confirme Presença',
                                    'photoGallery' => 'Galeria de Fotos',
                                ];
                                $sectionId = $sectionIds[$navItem['sectionKey']] ?? '';
                                $defaultLabel = $sectionLabels[$navItem['sectionKey']] ?? '';
                                $navLabel = !empty($navItem['label']) ? $navItem['label'] : $defaultLabel;
                            @endphp
                            @if($sectionId)
                            <a href="#{{ $sectionId }}">
                                {{ $navLabel }}
                            </a>
                            @endif
                        @endif
                    @endforeach
                </nav>
                @endif
                
                @if(!empty($content['sections']['header']['actionButton']['label']))
                @php $btnStyle = $content['sections']['header']['actionButton']['style'] ?? 'primary'; @endphp
                <a href="{{ $content['sections']['header']['actionButton']['target'] ?? '#' }}" class="header-action {{ $btnStyle }}">
                    {{ $content['sections']['header']['actionButton']['label'] }}
                </a>
                @endif
            </div>
        </div>
    </header>
    @endif

    @if(($content['sections']['hero']['enabled'] ?? true))
    <!-- Hero -->
    @php
        $heroMedia = $content['sections']['hero']['media'] ?? [];
        $mediaType = $heroMedia['type'] ?? 'image';
        $mediaUrl = $heroMedia['url'] ?? '';
        $mediaFallback = $heroMedia['fallback'] ?? '';
        $mediaAutoplay = $heroMedia['autoplay'] ?? true;
        $mediaLoop = $heroMedia['loop'] ?? true;
        $heroLayout = $content['sections']['hero']['layout'] ?? 'full-bleed';
        
        $isYouTube = fn($url) => str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be');
        $isVimeo = fn($url) => str_contains($url, 'vimeo.com');
        $isDirectVideo = fn($url) => preg_match('/\.(mp4|webm|ogg|mov)(\?.*)?$/i', $url);
        
        $getYouTubeId = function($url) {
            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', $url, $matches)) {
                return $matches[1];
            }
            return null;
        };
        
        $getVimeoId = function($url) {
            if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
                return $matches[1];
            }
            return null;
        };
        
        $youtubeId = $mediaUrl ? $getYouTubeId($mediaUrl) : null;
        $vimeoId = $mediaUrl ? $getVimeoId($mediaUrl) : null;
    @endphp
    
    @if($heroLayout === 'split')
    <section class="hero-split" id="hero">
        <div class="hero-media">
            @if($mediaUrl)
            <div class="hero-background" style="background-image: url('{{ $mediaUrl }}'); position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>
            @endif
        </div>
        <div class="hero-content">
            @php
                $heroTitleTypography = $content['sections']['hero']['titleTypography'] ?? [
                    'fontFamily' => 'Playfair Display',
                    'fontColor' => '#333333',
                    'fontSize' => 56,
                    'fontWeight' => 700,
                    'fontItalic' => false,
                    'fontUnderline' => false,
                ];
                $heroSubtitleTypography = $content['sections']['hero']['subtitleTypography'] ?? [
                    'fontFamily' => 'Montserrat',
                    'fontColor' => '#666666',
                    'fontSize' => 20,
                    'fontWeight' => 400,
                    'fontItalic' => false,
                    'fontUnderline' => false,
                ];
            @endphp
            <h2 style="
                font-family: {{ $heroTitleTypography['fontFamily'] ?? 'Playfair Display' }};
                color: {{ $heroTitleTypography['fontColor'] ?? '#333333' }};
                font-size: {{ $heroTitleTypography['fontSize'] ?? 56 }}px;
                font-weight: {{ $heroTitleTypography['fontWeight'] ?? 700 }};
                font-style: {{ ($heroTitleTypography['fontItalic'] ?? false) ? 'italic' : 'normal' }};
                text-decoration: {{ ($heroTitleTypography['fontUnderline'] ?? false) ? 'underline' : 'none' }};
            ">{{ $content['sections']['hero']['title'] ?? '' }}</h2>
            @if(!empty($content['sections']['hero']['subtitle']))
            <p class="subtitle" style="
                font-family: {{ $heroSubtitleTypography['fontFamily'] ?? 'Montserrat' }};
                color: {{ $heroSubtitleTypography['fontColor'] ?? '#666666' }};
                font-size: {{ $heroSubtitleTypography['fontSize'] ?? 20 }}px;
                font-weight: {{ $heroSubtitleTypography['fontWeight'] ?? 400 }};
                font-style: {{ ($heroSubtitleTypography['fontItalic'] ?? false) ? 'italic' : 'normal' }};
                text-decoration: {{ ($heroSubtitleTypography['fontUnderline'] ?? false) ? 'underline' : 'none' }};
            ">{{ $content['sections']['hero']['subtitle'] }}</p>
            @endif
            <div>
                @if(!empty($content['sections']['hero']['ctaPrimary']['label']))
                <a href="{{ $content['sections']['hero']['ctaPrimary']['target'] ?? '#' }}" class="cta-button">
                    {{ $content['sections']['hero']['ctaPrimary']['label'] }}
                </a>
                @endif
                @if(!empty($content['sections']['hero']['ctaSecondary']['label']))
                <a href="{{ $content['sections']['hero']['ctaSecondary']['target'] ?? '#' }}" class="cta-button" style="background: transparent; border: 2px solid var(--primary-color); color: var(--primary-color);">
                    {{ $content['sections']['hero']['ctaSecondary']['label'] }}
                </a>
                @endif
            </div>
        </div>
    </section>
    @else
    <section class="hero" id="hero">
        @if($mediaType === 'video' && $mediaUrl)
            @if($mediaFallback)
            <div class="hero-background" style="background-image: url('{{ $mediaFallback }}');"></div>
            @endif
            @if($youtubeId)
            <div class="hero-video-container">
                <iframe src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay={{ $mediaAutoplay ? 1 : 0 }}&mute=1&loop={{ $mediaLoop ? 1 : 0 }}&playlist={{ $youtubeId }}&controls=0&showinfo=0&rel=0&modestbranding=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
            @elseif($vimeoId)
            <div class="hero-video-container">
                <iframe src="https://player.vimeo.com/video/{{ $vimeoId }}?autoplay={{ $mediaAutoplay ? 1 : 0 }}&muted=1&loop={{ $mediaLoop ? 1 : 0 }}&background=1" frameborder="0" allow="autoplay; fullscreen"></iframe>
            </div>
            @elseif($isDirectVideo($mediaUrl))
            <div class="hero-video-container">
                <video src="{{ $mediaUrl }}" @if($mediaAutoplay) autoplay @endif muted @if($mediaLoop) loop @endif playsinline></video>
            </div>
            @endif
        @elseif($mediaUrl)
            <div class="hero-background" style="background-image: url('{{ $mediaUrl }}');"></div>
        @else
            <div class="hero-background" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));"></div>
        @endif
        
        <div class="hero-overlay"></div>
        
        <div class="hero-content">
            @php
                $heroTitleTypography = $content['sections']['hero']['titleTypography'] ?? [
                    'fontFamily' => 'Playfair Display',
                    'fontColor' => '#ffffff',
                    'fontSize' => 56,
                    'fontWeight' => 700,
                    'fontItalic' => false,
                    'fontUnderline' => false,
                ];
                $heroSubtitleTypography = $content['sections']['hero']['subtitleTypography'] ?? [
                    'fontFamily' => 'Montserrat',
                    'fontColor' => '#ffffff',
                    'fontSize' => 20,
                    'fontWeight' => 400,
                    'fontItalic' => false,
                    'fontUnderline' => false,
                ];
            @endphp
            <h2 style="
                font-family: {{ $heroTitleTypography['fontFamily'] ?? 'Playfair Display' }};
                color: {{ $heroTitleTypography['fontColor'] ?? '#ffffff' }};
                font-size: {{ $heroTitleTypography['fontSize'] ?? 56 }}px;
                font-weight: {{ $heroTitleTypography['fontWeight'] ?? 700 }};
                font-style: {{ ($heroTitleTypography['fontItalic'] ?? false) ? 'italic' : 'normal' }};
                text-decoration: {{ ($heroTitleTypography['fontUnderline'] ?? false) ? 'underline' : 'none' }};
                margin-bottom: 1rem;
            ">{{ $content['sections']['hero']['title'] ?? '' }}</h2>
            @if(!empty($content['sections']['hero']['subtitle']))
            <p class="subtitle" style="
                font-family: {{ $heroSubtitleTypography['fontFamily'] ?? 'Montserrat' }};
                color: {{ $heroSubtitleTypography['fontColor'] ?? '#ffffff' }};
                font-size: {{ $heroSubtitleTypography['fontSize'] ?? 20 }}px;
                font-weight: {{ $heroSubtitleTypography['fontWeight'] ?? 400 }};
                font-style: {{ ($heroSubtitleTypography['fontItalic'] ?? false) ? 'italic' : 'normal' }};
                text-decoration: {{ ($heroSubtitleTypography['fontUnderline'] ?? false) ? 'underline' : 'none' }};
                margin-bottom: 2rem;
                opacity: 0.9;
            ">{{ $content['sections']['hero']['subtitle'] }}</p>
            @endif
            @if(!empty($content['sections']['hero']['ctaPrimary']['label']))
            <a href="{{ $content['sections']['hero']['ctaPrimary']['target'] ?? '#' }}" class="cta-button">{{ $content['sections']['hero']['ctaPrimary']['label'] }}</a>
            @endif
            @if(!empty($content['sections']['hero']['ctaSecondary']['label']))
            <a href="{{ $content['sections']['hero']['ctaSecondary']['target'] ?? '#' }}" class="cta-button secondary">{{ $content['sections']['hero']['ctaSecondary']['label'] }}</a>
            @endif
        </div>
    </section>
    @endif
    @endif

    @if(($content['sections']['saveTheDate']['enabled'] ?? true))
    <!-- Save the Date -->
    @php
        $showCountdown = $content['sections']['saveTheDate']['showCountdown'] ?? true;
        $countdownFormat = $content['sections']['saveTheDate']['countdownFormat'] ?? 'full';
        $showMap = $content['sections']['saveTheDate']['showMap'] ?? false;
        $mapProvider = $content['sections']['saveTheDate']['mapProvider'] ?? 'google';
        $mapCoords = $content['sections']['saveTheDate']['mapCoordinates'] ?? null;
        $showCalendar = $content['sections']['saveTheDate']['showCalendarButton'] ?? true;
    @endphp
    <section class="section save-the-date" id="save-the-date">
        <div class="container">
            <div class="save-the-date-card">
                <h2>Save the Date</h2>
                
                @if($wedding->wedding_date)
                <p style="font-size: 1.5rem; margin-bottom: 20px;">{{ $wedding->wedding_date->format('d/m/Y') }}</p>
                
                @if($showCountdown)
                <div class="countdown" id="countdown" data-format="{{ $countdownFormat }}">
                    <div class="countdown-item">
                        <div class="countdown-number" id="days">--</div>
                        <div class="countdown-label">Dias</div>
                    </div>
                    @if(in_array($countdownFormat, ['hours', 'minutes', 'full']))
                    <div class="countdown-item">
                        <div class="countdown-number" id="hours">--</div>
                        <div class="countdown-label">Horas</div>
                    </div>
                    @endif
                    @if(in_array($countdownFormat, ['minutes', 'full']))
                    <div class="countdown-item">
                        <div class="countdown-number" id="minutes">--</div>
                        <div class="countdown-label">Minutos</div>
                    </div>
                    @endif
                    @if($countdownFormat === 'full')
                    <div class="countdown-item">
                        <div class="countdown-number" id="seconds">--</div>
                        <div class="countdown-label">Segundos</div>
                    </div>
                    @endif
                </div>
                @endif
                @endif
                
                @if(!empty($content['sections']['saveTheDate']['description']))
                <p>{{ $content['sections']['saveTheDate']['description'] }}</p>
                @endif
                
                @if(!empty($wedding->venue))
                <p style="margin-top: 20px;">
                    <strong>Local:</strong> {{ $wedding->venue }}
                    @if(!empty($wedding->city)) - {{ $wedding->city }} @endif
                    @if(!empty($wedding->state)), {{ $wedding->state }} @endif
                </p>
                @endif
                
                @if($showMap && $mapCoords && !empty($mapCoords['lat']) && !empty($mapCoords['lng']))
                <div class="map-container">
                    @if($mapProvider === 'google')
                    <iframe src="https://www.google.com/maps?q={{ $mapCoords['lat'] }},{{ $mapCoords['lng'] }}&z=15&output=embed" allowfullscreen loading="lazy"></iframe>
                    @else
                    <iframe src="https://www.openstreetmap.org/export/embed.html?bbox={{ $mapCoords['lng'] - 0.01 }},{{ $mapCoords['lat'] - 0.01 }},{{ $mapCoords['lng'] + 0.01 }},{{ $mapCoords['lat'] + 0.01 }}&layer=mapnik&marker={{ $mapCoords['lat'] }},{{ $mapCoords['lng'] }}" allowfullscreen loading="lazy"></iframe>
                    @endif
                </div>
                @endif
                
                @if($showCalendar)
                <a href="{{ route('public.site.calendar', ['slug' => $site->slug]) }}" class="calendar-button">📅 Adicionar ao Calendário</a>
                @endif
            </div>
        </div>
    </section>
    @endif

    @if(($content['sections']['giftRegistry']['enabled'] ?? false))
    <!-- Gift Registry -->
    <section class="section gift-registry" id="lista-presentes" style="background: {{ $content['sections']['giftRegistry']['style']['backgroundColor'] ?? '#ffffff' }};">
        <div class="container">
            <h2>{{ $content['sections']['giftRegistry']['title'] ?? 'Lista de Presentes' }}</h2>
            <p>{{ $content['sections']['giftRegistry']['description'] ?? 'Em breve...' }}</p>
        </div>
    </section>
    @endif
    
    @if(($content['sections']['rsvp']['enabled'] ?? false))
    <!-- RSVP -->
    <section class="section rsvp" id="confirmar-presenca" style="background: {{ $content['sections']['rsvp']['style']['backgroundColor'] ?? '#f5f5f5' }};">
        <div class="container">
            <h2>{{ $content['sections']['rsvp']['title'] ?? 'Confirme sua Presença' }}</h2>
            <p>{{ $content['sections']['rsvp']['description'] ?? 'Em breve você poderá confirmar sua presença.' }}</p>
        </div>
    </section>
    @endif
    
    @if(($content['sections']['photoGallery']['enabled'] ?? false))
    <!-- Photo Gallery -->
    @php
        $showLightbox = $content['sections']['photoGallery']['showLightbox'] ?? true;
        $allowDownload = $content['sections']['photoGallery']['allowDownload'] ?? false;
        $albums = $content['sections']['photoGallery']['albums'] ?? [];
    @endphp
    <section class="section photo-gallery" id="galeria">
        <div class="container">
            @foreach(['before', 'after'] as $albumKey)
                @if(!empty($albums[$albumKey]['photos']))
                @php
                    $albumTitle = $albums[$albumKey]['title'] ?? ($albumKey === 'before' ? 'Nossa História' : 'O Grande Dia');
                    $photos = collect($albums[$albumKey]['photos'])->filter(fn($p) => !($p['isPrivate'] ?? false));
                @endphp
                @if($photos->count() > 0)
                <h2 @if($albumKey === 'after') style="margin-top: 40px;" @endif>{{ $albumTitle }}</h2>
                <div class="gallery-grid">
                    @foreach($photos as $photo)
                    <div class="gallery-item" @if($showLightbox) onclick="openLightbox('{{ $photo['url'] ?? '' }}')" @endif>
                        <img src="{{ $photo['url'] ?? '' }}" alt="{{ $photo['alt'] ?? $photo['title'] ?? '' }}" loading="lazy">
                        @if(!empty($photo['caption']))
                        <div class="caption">{{ $photo['caption'] }}</div>
                        @endif
                        @if($allowDownload)
                        <a href="{{ $photo['url'] ?? '' }}" download class="download-btn" onclick="event.stopPropagation();">⬇ Download</a>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
                @endif
            @endforeach
        </div>
    </section>
    
    @if($showLightbox)
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close">&times;</span>
        <img id="lightbox-img" src="" alt="Foto ampliada">
    </div>
    @endif
    @endif

    @if(($content['sections']['footer']['enabled'] ?? true))
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            @if(!empty($content['sections']['footer']['socialLinks']))
            <div class="social-links">
                @foreach($content['sections']['footer']['socialLinks'] as $link)
                @php
                    $icons = [
                        'instagram' => '📷', 'facebook' => '📘', 'twitter' => '🐦',
                        'tiktok' => '🎵', 'youtube' => '▶️', 'pinterest' => '📌',
                        'linkedin' => '💼', 'whatsapp' => '💬', 'telegram' => '✈️', 'website' => '🌐'
                    ];
                    $icon = $icons[$link['platform'] ?? ''] ?? '🔗';
                @endphp
                <a href="{{ $link['url'] ?? '#' }}" target="_blank" rel="noopener" title="{{ ucfirst($link['platform'] ?? 'Link') }}">{{ $icon }}</a>
                @endforeach
            </div>
            @endif
            
            <p>
                {{ $content['sections']['footer']['copyrightText'] ?? '' }}
                © {{ $content['sections']['footer']['copyrightYear'] ?? date('Y') }}
            </p>
            
            @if(($content['sections']['footer']['showPrivacyPolicy'] ?? false) && !empty($content['sections']['footer']['privacyPolicyUrl']))
            <p style="margin-top: 10px;">
                <a href="{{ $content['sections']['footer']['privacyPolicyUrl'] }}" style="color: inherit; opacity: 0.8;">Política de Privacidade</a>
            </p>
            @endif
            
            @if(($content['sections']['footer']['showBackToTop'] ?? true))
            <a href="#" class="back-to-top">↑ Voltar ao topo</a>
            @endif
        </div>
    </footer>
    @endif

    <script>
        // Countdown timer
        @if($wedding->wedding_date && ($content['sections']['saveTheDate']['showCountdown'] ?? true))
        (function() {
            const weddingDate = new Date('{{ $wedding->wedding_date->format('Y-m-d') }}T00:00:00');
            const format = '{{ $content['sections']['saveTheDate']['countdownFormat'] ?? 'full' }}';
            
            function updateCountdown() {
                const now = new Date();
                const diff = weddingDate - now;
                
                const days = Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)));
                const hours = Math.max(0, Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)));
                const minutes = Math.max(0, Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)));
                const seconds = Math.max(0, Math.floor((diff % (1000 * 60)) / 1000));
                
                const daysEl = document.getElementById('days');
                const hoursEl = document.getElementById('hours');
                const minutesEl = document.getElementById('minutes');
                const secondsEl = document.getElementById('seconds');
                
                if (daysEl) daysEl.textContent = days;
                if (hoursEl) hoursEl.textContent = hours;
                if (minutesEl) minutesEl.textContent = minutes;
                if (secondsEl) secondsEl.textContent = seconds;
            }
            
            updateCountdown();
            setInterval(updateCountdown, 1000);
        })();
        @endif
        
        // Lightbox
        function openLightbox(src) {
            const lightbox = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            if (lightbox && img) {
                img.src = src;
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            if (lightbox) {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        // Close lightbox on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
    </script>
</body>
</html>
