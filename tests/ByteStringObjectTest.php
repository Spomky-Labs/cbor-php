<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\StringStream;

/**
 * @internal
 */
final class ByteStringObjectTest extends CBORTestCase
{
    /**
     * @test
     * @dataProvider getData
     */
    public function aByteStringObjectCanBeCreated(
        string $string,
        int $expectedAdditionalInformation,
        int $expectedLength,
        string $expectedEncodedObject
    ): void {
        $object = ByteStringObject::create($string);

        static::assertSame(CBORObject::MAJOR_TYPE_BYTE_STRING, $object->getMajorType());
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

        static::assertInstanceOf(ByteStringObject::class, $decoded);
        static::assertSame(CBORObject::MAJOR_TYPE_BYTE_STRING, $decoded->getMajorType());
        static::assertSame($expectedAdditionalInformation, $decoded->getAdditionalInformation());
        static::assertSame($string, $decoded->getValue());
        static::assertSame($expectedLength, $decoded->getLength());
        static::assertSame($string, $decoded->normalize());
    }

    public function getData(): array
    {
        return [
            ['Hello', 5, 5, '4548656c6c6f'],
            ['(｡◕‿◕｡)', 17, 17, '5128efbda1e29795e280bfe29795efbda129'],
            ['HelloHelloHelloHelloHello', 24, 25, '581948656c6c6f48656c6c6f48656c6c6f48656c6c6f48656c6c6f'],
        ];
    }
}
