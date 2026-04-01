<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\OtherObject\SinglePrecisionFloatObject;
use const INF;
use const NAN;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class SinglePrecisionFloatTest extends CBORTestCase
{
    #[Test]
    public function aSinglePrecisionObjectCanBeCreatedFromData(): void
    {
        $obj = SinglePrecisionFloatObject::create(hex2bin('47c35000'));
        static::assertEqualsWithDelta(100000.0, $obj->normalize(), 0.01);
        static::assertSame(hex2bin('fa47c35000'), $obj->__toString());
    }

    #[Test]
    public function aSinglePrecisionObjectCanBeCreatedFromFloat(): void
    {
        $obj = SinglePrecisionFloatObject::createFromFloat(100000.0);
        static::assertEqualsWithDelta(100000.0, $obj->normalize(), 0.01);
        static::assertSame(hex2bin('fa47c35000'), $obj->__toString());
    }

    #[Test]
    public function aSinglePrecisionObjectCanHandleInfinity(): void
    {
        $obj = SinglePrecisionFloatObject::createFromFloat(INF);
        static::assertInfinite($obj->normalize());
        static::assertGreaterThan(0, $obj->normalize());
        static::assertSame(hex2bin('fa7f800000'), $obj->__toString());
    }

    #[Test]
    public function aSinglePrecisionObjectCanHandleNegativeInfinity(): void
    {
        $obj = SinglePrecisionFloatObject::createFromFloat(-INF);
        static::assertInfinite($obj->normalize());
        static::assertLessThan(0, $obj->normalize());
        static::assertSame(hex2bin('faff800000'), $obj->__toString());
    }

    #[Test]
    public function aSinglePrecisionObjectCanHandleNaN(): void
    {
        $obj = SinglePrecisionFloatObject::createFromFloat(NAN);
        static::assertNan($obj->normalize());
        static::assertSame(hex2bin('fa7fc00000'), $obj->__toString());
    }

    #[Test]
    public function aSinglePrecisionObjectCanHandleNegativeNumbers(): void
    {
        $obj = SinglePrecisionFloatObject::createFromFloat(-4.0);
        static::assertEqualsWithDelta(-4.0, $obj->normalize(), 0.0001);
    }

    #[Test]
    public function aSinglePrecisionObjectCanHandleZero(): void
    {
        $obj = SinglePrecisionFloatObject::createFromFloat(0.0);
        static::assertSame(0.0, $obj->normalize());
    }
}
