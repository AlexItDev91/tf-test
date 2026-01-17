<?php

namespace App\Http\Controllers;

use App\Services\CartService;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}
}
