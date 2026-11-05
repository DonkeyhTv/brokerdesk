<?php

use App\Support\EnterpriseNumber;

it('parses a valid number in any common notation', function (string $input) {
    expect(EnterpriseNumber::parse($input)->digits)->toBe('0123456749');
})->with([
    'digits only' => '0123456749',
    'formatted' => '0123.456.749',
    'vat prefix' => 'BE0123456749',
    'lowercase prefix and spaces' => ' be 0123 456 749 ',
]);

it('accepts numbers starting with 1', function () {
    $number = EnterpriseNumber::fromBase(12_345_678);

    expect($number->digits)->toStartWith('1')
        ->and(EnterpriseNumber::parse($number->digits)->digits)->toBe($number->digits);
});

it('rejects malformed numbers', function (string $input) {
    EnterpriseNumber::parse($input);
})->with([
    'too short' => '012345674',
    'too long' => '01234567490',
    'letters' => '01234567AB',
    'starting with 2' => '2123456749',
    'empty' => '',
])->throws(InvalidArgumentException::class, 'ten digits');

it('rejects incorrect check digits', function () {
    EnterpriseNumber::parse('0123456748');
})->throws(InvalidArgumentException::class, 'check digits');

it('uses 97 as check digits when the base is a multiple of 97', function () {
    expect(EnterpriseNumber::fromBase(97)->digits)->toBe('0000009797')
        ->and(EnterpriseNumber::parse('0000.009.797')->digits)->toBe('0000009797');
});

it('computes check digits from the base', function () {
    expect(EnterpriseNumber::fromBase(1_234_567)->digits)->toBe('0123456749');
});

it('rejects a base outside the valid range', function (int $base) {
    EnterpriseNumber::fromBase($base);
})->with([-1, 20_000_000])->throws(InvalidArgumentException::class);

it('formats and serializes in the official notation', function () {
    $number = EnterpriseNumber::parse('0123456749');

    expect($number->formatted())->toBe('0123.456.749')
        ->and((string) $number)->toBe('0123.456.749')
        ->and(json_encode($number))->toBe('"0123.456.749"');
});
