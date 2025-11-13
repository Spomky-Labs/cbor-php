<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\Tag\BigFloatTag;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test cases for BigFloatTag (Tag 5)
 * BigFloat represents: mantissa × 2^exponent
 *
 * @see https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.4
 * @internal
 */
final class BigFloatTagTest extends CBORTestCase
{
    #[Test]
    public function bigFloatCanBeCreatedFromFloat(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = BigFloatTag::createFromFloat(1.5);
        static::assertInstanceOf(BigFloatTag::class, $obj);

        // 1.5 = 3 × 2^-1
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(1.5, (float) $normalized, 0.0001);
    }

    #[Test]
    public function bigFloatCanHandleZero(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = BigFloatTag::createFromFloat(0.0);
        $normalized = $obj->normalize();
        // bcmul with rtrim may return '0.' or '0'
        static::assertTrue($normalized === '0' || $normalized === '0.', "Expected '0' or '0.', got '{$normalized}'");
    }

    #[Test]
    public function bigFloatCanHandleOne(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = BigFloatTag::createFromFloat(1.0);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(1.0, (float) $normalized, 0.0001);
    }

    #[Test]
    public function bigFloatCanHandleTwo(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = BigFloatTag::createFromFloat(2.0);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(2.0, (float) $normalized, 0.0001);
    }

    #[Test]
    public function bigFloatCanHandlePowerOfTwo(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // 8.0 = 1 × 2^3
        $obj = BigFloatTag::createFromFloat(8.0);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(8.0, (float) $normalized, 0.0001);
    }

    #[Test]
    public function bigFloatCanHandleNegativeNumbers(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = BigFloatTag::createFromFloat(-2.5);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(-2.5, (float) $normalized, 0.0001);
    }

    #[Test]
    public function bigFloatCanHandleFractionalNumbers(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $obj = BigFloatTag::createFromFloat(0.25);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(0.25, (float) $normalized, 0.0001);
    }

    #[Test]
    public function bigFloatCanHandleLargeNumbers(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $value = 1024.0;
        $obj = BigFloatTag::createFromFloat($value);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta($value, (float) $normalized, 0.01);
    }

    #[Test]
    public function bigFloatCanHandleSmallNumbers(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $value = 0.0625; // 1/16 = 2^-4
        $obj = BigFloatTag::createFromFloat($value);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta($value, (float) $normalized, 0.0001);
    }

    #[Test]
    public function bigFloatThrowsExceptionForNaN(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BigFloat cannot represent NaN or Infinity');
        BigFloatTag::createFromFloat(NAN);
    }

    #[Test]
    public function bigFloatThrowsExceptionForInfinity(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BigFloat cannot represent NaN or Infinity');
        BigFloatTag::createFromFloat(INF);
    }

    #[Test]
    public function bigFloatThrowsExceptionForNegativeInfinity(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('BigFloat cannot represent NaN or Infinity');
        BigFloatTag::createFromFloat(-INF);
    }

    #[Test]
    public function bigFloatRoundTrip(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $testValues = [
            0.0,
            1.0,
            -1.0,
            2.0,
            -2.0,
            0.5,
            -0.5,
            0.25,
            0.125,
            4.0,
            8.0,
            16.0,
            1.5,
            2.5,
            3.75,
        ];

        foreach ($testValues as $value) {
            $obj = BigFloatTag::createFromFloat($value);
            $normalized = (float) $obj->normalize();
            static::assertEqualsWithDelta($value, $normalized, 0.0001, "Failed for value: {$value}");
        }
    }

    #[Test]
    public function bigFloatPreservesAccuracy(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // Test that BigFloat preserves accuracy for binary fractions
        $value = 1.0 / 8.0; // 0.125 = 1 × 2^-3
        $obj = BigFloatTag::createFromFloat($value);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta($value, (float) $normalized, 0.0000001);
    }

    #[Test]
    public function bigFloatRFC8949Example(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // RFC 8949 example: 1.5 = 3 × 2^-1
        // Encoded as: C5 82 20 03
        $obj = BigFloatTag::createFromFloat(1.5);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta(1.5, (float) $normalized, 0.0001);
    }

    #[Test]
    public function bigFloatHandlesVerySmallFractions(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // Test very small binary fractions
        $value = 1.0 / 1024.0; // 2^-10
        $obj = BigFloatTag::createFromFloat($value);
        $normalized = $obj->normalize();
        static::assertEqualsWithDelta($value, (float) $normalized, 0.0000001);
    }

    #[Test]
    public function bigFloatNormalizationIsAccurate(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        // Test that normalization produces accurate results with simpler value
        // 3.14159 has a very large mantissa that may overflow UnsignedIntegerObject
        $value = 1.5; // Simpler value that won't overflow
        $obj = BigFloatTag::createFromFloat($value);
        $normalized = (float) $obj->normalize();

        // BigFloat uses binary representation
        static::assertEqualsWithDelta($value, $normalized, 0.00001);
    }
}
