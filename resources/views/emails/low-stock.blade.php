<x-mail::message>
    # Low stock alert

    - **Product:** {{ $product->name }}
    - **Product ID:** {{ (int) $product->id }}
    - **Stock left:** {{ (int) $product->stock }}

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
