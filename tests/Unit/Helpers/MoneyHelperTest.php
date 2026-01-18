<?php

use App\Helpers\MoneyHelper;

test('toMoney converts cents to formatted string', function (int|string|null $cents, string $expected) {
    expect(MoneyHelper::toMoney($cents))->toBe($expected);
})->with([
    'integer cents' => [1234, '12.34'],
    'string cents' => ['1234', '12.34'],
    'zero cents' => [0, '0.00'],
    'null cents' => [null, '0.00'],
    'large amount' => [1234567, '12,345.67'],
    'negative cents' => [-1234, '-12.34'],
]);

test('toCents converts amount to cents', function (float|int|string $amount, int $expected) {
    expect(MoneyHelper::toCents($amount))->toBe($expected);
})->with([
    'float amount' => [12.34, 1234],
    'integer amount' => [12, 1200],
    'string amount' => ['12.34', 1234],
    'rounding up' => [12.345, 1235],
    'rounding down' => [12.344, 1234],
    'zero amount' => [0, 0],
    'negative amount' => [-12.34, -1234],
]);
