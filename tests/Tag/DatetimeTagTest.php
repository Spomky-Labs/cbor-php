<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\NegativeIntegerObject;
use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use CBOR\Tag\DatetimeTag;
use CBOR\Tag\TimestampTag;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class DatetimeTagTest extends TestCase
{
    #[DataProvider('getDatetimes')]
    #[Test]
    public function createValidDatetimeTag(CBORObject $object, string $expectedTimestamp): void
    {
        $tag = DatetimeTag::create($object);
        static::assertSame($expectedTimestamp, $tag->normalize()->format('U.u'));
    }

    #[Test]
    public function createInvalidDatetimeTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Byte String object.');

        DatetimeTag::create(ByteStringObject::create('data'));
    }

    #[Test]
    public function createValidTimestampTagWithUnsignedInteger(): void
    {
        $tag = TimestampTag::create(UnsignedIntegerObject::create(0));
        static::assertSame('0.000000', $tag->normalize()->format('U.u'));
    }

    #[Test]
    public function createValidTimestampTagWithNegativeInteger(): void
    {
        $tag = TimestampTag::create(NegativeIntegerObject::create(-10));
        static::assertSame('-10.000000', $tag->normalize()->format('U.u'));
    }

    #[Test]
    public function createValidTimestampTagWithHalfPrecisionFloat(): void
    {
        $tag = TimestampTag::create(
            HalfPrecisionFloatObject::create(hex2bin(base_convert('0011010101010101', 2, 16)))
        );
        static::assertSame('0.333251', $tag->normalize()->format('U.u'));
    }

    #[Test]
    public function createValidTimestampTagWithSinglePrecisionFloat(): void
    {
        $tag = TimestampTag::create(
            SinglePrecisionFloatObject::create(hex2bin(base_convert('00111111011111111111111111111111', 2, 16)))
        );
        static::assertSame('0.999999', $tag->normalize()->format('U.u'));
    }

    #[Test]
    public function createValidTimestampTagWithDoublePrecisionFloat(): void
    {
        $tag = TimestampTag::create(
            DoublePrecisionFloatObject::create(
                hex2bin(base_convert('0100000000001001001000011111101101010100010001000010110100011000', 2, 16))
            )
        );
        static::assertSame('3.141592', $tag->normalize()->format('U.u'));
    }

    public static function getDatetimes(): array
    {
        $buildTestEntry = static fn (string $datetime, string $timestamp): array => [
            TextStringObject::create($datetime),
            $timestamp,
        ];

        return [
            $buildTestEntry('2003-12-13T18:30:02Z', '1071340202.000000'),
            $buildTestEntry('2003-12-13T18:30:02.25Z', '1071340202.250000'),
            $buildTestEntry('2003-12-13T18:30:02+01:00', '1071336602.000000'),
            $buildTestEntry('2003-12-13T18:30:02.25+01:00', '1071336602.250000'),
            $buildTestEntry('2003-12-13T18:30:02.251254+01:00', '1071336602.251254'),
        ];
    }
}
