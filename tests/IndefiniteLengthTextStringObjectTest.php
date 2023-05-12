<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\CBORObject;
use CBOR\IndefiniteLengthTextStringObject;
use CBOR\StringStream;
use CBOR\TextStringObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class IndefiniteLengthTextStringObjectTest extends CBORTestCase
{
    #[DataProvider('getData')]
    #[Test]
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
        static::assertSame($expectedValue, $object->normalize());

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
        static::assertSame($expectedValue, $decoded->normalize());
    }

    public static function getData(): array
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
