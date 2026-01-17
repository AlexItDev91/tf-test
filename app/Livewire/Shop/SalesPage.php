<?php

namespace App\Livewire\Shop;

use App\Services\SaleService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layout.land')]
class SalesPage extends Component
{
    public Collection $sales;

    public function mount(SaleService $saleService): void
    {
        $this->sales = $saleService->listByUser((int) auth()->id());
    }

    public function render()
    {
        return view('livewire.shop.sales-page');
    }
}
