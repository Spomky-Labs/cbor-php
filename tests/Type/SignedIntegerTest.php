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

use CBOR\SignedIntegerObject;
use CBOR\StringStream;

final class SignedIntegerTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getValidValue
     */
    public function createOnValidValue(int $intValue, string $expectedIntValue, int $expectedMajorType, $expectedAdditionalInformation)
    {
        $unsignedInteger = SignedIntegerObject::create($intValue);
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
                -12345678,
                '-12345678',
                1,
                26,
            ],
            [
                -255,
                '-255',
                1,
                24,
            ],
            [
                -254,
                '-254',
                1,
                24,
            ],
            [
                -65535,
                '-65535',
                1,
                25,
            ],
            [
                -18,
                '-18',
                1,
                17,
            ],
        ];
    }

    /**
     * @test
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage The value must be a negative integer.
     */
    public function ceateOnNegativeValue()
    {
        SignedIntegerObject::create(1);
    }

    /**
     * @test
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Out of range. Please use NegativeBigIntegerTag tag with ByteStringObject object instead.
     */
    public function createOnOutOfRangeValue()
    {
        SignedIntegerObject::create(-4294967297);
    }

    /**
     * @test
     * @dataProvider getDataSet
     *
     * @param string $data
     * @param string $expectedNormalizedData
     */
    public function anUnsignedIntegerCanBeEncodedAndDecoded(string $data, string $expectedNormalizedData)
    {
        $stream = new StringStream(hex2bin($data));
        $object = $this->getDecoder()->decode($stream);
        $object->getNormalizedData();
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
                '20',
                '-1',
            ], [
                '29',
                '-10',
            ], [
                '3863',
                '-100',
            ], [
                '3903e7',
                '-1000',
            ], [
                'c349010000000000000000',
                '-18446744073709551617',
            ], [
                '3bffffffffffffffff',
                '-18446744073709551616',
            ],
        ];
    }
}
