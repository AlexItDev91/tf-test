<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-white">Products</h1>
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

                @php($disabled = ((int) $product->stock) <= 0)

                @if(auth()->guest())
                    <flux:modal.trigger name="auth-notification">
                        <flux:button
                            type="button"
                            variant="filled"
                            class="bg-black text-white disabled:opacity-50"
                            :disabled="$disabled"
                        >
                            Add
                        </flux:button>
                    </flux:modal.trigger>
                @else
                    <flux:button
                        type="button"
                        variant="filled"
                        class="bg-black text-white disabled:opacity-50"
                        wire:click="addToCart({{ (int) $product->id }})"
                        :disabled="$disabled"
                    >
                        Add
                    </flux:button>
                @endif
            </div>
        @empty
            <div class="rounded-lg border p-4 text-gray-600">
                No products
            </div>
        @endforelse
    </div>

        <flux:modal name="auth-notification" class="max-w-xl w-full">
            <div class="space-y-6 p-6">
                <div>
                    <flux:heading size="lg">Notification</flux:heading>
                    <flux:subheading>
                        Sign in to add products to your cart and place orders.
                    </flux:subheading>
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('login') }}">
                        <flux:button variant="ghost">Login</flux:button>
                    </a>

                    <flux:button
                        type="button"
                        variant="filled"
                        x-data
                        x-on:click="$flux.modal('auth-notification').close()"
                    >
                        Close
                    </flux:button>
                </div>
            </div>
        </flux:modal>
</div>
