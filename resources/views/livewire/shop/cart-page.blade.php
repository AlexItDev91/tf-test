<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-white">Cart</h1>
    </div>

    @if($items->isEmpty())
        <div class="rounded-lg border p-4 text-gray-600">
            Cart is empty
        </div>
    @else
        <div class="space-y-2">
            @foreach($items as $item)
                @php
                    $product = $item->product;
                    $name = $product?->name ?? 'Unknown';
                    $priceCents = (int) ($product?->price_cents ?? 0);
                    $qty = (int) $item->quantity;
                @endphp

                <div class="flex items-center justify-between rounded-lg border p-4">
                    <div class="space-y-1">
                        <div class="font-medium">{{ $name }}</div>
                        <div class="text-sm text-gray-600">
                            {{ number_format($priceCents / 100, 2) }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            type="number"
                            min="0"
                            class="w-24 rounded-md border px-2 py-1 text-sm bg-transparent"
                            value="{{ $qty }}"
                            wire:change="updateQuantity({{ (int) $item->product_id }}, $event.target.value)"
                        />

                        <flux:button
                            type="button"
                            variant="outline"
                            class="border-gray-300 text-black hover:bg-gray-50"
                            wire:click="remove({{ (int) $item->product_id }})"
                        >
                            Remove
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between rounded-lg border p-4">
            <div class="font-medium">Total</div>
            <div class="font-semibold">{{ number_format(((int) $totalCents) / 100, 2) }}</div>
        </div>

            <div class="flex items-center gap-2">
                <flux:button
                    type="button"
                    variant="filled"
                    class="bg-black text-white"
                    wire:click="checkout"
                >
                    Checkout
                </flux:button>

                <flux:button
                    type="button"
                    variant="outline"
                    class="border-gray-300 text-black hover:bg-gray-50"
                    wire:click="clear"
                >
                    Clear cart
                </flux:button>
            </div>
    @endif
</div>
