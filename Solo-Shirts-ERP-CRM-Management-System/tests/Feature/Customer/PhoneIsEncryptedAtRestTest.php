<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    $this->branch = makeBranch(['code' => 'HQ']);
    $this->user = makeUser($this->branch, 'Front Desk');
});

it('stores the phone as ciphertext while keeping phone_last4 in plaintext', function () {
    $this->withHeaders(bearer($this->user))
        ->postJson('/api/v1/customers', ['name' => 'Crypto', 'phone' => '9876543210'])
        ->assertCreated();

    $raw = DB::table('customers')->where('name', 'Crypto')->first();

    // The stored phone column is ciphertext, not the plaintext number.
    expect($raw->phone)->not->toBe('9876543210')
        ->and($raw->phone)->not->toContain('9876543210')
        ->and(Crypt::decryptString($raw->phone))->toBe('9876543210')
        ->and($raw->phone_last4)->toBe('3210');
});
