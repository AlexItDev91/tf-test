<x-mail::message>
    # Low stock alert

    Product: **{{ $productName }}**
    Product ID: **{{ (int) $productId }}**
    Stock left: **{{ (int) $stockLeft }}**

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
