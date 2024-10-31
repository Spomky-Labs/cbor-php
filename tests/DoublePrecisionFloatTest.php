<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\OtherObject\DoublePrecisionFloatObject;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class DoublePrecisionFloatTest extends CBORTestCase
{
    #[Test]
    public function aDoublePrecisionObjectCanBeCreatedFromData(): void
    {
        $obj = DoublePrecisionFloatObject::create(hex2bin('3fd5555555555555'));
        static::assertSame(1 / 3, $obj->normalize());
        static::assertSame(hex2bin('fb3fd5555555555555'), $obj->__toString());
    }

    #[Test]
    public function aDoublePrecisionObjectCanBeCreatedFromFloat(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(1 / 3);
        static::assertSame(1 / 3, $obj->normalize());
        static::assertSame(hex2bin('fb3fd5555555555555'), $obj->__toString());
    }
}
