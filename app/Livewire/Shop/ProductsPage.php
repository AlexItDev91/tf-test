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
    public function addToCart(CartService $cartService, int $productId): void
    {
        if (auth()->guest()) {
            return;
        }

        $cartService->addProduct((int) auth()->id(), $productId, 1);

        $this->dispatch('cart-updated');
        session()?->flash('status', 'Added to cart');
    }

    public function render(ProductRepositoryContract $productRepository): Factory|View
    {
        return view('livewire.shop.products-page', [
            'products' => $productRepository->listActive(),
        ]);
    }
}
