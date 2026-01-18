<?php

use App\Mail\DailySalesReportMail;
use App\Mail\LowStockMail;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

it('completes the full flow: register, auth, cart, buy, low stock notification, daily report', function () {
    Mail::fake();

    // 1. Register
    $registrationComponent = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $registrationComponent->call('register');
    $registrationComponent->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    // 2. Auth (Logout and Login to test full flow)
    $this->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();

    $loginComponent = Volt::test('pages.auth.login')
        ->set('form.email', 'test@example.com')
        ->set('form.password', 'password');

    $loginComponent->call('login');
    $loginComponent->assertRedirect(route('home', absolute: false));
    $this->assertAuthenticated();

    // 3. Add to cart
    // Create a product with stock 10. Low stock threshold is 3 by default.
    $product = Product::factory()->create([
        'name' => 'Flow Product',
        'stock' => 10,
        'price_cents' => 1000,
    ]);

    $this->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 8,
    ])->assertRedirect();

    // 4. Buy (Checkout)
    $this->post(route('checkout.store'))
        ->assertSuccessful()
        ->assertJsonPath('status', 'paid')
        ->assertJsonPath('total_cents', 8000);

    // 5. Notify low stock mail
    // After buying 8, stock is 2. 2 <= 3 (threshold), and previous stock was 10 > 3.
    // LowStockMail implements ShouldQueue, so with Mail::fake() we check assertQueued
    Mail::assertQueued(LowStockMail::class, function ($mail) use ($product) {
        return (int) $mail->productId === (int) $product->id;
    });

    // 6. Daily report
    $this->artisan('report:daily-sales')
        ->expectsOutput('Daily sales report job dispatched for '.now()->toDateString())
        ->assertSuccessful();

    // SendDailySalesReportJob handles the mailing.
    // Since QUEUE_CONNECTION=sync, the job runs immediately.
    // DailySalesReportMail does NOT implement ShouldQueue, so it is "sent" normally.
    // However, Mail::fake() intercepts it.
    Mail::assertSent(DailySalesReportMail::class, function ($mail) {
        return $mail->ordersCount === 1 &&
               $mail->itemsCount === 8 &&
               $mail->totalCents === 8000 &&
               $mail->lines[0]['name'] === 'Flow Product';
    });
});
