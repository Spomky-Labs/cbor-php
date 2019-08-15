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
use CBOR\TextStringWithChunkObject;

/**
 * @covers \CBOR\TextStringWithChunkObject
 */
final class TextStringWithChunkObjectTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function aTextStringWithChunkObjectCanBeCreated(array $chunks, int $expectedLength, string $expectedValue, string $expectedEncodedObject): void
    {
        $object = new TextStringWithChunkObject();
        foreach ($chunks as $chunk) {
            $object->add(new TextStringObject($chunk));
        }

        static::assertEquals(0b011, $object->getMajorType());
        static::assertEquals(0b00011111, $object->getAdditionalInformation());
        static::assertEquals($expectedValue, $object->getValue());
        static::assertEquals($expectedLength, $object->getLength());
        static::assertEquals($expectedValue, $object->getNormalizedData());

        $binary = (string) $object;
        static::assertEquals(hex2bin($expectedEncodedObject), $binary);

        $stream = new StringStream($binary);
        $decoded = $this->getDecoder()->decode($stream);

        static::assertInstanceOf(TextStringWithChunkObject::class, $decoded);
        static::assertEquals(0b011, $decoded->getMajorType());
        static::assertEquals(0b00011111, $decoded->getAdditionalInformation());
        static::assertEquals($expectedValue, $decoded->getValue());
        static::assertEquals($expectedLength, $decoded->getLength());
        static::assertEquals($expectedValue, $decoded->getNormalizedData());
    }

    public function getData(): array
    {
        return [
            [['He', 'll', 'o'], 5, 'Hello', '7f624865626c6c616fff'],
            [['(', '｡', '◕', '‿', '◕', '｡', ')'], 7, '(｡◕‿◕｡)', '7f612863efbda163e2979563e280bf63e2979563efbda16129ff'],
        ];
    }
}
