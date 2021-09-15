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

namespace CBOR\Test\Type\OtherObject;

use CBOR\CBORObject;
use CBOR\OtherObject\BreakObject;
use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\NullObject;
use CBOR\OtherObject\SimpleObject;
use CBOR\OtherObject\TrueObject;
use CBOR\OtherObject\UndefinedObject;
use CBOR\StringStream;
use CBOR\Test\Type\BaseTestCase;
use function chr;
use InvalidArgumentException;

/**
 * @internal
 */
final class AllTest extends BaseTestCase
{
    /**
     * @test
     */
    public function createValidFalseObject(): void
    {
        $object = FalseObject::create();

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $object->getMajorType());
        static::assertEquals(CBORObject::OBJECT_FALSE, $object->getAdditionalInformation());
        static::assertNull($object->getContent());

        $stream = StringStream::create($object->__toString());
        $decoded = $this->getDecoder()->decode($stream);

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $decoded->getMajorType());
        static::assertEquals(CBORObject::OBJECT_FALSE, $decoded->getAdditionalInformation());
        static::assertNull($decoded->getContent());
    }

    /**
     * @test
     */
    public function createValidTrueObject(): void
    {
        $object = TrueObject::create();

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $object->getMajorType());
        static::assertEquals(CBORObject::OBJECT_TRUE, $object->getAdditionalInformation());

        $stream = StringStream::create($object->__toString());
        $decoded = $this->getDecoder()->decode($stream);

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $decoded->getMajorType());
        static::assertEquals(CBORObject::OBJECT_TRUE, $decoded->getAdditionalInformation());
        static::assertNull($decoded->getContent());
    }

    /**
     * @test
     */
    public function createValidNullObject(): void
    {
        $object = NullObject::create();

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $object->getMajorType());
        static::assertEquals(CBORObject::OBJECT_NULL, $object->getAdditionalInformation());

        $stream = StringStream::create($object->__toString());
        $decoded = $this->getDecoder()->decode($stream);

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $decoded->getMajorType());
        static::assertEquals(CBORObject::OBJECT_NULL, $decoded->getAdditionalInformation());
        static::assertNull($decoded->getContent());
    }

    /**
     * @test
     */
    public function createValidUndefinedObject(): void
    {
        $object = UndefinedObject::create();

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $object->getMajorType());
        static::assertEquals(CBORObject::OBJECT_UNDEFINED, $object->getAdditionalInformation());

        $stream = StringStream::create($object->__toString());
        $decoded = $this->getDecoder()->decode($stream);

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $decoded->getMajorType());
        static::assertEquals(CBORObject::OBJECT_UNDEFINED, $decoded->getAdditionalInformation());
        static::assertNull($decoded->getContent());
    }

    /**
     * @test
     */
    public function createValidBreakObject(): void
    {
        $object = BreakObject::create();

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $object->getMajorType());
        static::assertEquals(CBORObject::OBJECT_BREAK, $object->getAdditionalInformation());
    }

    /**
     * @test
     * @dataProvider getSimpleObjectWithoutContent
     */
    public function createValidSimpleObjectWithoutContent(int $value): void
    {
        $object = SimpleObject::create($value);

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $object->getMajorType());
        static::assertEquals($value, $object->getAdditionalInformation());
        static::assertNull($object->getContent());

        $stream = StringStream::create($object->__toString());
        $decoded = $this->getDecoder()->decode($stream);

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $decoded->getMajorType());
        static::assertEquals($value, $decoded->getAdditionalInformation());
        static::assertNull($decoded->getContent());
    }

    /**
     * @test
     * @dataProvider getSimpleObjectWithContent
     */
    public function createValidSimpleObjectWithContent(int $value): void
    {
        $object = SimpleObject::create($value);

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $object->getMajorType());
        static::assertEquals(CBORObject::OBJECT_SIMPLE_VALUE, $object->getAdditionalInformation());
        static::assertEquals(chr($value), $object->getContent());

        $stream = StringStream::create($object->__toString());
        $decoded = $this->getDecoder()->decode($stream);

        static::assertEquals(CBORObject::MAJOR_TYPE_OTHER_TYPE, $decoded->getMajorType());
        static::assertEquals(CBORObject::OBJECT_SIMPLE_VALUE, $decoded->getAdditionalInformation());
        static::assertEquals(chr($value), $decoded->getContent());
    }

    /**
     * @test
     */
    public function createInvalidSimpleObjectWithContent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid simple value. Content data should not be present.');

        SimpleObject::createFromLoadedData(0, ' ');
    }

    /**
     * @test
     */
    public function createInvalidSimpleObjectOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value is not a valid simple value.');

        SimpleObject::create(256);
    }

    public function getSimpleObjectWithoutContent(): array
    {
        return [
            [0],
            [18],
            [19],
        ];
    }

    public function getSimpleObjectWithContent(): array
    {
        return [
            [32],
            [255],
        ];
    }
}
