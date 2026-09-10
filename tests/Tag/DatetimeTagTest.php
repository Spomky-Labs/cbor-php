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
use const INF;
use InvalidArgumentException;
use Iterator;
use const NAN;
use function pack;
use const PHP_FLOAT_MAX;
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
        static::assertEqualsWithDelta(-10.0, $tag->normalize()->format('U.u'), 0.00001);
    }

    /**
     * The half precision value is 0.333251953125, which is closer to 333252 than to 333251 microseconds.
     */
    #[Test]
    public function createValidTimestampTagWithHalfPrecisionFloat(): void
    {
        $tag = TimestampTag::create(
            HalfPrecisionFloatObject::create(hex2bin(base_convert('0011010101010101', 2, 16)))
        );
        static::assertSame('0.333252', $tag->normalize()->format('U.u'));
    }

    /**
     * The single precision value is 0.9999999403953552, which rounds up to a whole second: the carry has to move
     * to the seconds instead of overflowing the microseconds field.
     */
    #[Test]
    public function createValidTimestampTagWithSinglePrecisionFloat(): void
    {
        $tag = TimestampTag::create(
            SinglePrecisionFloatObject::create(hex2bin(base_convert('00111111011111111111111111111111', 2, 16)))
        );
        static::assertSame('1.000000', $tag->normalize()->format('U.u'));
    }

    #[Test]
    public function createValidTimestampTagWithDoublePrecisionFloat(): void
    {
        $tag = TimestampTag::create(
            DoublePrecisionFloatObject::create(
                hex2bin(base_convert('0100000000001001001000011111101101010100010001000010110100011000', 2, 16))
            )
        );
        static::assertSame('3.141593', $tag->normalize()->format('U.u'));
    }

    /**
     * @return Iterator<array{float, string}>
     */
    public static function getFloatTimestamps(): Iterator
    {
        // An integral float is a valid timestamp per RFC 8949 section 3.4.2. Cutting the decimal representation
        // apart used to leave nothing after the point and "U.u" rejected the result outright.
        yield [1700000000.0, '2023-11-14T22:13:20.000000'];
        yield [1.0, '1970-01-01T00:00:01.000000'];
        yield [0.0, '1970-01-01T00:00:00.000000'];
        // The seconds have to be floored, not truncated towards zero, or a negative timestamp lands a second late.
        yield [-1.5, '1969-12-31T23:59:58.500000'];
        yield [-0.5, '1969-12-31T23:59:59.500000'];
        yield [-1.0, '1969-12-31T23:59:59.000000'];
        // The microseconds are computed, not read off a decimal representation whose length depends on the
        // "precision" ini setting.
        yield [1700000000.123456, '2023-11-14T22:13:20.123456'];
        yield [0.7, '1970-01-01T00:00:00.700000'];
        yield [1.0e-7, '1970-01-01T00:00:00.000000'];
    }

    #[DataProvider('getFloatTimestamps')]
    #[Test]
    public function createValidTimestampTagFromFloat(float $timestamp, string $expected): void
    {
        $tag = TimestampTag::create(DoublePrecisionFloatObject::create(self::encodeDouble($timestamp)));

        static::assertSame($expected, $tag->normalize()->format('Y-m-d\TH:i:s.u'));
    }

    /**
     * @return Iterator<array{float}>
     */
    public static function getUnrepresentableTimestamps(): Iterator
    {
        yield [NAN];
        yield [INF];
        yield [-INF];
        // PHP_FLOAT_MAX seconds is not an instant any date object can hold; it used to be reported as
        // 1970-01-01T00:00:01.797693.
        yield [PHP_FLOAT_MAX];
        yield [-PHP_FLOAT_MAX];
    }

    #[DataProvider('getUnrepresentableTimestamps')]
    #[Test]
    public function anUnrepresentableTimestampIsRejected(float $timestamp): void
    {
        $tag = TimestampTag::create(DoublePrecisionFloatObject::create(self::encodeDouble($timestamp)));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot be converted into a datetime object');

        $tag->normalize();
    }

    /**
     * @return Iterator<array{string}>
     */
    public static function getImpossibleDatetimes(): Iterator
    {
        // createFromFormat() rolls these over silently: the 30th of February became the 2nd of March, and the
        // twenty-fifth hour the next day.
        yield ['2013-02-30T12:00:00Z'];
        yield ['2015-02-29T00:00:00Z'];
        yield ['2003-12-13T25:00:00Z'];
        yield ['2003-12-13T18:61:00Z'];
        yield ['2003-13-01T18:30:02Z'];
        yield ['2003-12-13T18:30:02Z and some trailing data'];
    }

    #[DataProvider('getImpossibleDatetimes')]
    #[Test]
    public function anImpossibleDatetimeIsRejected(string $datetime): void
    {
        $tag = DatetimeTag::create(TextStringObject::create($datetime));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot be converted into a datetime object');

        $tag->normalize();
    }

    /**
     * A leap second is valid RFC 3339 but has no PHP representation. It is reported as the instant that follows,
     * which is what the rollover already did, rather than rejected along with the impossible dates.
     */
    #[Test]
    public function aLeapSecondIsAccepted(): void
    {
        $tag = DatetimeTag::create(TextStringObject::create('2016-12-31T23:59:60Z'));

        static::assertSame('2017-01-01T00:00:00.000000', $tag->normalize()->format('Y-m-d\TH:i:s.u'));
    }

    #[Test]
    public function aLeapSecondWithAnImpossibleDateIsStillRejected(): void
    {
        $tag = DatetimeTag::create(TextStringObject::create('2016-12-32T23:59:60Z'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot be converted into a datetime object');

        $tag->normalize();
    }

    /**
     * Encodes a float the way a document carries it: eight bytes, most significant first.
     */
    private static function encodeDouble(float $value): string
    {
        return pack('E', $value);
    }

    public static function getDatetimes(): Iterator
    {
        $buildTestEntry = static fn (string $datetime, string $timestamp): array => [
            TextStringObject::create($datetime),
            $timestamp,
        ];
        yield $buildTestEntry('2003-12-13T18:30:02Z', '1071340202.000000');
        yield $buildTestEntry('2003-12-13T18:30:02.25Z', '1071340202.250000');
        yield $buildTestEntry('2003-12-13T18:30:02+01:00', '1071336602.000000');
        yield $buildTestEntry('2003-12-13T18:30:02.25+01:00', '1071336602.250000');
        yield $buildTestEntry('2003-12-13T18:30:02.251254+01:00', '1071336602.251254');
    }
}
