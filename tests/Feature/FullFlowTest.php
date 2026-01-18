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
    /*
    |--------------------------------------------------------------------------
    | Arrange
    |--------------------------------------------------------------------------
    */
    Mail::fake();
    Bus::fake();

    // Create product with enough stock
    $product = Product::factory()->create([
        'name' => 'Flow Product',
        'stock' => 10,
        'price_cents' => 1000,
        'is_active' => true,
    ]);

    /*
    |--------------------------------------------------------------------------
    | 1. Register
    |--------------------------------------------------------------------------
    */
    Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();

    /** @var User $user */
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    /*
    |--------------------------------------------------------------------------
    | 2. Logout → Login (full auth cycle)
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
    | 3. Add to cart (8 items)
    |--------------------------------------------------------------------------
    */
    $this->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 8,
    ])->assertRedirect();

    /*
    |--------------------------------------------------------------------------
    | 4. Checkout
    |--------------------------------------------------------------------------
    */
    $response = $this->post(route('checkout.store'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('status', 'paid')
        ->assertJsonPath('total_cents', 8000);

    /*
    |--------------------------------------------------------------------------
    | 5. Assert stock changed
    |--------------------------------------------------------------------------
    */
    $product->refresh();
    expect($product->stock)->toBe(2); // 10 - 8

    /*
    |--------------------------------------------------------------------------
    | 6. Low stock notification (CQRS)
    |--------------------------------------------------------------------------
    | threshold = 3
    | previous = 10
    | new = 2 → should notify
    |--------------------------------------------------------------------------
    */

    // 6.1 Job dispatched by listener
    Bus::assertDispatched(SendLowStockNotificationJob::class, function ($job) use ($product) {
        return $job->productId === $product->id;
    });

    // 6.2 Run job manually (Bus::fake prevents auto execution)
    $job = new SendLowStockNotificationJob(
        productId: $product->id,
        productName: $product->name,
        previousStock: 10,
        newStock: 2,
    );
    $job->handle();

    // 6.3 Mail sent by job
    Mail::assertQueued(LowStockMail::class, function ($mail) use ($product) {
        return $mail->productId === $product->id
            && $mail->stockLeft === 2;
    });

    /*
    |--------------------------------------------------------------------------
    | 7. Daily sales report
    |--------------------------------------------------------------------------
    */
    $this->artisan('report:daily-sales')
        ->expectsOutput(
            'Daily sales report job dispatched for '.now()->toDateString()
        )
        ->assertSuccessful();

    Bus::assertDispatched(SendDailySalesReportJob::class);

    $job = new SendDailySalesReportJob(now()->toDateString());
    $this->app->call([$job, 'handle']);

    Mail::assertSent(DailySalesReportMail::class, function ($mail) {
        return $mail->ordersCount === 1
            && $mail->itemsCount === 8
            && $mail->totalCents === 8000
            && $mail->lines[0]['name'] === 'Flow Product';
    });
});
