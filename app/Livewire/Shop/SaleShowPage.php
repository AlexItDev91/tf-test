<?php

namespace App\Livewire\Shop;

use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SaleShowPage extends Component
{
    public Sale $sale;

    public function mount(SaleService $saleService, int $sale): void
    {
        $this->sale = $saleService->getForUserWithItems((int) auth()->id(), $sale);
    }

    public function render(): Factory|View
    {
        return view('livewire.shop.sale-show-page');
    }
}
