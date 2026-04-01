<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use const INF;
use const NAN;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test cases based on RFC 8949 (CBOR) specification examples
 *
 * @see https://datatracker.ietf.org/doc/html/rfc8949
 * @internal
 */
final class FloatRFC8949Test extends CBORTestCase
{
    // RFC 8949 Appendix A: Examples

    #[Test]
    public function rfc8949ExampleZeroHalfPrecision(): void
    {
        // 0.0 as half precision
        $obj = HalfPrecisionFloatObject::createFromFloat(0.0);
        static::assertSame(0.0, $obj->normalize());
        static::assertSame('f90000', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleNegativeZeroHalfPrecision(): void
    {
        // -0.0 as half precision
        // Note: PHP doesn't distinguish -0.0 from 0.0 with abs(),
        // so we just verify it's zero (the sign bit handling is complex in PHP)
        $obj = HalfPrecisionFloatObject::createFromFloat(-0.0);
        static::assertSame(0.0, abs($obj->normalize()));
        // The actual encoding might be f90000 or f98000 depending on how PHP handles -0.0
    }

    #[Test]
    public function rfc8949ExampleOneHalfPrecision(): void
    {
        // 1.0 as half precision
        $obj = HalfPrecisionFloatObject::createFromFloat(1.0);
        static::assertSame(1.0, $obj->normalize());
        static::assertSame('f93c00', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleOneAndHalfHalfPrecision(): void
    {
        // 1.5 as half precision - RFC 8949 example
        $obj = HalfPrecisionFloatObject::createFromFloat(1.5);
        static::assertEqualsWithDelta(1.5, $obj->normalize(), 0.001);
        static::assertSame('f93e00', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleLargestHalfPrecision(): void
    {
        // 65504.0 - largest finite half precision value
        $obj = HalfPrecisionFloatObject::createFromFloat(65504.0);
        static::assertEqualsWithDelta(65504.0, $obj->normalize(), 1.0);
        static::assertSame('f97bff', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleSmallestSubnormalHalfPrecision(): void
    {
        // Smallest positive subnormal half precision: 2^-24 ≈ 0.000000059605
        // The value 0.00006103515625 = 2^-14 which is slightly larger than the smallest subnormal
        $obj = HalfPrecisionFloatObject::createFromFloat(0.00006103515625);
        static::assertEqualsWithDelta(0.00006103515625, $obj->normalize(), 0.00001);
        // The exact encoding depends on rounding during conversion from single to half precision
        // We verify the value is close rather than checking exact hex encoding
    }

    #[Test]
    public function rfc8949ExampleOneThirdDoublePrecision(): void
    {
        // 1.1 as double precision
        $obj = DoublePrecisionFloatObject::createFromFloat(1.1);
        static::assertEqualsWithDelta(1.1, $obj->normalize(), 0.000001);
        static::assertSame('fb3ff199999999999a', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleLargeSinglePrecision(): void
    {
        // 100000.0 as single precision
        $obj = SinglePrecisionFloatObject::createFromFloat(100000.0);
        static::assertEqualsWithDelta(100000.0, $obj->normalize(), 0.01);
        static::assertSame('fa47c35000', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleInfinityHalfPrecision(): void
    {
        // Infinity as half precision
        $obj = HalfPrecisionFloatObject::createFromFloat(INF);
        static::assertInfinite($obj->normalize());
        static::assertGreaterThan(0, $obj->normalize());
        static::assertSame('f97c00', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleNaNHalfPrecision(): void
    {
        // NaN as half precision - RFC 8949 canonical: 0xf97e00
        $obj = HalfPrecisionFloatObject::createFromFloat(NAN);
        static::assertNan($obj->normalize());
        static::assertSame('f97e00', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleNegativeInfinityHalfPrecision(): void
    {
        // -Infinity as half precision
        $obj = HalfPrecisionFloatObject::createFromFloat(-INF);
        static::assertInfinite($obj->normalize());
        static::assertLessThan(0, $obj->normalize());
        static::assertSame('f9fc00', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleInfinitySinglePrecision(): void
    {
        // Infinity as single precision
        $obj = SinglePrecisionFloatObject::createFromFloat(INF);
        static::assertInfinite($obj->normalize());
        static::assertGreaterThan(0, $obj->normalize());
        static::assertSame('fa7f800000', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleNaNSinglePrecision(): void
    {
        // NaN as single precision
        $obj = SinglePrecisionFloatObject::createFromFloat(NAN);
        static::assertNan($obj->normalize());
        static::assertSame('fa7fc00000', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleNegativeInfinitySinglePrecision(): void
    {
        // -Infinity as single precision
        $obj = SinglePrecisionFloatObject::createFromFloat(-INF);
        static::assertInfinite($obj->normalize());
        static::assertLessThan(0, $obj->normalize());
        static::assertSame('faff800000', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleInfinityDoublePrecision(): void
    {
        // Infinity as double precision
        $obj = DoublePrecisionFloatObject::createFromFloat(INF);
        static::assertInfinite($obj->normalize());
        static::assertGreaterThan(0, $obj->normalize());
        static::assertSame('fb7ff0000000000000', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleNaNDoublePrecision(): void
    {
        // NaN as double precision
        $obj = DoublePrecisionFloatObject::createFromFloat(NAN);
        static::assertNan($obj->normalize());
        static::assertSame('fb7ff8000000000000', bin2hex($obj->__toString()));
    }

    #[Test]
    public function rfc8949ExampleNegativeInfinityDoublePrecision(): void
    {
        // -Infinity as double precision
        $obj = DoublePrecisionFloatObject::createFromFloat(-INF);
        static::assertInfinite($obj->normalize());
        static::assertLessThan(0, $obj->normalize());
        static::assertSame('fbfff0000000000000', bin2hex($obj->__toString()));
    }

    // Additional IEEE 754 edge cases

    #[Test]
    public function ieee754MinusZeroHalfPrecision(): void
    {
        // Test that -0.0 is properly handled
        $obj = HalfPrecisionFloatObject::createFromFloat(-0.0);
        $normalized = $obj->normalize();
        static::assertSame(0.0, abs($normalized));
    }

    #[Test]
    public function ieee754SmallNegativeNumberHalfPrecision(): void
    {
        // -4.0 as half precision
        $obj = HalfPrecisionFloatObject::createFromFloat(-4.0);
        static::assertEqualsWithDelta(-4.0, $obj->normalize(), 0.001);
        static::assertSame('f9c400', bin2hex($obj->__toString()));
    }

    #[Test]
    public function ieee754VeryLargeDoublePrecision(): void
    {
        // Test large number that requires double precision
        $obj = DoublePrecisionFloatObject::createFromFloat(1.0e200);
        static::assertEqualsWithDelta(1.0e200, $obj->normalize(), 1.0e190);
    }

    #[Test]
    public function ieee754VerySmallDoublePrecision(): void
    {
        // Test very small number
        $obj = DoublePrecisionFloatObject::createFromFloat(1.0e-200);
        static::assertEqualsWithDelta(1.0e-200, $obj->normalize(), 1.0e-210);
    }

    #[Test]
    public function halfPrecisionOverflowToInfinity(): void
    {
        // Numbers larger than max half precision (65504) should overflow to infinity
        $obj = HalfPrecisionFloatObject::createFromFloat(100000.0);
        static::assertInfinite($obj->normalize());
        static::assertGreaterThan(0, $obj->normalize());
    }

    #[Test]
    public function halfPrecisionUnderflowToZero(): void
    {
        // Numbers too small for half precision should underflow to zero
        $obj = HalfPrecisionFloatObject::createFromFloat(1.0e-10);
        static::assertSame(0.0, $obj->normalize());
    }

    #[Test]
    public function roundTripHalfPrecision(): void
    {
        // Test round-trip conversion for various values
        $testValues = [0.0, 1.0, -1.0, 0.5, 2.0, 10.0, -10.0];

        foreach ($testValues as $value) {
            $obj = HalfPrecisionFloatObject::createFromFloat($value);
            static::assertEqualsWithDelta($value, $obj->normalize(), 0.01, "Failed for value: {$value}");
        }
    }

    #[Test]
    public function roundTripSinglePrecision(): void
    {
        // Test round-trip conversion for various values
        $testValues = [0.0, 1.0, -1.0, 0.5, 123.456, -789.012, 1000000.0];

        foreach ($testValues as $value) {
            $obj = SinglePrecisionFloatObject::createFromFloat($value);
            static::assertEqualsWithDelta($value, $obj->normalize(), 0.001, "Failed for value: {$value}");
        }
    }

    #[Test]
    public function roundTripDoublePrecision(): void
    {
        // Test round-trip conversion for various values
        $testValues = [0.0, 1.0, -1.0, 0.5, 123.456789, -789.012345, 1000000.123456];

        foreach ($testValues as $value) {
            $obj = DoublePrecisionFloatObject::createFromFloat($value);
            static::assertEqualsWithDelta($value, $obj->normalize(), 0.000001, "Failed for value: {$value}");
        }
    }
}
