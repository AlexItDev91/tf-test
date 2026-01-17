<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lists sales for authenticated user', function () {
    Sale::factory()->count(3)->create(['user_id' => $this->user->id]);
    Sale::factory()->create(['user_id' => User::factory()->create()->id]); // Another user's sale

    $this->actingAs($this->user)
        ->get('/sales')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('shows specific sale for authenticated user', function () {
    $sale = Sale::factory()->create(['user_id' => $this->user->id]);

    $this->actingAs($this->user)
        ->get("/sales/{$sale->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $sale->id);
});

it('returns 500 when viewing another user sale', function () {
    $anotherUser = User::factory()->create();
    $sale = Sale::factory()->create(['user_id' => $anotherUser->id]);

    $this->actingAs($this->user)
        ->get("/sales/{$sale->id}")
        ->assertStatus(500);
});

it('returns 500 when sale does not exist', function () {
    $this->actingAs($this->user)
        ->get('/sales/999')
        ->assertStatus(500);
});
