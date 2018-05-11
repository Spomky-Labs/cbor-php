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
use CBOR\ByteStringWithChunkObject;
use CBOR\StringStream;

/**
 * @covers \CBOR\ByteStringWithChunkObject
 */
final class ByteStringWithChunkObjectTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function aByteStringWithChunkObjectCanBeCreated(array $chunks, int $expectedLength, string $expectedValue, string $expectedEncodedObject)
    {
        $object = ByteStringWithChunkObject::create();
        foreach ($chunks as $chunk) {
            $object->addChunk(ByteStringObject::create($chunk));
        }

        self::assertEquals(0b010, $object->getMajorType());
        self::assertEquals(0b00011111, $object->getAdditionalInformation());
        self::assertEquals($expectedValue, $object->getValue());
        self::assertEquals($expectedLength, $object->getLength());
        self::assertEquals($expectedValue, $object->getNormalizedData());

        $binary = (string) $object;
        self::assertEquals(hex2bin($expectedEncodedObject), $binary);

        $stream = new StringStream($binary);
        $decoded = $this->getDecoder()->decode($stream);

        self::assertEquals(0b010, $decoded->getMajorType());
        self::assertEquals(0b00011111, $decoded->getAdditionalInformation());
        self::assertEquals($expectedValue, $decoded->getValue());
        self::assertEquals($expectedLength, $decoded->getLength());
        self::assertEquals($expectedValue, $decoded->getNormalizedData());
    }

    public function getData(): array
    {
        return [
            [['He', 'll', 'o'], 5, 'Hello', '5f424865426c6c416fff'],
            [['(', '｡', '◕', '‿', '◕', '｡', ')'], 17, '(｡◕‿◕｡)', '5f412843efbda143e2979543e280bf43e2979543efbda14129ff'],
        ];
    }
}
