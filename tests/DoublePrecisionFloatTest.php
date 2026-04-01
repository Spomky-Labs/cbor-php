<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\OtherObject\DoublePrecisionFloatObject;
use const INF;
use function is_float;
use function is_int;
use const M_E;
use const M_PI;
use const NAN;
use const PHP_FLOAT_MAX;
use const PHP_FLOAT_MIN;
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

    #[Test]
    public function aDoublePrecisionObjectCanHandleZero(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(0.0);
        static::assertSame(0.0, $obj->normalize());
        static::assertSame(hex2bin('fb0000000000000000'), $obj->__toString());
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleOne(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(1.0);
        static::assertSame(1.0, $obj->normalize());
        static::assertSame(hex2bin('fb3ff0000000000000'), $obj->__toString());
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleMinusOne(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(-1.0);
        static::assertSame(-1.0, $obj->normalize());
        static::assertSame(hex2bin('fbbff0000000000000'), $obj->__toString());
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleInfinity(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(INF);
        static::assertInfinite($obj->normalize());
        static::assertGreaterThan(0, $obj->normalize());
        static::assertSame(hex2bin('fb7ff0000000000000'), $obj->__toString());
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleNegativeInfinity(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(-INF);
        static::assertInfinite($obj->normalize());
        static::assertLessThan(0, $obj->normalize());
        static::assertSame(hex2bin('fbfff0000000000000'), $obj->__toString());
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleNaN(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(NAN);
        static::assertNan($obj->normalize());
        static::assertSame(hex2bin('fb7ff8000000000000'), $obj->__toString());
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandlePi(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(M_PI);
        static::assertSame(M_PI, $obj->normalize());
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleEuler(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(M_E);
        static::assertSame(M_E, $obj->normalize());
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleVeryLargeNumbers(): void
    {
        $value = 1.0e100;
        $obj = DoublePrecisionFloatObject::createFromFloat($value);
        static::assertEqualsWithDelta($value, $obj->normalize(), $value * 1e-10);
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleVerySmallNumbers(): void
    {
        $value = 1.0e-100;
        $obj = DoublePrecisionFloatObject::createFromFloat($value);
        static::assertEqualsWithDelta($value, $obj->normalize(), $value * 1e-10);
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleNegativeNumbers(): void
    {
        $value = -123.456789;
        $obj = DoublePrecisionFloatObject::createFromFloat($value);
        static::assertSame($value, $obj->normalize());
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleFractionalNumbers(): void
    {
        $value = 0.123456789012345;
        $obj = DoublePrecisionFloatObject::createFromFloat($value);
        static::assertEqualsWithDelta($value, $obj->normalize(), 1e-15);
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleMaxValue(): void
    {
        // Maximum finite double precision value: approximately 1.7976931348623157e+308
        $value = PHP_FLOAT_MAX;
        $obj = DoublePrecisionFloatObject::createFromFloat($value);
        static::assertEqualsWithDelta($value, $obj->normalize(), $value * 1e-10);
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleMinPositiveValue(): void
    {
        // Minimum positive normal double precision value: approximately 2.2250738585072014e-308
        $value = PHP_FLOAT_MIN;
        $obj = DoublePrecisionFloatObject::createFromFloat($value);
        static::assertEqualsWithDelta($value, $obj->normalize(), $value * 1e-10);
    }

    #[Test]
    public function aDoublePrecisionObjectCanHandleSubnormalNumbers(): void
    {
        // A very small subnormal number
        $value = 1.0e-320;
        $obj = DoublePrecisionFloatObject::createFromFloat($value);
        static::assertEqualsWithDelta($value, $obj->normalize(), $value * 0.1);
    }

    #[Test]
    public function aDoublePrecisionObjectRoundTrip(): void
    {
        $testValues = [
            0.0,
            1.0,
            -1.0,
            0.5,
            -0.5,
            2.0,
            -2.0,
            10.0,
            -10.0,
            100.5,
            -100.5,
            1234.56789,
            -9876.54321,
            0.00000001,
            -0.00000001,
        ];

        foreach ($testValues as $value) {
            $obj = DoublePrecisionFloatObject::createFromFloat($value);
            static::assertEqualsWithDelta($value, $obj->normalize(), 1e-15, "Failed for value: {$value}");
        }
    }

    #[Test]
    public function aDoublePrecisionObjectPrecisionPreservation(): void
    {
        // Test that double precision preserves high precision values
        $value = 1.23456789012345678901234567890;
        $obj = DoublePrecisionFloatObject::createFromFloat($value);

        // PHP floats are double precision, so the value itself is already truncated
        // We just verify that we can round-trip it without additional loss
        static::assertSame($value, $obj->normalize());
    }

    #[Test]
    public function aDoublePrecisionObjectNormalizeReturnsFloatOrInt(): void
    {
        // Verify that normalize() returns float|int as per the type hint
        $obj = DoublePrecisionFloatObject::createFromFloat(42.5);
        $normalized = $obj->normalize();
        static::assertTrue(is_float($normalized) || is_int($normalized));
    }

    #[Test]
    public function aDoublePrecisionObjectComponentExtraction(): void
    {
        // Test getSign, getExponent, getMantissa methods
        $obj = DoublePrecisionFloatObject::createFromFloat(1.0);

        static::assertSame(1, $obj->getSign());
        static::assertSame(1023, $obj->getExponent()); // Bias is 1023 for double precision
        static::assertSame(0, $obj->getMantissa()); // 1.0 has zero mantissa
    }

    #[Test]
    public function aDoublePrecisionObjectNegativeNumberComponents(): void
    {
        $obj = DoublePrecisionFloatObject::createFromFloat(-2.0);

        static::assertSame(-1, $obj->getSign());
        static::assertSame(1024, $obj->getExponent()); // exponent for 2.0
    }
}
