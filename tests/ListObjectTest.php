<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test;

use CBOR\IndefiniteLengthListObject;
use CBOR\ListObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;

/**
 * @internal
 */
final class ListObjectTest extends BaseTestCase
{
    /**
     * @test
     */
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
        static::assertEquals(['Hello', 'World', 3], $object2->normalize());
        static::assertEquals($object1->normalize(), $object2->normalize());
        static::assertEquals((string) $object1, (string) $object2);
        static::assertTrue(isset($object2[0]));
        static::assertEquals(TextStringObject::create('World'), $object2[1]);
    }

    /**
     * @test
     */
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
        static::assertEquals(['Hello', 'World', 3], $object2->normalize());
        static::assertEquals($object1->normalize(), $object2->normalize());
        static::assertEquals((string) $object1, (string) $object2);
        static::assertTrue(isset($object2[0]));
        static::assertEquals(TextStringObject::create('World'), $object2[1]);
    }
}
