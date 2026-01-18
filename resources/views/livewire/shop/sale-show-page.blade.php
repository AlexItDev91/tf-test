<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-white">Sale #{{ (int) $sale->id }}</h1>
    </div>

    <div class="rounded-lg border p-4 space-y-2">
        <div class="flex items-center justify-between">
            <div class="text-sm text-white">Status</div>
            <div class="font-medium text-green-300">{{ $sale->status->value }}</div>
        </div>
        <div class="flex items-center justify-between">
            <div class="text-sm text-white">Total</div>
            <div class="font-bold text-white">{{ \App\Helpers\MoneyHelper::toMoney($sale->total_cents) }}</div>
        </div>
        <div class="flex items-center justify-between">
            <div class="text-sm text-white">Created</div>
            <div class="font-medium">{{ $sale->created_at?->toDateTimeString() }}</div>
        </div>
    </div>

    <div class="space-y-2">
        @foreach($sale->items as $item)
            <div class="flex items-center justify-between rounded-lg border p-4">
                <div>
                    <div class="font-medium">{{ $item->product_name }}</div>
                    <div class="text-sm text-gray-100">
                        <span class="font-bold">{{ \App\Helpers\MoneyHelper::toMoney($item->unit_price_cents) }}</span>
                        × {{ (int) $item->quantity }}
                    </div>
                </div>

                <div class="font-bold text-white">
                    {{ \App\Helpers\MoneyHelper::toMoney($item->line_total_cents) }}
                </div>
            </div>
        @endforeach
    </div>
</div>
