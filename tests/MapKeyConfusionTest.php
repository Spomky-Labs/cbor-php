<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\StringStream;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use function hex2bin;
use InvalidArgumentException;
use function iterator_to_array;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use function sprintf;

/**
 * Regression tests for GHSA-388j-mw2g-rx5f.
 *
 * Map entries were stored in a native PHP array keyed by the normalized key, which caused two problems. A key that
 * normalizes to an array -- a CBOR list or map, which RFC 8949 permits as a key -- reached the offset as an array
 * and raised a TypeError, so a three byte document crashed the decoder. And because PHP casts numeric strings to
 * integers when they are used as an offset, structurally distinct keys silently overwrote one another: the integer
 * 1, the text string "1" and the byte string h'31' all normalize to the string '1'.
 *
 * @internal
 */
final class MapKeyConfusionTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function keysThatUsedToCrashTheDecoder(): iterable
    {
        yield 'definite length map, list as a key' => ['a18000'];
        yield 'definite length map, map as a key' => ['a1a000'];
        yield 'definite length map, non empty list as a key' => ['a181000000'];
        yield 'indefinite length map, list as a key' => ['bf8000ff'];
        yield 'indefinite length map, map as a key' => ['bfa000ff'];
    }

    /**
     * A TypeError is outside the error contract of this library, and the documents above are valid CBOR: RFC 8949
     * section 3.1 allows any data item as a map key. They have to be turned down, not crash the parser.
     */
    #[Test]
    #[DataProvider('keysThatUsedToCrashTheDecoder')]
    public function aNonScalarKeyIsRejectedInsteadOfCrashing(string $payload): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A map key shall normalize to an integer or a string');

        $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function keysThatUsedToCollide(): iterable
    {
        yield 'integer 1 and text string "1"' => ['a201614161316142', '0', '3'];
        yield 'byte string h\'31\' and text string "1"' => ['a24131614161316142', '2', '3'];
        yield 'integer 1 and byte string h\'31\'' => ['a201614141316142', '0', '2'];
        yield 'indefinite length map, integer 1 and text string "1"' => ['bf01614161316142ff', '0', '3'];
    }

    #[Test]
    #[DataProvider('keysThatUsedToCollide')]
    public function twoDistinctKeysCollidingOnOneOffsetAreRejected(
        string $payload,
        string $firstMajorType,
        string $secondMajorType
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'A key of major type %s and a key of major type %s both resolve to the offset "1".',
            $firstMajorType,
            $secondMajorType
        ));

        $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function keysThatDoNotNormalizeToAScalar(): iterable
    {
        yield 'half precision float 1.0, which used to collide with the integer 1' => ['a2016141f93c006142'];
        yield 'boolean true' => ['a1f500'];
        yield 'boolean false' => ['a1f400'];
        yield 'null' => ['a1f600'];
    }

    #[Test]
    #[DataProvider('keysThatDoNotNormalizeToAScalar')]
    public function aKeyThatDoesNotNormalizeToAScalarIsRejected(string $payload): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A map key shall normalize to an integer or a string');

        $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function duplicateKeys(): iterable
    {
        yield 'definite length map' => ['a2016141016142'];
        yield 'indefinite length map' => ['bf016141016142ff'];
        yield 'text string keys' => ['a2616101616102'];
    }

    /**
     * RFC 8949 section 5.6 tells a strict decoder to reject a map with duplicate keys. The previous behaviour was to
     * keep the last occurrence and report a count that disagreed with the wire.
     */
    #[Test]
    #[DataProvider('duplicateKeys')]
    public function aDuplicateKeyIsRejected(string $payload): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is defined more than once in the map');

        $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;
    }

    /**
     * @return iterable<string, array{string, int, array<int|string, mixed>}>
     */
    public static function legitimateMaps(): iterable
    {
        yield 'integer keys' => [
            'a201614102614 2', 2, [
                1 => 'A',
                2 => 'B',
            ]];
        yield 'text string keys' => [
            'a2616101616202', 2, [
                'a' => '1',
                'b' => '2',
            ]];
        yield 'empty map' => ['a0', 0, []];
        yield 'indefinite length, integer keys' => [
            'bf016141026142ff', 2, [
                1 => 'A',
                2 => 'B',
            ]];
        // A COSE_Key shaped map: negative integer keys next to positive ones, which is the layout that made
        // the collision matter for the consumers of this library.
        yield 'a COSE shaped map' => [
            'a4010203262001214101', 4, [
                1 => '2',
                3 => '-7',
                -1 => '1',
                -2 => "\x01",
            ]];
    }

    #[Test]
    #[DataProvider('legitimateMaps')]
    public function legitimateMapsAreStillDecoded(string $payload, int $expectedCount, array $expected): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin(str_replace(' ', '', $payload))))
        ;

        static::assertCount($expectedCount, iterator_to_array($object->getIterator()));
        static::assertSame($expected, $object->normalize());
    }

    /**
     * The count used to disagree with the wire whenever two keys collided. It cannot any more, because a colliding
     * document no longer decodes at all.
     */
    #[Test]
    public function theCountAlwaysMatchesTheNumberOfEntriesOnTheWire(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('a3016141026142036143')))
        ;

        static::assertCount(3, $object);
        static::assertCount(3, $object->normalize());
    }

    /**
     * ArrayAccess has to keep working: replacing the value of a key that is already present is a legitimate use of
     * the setter, and is not what the duplicate detection is aimed at.
     */
    #[Test]
    public function replacingTheValueOfAnExistingKeyIsStillAllowed(): void
    {
        $map = MapObject::create();
        $map->add(UnsignedIntegerObject::create(1), TextStringObject::create('A'));
        $map->set(MapItem::create(UnsignedIntegerObject::create(1), TextStringObject::create('B')));

        static::assertCount(1, $map);
        static::assertSame([
            '1' => 'B',
        ], $map->normalize());
    }

    #[Test]
    public function addingTheSameKeyTwiceIsRejected(): void
    {
        $map = MapObject::create();
        $map->add(UnsignedIntegerObject::create(1), TextStringObject::create('A'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is defined more than once in the map');

        $map->add(UnsignedIntegerObject::create(1), TextStringObject::create('B'));
    }

    #[Test]
    public function addingANonScalarKeyIsRejected(): void
    {
        $map = MapObject::create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A map key shall normalize to an integer or a string');

        $map->add(ListObject::create(), TextStringObject::create('A'));
    }
}
