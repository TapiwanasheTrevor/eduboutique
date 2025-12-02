@php
    use App\Models\Product;

    $items = $getRecord()->cart_items ?? [];

    // Pre-fetch all products for items that don't have complete data
    $productIds = collect($items)->pluck('product_id')->filter()->toArray();
    $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
@endphp

<div class="space-y-3">
    @forelse($items as $item)
        @php
            // Get product from database if available
            $product = $products[$item['product_id']] ?? null;

            // Use stored data first, fall back to database product
            $coverImage = $item['cover_image'] ?? $product?->cover_image ?? null;
            $title = $item['title'] ?? $product?->title ?? 'Unknown Title';
            $author = $item['author'] ?? $product?->author ?? null;
            $isbn = $item['isbn'] ?? $product?->isbn ?? null;

            // Build image URL
            $imageUrl = null;
            if ($coverImage) {
                if (str_starts_with($coverImage, 'http')) {
                    $imageUrl = $coverImage;
                } elseif (str_starts_with($coverImage, '/')) {
                    $imageUrl = $coverImage;
                } else {
                    $imageUrl = '/storage/' . $coverImage;
                }
            }
        @endphp
        <div class="flex gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            {{-- Cover Image --}}
            <div class="flex-shrink-0">
                @if($imageUrl)
                    <img
                        src="{{ $imageUrl }}"
                        alt="{{ $title }}"
                        class="w-16 h-20 object-cover rounded shadow-sm"
                        loading="lazy"
                        onerror="this.onerror=null; this.src='/logo.png'; this.classList.add('p-2', 'bg-gray-200');"
                    />
                @else
                    <div class="w-16 h-20 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                        <x-heroicon-o-book-open class="w-8 h-8 text-gray-400" />
                    </div>
                @endif
            </div>

            {{-- Book Details --}}
            <div class="flex-1 min-w-0">
                <h4 class="font-semibold text-gray-900 dark:text-white text-sm leading-tight mb-1">
                    {{ $title }}
                </h4>

                @if($author)
                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">
                        <span class="font-medium">Author:</span> {{ $author }}
                    </p>
                @endif

                @if($isbn)
                    <p class="text-xs text-gray-500 dark:text-gray-500 font-mono">
                        ISBN: {{ $isbn }}
                    </p>
                @endif

                @php
                    $odooProductId = $item['odoo_product_id'] ?? $product?->odoo_product_id ?? null;
                    $sku = $item['sku'] ?? $product?->sku ?? null;
                @endphp

                @if($odooProductId)
                    <p class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1">
                        <span class="inline-flex items-center gap-1">
                            <x-heroicon-o-check-circle class="w-3 h-3" />
                            Odoo Product ID: {{ $odooProductId }}
                        </span>
                    </p>
                @else
                    <p class="text-xs text-orange-500 dark:text-orange-400 mt-1">
                        <span class="inline-flex items-center gap-1">
                            <x-heroicon-o-exclamation-triangle class="w-3 h-3" />
                            No Odoo link
                        </span>
                    </p>
                @endif

                @if($sku)
                    <p class="text-xs text-gray-500 dark:text-gray-500 font-mono">
                        SKU: {{ $sku }}
                    </p>
                @endif
            </div>

            {{-- Quantity & Pricing --}}
            <div class="flex-shrink-0 text-right space-y-1">
                <div class="inline-flex items-center justify-center bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 px-2 py-1 rounded text-xs font-semibold">
                    Qty: {{ $item['quantity'] ?? 1 }}
                </div>

                <div class="text-sm font-bold text-gray-900 dark:text-white">
                    ${{ number_format($item['price_usd'] ?? 0, 2) }} USD
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    ${{ number_format($item['price_zwl'] ?? 0, 2) }} ZWL
                </div>

                @if(($item['quantity'] ?? 1) > 1)
                    <div class="text-xs text-gray-600 dark:text-gray-400 font-medium pt-1 border-t border-gray-200 dark:border-gray-600">
                        Subtotal: ${{ number_format(($item['price_usd'] ?? 0) * ($item['quantity'] ?? 1), 2) }} USD
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-shopping-cart class="w-12 h-12 mx-auto mb-2 text-gray-300" />
            <p>No items in this inquiry</p>
        </div>
    @endforelse

    {{-- Summary --}}
    @if(count($items) > 0)
        <div class="mt-4 pt-4 border-t-2 border-gray-300 dark:border-gray-600">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ count($items) }} item(s) total
                </span>
                <div class="text-right">
                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                        Total: ${{ number_format($getRecord()->total_usd ?? 0, 2) }} USD
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        ${{ number_format($getRecord()->total_zwl ?? 0, 2) }} ZWL
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
