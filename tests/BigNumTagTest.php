<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\ByteStringObject;
use CBOR\StringStream;
use CBOR\Tag\NegativeBigIntegerTag;
use CBOR\Tag\UnsignedBigIntegerTag;
use function hex2bin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression tests for the empty big num payload reported in Spomky-Labs/cbor-php#151.
 *
 * RFC 8949 section 3.4.3 makes the empty byte string the preferred serialization of zero, so "c2 40" is 0 and
 * "c3 40" is -1. Both used to be rejected, at normalize() and, when the big num was used as a map key, from
 * decode() itself, while the equivalent leading zero forms were accepted.
 *
 * @internal
 */
final class BigNumTagTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function emptyBigNums(): iterable
    {
        // RFC 8949 section 3.4.3: the empty byte string is the preferred serialization of zero.
        yield 'unsigned big num, tag 2' => ['c240', '0'];
        yield 'negative big num, tag 3' => ['c340', '-1'];
        // The leading zero forms were already accepted and shall keep their value.
        yield 'unsigned big num, leading zero' => ['c24100', '0'];
        yield 'negative big num, leading zero' => ['c34100', '-1'];
    }

    #[Test]
    #[DataProvider('emptyBigNums')]
    public function anEmptyBigNumIsZero(string $payload, string $expected): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertSame($expected, $object->normalize());
    }

    /**
     * The map key path normalizes the key on its own, and used to reject the whole document because of it.
     */
    #[Test]
    public function anEmptyBigNumIsAcceptedAsAMapKey(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('a2c2406161c3406162')))
        ;

        static::assertSame([
            0 => 'a',
            -1 => 'b',
        ], $object->normalize());
    }

    #[Test]
    public function anEmptyBigNumIsNormalizedByTheTagItself(): void
    {
        $unsigned = UnsignedBigIntegerTag::create(ByteStringObject::create(''));
        $negative = NegativeBigIntegerTag::create(ByteStringObject::create(''));

        static::assertInstanceOf(UnsignedBigIntegerTag::class, $unsigned);
        static::assertInstanceOf(NegativeBigIntegerTag::class, $negative);
        static::assertSame('0', $unsigned->normalize());
        static::assertSame('-1', $negative->normalize());
    }
}
