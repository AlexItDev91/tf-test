<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Sale #{{ (int) $sale->id }}</h1>

        <div class="flex items-center gap-2">
            <a href="{{ route('shop.sales') }}" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50">
                Back
            </a>

            <a href="{{ route('shop.products') }}" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50">
                Products
            </a>

            <a href="{{ route('shop.cart') }}" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50">
                Cart
            </a>
        </div>
    </div>

    <div class="rounded-lg border p-4 space-y-2">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">Status</div>
            <div class="font-medium">{{ $sale->status->value }}</div>
        </div>
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">Total</div>
            <div class="font-medium">{{ number_format(((int) $sale->total_cents) / 100, 2) }}</div>
        </div>
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">Created</div>
            <div class="font-medium">{{ $sale->created_at?->toDateTimeString() }}</div>
        </div>
    </div>

    <div class="space-y-2">
        @foreach($sale->items as $item)
            <div class="flex items-center justify-between rounded-lg border p-4">
                <div>
                    <div class="font-medium">{{ $item->product_name }}</div>
                    <div class="text-sm text-gray-600">
                        {{ number_format(((int) $item->unit_price_cents) / 100, 2) }}
                        × {{ (int) $item->quantity }}
                    </div>
                </div>

                <div class="font-medium">
                    {{ number_format(((int) $item->line_total_cents) / 100, 2) }}
                </div>
            </div>
        @endforeach
    </div>
</div>
