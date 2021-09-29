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

use CBOR\OtherObject\DoublePrecisionFloatObject;

/**
 * @internal
 */
final class DoublePrecisionFloatTest extends BaseTestCase
{
    /**
     * @test
     */
    public function aDoublePrecisionObjectCanBeCreated(): void
    {
        $obj = DoublePrecisionFloatObject::create(hex2bin('3fd5555555555555'));
        static::assertEquals(1 / 3, $obj->getNormalizedData());
    }
}
