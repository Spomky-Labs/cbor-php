<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Type;

use Brick\Math\Exception\IntegerOverflowException;
use CBOR\StringStream;

/**
 * @internal
 */
final class InvalidTypeTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getInvalidDataItems
     */
    public function invalidData(string $item, string $class, string $expectedError): void
    {
        $this->expectException($class);
        $this->expectExceptionMessage($expectedError);

        $stream = new StringStream(hex2bin($item));
        dump($this->getDecoder()->decode($stream)->getNormalizedData());
    }

    /**
     * @see https://datatracker.ietf.org/doc/html/rfc8949#appendix-F.1
     */
    public function getInvalidDataItems(): array
    {
        return [
            [base_convert('00000011100', 2, 16), 'InvalidArgumentException', 'Cannot parse the data. Found invalid Additional Information "00011100" (28).'],
            [base_convert('00000011101', 2, 16), 'InvalidArgumentException', 'Cannot parse the data. Found invalid Additional Information "00011101" (29).'],
            [base_convert('00000011110', 2, 16), 'InvalidArgumentException', 'Cannot parse the data. Found invalid Additional Information "00011110" (30).'],
            ['18', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['19', 'InvalidArgumentException', 'Out of range. Expected: 2, read: 0.'],
            ['1a', 'InvalidArgumentException', 'Out of range. Expected: 4, read: 0.'],
            ['1b', 'InvalidArgumentException', 'Out of range. Expected: 8, read: 0.'],
            ['1901', 'InvalidArgumentException', 'Out of range. Expected: 2, read: 0.'],
            ['1a0102', 'InvalidArgumentException', 'Out of range. Expected: 4, read: 0.'],
            ['1b01020304050607', 'InvalidArgumentException', 'Out of range. Expected: 8, read: 0.'],
            ['38', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['58', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['78', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['98', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['9a01ff00', 'InvalidArgumentException', 'Out of range. Expected: 4, read: 0.'],
            ['b8', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['d8', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['f8', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['f900', 'InvalidArgumentException', 'Out of range. Expected: 2, read: 0.'],
            ['fa0000', 'InvalidArgumentException', 'Out of range. Expected: 4, read: 0.'],
            ['fb000000', 'InvalidArgumentException', 'Out of range. Expected: 8, read: 0.'],
            ['41', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['61', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['5affffffff00', 'InvalidArgumentException', 'Out of range. Expected: 4294967295, read: 0.'],
            ['5bffffffffffffffff010203', IntegerOverflowException::class, '18446744073709551615 is out of range -9223372036854775808 to 9223372036854775807 and cannot be represented as an integer.'],
            ['7affffffff00', 'InvalidArgumentException', 'Out of range. Expected: 4294967295, read: 0.'],
            ['7b7fffffffffffffff010203', 'InvalidArgumentException', 'Out of range. Expected: 9223372036854775807, read: 0.'],
            ['81', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['818181818181818181', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['8200', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['a1', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['a20102', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['a100', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['a2000000', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            ['c0', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            // ['5f41007f6100', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            // ['9f9f0102bfbf01020102819f9f80009f9f9f9f9fffffffff9f819f819f9fffffff', 'InvalidArgumentException', 'Out of range. Expected: 1, read: 0.'],
            // ['1c1d1e3c3d3e5c5d5e7c7d7e9c9d9ebcbdbedcdddefcfdfe', 'InvalidArgumentException', 'Cannot parse the data. Found invalid Additional Information "00011100" (28).'],
            // ['f800f801f818f81f', 'InvalidArgumentException', 'Cannot parse the data. Found invalid Additional Information "00011100" (28).'],
            // ['5f00ff5f21ff5f6100ff5f80ff5fa0ff5fc000ff5fe0ff7f4100ff', 'RuntimeException', 'Unable to parse the data. Infinite Byte String object can only get Byte String objects.'],
            // ['5f5f4100ffff7f7f6100ffff', 'RuntimeException', 'Unable to parse the data. Infinite Byte String object can only get Byte String objects.'],
            // ['ff', 'InvalidArgumentException', 'Cannot parse the data. No enclosing indefinite.'],
            // ['81ff8200ffa1ffa1ff00a100ffa20000ff9f81ff9f829f819f9fffffffff', 'InvalidArgumentException', 'Cannot parse the data. No enclosing indefinite.'],
            // ['bf00ff', 'InvalidArgumentException', 'Cannot parse the data. No enclosing indefinite.'],
            // ['bf000000ff', 'InvalidArgumentException', 'Out of range. Expected: 8, read: 3.'],
        ];
    }
}
