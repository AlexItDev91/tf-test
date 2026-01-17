<?php

namespace App\Enums;

enum UserAction: string
{
    case CART_ADD = 'cart.add';
    case CART_UPDATE_QUANTITY = 'cart.update_quantity';
    case CART_REMOVE = 'cart.remove';
    case CART_CLEAR = 'cart.clear';
    case CHECKOUT_SUCCESS = 'checkout.success';
}
