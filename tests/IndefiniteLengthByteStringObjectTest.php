<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\CBORObject;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\StringStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class IndefiniteLengthByteStringObjectTest extends CBORTestCase
{
    #[DataProvider('getData')]
    #[Test]
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
        static::assertSame($expectedValue, $object->normalize());

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
        static::assertSame($expectedValue, $decoded->normalize());
    }

    public static function getData(): array
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
