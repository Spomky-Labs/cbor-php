<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\IndefiniteLengthListObject;
use CBOR\ListObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class ListObjectTest extends CBORTestCase
{
    #[Test]
    public function aListActsAsAnArray(): void
    {
        $object1 = ListObject::create()
            ->add(TextStringObject::create('Hello'))
            ->add(TextStringObject::create('World'))
            ->add(UnsignedIntegerObject::create(1))
            ->add(UnsignedIntegerObject::create(2))
            ->set(2, UnsignedIntegerObject::create(3))
            ->remove(3)
        ;

        $object2 = ListObject::create();
        $object2[] = TextStringObject::create('Hello');
        $object2[] = TextStringObject::create('World');
        $object2[] = UnsignedIntegerObject::create(1);
        $object2[] = UnsignedIntegerObject::create(2);
        $object2[2] = UnsignedIntegerObject::create(3);
        unset($object2[3]);

        static::assertCount(3, $object1);
        static::assertCount(3, $object2);
        static::assertSame(['Hello', 'World', '3'], $object2->normalize());
        static::assertSame($object1->normalize(), $object2->normalize());
        static::assertSame((string) $object1, (string) $object2);
        static::assertArrayHasKey(0, $object2);
        static::assertArrayHasKey(1, $object2);
        static::assertArrayHasKey(2, $object2);
        static::assertSame($object2[0]->normalize(), 'Hello');
        static::assertSame($object2[1]->normalize(), 'World');
        static::assertSame($object2[2]->normalize(), '3');
    }

    #[Test]
    public function anIndefiniteLengthListActsAsAnArray(): void
    {
        $object1 = IndefiniteLengthListObject::create()
            ->add(TextStringObject::create('Hello'))
            ->add(TextStringObject::create('World'))
            ->add(UnsignedIntegerObject::create(1))
            ->add(UnsignedIntegerObject::create(2))
            ->set(2, UnsignedIntegerObject::create(3))
            ->remove(3)
        ;

        $object2 = IndefiniteLengthListObject::create();
        $object2[] = TextStringObject::create('Hello');
        $object2[] = TextStringObject::create('World');
        $object2[] = UnsignedIntegerObject::create(1);
        $object2[] = UnsignedIntegerObject::create(2);
        $object2[2] = UnsignedIntegerObject::create(3);
        unset($object2[3]);

        static::assertCount(3, $object1);
        static::assertCount(3, $object2);
        static::assertSame(['Hello', 'World', '3'], $object2->normalize());
        static::assertSame($object1->normalize(), $object2->normalize());
        static::assertSame((string) $object1, (string) $object2);
        static::assertArrayHasKey(0, $object2);
        static::assertArrayHasKey(1, $object2);
        static::assertArrayHasKey(2, $object2);
        static::assertSame($object2[0]->normalize(), 'Hello');
        static::assertSame($object2[1]->normalize(), 'World');
        static::assertSame($object2[2]->normalize(), '3');
    }
}
