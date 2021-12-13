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

use CBOR\CBORObject;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\StringStream;

/**
 * @internal
 */
final class IndefiniteLengthByteStringObjectTest extends CBORTestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function aIndefiniteLengthByteStringObjectCanBeCreated(
        array $chunks,
        int $expectedLength,
        string $expectedValue,
        string $expectedEncodedObject
    ): void {
        $object = IndefiniteLengthByteStringObject::create();
        foreach ($chunks as $chunk) {
            $object->append($chunk);
        }

        static::assertSame(CBORObject::MAJOR_TYPE_BYTE_STRING, $object->getMajorType());
        static::assertSame(CBORObject::LENGTH_INDEFINITE, $object->getAdditionalInformation());
        static::assertSame($expectedValue, $object->getValue());
        static::assertSame($expectedLength, $object->getLength());
        static::assertSame($expectedValue, $object->getNormalizedData());

        $binary = (string) $object;
        static::assertSame(hex2bin($expectedEncodedObject), $binary);

        $stream = StringStream::create($binary);
        $decoded = $this->getDecoder()
            ->decode($stream)
        ;

        static::assertInstanceOf(IndefiniteLengthByteStringObject::class, $decoded);
        static::assertSame(CBORObject::MAJOR_TYPE_BYTE_STRING, $decoded->getMajorType());
        static::assertSame(CBORObject::LENGTH_INDEFINITE, $decoded->getAdditionalInformation());
        static::assertSame($expectedValue, $decoded->getValue());
        static::assertSame($expectedLength, $decoded->getLength());
        static::assertSame($expectedValue, $decoded->getNormalizedData());
    }

    public function getData(): array
    {
        return [
            [['He', 'll', 'o'], 5, 'Hello', '5f424865426c6c416fff'],
            [['(', '｡', '◕', '‿', '◕', '｡', ')'],
                17,
                '(｡◕‿◕｡)',
                '5f412843efbda143e2979543e280bf43e2979543efbda14129ff',
            ],
        ];
    }
}
