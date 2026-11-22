<?php

use App\Support\EmployeeNumber;
use InvalidArgumentException;

test('pads short employee numbers to four digits', function (string $input, string $expected) {
    expect(EmployeeNumber::normalize($input))->toBe($expected);
})->with([
    ['3', '0003'],
    ['89', '0089'],
    ['756', '0756'],
    ['7598', '7598'],
    [' 3145 ', '3145'],
    ['#3219', '3219'],
]);

test('rejects empty or oversized employee numbers', function (string $input) {
    EmployeeNumber::normalize($input);
})->with([
    [''],
    ['abc'],
    ['12345'],
])->throws(InvalidArgumentException::class);

test('tryNormalize returns null for invalid values', function () {
    expect(EmployeeNumber::tryNormalize(null))->toBeNull()
        ->and(EmployeeNumber::tryNormalize(''))->toBeNull()
        ->and(EmployeeNumber::tryNormalize('12345'))->toBeNull()
        ->and(EmployeeNumber::tryNormalize('89'))->toBe('0089');
});
