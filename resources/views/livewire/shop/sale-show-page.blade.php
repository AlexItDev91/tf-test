<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-white">Sale #{{ (int) $sale->id }}</h1>
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
