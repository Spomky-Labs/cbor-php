<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\Tag\DecimalFractionTag;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test cases for DecimalFractionTag (Tag 4)
 * DecimalFraction represents: mantissa × 10^exponent
 *
 * @see https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.4
 * @internal
 */
final class DecimalFractionTagTest extends CBORTestCase
{
    #[Test]
    public function decimalFractionCanBeCreatedFromFloat(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = DecimalFractionTag::createFromFloat(273.15);
        static::assertInstanceOf(DecimalFractionTag::class, $obj);

        // Should be close to 273.15
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(273.15, (float) $normalized, 0.0001);
    }

    #[Test]
    public function decimalFractionCanHandleZero(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = DecimalFractionTag::createFromFloat(0.0);
        $normalized = $obj->normalize();
        // bcmul with rtrim may return '0.' or '0'
        static::assertTrue($normalized === '0' || $normalized === '0.', "Expected '0' or '0.', got '{$normalized}'");
    }

    #[Test]
    public function decimalFractionCanHandleOne(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = DecimalFractionTag::createFromFloat(1.0);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(1.0, (float) $normalized, 0.0001);
    }

    #[Test]
    public function decimalFractionCanHandleOnePointOne(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // 1.1 is a classic example that cannot be represented exactly in binary
        // but can be represented exactly in decimal: 11 × 10^-1
        $obj = DecimalFractionTag::createFromFloat(1.1);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(1.1, (float) $normalized, 0.0001);
    }

    #[Test]
    public function decimalFractionCanHandleNegativeNumbers(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = DecimalFractionTag::createFromFloat(-12.34);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(-12.34, (float) $normalized, 0.0001);
    }

    #[Test]
    public function decimalFractionCanHandleFractionalNumbers(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = DecimalFractionTag::createFromFloat(0.25);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(0.25, (float) $normalized, 0.0001);
    }

    #[Test]
    public function decimalFractionCanHandleLargeNumbers(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $value = 12345.6789;
        $obj = DecimalFractionTag::createFromFloat($value);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta($value, (float) $normalized, 0.001);
    }

    #[Test]
    public function decimalFractionCanHandleSmallNumbers(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $value = 0.0001;
        $obj = DecimalFractionTag::createFromFloat($value);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta($value, (float) $normalized, 0.000001);
    }

    #[Test]
    public function decimalFractionThrowsExceptionForNaN(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DecimalFraction cannot represent NaN or Infinity');
        DecimalFractionTag::createFromFloat(NAN);
    }

    #[Test]
    public function decimalFractionThrowsExceptionForInfinity(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DecimalFraction cannot represent NaN or Infinity');
        DecimalFractionTag::createFromFloat(INF);
    }

    #[Test]
    public function decimalFractionThrowsExceptionForNegativeInfinity(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DecimalFraction cannot represent NaN or Infinity');
        DecimalFractionTag::createFromFloat(-INF);
    }

    #[Test]
    public function decimalFractionRoundTrip(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $testValues = [
            0.0,
            1.0,
            -1.0,
            2.5,
            -2.5,
            0.5,
            -0.5,
            0.25,
            0.125,
            10.0,
            100.0,
            1.23,
            12.34,
            123.45,
        ];

        foreach ($testValues as $value) {
            $obj = DecimalFractionTag::createFromFloat($value);
            $normalized = (float) $obj->normalize();
            static::assertEqualsWithDelta($value, $normalized, 0.0001, "Failed for value: {$value}");
        }
    }

    #[Test]
    public function decimalFractionWithCustomPrecision(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // Test with different precision levels
        $value = 1.23456789;

        // Lower precision
        $obj1 = DecimalFractionTag::createFromFloat($value, 2);
        $normalized1 = (float) $obj1->normalize();
        static::assertEqualsWithDelta($value, $normalized1, 0.01);

        // Higher precision
        $obj2 = DecimalFractionTag::createFromFloat($value, 8);
        $normalized2 = (float) $obj2->normalize();
        static::assertEqualsWithDelta($value, $normalized2, 0.00001);
    }

    #[Test]
    public function decimalFractionRFC8949Example(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // RFC 8949 example: 273.15 = 27315 × 10^-2
        // Encoded as: C4 82 21 19 6ab3
        $obj = DecimalFractionTag::createFromFloat(273.15);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(273.15, (float) $normalized, 0.0001);
    }

    #[Test]
    public function decimalFractionPreservesDecimalAccuracy(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // DecimalFraction should handle decimal values better than binary
        $value = 0.1;
        $obj = DecimalFractionTag::createFromFloat($value);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta($value, (float) $normalized, 0.0001);
    }

    #[Test]
    public function decimalFractionHandlesMonetaryValues(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // Test typical monetary values
        $values = [19.99, 99.95, 1234.56, 0.01, 0.99];

        foreach ($values as $value) {
            $obj = DecimalFractionTag::createFromFloat($value);
            $normalized = (float) $obj->normalize();
            static::assertEqualsWithDelta($value, $normalized, 0.001, "Failed for monetary value: {$value}");
        }
    }

    #[Test]
    public function decimalFractionThrowsExceptionForNegativePrecision(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Precision must be non-negative');
        DecimalFractionTag::createFromFloat(1.23, -1);
    }

    #[Test]
    public function decimalFractionHandlesVerySmallFractions(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $value = 0.000001; // 1 × 10^-6
        $obj = DecimalFractionTag::createFromFloat($value);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta($value, (float) $normalized, 0.0000001);
    }

    #[Test]
    public function decimalFractionNormalizationIsAccurate(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // Test that normalization produces accurate results
        $value = 3.14159;
        $obj = DecimalFractionTag::createFromFloat($value, 5);
        $normalized = (float) $obj->normalize();

        static::assertEqualsWithDelta($value, $normalized, 0.00001);
    }
}
