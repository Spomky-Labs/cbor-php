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

use CBOR\ByteStringWithChunkObject;
use CBOR\StringStream;
use function Safe\hex2bin;

/**
 * @covers \CBOR\ByteStringWithChunkObject
 */
final class ByteStringWithChunkObjectTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function aByteStringWithChunkObjectCanBeCreated(array $chunks, int $expectedLength, string $expectedValue, string $expectedEncodedObject): void
    {
        $object = new ByteStringWithChunkObject();
        foreach ($chunks as $chunk) {
            $object->append($chunk);
        }

        static::assertEquals(0b010, $object->getMajorType());
        static::assertEquals(0b00011111, $object->getAdditionalInformation());
        static::assertEquals($expectedValue, $object->getValue());
        static::assertEquals($expectedLength, $object->getLength());
        static::assertEquals($expectedValue, $object->getNormalizedData());

        $binary = (string) $object;
        static::assertEquals(hex2bin($expectedEncodedObject), $binary);

        $stream = new StringStream($binary);
        $decoded = $this->getDecoder()->decode($stream);

        static::assertInstanceOf(ByteStringWithChunkObject::class, $decoded);
        static::assertEquals(0b010, $decoded->getMajorType());
        static::assertEquals(0b00011111, $decoded->getAdditionalInformation());
        static::assertEquals($expectedValue, $decoded->getValue());
        static::assertEquals($expectedLength, $decoded->getLength());
        static::assertEquals($expectedValue, $decoded->getNormalizedData());
    }

    public function getData(): array
    {
        return [
            [['He', 'll', 'o'], 5, 'Hello', '5f424865426c6c416fff'],
            [['(', '｡', '◕', '‿', '◕', '｡', ')'], 17, '(｡◕‿◕｡)', '5f412843efbda143e2979543e280bf43e2979543efbda14129ff'],
        ];
    }
}
