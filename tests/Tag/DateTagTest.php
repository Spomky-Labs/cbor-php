<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\Normalizable;
use CBOR\StringStream;
use CBOR\Tag\DateStringTag;
use CBOR\Tag\DateTag;
use CBOR\Test\CBORTestCase;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use function date_default_timezone_get;
use function date_default_timezone_set;
use DateTimeInterface;
use function hex2bin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The two date tags of RFC 8943: 100, a number of days since the epoch, and 1004, an RFC 3339 full-date.
 *
 * Both denote a calendar day rather than an instant, and both are normalized to midnight UTC -- the only
 * reading that keeps the day intact, since PHP has no date-only type.
 *
 * @internal
 */
final class DateTagTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dates(): iterable
    {
        yield 'the epoch itself' => ['d86400', '1970-01-01'];
        yield 'a day after the epoch' => ['d864194d2f', '2024-02-06'];
        yield 'a day before the epoch' => ['d86420', '1969-12-31'];
        yield 'a full-date string' => ['d903ec6a323032342d30312d3135', '2024-01-15'];
        yield 'a leap day' => ['d903ec6a323032342d30322d3239', '2024-02-29'];
    }

    #[DataProvider('dates')]
    #[Test]
    public function aDateIsNormalizedToMidnightUtc(string $data, string $expected): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($data)));

        static::assertInstanceOf(Normalizable::class, $object);
        $date = $object->normalize();

        static::assertInstanceOf(DateTimeInterface::class, $date);
        static::assertSame($expected . ' 00:00:00 +00:00', $date->format('Y-m-d H:i:s P'));
    }

    #[Test]
    public function aDayCountThatNoDateCanHoldIsRejected(): void
    {
        // 100(18446744073709551615)
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d8641bffffffffffffffff')));

        static::assertInstanceOf(DateTag::class, $object);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot be converted into a datetime object');
        $object->normalize();
    }

    #[Test]
    public function aDayCountThatIsNotAnIntegerIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts an Integer object.');
        DateTag::create(TextStringObject::create('2024-01-15'));
    }

    /**
     * createFromFormat() rolls a day that does not exist over into the next month instead of rejecting it, so
     * the warning it raises is what tells the two apart.
     */
    #[Test]
    public function aDayThatDoesNotExistIsRejected(): void
    {
        $tag = DateStringTag::create(TextStringObject::create('2013-02-30'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot be converted into a datetime object');
        $tag->normalize();
    }

    #[Test]
    public function aDateStringWithATimeIsRejected(): void
    {
        $tag = DateStringTag::create(TextStringObject::create('2024-01-15T10:30:00Z'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot be converted into a datetime object');
        $tag->normalize();
    }

    #[Test]
    public function aDateStringThatIsNotTextIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        DateStringTag::create(ByteStringObject::create('2024-01-15'));
    }

    /**
     * The day is the same wherever the process happens to be running: a date tag carries no time zone, so the
     * ambient one must not leak into the value.
     */
    #[Test]
    public function theDefaultTimeZoneDoesNotMoveTheDay(): void
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Pacific/Kiritimati');

        try {
            $tag = DateStringTag::create(TextStringObject::create('2024-01-15'));
            static::assertSame('2024-01-15', $tag->normalize()->format('Y-m-d'));

            $day = DateTag::create(UnsignedIntegerObject::create(19737));
            static::assertSame('2024-01-15', $day->normalize()->format('Y-m-d'));
        } finally {
            date_default_timezone_set($timezone);
        }
    }
}
