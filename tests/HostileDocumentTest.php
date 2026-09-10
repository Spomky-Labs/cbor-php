<?php

declare(strict_types=1);

namespace CBOR\Test;

use function bin2hex;
use CBOR\StringStream;
use CBOR\Tag\NegativeBigIntegerTag;
use CBOR\Tag\UnsignedBigIntegerTag;
use function chr;
use function hex2bin;
use InvalidArgumentException;
use function microtime;
use function pack;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use function str_repeat;
use function strlen;

/**
 * Regression tests for GHSA-jfrf-557c-963v.
 *
 * Five paths let a document written by an attacker cost far more than its size, or fail outside the error contract
 * of this library:
 *
 *  - the two map normalize() methods were quadratic in the number of entries, and a map used as a map key was fully
 *    normalized before being turned down, so the cost was paid inside decode() itself;
 *  - a decimal fraction or a big float with a large exponent expanded a six byte item into kilobytes, which a map
 *    made of such keys turned into an out-of-memory fatal error no try/catch can intercept;
 *  - converting the byte string of a big number into its decimal form is quadratic on every brick/math calculator
 *    but GMP, which this package only suggests;
 *  - a date-time holding a NUL byte raised a ValueError, an Error rather than the documented exception;
 *  - an indefinite length string with a chunk of the wrong type raised a RuntimeException, which the documentation
 *    reserves for a missing extension.
 *
 * @internal
 */
final class HostileDocumentTest extends CBORTestCase
{
    /**
     * Head of a data item: the major type, then the argument in the shortest form that holds it.
     */
    private static function head(int $majorType, int $argument): string
    {
        if ($argument < 24) {
            return chr($majorType << 5 | $argument);
        }
        if ($argument < 256) {
            return chr($majorType << 5 | 24) . chr($argument);
        }
        if ($argument < 65536) {
            return chr($majorType << 5 | 25) . pack('n', $argument);
        }

        return chr($majorType << 5 | 26) . pack('N', $argument);
    }

    private static function textString(string $value): string
    {
        return self::head(3, strlen($value)) . $value;
    }

    /**
     * A map of n entries, {"k0": 0, "k1": 1, ...}.
     */
    private static function map(int $entries): string
    {
        $document = self::head(5, $entries);
        for ($i = 0; $i < $entries; $i++) {
            $document .= self::textString('k' . $i) . self::head(0, $i);
        }

        return $document;
    }

    /**
     * normalize() built its result with array_reduce(), whose closure takes the accumulator by value: PHP copied the
     * whole array on every entry, so the cost grew with the square of the number of entries. 100 000 entries, 469 kB
     * on the wire, took 45 s. The budget below is two orders of magnitude above what the fix costs and still an
     * order of magnitude under what the defect did.
     */
    #[Test]
    public function aLargeMapIsNormalizedInLinearTime(): void
    {
        $entries = 50000;
        $object = $this->getDecoder()
            ->decode(StringStream::create(self::map($entries)))
        ;

        $start = microtime(true);
        $normalized = $object->normalize();
        $elapsed = microtime(true) - $start;

        static::assertCount($entries, $normalized);
        static::assertSame('0', $normalized['k0']);
        static::assertSame((string) ($entries - 1), $normalized['k' . ($entries - 1)]);
        static::assertLessThan(5.0, $elapsed, 'Normalizing a large map shall not be quadratic in its entry count.');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function containerKeys(): iterable
    {
        // {[4([8192, 1])]: 0} -- a list holding a decimal fraction whose exponent is out of range.
        yield 'list as a key' => ['a181c4821920000100'];
        // {{"a": 4([8192, 1])}: 0}
        yield 'map as a key' => ['a1a16161c4821920000100'];
    }

    /**
     * A list or a map can never normalize to the integer or the string a PHP array offset needs, and the major type
     * says so before the content is touched. It used to be normalized first, so a one entry map whose key was a
     * 100 000 entry map cost 37 s inside decode(), for a document that was rejected anyway.
     *
     * The keys below hold an item whose own normalize() throws, so the message tells which of the two happened: the
     * exponent is only reached by walking into the container.
     */
    #[Test]
    #[DataProvider('containerKeys')]
    public function aContainerUsedAsAMapKeyIsRejectedWithoutBeingNormalized(string $payload): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A map key shall normalize to an integer or a string');

        $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;
    }

    /**
     * 6000 keys of the form 4([8192, n]) are 53 725 bytes on the wire. Each one used to expand to an 8.3 kB decimal
     * string, and because keys are normalized as the map is built, decode() alone exhausted a 128 MB limit with a
     * fatal error. The exponent bound now turns the document down before the first expansion.
     */
    #[Test]
    public function aMapOfLargeExponentKeysIsRejectedWithoutAllocating(): void
    {
        $document = self::head(5, 6000);
        for ($i = 0; $i < 6000; $i++) {
            $document .= self::head(6, 4) . self::head(4, 2) . self::head(0, 8192) . self::head(0, $i)
                . self::head(0, 0);
        }

        $start = microtime(true);
        $caught = null;

        try {
            $this->getDecoder()
                ->decode(StringStream::create($document))
            ;
        } catch (InvalidArgumentException $exception) {
            $caught = $exception;
        }

        static::assertInstanceOf(InvalidArgumentException::class, $caught);
        static::assertStringContainsString('The exponent is out of range', $caught->getMessage());
        static::assertLessThan(
            5.0,
            microtime(true) - $start,
            'Rejecting the document shall not expand a single key.'
        );
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function bigNumberTags(): iterable
    {
        yield 'unsigned big number, tag 2' => [2, UnsignedBigIntegerTag::MAX_BYTE_LENGTH];
        yield 'negative big number, tag 3' => [3, NegativeBigIntegerTag::MAX_BYTE_LENGTH];
    }

    #[Test]
    #[DataProvider('bigNumberTags')]
    public function aBigNumberOnTheLengthBoundIsStillNormalized(int $tag, int $bound): void
    {
        $payload = str_repeat("\xff", $bound);
        $object = $this->getDecoder()
            ->decode(StringStream::create(self::head(6, $tag) . self::head(2, strlen($payload)) . $payload))
        ;

        static::assertIsString($object->normalize());
    }

    /**
     * Base conversion is quadratic on the calculator a default installation runs -- neither ext-gmp nor ext-bcmath
     * is required by this package -- so a 500 byte big number cost 1.8 s and a 2 kB one close to a hundred. Used as
     * a map key, that was reachable from decode() alone.
     */
    #[Test]
    #[DataProvider('bigNumberTags')]
    public function aBigNumberLongerThanTheBoundIsRejected(int $tag, int $bound): void
    {
        $payload = str_repeat("\xff", $bound + 1);
        $object = $this->getDecoder()
            ->decode(StringStream::create(self::head(6, $tag) . self::head(2, strlen($payload)) . $payload))
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The big number is out of range');

        $object->normalize();
    }

    #[Test]
    #[DataProvider('bigNumberTags')]
    public function aBigNumberUsedAsAMapKeyIsBoundedTheSameWay(int $tag, int $bound): void
    {
        $payload = str_repeat("\xff", $bound + 1);
        $document = self::head(5, 1) . self::head(6, $tag) . self::head(2, strlen($payload)) . $payload
            . self::head(0, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The big number is out of range');

        $this->getDecoder()
            ->decode(StringStream::create($document))
        ;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function dateTimesHoldingANulByte(): iterable
    {
        yield 'tag 0 alone' => [bin2hex("\xc0" . self::textString("\0"))];
        yield 'tag 0 as a map key' => [bin2hex(self::head(5, 1) . "\xc0" . self::textString("\0") . self::head(0, 1))];
    }

    /**
     * createFromFormat() raises a ValueError on a NUL byte, which is an Error: the "=== false" guard never sees it
     * and neither does a caller guarding the parse with InvalidArgumentException.
     */
    #[Test]
    #[DataProvider('dateTimesHoldingANulByte')]
    public function aDateTimeHoldingANulByteIsRejectedWithinTheErrorContract(string $payload): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid data. Cannot be converted into a datetime object');

        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;
        $object->normalize();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function indefiniteLengthStringsWithAnInvalidChunk(): iterable
    {
        yield 'byte string, integer chunk' => ['5f00ff'];
        yield 'text string, integer chunk' => ['7f00ff'];
        yield 'byte string, indefinite length chunk' => ['5f7f4100ffff'];
    }

    /**
     * RFC 8949 section 3.2.3 requires every chunk of an indefinite length string to be a definite length string of
     * the same major type. Rejecting one is a parse error, not the missing extension RuntimeException stands for.
     */
    #[Test]
    #[DataProvider('indefiniteLengthStringsWithAnInvalidChunk')]
    public function anInvalidChunkIsRejectedWithinTheErrorContract(string $payload): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('can only get');

        $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;
    }
}
