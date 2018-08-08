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

use CBOR\OtherObject\DoublePrecisionFloatObject;

final class DoublePrecisionFloatTest extends BaseTestCase
{
    /**
     * @test
     */
    public function aDoublePrecisionObjectCanBeCreated()
    {
        $obj = DoublePrecisionFloatObject::create(hex2bin('3fd5555555555555'));
        static::assertEquals(1 / 3, $obj->getNormalizedData());
    }
}
