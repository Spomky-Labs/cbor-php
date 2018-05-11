<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Type;

use CBOR\ByteStringObject;
use CBOR\StringStream;

/**
 * @covers \CBOR\ByteStringObject
 */
final class ByteStringObjectTest extends BaseTestCase
{
    /**
     * @test
     */
    public function aByteStringObjectCanBeCreated()
    {
        $object = ByteStringObject::create('Hello');

        self::assertEquals('Hello', $object->getNormalizedData());

        $normalized = (string)$object;
        $stream = new StringStream($normalized);
        $decoded = $this->getDecoder()->decode($stream);

        self::assertEquals('Hello', $decoded->getNormalizedData());
    }
}
