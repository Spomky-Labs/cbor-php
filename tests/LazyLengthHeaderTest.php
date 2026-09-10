<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * A container computes its length head only when that head is observed, so every mutation has to invalidate it.
 *
 * The head used to be recomputed on each add(), set() and remove(). Deferring it is only safe if nothing can read a
 * head that a later mutation has made wrong, which is what these tests pin down: the two observers are __toString()
 * and getAdditionalInformation(), and both have to see the current item count.
 *
 * @internal
 */
final class LazyLengthHeaderTest extends CBORTestCase
{
    #[DataProvider('getListSize')]
    #[Test]
    public function aListReportsItsHeadAfterEveryAdd(int $size, int $expectedAdditionalInformation): void
    {
        $list = ListObject::create();
        for ($i = 0; $i < $size; ++$i) {
            $list->add(UnsignedIntegerObject::create(1));
        }

        static::assertSame($expectedAdditionalInformation, $list->getAdditionalInformation());
        static::assertCount($size, $list);
    }

    /**
     * The head widens at 24 items, then at 256: the boundaries are where a stale head would show.
     */
    public static function getListSize(): Iterator
    {
        yield 'empty' => [0, 0];
        yield 'one' => [1, 1];
        yield 'last inline count' => [23, 23];
        yield 'first one-byte count' => [24, 24];
        yield 'last one-byte count' => [255, 24];
        yield 'first two-byte count' => [256, 25];
    }

    #[Test]
    public function aListHeadShrinksBackWhenItemsAreRemoved(): void
    {
        $list = ListObject::create();
        for ($i = 0; $i < 24; ++$i) {
            $list->add(UnsignedIntegerObject::create(1));
        }
        static::assertSame(24, $list->getAdditionalInformation());

        $list->remove(0);

        static::assertSame(23, $list->getAdditionalInformation());
        static::assertCount(23, $list);
        static::assertStringStartsWith('97', bin2hex((string) $list));
    }

    /**
     * set() replaces an item, so the count -- and the head with it -- has to stay put.
     */
    #[Test]
    public function replacingAListItemLeavesTheHeadAlone(): void
    {
        $list = ListObject::create();
        for ($i = 0; $i < 24; ++$i) {
            $list->add(UnsignedIntegerObject::create(1));
        }

        $list->set(0, TextStringObject::create('replaced'));

        static::assertSame(24, $list->getAdditionalInformation());
        static::assertCount(24, $list);
    }

    #[Test]
    public function aMapReportsItsHeadAfterEveryAdd(): void
    {
        $map = MapObject::create();
        for ($i = 0; $i < 24; ++$i) {
            $map->add(UnsignedIntegerObject::create($i), UnsignedIntegerObject::create(1));
        }

        static::assertSame(24, $map->getAdditionalInformation());
        static::assertStringStartsWith('b818', bin2hex((string) $map));
    }

    /**
     * set() on a map may introduce a key rather than replace one, and then the count does move.
     */
    #[Test]
    public function aMapHeadFollowsAKeyAddedThroughSet(): void
    {
        $map = MapObject::create();
        for ($i = 0; $i < 23; ++$i) {
            $map->add(UnsignedIntegerObject::create($i), UnsignedIntegerObject::create(1));
        }
        static::assertSame(23, $map->getAdditionalInformation());

        $map->set(MapItem::create(UnsignedIntegerObject::create(23), UnsignedIntegerObject::create(1)));

        static::assertSame(24, $map->getAdditionalInformation());
        static::assertCount(24, $map);
    }

    #[Test]
    public function aMapHeadShrinksBackWhenAKeyIsRemoved(): void
    {
        $map = MapObject::create();
        for ($i = 0; $i < 24; ++$i) {
            $map->add(UnsignedIntegerObject::create($i), UnsignedIntegerObject::create(1));
        }
        static::assertSame(24, $map->getAdditionalInformation());

        $map->remove(0);

        static::assertSame(23, $map->getAdditionalInformation());
        static::assertStringStartsWith('b7', bin2hex((string) $map));
    }

    /**
     * Reading the head must not freeze it: a mutation after a read has to invalidate it again.
     */
    #[Test]
    public function theHeadIsInvalidatedAgainAfterItHasBeenRead(): void
    {
        $list = ListObject::create();
        for ($i = 0; $i < 23; ++$i) {
            $list->add(UnsignedIntegerObject::create(1));
        }

        static::assertSame(23, $list->getAdditionalInformation());
        static::assertStringStartsWith('97', bin2hex((string) $list));

        $list->add(UnsignedIntegerObject::create(1));

        static::assertSame(24, $list->getAdditionalInformation());
        static::assertStringStartsWith('9818', bin2hex((string) $list));
    }
}
