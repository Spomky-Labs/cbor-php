<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\ByteStringObject;
use CBOR\IndefiniteLengthMapObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class MapObjectTest extends CBORTestCase
{
    #[Test]
    public function aMapActsAsAnArray(): void
    {
        $object1 = MapObject::create()
            ->add(UnsignedIntegerObject::create(10), TextStringObject::create('Hello'))
            ->add(NegativeIntegerObject::create(-150), TextStringObject::create('World'))
            ->add(ByteStringObject::create('AZERTY'), UnsignedIntegerObject::create(1))
            ->add(TextStringObject::create('Test'), UnsignedIntegerObject::create(2))
            ->set(MapItem::create(TextStringObject::create('Test'), UnsignedIntegerObject::create(3)))
            ->remove(3)
        ;

        $object2 = MapObject::create();
        $object2[UnsignedIntegerObject::create(10)] = TextStringObject::create('Hello');
        $object2[NegativeIntegerObject::create(-150)] = TextStringObject::create('World');
        $object2[ByteStringObject::create('AZERTY')] = UnsignedIntegerObject::create(1);
        $object2[TextStringObject::create('Test')] = UnsignedIntegerObject::create(2);
        $object2[TextStringObject::create('Test')] = UnsignedIntegerObject::create(3);
        unset($object2[3]);

        static::assertCount(4, $object1);
        static::assertCount(4, $object2);
        static::assertSame([
            10 => 'Hello',
            -150 => 'World',
            'AZERTY' => '1',
            'Test' => '3',
        ], $object2->normalize());
        static::assertSame($object1->normalize(), $object2->normalize());
        static::assertSame((string) $object1, (string) $object2);
        static::assertTrue(isset($object2[10]));
        static::assertTrue(isset($object2[-150]));
        static::assertTrue(isset($object2['AZERTY']));
        static::assertTrue(isset($object2['Test']));
        static::assertSame($object2[10]->normalize(), 'Hello');
        static::assertSame($object2[-150]->normalize(), 'World');
        static::assertSame($object2['AZERTY']->normalize(), '1');
        static::assertSame($object2['Test']->normalize(), '3');
    }

    #[Test]
    public function anIndefiniteLengthMapActsAsAnArray(): void
    {
        $object1 = IndefiniteLengthMapObject::create()
            ->add(UnsignedIntegerObject::create(10), TextStringObject::create('Hello'))
            ->add(NegativeIntegerObject::create(-150), TextStringObject::create('World'))
            ->add(ByteStringObject::create('AZERTY'), UnsignedIntegerObject::create(1))
            ->add(TextStringObject::create('Test'), UnsignedIntegerObject::create(2))
            ->set(MapItem::create(TextStringObject::create('Test'), UnsignedIntegerObject::create(3)))
            ->remove(3)
        ;

        $object2 = IndefiniteLengthMapObject::create();
        $object2[UnsignedIntegerObject::create(10)] = TextStringObject::create('Hello');
        $object2[NegativeIntegerObject::create(-150)] = TextStringObject::create('World');
        $object2[ByteStringObject::create('AZERTY')] = UnsignedIntegerObject::create(1);
        $object2[TextStringObject::create('Test')] = UnsignedIntegerObject::create(2);
        $object2[TextStringObject::create('Test')] = UnsignedIntegerObject::create(3);
        unset($object2[3]);

        static::assertCount(4, $object1);
        static::assertCount(4, $object2);
        static::assertSame([
            10 => 'Hello',
            -150 => 'World',
            'AZERTY' => '1',
            'Test' => '3',
        ], $object2->normalize());
        static::assertSame($object1->normalize(), $object2->normalize());
        static::assertSame((string) $object1, (string) $object2);
        static::assertTrue(isset($object2[10]));
        static::assertTrue(isset($object2[-150]));
        static::assertTrue(isset($object2['AZERTY']));
        static::assertTrue(isset($object2['Test']));
        static::assertSame($object2[10]->normalize(), 'Hello');
        static::assertSame($object2[-150]->normalize(), 'World');
        static::assertSame($object2['AZERTY']->normalize(), '1');
        static::assertSame($object2['Test']->normalize(), '3');
    }
}
