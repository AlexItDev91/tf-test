<?php

namespace App\Livewire\Shop;

use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layout.land')]
class SaleShowPage extends Component
{
    public Sale $sale;

    public function mount(SaleService $saleService, Sale $sale): void
    {
        $this->sale = $saleService->getForUserWithItems((int) auth()->id(), $sale->id);
    }

    public function render(): Factory|View
    {
        return view('livewire.shop.sale-show-page');
    }
}
