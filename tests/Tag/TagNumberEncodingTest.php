<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\StringStream;
use CBOR\Tag;
use CBOR\Test\CBORTestCase;
use InvalidArgumentException;
use Iterator;
use const PHP_INT_MAX;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A tag number is the argument of a major type 6 head: RFC 8949 section 4.2 asks for the shortest of the five heads
 * that holds it, and the payload has to fill the width that head announces so the decoder reads it back.
 *
 * @internal
 */
final class TagNumberEncodingTest extends CBORTestCase
{
    #[DataProvider('getTagNumber')]
    #[Test]
    public function aTagNumberIsEncodedInItsShortestFormAndDecodedBack(int $tag, string $expectedEncodedData): void
    {
        $data = (string) ArbitraryNumberTag::createWithTagNumber($tag, ByteStringObject::create('x'));
        static::assertSame($expectedEncodedData, bin2hex($data));

        $decoded = $this->getDecoder()
            ->decode(StringStream::create($data))
        ;
        static::assertInstanceOf(Tag::class, $decoded);
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $decoded->getMajorType());
        static::assertSame($data, (string) $decoded);
    }

    public static function getTagNumber(): Iterator
    {
        yield [6, 'c64178'];
        yield [23, 'd74178'];
        yield [24, 'd8184178'];
        yield [255, 'd8ff4178'];
        yield [256, 'd901004178'];
        yield [4095, 'd90fff4178'];
        yield [65_535, 'd9ffff4178'];
        yield [65_536, 'da000100004178'];
        yield [1_048_575, 'da000fffff4178'];
        yield [4_294_967_295, 'daffffffff4178'];
        yield [4_294_967_296, 'db00000001000000004178'];
        yield [PHP_INT_MAX, 'db7fffffffffffffff4178'];
    }

    #[Test]
    public function aNegativeTagNumberIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a positive integer.');

        ArbitraryNumberTag::createWithTagNumber(-1, ByteStringObject::create('x'));
    }
}
