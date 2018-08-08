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
use CBOR\TextStringObject;

/**
 * @covers \CBOR\TextStringObject
 */
final class TextStringObjectTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function aTextStringObjectCanBeCreated(string $string, int $expectedAdditionalInformation, int $expectedLength, string $expectedEncodedObject)
    {
        $object = new TextStringObject($string);

        static::assertEquals(0b011, $object->getMajorType());
        static::assertEquals($expectedAdditionalInformation, $object->getAdditionalInformation());
        static::assertEquals($string, $object->getValue());
        static::assertEquals($expectedLength, $object->getLength());
        static::assertEquals($string, $object->getNormalizedData());

        $binary = (string) $object;
        static::assertEquals(hex2bin($expectedEncodedObject), $binary);

        $stream = new StringStream($binary);
        $decoded = $this->getDecoder()->decode($stream);

        static::assertEquals(0b011, $decoded->getMajorType());
        static::assertEquals($expectedAdditionalInformation, $decoded->getAdditionalInformation());
        static::assertEquals($string, $decoded->getValue());
        static::assertEquals($expectedLength, $decoded->getLength());
        static::assertEquals($string, $decoded->getNormalizedData());
    }

    public function getData(): array
    {
        return [
            ['Hello', 5, 5, '6548656c6c6f'],
            ['(｡◕‿◕｡)', 17, 7, '7128efbda1e29795e280bfe29795efbda129'],
            ['HelloHelloHelloHelloHello', 24, 25, '781948656c6c6f48656c6c6f48656c6c6f48656c6c6f48656c6c6f'],
        ];
    }
}
