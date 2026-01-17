<?php

namespace App\Livewire\Shop;

use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

#[Layout('livewire.layout.land')]

class CartPage extends Component
{
    public Collection $items;

    public int $totalCents = 0;

    public function mount(CartService $cartService): void
    {
        $this->loadCart($cartService);
    }

    #[On('cart-updated')]
    public function refreshCart(CartService $cartService): void
    {
        $this->loadCart($cartService);
    }

    public function updateQuantity(CartService $cartService, int $productId, int $quantity): void
    {
        $cartService->updateQuantity((int) auth()->id(), $productId, $quantity);

        $this->loadCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function remove(CartService $cartService, int $productId): void
    {
        $cartService->removeProduct((int) auth()->id(), $productId);

        $this->loadCart($cartService);
        $this->dispatch('cart-updated');
    }

    public function clear(CartService $cartService): void
    {
        $cartService->clear((int) auth()->id());

        $this->loadCart($cartService);
        $this->dispatch('cart-updated');
        session()?->flash('status', 'Cart cleared');
    }

    /**
     * @throws Throwable
     */
    public function checkout(CheckoutService $checkoutService, CartService $cartService): void
    {
        $sale = $checkoutService->checkout((int) auth()->id());

        $this->loadCart($cartService);
        $this->dispatch('cart-updated');

        redirect()->route('shop.sales')->with('status', 'Checkout success: #'.(int) $sale->id);
    }

    private function loadCart(CartService $cartService): void
    {
        $this->items = $cartService->getItems((int) auth()->id());
        $this->totalCents = $cartService->totalCents((int) auth()->id());
    }

    public function render(): Factory|View
    {
        return view('livewire.shop.cart-page');
    }
}
