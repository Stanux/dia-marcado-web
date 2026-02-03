<x-filament-panels::page>
    {{-- Vue.js Media Gallery Component Container --}}
    <div id="media-gallery-app" class="contents"></div>

    {{-- Pass albums data to JavaScript --}}
    <script>
        window.__mediaGalleryData = {
            albums: @json($this->albums)
        };
    </script>

    {{-- Load Vue.js app with Media Gallery --}}
    @vite(['resources/js/media-gallery.js'])
</x-filament-panels::page>
