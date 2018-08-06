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

final class UnsignedIntegerTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getValidValue
     */
    public function createOnValidValue(int $intValue, string $expectedIntValue, int $expectedMajorType, $expectedAdditionalInformation)
    {
        $unsignedInteger = UnsignedIntegerObject::create($intValue);
        $this->assertEquals($expectedIntValue, $unsignedInteger->getValue());
        $this->assertEquals($expectedMajorType, $unsignedInteger->getMajorType());
        $this->assertEquals($expectedAdditionalInformation, $unsignedInteger->getAdditionalInformation());
    }

    /**
     * @return array
     */
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
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The value must be a positive integer.
     */
    public function ceateOnNegativeValue()
    {
        UnsignedIntegerObject::create(-1);
    }

    /**
     * @test
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Out of range. Please use PositiveBigIntegerTag tag with ByteStringObject object instead.
     */
    public function createOnOutOfRangeValue()
    {
        UnsignedIntegerObject::create(4294967296);
    }

    /**
     * @test
     * @dataProvider getDataSet
     *
     * @param string $data
     * @param string $expectedNormalizedData
     */
    public function anUnsignedIntegerCanBeParsed(string $data, string $expectedNormalizedData)
    {
        $stream = new StringStream(hex2bin($data));
        $object = $this->getDecoder()->decode($stream);
        self::assertEquals($data, bin2hex((string) $object));
        self::assertEquals($expectedNormalizedData, $object->getNormalizedData());
    }

    /**
     * @return array
     */
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
