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

use CBOR\ByteStringObject;
use CBOR\StringStream;

/**
 * @covers \CBOR\ByteStringObject
 */
final class ByteStringObjectTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function aByteStringObjectCanBeCreated(string $string, int $expectedAdditionalInformation, int $expectedLength, string $expectedEncodedObject): void
    {
        $object = new ByteStringObject($string);

        static::assertEquals(0b010, $object->getMajorType());
        static::assertEquals($expectedAdditionalInformation, $object->getAdditionalInformation());
        static::assertEquals($string, $object->getValue());
        static::assertEquals($expectedLength, $object->getLength());
        static::assertEquals($string, $object->getNormalizedData());

        $binary = (string) $object;
        static::assertEquals(hex2bin($expectedEncodedObject), $binary);

        $stream = new StringStream($binary);
        $decoded = $this->getDecoder()->decode($stream);

        static::assertInstanceOf(ByteStringObject::class, $decoded);
        static::assertEquals(0b010, $decoded->getMajorType());
        static::assertEquals($expectedAdditionalInformation, $decoded->getAdditionalInformation());
        static::assertEquals($string, $decoded->getValue());
        static::assertEquals($expectedLength, $decoded->getLength());
        static::assertEquals($string, $decoded->getNormalizedData());
    }

    public function getData(): array
    {
        return [
            ['Hello', 5, 5, '4548656c6c6f'],
            ['(｡◕‿◕｡)', 17, 17, '5128efbda1e29795e280bfe29795efbda129'],
            ['HelloHelloHelloHelloHello', 24, 25, '581948656c6c6f48656c6c6f48656c6c6f48656c6c6f48656c6c6f'],
        ];
    }
}
