<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Throwable;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService
    ) {}

    /**
     * @throws Throwable
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        try {
            $sale = $this->checkoutService->checkout(auth()->id());

            return response()->json([
                'sale_id' => (int) $sale->id,
                'status' => $sale->status->value,
                'total_cents' => (int) $sale->total_cents,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
