<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Products</h1>

        <a href="{{ route('shop.cart') }}" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50">
            Cart
        </a>
    </div>

    <div class="space-y-2">
        @forelse($products as $product)
            <div class="flex items-center justify-between rounded-lg border p-4">
                <div class="space-y-1">
                    <div class="font-medium">{{ $product->name }}</div>
                    <div class="text-sm text-gray-600">
                        {{ number_format(((int) $product->price_cents) / 100, 2) }}
                        · stock: {{ (int) $product->stock }}
                    </div>
                </div>

                <button
                    type="button"
                    class="rounded-md bg-black px-3 py-2 text-sm text-white disabled:opacity-50"
                    wire:click="addToCart({{ (int) $product->id }})"
                    @disabled(((int) $product->stock) <= 0)
                >
                    Add
                </button>
            </div>
        @empty
            <div class="rounded-lg border p-4 text-gray-600">
                No products
            </div>
        @endforelse
    </div>
</div>
