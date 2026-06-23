<?php

declare(strict_types=1);

use App\Modules\Shared\Services\CodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit-style coverage for the row-locked sequential code generator (the engine
 * behind customer / order / invoice numbers). Proves gap-free, per-branch,
 * zero-padded output.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gen = app(CodeGenerator::class);
    $this->branchA = makeBranch(['code' => 'HQ'])->id;
    $this->branchB = makeBranch(['code' => 'BR2'])->id;
});

it('issues gap-free, zero-padded sequential codes per branch', function () {
    expect($this->gen->next('customer_sequences', $this->branchA, 'SSI-HQ-'))->toBe('SSI-HQ-000001')
        ->and($this->gen->next('customer_sequences', $this->branchA, 'SSI-HQ-'))->toBe('SSI-HQ-000002')
        ->and($this->gen->next('customer_sequences', $this->branchA, 'SSI-HQ-'))->toBe('SSI-HQ-000003');
});

it('keeps a separate counter per branch', function () {
    $this->gen->next('customer_sequences', $this->branchA, 'SSI-HQ-');
    $this->gen->next('customer_sequences', $this->branchA, 'SSI-HQ-');

    // Branch B starts fresh at 1, independent of branch A's counter.
    expect($this->gen->next('customer_sequences', $this->branchB, 'SSI-BR2-'))->toBe('SSI-BR2-000001');
});

it('honours a custom pad width', function () {
    expect($this->gen->next('customer_sequences', $this->branchA, 'INV-', 4))->toBe('INV-0001');
});
