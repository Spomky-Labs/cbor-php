<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\ByteStringObject;
use CBOR\IndefiniteLengthListObject;
use CBOR\IndefiniteLengthMapObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\StringStream;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Countable;
use function hex2bin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression tests for https://github.com/Spomky-Labs/cbor-php/issues/152.
 *
 * `MapObject::__construct()` stored the caller's entries as they came, and both `remove()` implementations reindexed
 * the remaining entries with `array_values()`. In both cases the map ended up keyed by list offsets instead of by
 * normalized keys, which defeats the key registry: duplicates went undetected, `has()`/`get()` by key failed, and
 * `add()` reported collisions between keys that were not in the map at all.
 *
 * @internal
 */
final class MapKeyRegistryTest extends CBORTestCase
{
    #[Test]
    public function theConstructorRejectsDuplicateKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key. The key "a" is defined more than once in the map.');

        MapObject::create([
            MapItem::create(TextStringObject::create('a'), UnsignedIntegerObject::create(1)),
            MapItem::create(TextStringObject::create('a'), UnsignedIntegerObject::create(2)),
        ]);
    }

    #[Test]
    public function theConstructorRejectsKeysThatCollideOnTheSameOffset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('both resolve to the offset "1"');

        MapObject::create([
            MapItem::create(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(1)),
            MapItem::create(TextStringObject::create('1'), UnsignedIntegerObject::create(2)),
        ]);
    }

    #[Test]
    public function theConstructorRejectsEntriesThatAreNotMapItems(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A map shall only contain "CBOR\MapItem" objects, got "CBOR\TextStringObject".');

        /** @phpstan-ignore-next-line */
        MapObject::create([TextStringObject::create('a')]);
    }

    #[Test]
    public function theConstructorRegistersTheEntriesUnderTheirKey(): void
    {
        $object = MapObject::create([
            MapItem::create(TextStringObject::create('a'), UnsignedIntegerObject::create(1)),
            MapItem::create(UnsignedIntegerObject::create(10), TextStringObject::create('Hello')),
            MapItem::create(ByteStringObject::create('AZERTY'), UnsignedIntegerObject::create(2)),
        ]);

        static::assertCount(3, $object);
        static::assertTrue($object->has('a'));
        static::assertTrue($object->has(10));
        static::assertTrue($object->has('AZERTY'));
        static::assertSame('1', $object->get('a')->normalize());
        static::assertSame('Hello', $object->get(10)->normalize());
        static::assertSame([
            'a' => '1',
            10 => 'Hello',
            'AZERTY' => '2',
        ], $object->normalize());
    }

    #[Test]
    public function aMapBuiltFromItemsIsDecodedBackToItself(): void
    {
        $object = MapObject::create([
            MapItem::create(TextStringObject::create('a'), UnsignedIntegerObject::create(1)),
            MapItem::create(TextStringObject::create('b'), UnsignedIntegerObject::create(2)),
        ]);

        $decoded = $this->getDecoder()
            ->decode(StringStream::create((string) $object));

        static::assertSame($object->normalize(), $decoded->normalize());
    }

    #[Test]
    public function removingAnEntryKeepsTheOtherEntriesAddressableByKey(): void
    {
        $object = MapObject::create()
            ->add(TextStringObject::create('a'), UnsignedIntegerObject::create(1))
            ->add(TextStringObject::create('b'), UnsignedIntegerObject::create(2))
            ->add(TextStringObject::create('c'), UnsignedIntegerObject::create(3))
            ->remove('a')
        ;

        static::assertCount(2, $object);
        static::assertFalse($object->has('a'));
        static::assertTrue($object->has('b'));
        static::assertSame('2', $object->get('b')->normalize());
        static::assertSame([
            'b' => '2',
            'c' => '3',
        ], $object->normalize());
    }

    #[Test]
    public function removingAnEntryDoesNotFreeTheOffsetsOfTheRemainingKeys(): void
    {
        $object = MapObject::create()
            ->add(TextStringObject::create('a'), UnsignedIntegerObject::create(1))
            ->add(TextStringObject::create('b'), UnsignedIntegerObject::create(2))
            ->remove('a')
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid key. The key "b" is defined more than once in the map.');

        $object->add(TextStringObject::create('b'), UnsignedIntegerObject::create(9));
    }

    #[Test]
    public function removingAnEntryFreesItsOwnOffsetForAnotherMajorType(): void
    {
        $object = MapObject::create()
            ->add(TextStringObject::create('0'), UnsignedIntegerObject::create(1))
            ->add(TextStringObject::create('b'), UnsignedIntegerObject::create(2))
            ->remove('0')
            ->add(UnsignedIntegerObject::create(0), UnsignedIntegerObject::create(9))
        ;

        static::assertCount(2, $object);
        static::assertSame([
            'b' => '2',
            0 => '9',
        ], $object->normalize());
    }

    #[Test]
    public function aMapIsStillEncodedWithTheRightLengthAfterARemoval(): void
    {
        $object = MapObject::create()
            ->add(TextStringObject::create('a'), UnsignedIntegerObject::create(1))
            ->add(TextStringObject::create('b'), UnsignedIntegerObject::create(2))
            ->add(TextStringObject::create('c'), UnsignedIntegerObject::create(3))
            ->remove('b')
        ;

        static::assertSame(hex2bin('a2616101616303'), (string) $object);

        $decoded = $this->getDecoder()
            ->decode(StringStream::create((string) $object));

        static::assertSame([
            'a' => '1',
            'c' => '3',
        ], $decoded->normalize());
    }

    #[Test]
    public function removingAnEntryOfAnIndefiniteLengthMapKeepsTheOtherEntriesAddressableByKey(): void
    {
        $object = IndefiniteLengthMapObject::create()
            ->add(TextStringObject::create('a'), UnsignedIntegerObject::create(1))
            ->add(TextStringObject::create('b'), UnsignedIntegerObject::create(2))
            ->add(TextStringObject::create('c'), UnsignedIntegerObject::create(3))
            ->remove('a')
        ;

        static::assertCount(2, $object);
        static::assertFalse($object->has('a'));
        static::assertTrue($object->has('b'));
        static::assertSame('2', $object->get('b')->normalize());
        static::assertSame(hex2bin('bf616202616303ff'), (string) $object);
    }

    #[Test]
    public function anIndefiniteLengthMapIsCountable(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('bf616101616202ff')));

        static::assertInstanceOf(IndefiniteLengthMapObject::class, $object);
        static::assertInstanceOf(Countable::class, $object);
        static::assertSame(2, $object->count());
    }

    #[Test]
    public function anIndefiniteLengthListIsCountable(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('9f010203ff')));

        static::assertInstanceOf(IndefiniteLengthListObject::class, $object);
        static::assertInstanceOf(Countable::class, $object);
        static::assertSame(3, $object->count());

        static::assertCount(2, IndefiniteLengthListObject::create(
            UnsignedIntegerObject::create(1),
            UnsignedIntegerObject::create(2),
        ));
    }
}
