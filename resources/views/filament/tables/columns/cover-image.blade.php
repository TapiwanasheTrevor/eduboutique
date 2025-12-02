@php
    $path = $getRecord()->cover_image;
    $imageUrl = null;

    if ($path) {
        if (str_starts_with($path, '/')) {
            // Public path like /library/books/...
            $imageUrl = $path;
        } elseif (str_starts_with($path, 'http')) {
            // Already a full URL
            $imageUrl = $path;
        } else {
            // Storage path
            $imageUrl = '/storage/' . $path;
        }
    }
@endphp

<div class="flex items-center justify-center">
    @if($imageUrl)
        <img
            src="{{ $imageUrl }}"
            alt="Cover"
            class="w-10 h-10 object-cover rounded"
            loading="lazy"
        />
    @else
        <div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
    @endif
</div>
