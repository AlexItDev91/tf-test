<?php

namespace App\Http\Controllers;

use App\Http\Resources\SaleResource;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $sales = $this->saleService->listByUser(auth()->id());

        return SaleResource::collection($sales);
    }

    public function show(int $saleId): SaleResource
    {
        $sale = $this->saleService->getForUserWithItems(auth()->id(), $saleId);

        return new SaleResource($sale);
    }
}
