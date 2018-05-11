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

use CBOR\TextStringObject;
use CBOR\TextStringWithChunkObject;
use CBOR\StringStream;

/**
 * @covers \CBOR\TextStringWithChunkObject
 */
final class TextStringWithChunkObjectTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function aTextStringWithChunkObjectCanBeCreated(array $chunks, int $expectedLength, string $expectedValue, string $expectedEncodedObject)
    {
        $object = TextStringWithChunkObject::create();
        foreach ($chunks as $chunk) {
            $object->addChunk(TextStringObject::create($chunk));
        }

        self::assertEquals(0b011, $object->getMajorType());
        self::assertEquals(0b00011111, $object->getAdditionalInformation());
        self::assertEquals($expectedValue, $object->getValue());
        self::assertEquals($expectedLength, $object->getLength());
        self::assertEquals($expectedValue, $object->getNormalizedData());

        $binary = (string) $object;
        self::assertEquals(hex2bin($expectedEncodedObject), $binary);

        $stream = new StringStream($binary);
        $decoded = $this->getDecoder()->decode($stream);

        self::assertEquals(0b011, $decoded->getMajorType());
        self::assertEquals(0b00011111, $decoded->getAdditionalInformation());
        self::assertEquals($expectedValue, $decoded->getValue());
        self::assertEquals($expectedLength, $decoded->getLength());
        self::assertEquals($expectedValue, $decoded->getNormalizedData());
    }

    public function getData(): array
    {
        return [
            [['He', 'll', 'o'], 5, 'Hello', '7f624865626c6c616fff'],
            [['(', '｡', '◕', '‿', '◕', '｡', ')'], 7, '(｡◕‿◕｡)', '7f612863efbda163e2979563e280bf63e2979563efbda16129ff'],
        ];
    }
}
