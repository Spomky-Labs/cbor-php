<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\CBORObject;
use CBOR\StringStream;
use CBOR\TextStringObject;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class TextStringObjectTest extends CBORTestCase
{
    #[DataProvider('getData')]
    #[Test]
    public function aTextStringObjectCanBeCreated(
        string $string,
        int $expectedAdditionalInformation,
        int $expectedLength,
        string $expectedEncodedObject
    ): void {
        $object = TextStringObject::create($string);

        static::assertSame(CBORObject::MAJOR_TYPE_TEXT_STRING, $object->getMajorType());
        static::assertSame($expectedAdditionalInformation, $object->getAdditionalInformation());
        static::assertSame($string, $object->getValue());
        static::assertSame($expectedLength, $object->getLength());
        static::assertSame($string, $object->normalize());

        $binary = (string) $object;
        static::assertSame(hex2bin($expectedEncodedObject), $binary);

        $stream = StringStream::create($binary);
        $decoded = $this->getDecoder()
            ->decode($stream)
        ;

        static::assertInstanceOf(TextStringObject::class, $decoded);
        static::assertSame(CBORObject::MAJOR_TYPE_TEXT_STRING, $decoded->getMajorType());
        static::assertSame($expectedAdditionalInformation, $decoded->getAdditionalInformation());
        static::assertSame($string, $decoded->getValue());
        static::assertSame($expectedLength, $decoded->getLength());
        static::assertSame($string, $decoded->normalize());
    }

    public static function getData(): Iterator
    {
        yield ['Hello', 5, 5, '6548656c6c6f'];
        yield ['(｡◕‿◕｡)', 17, 7, '7128efbda1e29795e280bfe29795efbda129'];
        yield ['HelloHelloHelloHelloHello', 24, 25, '781948656c6c6f48656c6c6f48656c6c6f48656c6c6f48656c6c6f'];
    }
}
