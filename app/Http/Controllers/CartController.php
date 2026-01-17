<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    public function show(): JsonResponse
    {
        $userId = auth()->id();

        return response()->json([
            'cart' => $this->cartService->getCart($userId),
            'items' => $this->cartService->getItems($userId),
            'total_cents' => $this->cartService->totalCents($userId),
        ]);
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $this->cartService->addProduct(
            auth()->id(),
            $request->integer('product_id'),
            $request->integer('quantity')
        );

        return redirect()->back();
    }

    public function update(UpdateCartItemRequest $request, int $productId): RedirectResponse
    {
        $this->cartService->updateQuantity(
            auth()->id(),
            $productId,
            $request->integer('quantity')
        );

        return redirect()->back();
    }

    public function destroy(int $productId): RedirectResponse
    {
        $this->cartService->removeProduct(
            auth()->id(),
            $productId
        );

        return redirect()->back();
    }
}
