<?php

use App\Jobs\SendDailySalesReportJob;
use App\Jobs\SendLowStockNotificationJob;
use App\Mail\DailySalesReportMail;
use App\Mail\LowStockMail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

it('completes full flow: auth → cart → checkout → low stock mail → daily report', function () {
    Mail::fake();
    Bus::fake();

    $product = Product::factory()->create([
        'name' => 'Flow Product',
        'stock' => 10,
        'price_cents' => 1000,
        'is_active' => true,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Register
    |--------------------------------------------------------------------------
    */
    Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('home', absolute: false));

    $user = User::whereEmail('test@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($user);

    /*
    |--------------------------------------------------------------------------
    | Logout → Login
    |--------------------------------------------------------------------------
    */
    $this->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();

    Volt::test('pages.auth.login')
        ->set('form.email', 'test@example.com')
        ->set('form.password', 'password')
        ->call('login')
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticatedAs($user);

    /*
    |--------------------------------------------------------------------------
    | Add to cart
    |--------------------------------------------------------------------------
    */
    $this->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 8,
    ])->assertRedirect();

    /*
    |--------------------------------------------------------------------------
    | Checkout
    |--------------------------------------------------------------------------
    */
    $this->post(route('checkout.store'))
        ->assertSuccessful()
        ->assertJsonPath('status', 'paid')
        ->assertJsonPath('total_cents', 8000);

    $product->refresh();
    expect($product->stock)->toBe(2);

    /*
    |--------------------------------------------------------------------------
    | Low stock notification (CQRS)
    |--------------------------------------------------------------------------
    */
    Bus::assertDispatched(
        SendLowStockNotificationJob::class,
        function (SendLowStockNotificationJob $job) use ($product) {
            // выполняем реальный job
            $job->handle();

            return $job->productId === $product->id;
        }
    );

    Mail::assertQueued(LowStockMail::class, function ($mail) use ($product) {
        return $mail->productId === $product->id
            && $mail->stockLeft === 2;
    });

    /*
    |--------------------------------------------------------------------------
    | Daily sales report
    |--------------------------------------------------------------------------
    */
    $this->artisan('report:daily-sales')
        ->expectsOutput('Daily sales report job dispatched for '.now()->toDateString())
        ->assertSuccessful();

    Bus::assertDispatched(
        SendDailySalesReportJob::class,
        function (SendDailySalesReportJob $job) {
            $this->app->call([$job, 'handle']);
            return true;
        }
    );

    Mail::assertSent(DailySalesReportMail::class, function ($mail) {
        return $mail->ordersCount === 1
            && $mail->itemsCount === 8
            && $mail->totalCents === 8000
            && $mail->lines[0]['name'] === 'Flow Product';
    });
});
