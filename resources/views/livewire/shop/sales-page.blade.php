<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">My sales</h1>

        <div class="flex items-center gap-2">
            <a href="{{ route('shop.products') }}" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50">
                Products
            </a>

            <a href="{{ route('shop.cart') }}" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50">
                Cart
            </a>
        </div>
    </div>

    <div class="space-y-2">
        @forelse($sales as $sale)
            <a
                href="{{ route('shop.sales.show', (int) $sale->id) }}"
                class="block rounded-lg border p-4 hover:bg-gray-50"
            >
                <div class="flex items-center justify-between">
                    <div class="font-medium">#{{ (int) $sale->id }}</div>
                    <div class="text-sm text-gray-600">{{ $sale->status->value }}</div>
                </div>

                <div class="text-sm text-gray-600">
                    Total: {{ number_format(((int) $sale->total_cents) / 100, 2) }}
                </div>
            </a>
        @empty
            <div class="rounded-lg border p-4 text-gray-600">
                No sales yet
            </div>
        @endforelse
    </div>
</div>
