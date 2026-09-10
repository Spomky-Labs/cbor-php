<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use const INF;
use function is_float;
use Iterator;
use const PHP_FLOAT_EPSILON;
use const PHP_FLOAT_MAX;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use function sprintf;

/**
 * A float object shall normalize to the value its payload encodes, as a float, whatever its magnitude.
 *
 * normalize() used to assemble the value with `2 ** $shift`, which PHP evaluates to an *integer* whenever the shift
 * is not negative. Every finite value large enough to reach that branch therefore came back as an int -- roughly one
 * single-precision payload in six -- until the product grew past PHP_INT_MAX and silently became a float again.
 *
 * @internal
 */
final class FloatBitPatternTest extends CBORTestCase
{
    /**
     * These magnitudes all sat in the band that used to normalize to an int.
     */
    #[DataProvider('getLargeValue')]
    #[Test]
    public function aLargeValueNormalizesToAFloat(string $class, string $payload, float $expected): void
    {
        /** @var DoublePrecisionFloatObject|HalfPrecisionFloatObject|SinglePrecisionFloatObject $object */
        $object = $class::create(hex2bin($payload));
        $normalized = $object->normalize();

        static::assertIsFloat($normalized);
        static::assertSame($expected, $normalized);
    }

    public static function getLargeValue(): Iterator
    {
        yield 'half 1024' => [HalfPrecisionFloatObject::class, '6400', 1024.0];
        yield 'half 2047' => [HalfPrecisionFloatObject::class, '67ff', 2047.0];
        yield 'single 2^23' => [SinglePrecisionFloatObject::class, '4b000000', 8_388_608.0];
        yield 'single 2^24-1' => [SinglePrecisionFloatObject::class, '4b7fffff', 16_777_215.0];
        yield 'double 2^52' => [DoublePrecisionFloatObject::class, '4330000000000000', 4_503_599_627_370_496.0];
        yield 'double 2^53-1' => [
            DoublePrecisionFloatObject::class,
            '433fffffffffffff',
            9_007_199_254_740_991.0,
        ];
    }

    /**
     * Every exponent of the 16-bit format, both signs, across the mantissa range: the subnormal branch, the thirty
     * normal ones and the infinity/NaN branch all have to yield a float.
     */
    #[Test]
    public function everyHalfPrecisionExponentNormalizesToAFloat(): void
    {
        $offenders = [];
        foreach (range(0, 31) as $exponent) {
            foreach ([0, 1] as $sign) {
                foreach ([0, 1, 341, 682, 1022, 1023] as $mantissa) {
                    $bits = $sign << 15 | $exponent << 10 | $mantissa;
                    $normalized = HalfPrecisionFloatObject::create(pack('n', $bits))->normalize();
                    if (! is_float($normalized)) {
                        $offenders[] = sprintf('0x%04x', $bits);
                    }
                }
            }
        }

        static::assertSame([], $offenders);
    }

    #[DataProvider('getRoundTripValue')]
    #[Test]
    public function aDoublePrecisionValueSurvivesARoundTrip(float $value): void
    {
        static::assertSame($value, DoublePrecisionFloatObject::createFromFloat($value)->normalize());
    }

    public static function getRoundTripValue(): Iterator
    {
        yield 'zero' => [0.0];
        yield 'negative zero' => [-0.0];
        yield 'one' => [1.0];
        yield 'minus one' => [-1.0];
        yield 'fraction' => [0.123456789012345];
        yield 'epsilon' => [PHP_FLOAT_EPSILON];
        yield 'max' => [PHP_FLOAT_MAX];
        yield 'smallest subnormal' => [4.9406564584124654e-324];
        yield 'largest subnormal' => [2.2250738585072009e-308];
        yield 'two to the 52' => [4_503_599_627_370_496.0];
        yield 'two to the 53' => [9_007_199_254_740_992.0];
        yield 'large' => [1.0e300];
    }

    /**
     * Negative zero has the same magnitude as zero and only the sign bit tells them apart.
     */
    #[Test]
    public function negativeZeroKeepsItsSign(): void
    {
        $normalized = DoublePrecisionFloatObject::create(hex2bin('8000000000000000'))->normalize();

        static::assertSame(0.0, abs($normalized));
        // fdiv() rather than "/", which raises DivisionByZeroError: the sign is what separates -0.0 from 0.0.
        static::assertSame(-INF, fdiv(1.0, $normalized));
    }

    #[DataProvider('getBitPattern')]
    #[Test]
    public function theAccessorsReportTheBitPattern(
        string $class,
        string $payload,
        int $sign,
        int $exponent,
        int $mantissa
    ): void {
        /** @var DoublePrecisionFloatObject|HalfPrecisionFloatObject|SinglePrecisionFloatObject $object */
        $object = $class::create(hex2bin($payload));

        static::assertSame($sign, $object->getSign());
        static::assertSame($exponent, $object->getExponent());
        static::assertSame($mantissa, $object->getMantissa());
    }

    public static function getBitPattern(): Iterator
    {
        yield 'half -2' => [HalfPrecisionFloatObject::class, 'c000', -1, 16, 0];
        yield 'half largest' => [HalfPrecisionFloatObject::class, '7bff', 1, 30, 1023];
        yield 'single -2' => [SinglePrecisionFloatObject::class, 'c0000000', -1, 128, 0];
        yield 'single largest' => [SinglePrecisionFloatObject::class, '7f7fffff', 1, 254, 8_388_607];
        yield 'double 1.0' => [DoublePrecisionFloatObject::class, '3ff0000000000000', 1, 1023, 0];
        yield 'double -2.0' => [DoublePrecisionFloatObject::class, 'c000000000000000', -1, 1024, 0];
        yield 'double largest' => [
            DoublePrecisionFloatObject::class,
            '7fefffffffffffff',
            1,
            2046,
            4_503_599_627_370_495,
        ];
        yield 'double negative NaN' => [DoublePrecisionFloatObject::class, 'fff8000000000000', -1, 2047, 1 << 51];
    }
}
