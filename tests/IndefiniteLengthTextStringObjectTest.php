<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test;

use CBOR\CBORObject;
use CBOR\IndefiniteLengthTextStringObject;
use CBOR\StringStream;
use CBOR\TextStringObject;

/**
 * @internal
 */
final class IndefiniteLengthTextStringObjectTest extends CBORTestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function aIndefiniteLengthTextStringObjectCanBeCreated(
        array $chunks,
        int $expectedLength,
        string $expectedValue,
        string $expectedEncodedObject
    ): void {
        $object = IndefiniteLengthTextStringObject::create();
        foreach ($chunks as $chunk) {
            $object->add(TextStringObject::create($chunk));
        }

        static::assertSame(CBORObject::MAJOR_TYPE_TEXT_STRING, $object->getMajorType());
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

        static::assertInstanceOf(IndefiniteLengthTextStringObject::class, $decoded);
        static::assertSame(CBORObject::MAJOR_TYPE_TEXT_STRING, $decoded->getMajorType());
        static::assertSame(CBORObject::LENGTH_INDEFINITE, $decoded->getAdditionalInformation());
        static::assertSame($expectedValue, $decoded->getValue());
        static::assertSame($expectedLength, $decoded->getLength());
        static::assertSame($expectedValue, $decoded->getNormalizedData());
    }

    public function getData(): array
    {
        return [
            [['He', 'll', 'o'], 5, 'Hello', '7f624865626c6c616fff'],
            [['(', '｡', '◕', '‿', '◕', '｡', ')'],
                7,
                '(｡◕‿◕｡)',
                '7f612863efbda163e2979563e280bf63e2979563efbda16129ff',
            ],
        ];
    }
}
