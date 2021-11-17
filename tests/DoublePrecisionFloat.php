<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\OtherObject\DoublePrecisionFloatObject;

/**
 * @internal
 */
final class DoublePrecisionFloat extends CBORTestCase
{
    /**
     * @test
     */
    public function aDoublePrecisionObjectCanBeCreated(): void
    {
        $obj = DoublePrecisionFloatObject::create(hex2bin('3fd5555555555555'));
        static::assertSame(1 / 3, $obj->getNormalizedData());
    }
}
