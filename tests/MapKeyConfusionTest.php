<?php

declare(strict_types=1);

namespace CBOR\Test;

use function bin2hex;
use CBOR\IndefiniteLengthMapObject;
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
 * Both used to be turned down at decode time. They are valid CBOR, so the map is now kept as it was read and only
 * refuses to become a PHP array; the keys in question are reached by iterating, not through a PHP offset.
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
        yield 'definite length map, non empty list as a key' => ['a1810000'];
        yield 'indefinite length map, list as a key' => ['bf8000ff'];
        yield 'indefinite length map, map as a key' => ['bfa000ff'];
    }

    /**
     * A TypeError is outside the error contract of this library, and the documents above are valid CBOR: RFC 8949
     * section 3.1 allows any data item as a map key. They decode, iterate and write back as they were read; what
     * they cannot do is become a PHP array, whose offsets are integers and strings.
     */
    #[Test]
    #[DataProvider('keysThatUsedToCrashTheDecoder')]
    public function aNonScalarKeyIsKeptAndOnlyRefusedByNormalize(string $payload): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertTrue($object instanceof MapObject || $object instanceof IndefiniteLengthMapObject);
        static::assertCount(1, $object);
        static::assertSame($payload, bin2hex((string) $object));
        static::assertFalse($object->has(0));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A map key shall normalize to an integer or a string, got "array".');
        $object->normalize();
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

    /**
     * The two keys are distinct in the generic data model, so the map decodes with both of them and writes back
     * unchanged. The offset they meet on is ambiguous: neither is reached through it, and normalizing the map, which
     * would have to pick one, is refused instead.
     */
    #[Test]
    #[DataProvider('keysThatUsedToCollide')]
    public function twoDistinctKeysCollidingOnOneOffsetAreBothKeptAndNeitherIsAddressable(
        string $payload,
        string $firstMajorType,
        string $secondMajorType
    ): void {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertCount(2, $object);
        static::assertSame($payload, bin2hex((string) $object));
        static::assertSame(['A', 'B'], array_map(
            static fn (MapItem $item): mixed => $item->getValue()
                ->normalize(),
            iterator_to_array($object->getIterator(), false)
        ));
        static::assertFalse($object->has(1));
        static::assertFalse($object->has('1'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'A key of major type %s and a key of major type %s both resolve to the offset "1".',
            $firstMajorType,
            $secondMajorType
        ));
        $object->normalize();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function keysThatDoNotNormalizeToAScalar(): iterable
    {
        yield 'half precision float 1.0, which used to collide with the integer 1' => ['a2016141f93c006142', 'float'];
        yield 'negative zero (cbor-wg-good-83)' => ['a1f9800080', 'float'];
        yield 'boolean true' => ['a1f500', 'bool'];
        yield 'boolean false' => ['a1f400', 'bool'];
        yield 'null' => ['a1f600', 'null'];
        yield 'undefined' => ['a1f700', 'null'];
        yield 'a tag that expands to an object' => ['a1c10000', 'DateTimeImmutable'];
    }

    #[Test]
    #[DataProvider('keysThatDoNotNormalizeToAScalar')]
    public function aKeyThatDoesNotNormalizeToAScalarIsKeptAndOnlyRefusedByNormalize(
        string $payload,
        string $type
    ): void {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertSame($payload, bin2hex((string) $object));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'A map key shall normalize to an integer or a string, got "%s".',
            $type
        ));
        $object->normalize();
    }

    /**
     * The "interesting keys" vector of the CBOR working group test suite (cbor-wg-good-84): twenty-six keys, among
     * them the integer 0 next to the text string "0", the empty byte string next to the empty text string, floats,
     * NaN, the three simple values, a bignum, containers and a tag.
     */
    #[Test]
    public function everyKindOfKeyOfTheCborWgVectorIsKept(): void
    {
        $payload = 'b81a808081008081808081810080f580f480f680f7800080613080fb3fb999999999999a8001802080f97c0080f9fc0080'
            . 'f97e0080c2491c000000000000000080a080a1808080a1a08080a1a18080808040804100806080616180c10080';

        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertCount(26, $object);
        static::assertSame($payload, bin2hex((string) $object));
    }

    /**
     * cbor-wg-good-86: a key nested a few hundred maps deep. It is neither normalized nor walked when the map is
     * built, so the document costs what it weighs.
     */
    #[Test]
    public function aDeeplyNestedKeyIsKept(): void
    {
        $depth = 100;
        $payload = str_repeat('a1', $depth) . str_repeat('00', $depth + 1);

        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertSame($payload, bin2hex((string) $object));
    }

    /**
     * Opaque keys are compared on their encoded bytes, so RFC 8949 section 5.6 still holds for them.
     */
    #[Test]
    public function aDuplicateContainerKeyIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key. The key "8100" is defined more than once in the map.');

        $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('a2810000810001')))
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
     * The count used to disagree with the wire whenever two keys collided. It cannot any more, because every key on
     * the wire is kept.
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
    public function addingANonScalarKeyIsAccepted(): void
    {
        $map = MapObject::create();
        $map->add(ListObject::create(), TextStringObject::create('A'));

        static::assertCount(1, $map);
        static::assertSame('a18061' . bin2hex('A'), bin2hex((string) $map));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A map key shall normalize to an integer or a string, got "array".');
        $map->normalize();
    }

    /**
     * The offset stays with the key that owns it when the other one is removed, and the ambiguity goes with it.
     */
    #[Test]
    public function aKeyMeetingAnotherOnItsOffsetLeavesTheMapReachableAgainWhenItGoes(): void
    {
        $map = MapObject::create();
        $map->add(UnsignedIntegerObject::create(1), TextStringObject::create('A'));
        $map->add(TextStringObject::create('1'), TextStringObject::create('B'));

        static::assertCount(2, $map);
        static::assertFalse($map->has(1));

        $map->remove(1);

        static::assertCount(2, $map);
    }
}
