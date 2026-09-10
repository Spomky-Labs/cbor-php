<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

/**
 * The stream hands out the payload in sequence and never reads past its end.
 *
 * It used to hold the data in a php://memory handle and pull it back with fread(). Reading a string that is already
 * in memory through a file handle is what these tests keep honest now that it is a cursor over that string: the
 * successive reads have to advance, stop exactly at the end, and report what was left when they cannot.
 *
 * @internal
 */
final class StringStreamTest extends CBORTestCase
{
    #[Test]
    public function successiveReadsAdvanceThroughThePayload(): void
    {
        $stream = StringStream::create('abcdefghij');

        static::assertSame('abc', $stream->read(3));
        static::assertSame('de', $stream->read(2));
        static::assertSame('fghij', $stream->read(5));
    }

    #[Test]
    public function aZeroLengthReadYieldsNothingAndDoesNotAdvance(): void
    {
        $stream = StringStream::create('abc');

        static::assertSame('', $stream->read(0));
        static::assertSame('abc', $stream->read(3));
    }

    #[Test]
    public function readingExactlyToTheEndIsAllowed(): void
    {
        $stream = StringStream::create('abc');

        static::assertSame('abc', $stream->read(3));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Out of range. Expected: 1, read: 0.');
        $stream->read(1);
    }

    #[Test]
    public function readingPastTheEndReportsWhatWasLeft(): void
    {
        $stream = StringStream::create('abcdefghij');
        $stream->read(7);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Out of range. Expected: 5, read: 3.');
        $stream->read(5);
    }

    #[Test]
    public function anEmptyPayloadHasNothingToGive(): void
    {
        $stream = StringStream::create('');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Out of range. Expected: 1, read: 0.');
        $stream->read(1);
    }

    /**
     * The old implementation pulled the payload in 1024-byte slices, so a read spanning that boundary is worth
     * keeping an eye on.
     */
    #[Test]
    public function aReadLargerThanTheOldSliceSizeIsReturnedWhole(): void
    {
        $payload = str_repeat('x', 5000);
        $stream = StringStream::create($payload);

        static::assertSame($payload, $stream->read(5000));
    }

    #[Test]
    public function binaryPayloadsAreReturnedByteForByte(): void
    {
        $payload = "\x00\xff\x0a\x1b\x00";
        $stream = StringStream::create($payload);

        static::assertSame($payload, $stream->read(5));
    }
}
