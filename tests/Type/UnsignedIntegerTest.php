<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Type;

use CBOR\StringStream;
use CBOR\UnsignedIntegerObject;
use function Safe\hex2bin;

final class UnsignedIntegerTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getValidValue
     */
    public function createOnValidValue(int $intValue, string $expectedIntValue, int $expectedMajorType, int $expectedAdditionalInformation): void
    {
        $unsignedInteger = UnsignedIntegerObject::create($intValue);
        static::assertEquals($expectedIntValue, $unsignedInteger->getValue());
        static::assertEquals($expectedMajorType, $unsignedInteger->getMajorType());
        static::assertEquals($expectedAdditionalInformation, $unsignedInteger->getAdditionalInformation());
    }

    public function getValidValue(): array
    {
        return [
            [
                12345678,
                '12345678',
                0,
                26,
            ],
            [
                255,
                '255',
                0,
                25,
            ],
            [
                254,
                '254',
                0,
                24,
            ],
            [
                18,
                '18',
                0,
                18,
            ],
        ];
    }

    /**
     * @test
     */
    public function ceateOnNegativeValue(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The value must be a positive integer.');
        UnsignedIntegerObject::create(-1);
    }

    /**
     * @test
     */
    public function createOnOutOfRangeValue(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Out of range. Please use PositiveBigIntegerTag tag with ByteStringObject object instead.');
        UnsignedIntegerObject::create(4294967296);
    }

    /**
     * @test
     * @dataProvider getDataSet
     */
    public function anUnsignedIntegerCanBeParsed(string $data, string $expectedNormalizedData): void
    {
        $stream = new StringStream(hex2bin($data));
        $object = $this->getDecoder()->decode($stream);
        static::assertEquals($data, bin2hex((string) $object));
        static::assertEquals($expectedNormalizedData, $object->getNormalizedData());
    }

    public function getDataSet(): array
    {
        return [
            [
                '00',
                '0',
            ], [
                '01',
                '1',
            ], [
                '0a',
                '10',
            ], [
                '17',
                '23',
            ], [
                '1818',
                '24',
            ], [
                '1819',
                '25',
            ], [
                '1864',
                '100',
            ], [
                '1903e8',
                '1000',
            ], [
                '1a000f4240',
                '1000000',
            ], [
                '1b000000e8d4a51000',
                '1000000000000',
            ], [
                '1bffffffffffffffff',
                '18446744073709551615',
            ], [
                'c249010000000000000000',
                '18446744073709551616',
            ],
        ];
    }
}
