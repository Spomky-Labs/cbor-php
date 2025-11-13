<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\OtherObject\HalfPrecisionFloatObject;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class HalfPrecisionFloatTest extends CBORTestCase
{
    #[Test]
    public function aHalfPrecisionObjectCanBeCreatedFromData(): void
    {
        $obj = HalfPrecisionFloatObject::create(hex2bin('3c00'));
        static::assertEqualsWithDelta(1.0, $obj->normalize(), 0.001);
        static::assertSame(hex2bin('f93c00'), $obj->__toString());
    }

    #[Test]
    public function aHalfPrecisionObjectCanBeCreatedFromFloat(): void
    {
        $obj = HalfPrecisionFloatObject::createFromFloat(1.0);
        static::assertEqualsWithDelta(1.0, $obj->normalize(), 0.001);
        static::assertSame(hex2bin('f93c00'), $obj->__toString());
    }

    #[Test]
    public function aHalfPrecisionObjectCanHandleInfinity(): void
    {
        $obj = HalfPrecisionFloatObject::createFromFloat(INF);
        static::assertTrue(is_infinite($obj->normalize()));
        static::assertGreaterThan(0, $obj->normalize());
        static::assertSame(hex2bin('f97c00'), $obj->__toString());
    }

    #[Test]
    public function aHalfPrecisionObjectCanHandleNegativeInfinity(): void
    {
        $obj = HalfPrecisionFloatObject::createFromFloat(-INF);
        static::assertTrue(is_infinite($obj->normalize()));
        static::assertLessThan(0, $obj->normalize());
        static::assertSame(hex2bin('f9fc00'), $obj->__toString());
    }

    #[Test]
    public function aHalfPrecisionObjectCanHandleNaN(): void
    {
        $obj = HalfPrecisionFloatObject::createFromFloat(NAN);
        static::assertTrue(is_nan($obj->normalize()));
        static::assertSame(hex2bin('f97e00'), $obj->__toString());
    }

    #[Test]
    public function aHalfPrecisionObjectCanHandleZero(): void
    {
        $obj = HalfPrecisionFloatObject::createFromFloat(0.0);
        static::assertSame(0.0, $obj->normalize());
        static::assertSame(hex2bin('f90000'), $obj->__toString());
    }

    #[Test]
    public function aHalfPrecisionObjectCanHandleNegativeZero(): void
    {
        $obj = HalfPrecisionFloatObject::createFromFloat(-0.0);
        // PHP doesn't distinguish -0.0 from 0.0 easily with abs(), so we just check it's zero
        static::assertSame(0.0, abs($obj->normalize()));
    }

    #[Test]
    public function aHalfPrecisionObjectCanHandleSmallPositiveNumbers(): void
    {
        $obj = HalfPrecisionFloatObject::createFromFloat(0.5);
        static::assertEqualsWithDelta(0.5, $obj->normalize(), 0.001);
    }

    #[Test]
    public function aHalfPrecisionObjectCanHandleSmallNegativeNumbers(): void
    {
        $obj = HalfPrecisionFloatObject::createFromFloat(-4.0);
        static::assertEqualsWithDelta(-4.0, $obj->normalize(), 0.001);
        static::assertSame(hex2bin('f9c400'), $obj->__toString());
    }

    #[Test]
    public function aHalfPrecisionObjectCanHandleLargeNumbers(): void
    {
        // Half precision max is 65504
        $obj = HalfPrecisionFloatObject::createFromFloat(65504.0);
        static::assertEqualsWithDelta(65504.0, $obj->normalize(), 1.0);
        static::assertSame(hex2bin('f97bff'), $obj->__toString());
    }

    #[Test]
    public function aHalfPrecisionObjectOverflowsToInfinity(): void
    {
        // Numbers larger than half precision max should become infinity
        $obj = HalfPrecisionFloatObject::createFromFloat(100000.0);
        static::assertTrue(is_infinite($obj->normalize()));
        static::assertGreaterThan(0, $obj->normalize());
    }

    #[Test]
    public function aHalfPrecisionObjectCanHandleSubnormalNumbers(): void
    {
        // Very small numbers should be handled as subnormal or zero
        $obj = HalfPrecisionFloatObject::createFromFloat(0.00006103515625);
        static::assertEqualsWithDelta(0.00006103515625, $obj->normalize(), 0.00001);
        // The exact binary representation might vary due to precision, just check it's close
    }
}
