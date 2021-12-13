<?php

declare(strict_types=1);

/*
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test;

use CBOR\LengthCalculator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class LengthCalculatorTest extends TestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function theLengthIsCorrectlyComputed(
        string $data,
        int $expectedAdditionalInformation,
        ?string $expectedLength
    ): void {
        [$additionalInformation, $length] = LengthCalculator::getLengthOfString($data);

        static::assertSame($expectedAdditionalInformation, $additionalInformation);
        static::assertSame($expectedLength, $length);
    }

    public function getData(): iterable
    {
        yield [str_pad('', 0, ' '), 0, null];
        yield [str_pad('', 23, ' '), 23, null];

        yield [str_pad('', 24, ' '), 24, "\x18"];
        yield [str_pad('', 25, ' '), 24, "\x19"];
        yield [str_pad('', 0xFF, ' '), 24, "\xFF"];

        yield [str_pad('', 0x0100, ' '), 25, "\x01\x00"];
        yield [str_pad('', 0xFFFF, ' '), 25, "\xFF\xFF"];
    }
}
