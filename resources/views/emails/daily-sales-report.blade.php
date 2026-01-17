<x-mail::message>
    # Daily sales report

    **Date:** {{ $date }}
    **Orders:** {{ $ordersCount }}
    **Items sold:** {{ $itemsCount }}
    **Revenue:** {{ number_format($totalCents / 100, 2, '.', '') }}

    @if(empty($lines))
        No sales today.
    @else
        ## Sold products

        @foreach($lines as $row)
            - **{{ $row['name'] }}** — qty: {{ $row['qty'] }}, revenue: {{ number_format($row['revenue_cents'] / 100, 2, '.', '') }}
        @endforeach
    @endif

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
