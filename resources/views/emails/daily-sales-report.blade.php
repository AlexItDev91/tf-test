<x-mail::message>
    # Daily sales report

    **Date:** {{ $date }}
    **Orders:** {{ $ordersCount }}
    **Items sold:** {{ $itemsCount }}
    **Revenue:** {{ \App\Helpers\MoneyHelper::toMoney($totalCents) }}

    @if(empty($lines))
        No sales today.
    @else
        ## Sold products

        @foreach($lines as $row)
            - **{{ $row['name'] }}** — qty: {{ $row['qty'] }}, revenue: {{ \App\Helpers\MoneyHelper::toMoney($row['revenue_cents']) }}
        @endforeach
    @endif

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
