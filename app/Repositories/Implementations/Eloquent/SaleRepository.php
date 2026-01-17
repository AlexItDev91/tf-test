<?php

namespace App\Repositories\Implementations\Eloquent;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use JetBrains\PhpStorm\ArrayShape;

class SaleRepository implements SaleRepositoryContract
{
    public function createPending(int $userId): Sale
    {
        return Sale::query()->create([
            'user_id' => $userId,
            'status' => SaleStatus::PENDING,
            'total_cents' => 0,
        ]);
    }

    public function updateTotalCents(int $saleId, int $totalCents): void
    {
        Sale::query()
            ->whereKey($saleId)
            ->update(['total_cents' => $totalCents]);
    }

    public function setStatus(int $saleId, SaleStatus $status): void
    {
        Sale::query()
            ->whereKey($saleId)
            ->update(['status' => $status]);
    }

    public function getWithItems(int $saleId): ?Sale
    {
        return Sale::query()
            ->with('items')
            ->find($saleId);
    }

    public function getByUserId(int $userId): Collection
    {
        return Sale::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();
    }

    public function getById(int $saleId): ?Sale
    {
        return Sale::query()->find($saleId);
    }

    #[ArrayShape([
        'ordersCount' => 'int',
        'itemsCount' => 'int',
        'totalCents' => 'int',
        'lines' => 'array',
    ])]
    public function dailyReport(string $dateYmd): array
    {
        $ordersCount = Sale::query()
            ->whereDate('created_at', $dateYmd)
            ->where('status', 'paid')
            ->count();

        $totalCents = (int) (Sale::query()
            ->whereDate('created_at', $dateYmd)
            ->where('status', 'paid')
            ->sum('total_cents') ?? 0);

        $itemsCount = (int) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', $dateYmd)
            ->where('sales.status', 'paid')
            ->sum('sale_items.quantity');

        $lines = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sales.created_at', $dateYmd)
            ->where('sales.status', 'paid')
            ->selectRaw('sale_items.product_name as name, SUM(sale_items.quantity) as qty, SUM(sale_items.line_total_cents) as revenue_cents')
            ->groupBy('sale_items.product_name')
            ->orderByDesc('revenue_cents')
            ->get()
            ->map(fn ($r) => [
                'name' => (string) $r->name,
                'qty' => (int) $r->qty,
                'revenue_cents' => (int) $r->revenue_cents,
            ])
            ->all();

        return [
            'ordersCount' => $ordersCount,
            'itemsCount' => $itemsCount,
            'totalCents' => $totalCents,
            'lines' => $lines,
        ];
    }
}
