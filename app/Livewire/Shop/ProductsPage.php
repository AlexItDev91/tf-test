<?php

namespace App\Livewire\Shop;

use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\CartService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.layout.land')]
class ProductsPage extends Component
{
    private ProductRepositoryContract $productRepository;

    private CartService $cartService;

    public function mount(
        ProductRepositoryContract $productRepository,
        CartService $cartService
    ): void {
        $this->productRepository = $productRepository;
        $this->cartService = $cartService;
    }

    public function addToCart(int $productId): void
    {
        $this->cartService->addProduct((int) auth()->id(), $productId, 1);

        $this->dispatch('cart-updated');
        session()?->flash('status', 'Added to cart');
    }

    public function render(): Factory|View
    {
        return view('livewire.shop.products-page', [
            'products' => $this->productRepository->listActive(),
        ]);
    }
}
