<?php

declare(strict_types=1);

namespace CBOR\Test\Type;

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
