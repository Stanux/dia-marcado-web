<div 
    class="flex items-center gap-x-4"
    x-data
    @navigate.window="$wire.updateTitle()"
>
    <span class="text-lg font-semibold text-gray-950 dark:text-white">
        {{ $title }}
    </span>
</div>
