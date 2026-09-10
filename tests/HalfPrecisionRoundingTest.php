<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\OtherObject\HalfPrecisionFloatObject;
use const INF;
use const M_PI;
use const NAN;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * IEEE 754 binary16 encoding: round to nearest, ties to even, and a preserved sign bit.
 *
 * The expected encodings are the ones produced by a reference IEEE 754 implementation (Python's `struct` "e" format).
 *
 * @internal
 */
final class HalfPrecisionRoundingTest extends CBORTestCase
{
    #[Test]
    #[DataProvider('valuesAndTheirEncodings')]
    public function aFloatIsEncodedToTheNearestHalfPrecisionValue(float $value, string $expectedHex): void
    {
        $object = HalfPrecisionFloatObject::createFromFloat($value);

        static::assertSame($expectedHex, bin2hex((string) $object));
    }

    /**
     * @return iterable<string, array{float, string}>
     */
    public static function valuesAndTheirEncodings(): iterable
    {
        yield 'zero keeps its sign' => [0.0, 'f90000'];
        yield 'negative zero keeps its sign' => [-0.0, 'f98000'];
        yield 'one' => [1.0, 'f93c00'];
        yield 'minus one' => [-1.0, 'f9bc00'];
        yield 'one and a half' => [1.5, 'f93e00'];

        // Above 2048 the format steps by 2, so 2050 and 2052 are neighbours and 2051 sits exactly between them.
        // Truncating the mantissa instead of rounding used to return 2050 here.
        yield 'a tie rounds up to the even neighbour' => [2051.0, 'f96802'];
        yield 'an exactly representable value is kept' => [2050.0, 'f96801'];
        yield 'a tie rounds down to the even neighbour' => [2053.0, 'f96802'];
        yield 'a tie rounds down to the even neighbour of the previous exponent' => [2049.0, 'f96800'];

        yield 'largest finite value' => [65504.0, 'f97bff'];
        yield 'largest finite negative value' => [-65504.0, 'f9fbff'];
        yield 'below the overflow tie stays finite' => [65519.0, 'f97bff'];
        yield 'the overflow tie rounds to infinity' => [65520.0, 'f97c00'];
        yield 'too large for the format' => [100000.0, 'f97c00'];
        yield 'too large and negative' => [-100000.0, 'f9fc00'];
        yield 'infinity' => [INF, 'f97c00'];
        yield 'negative infinity' => [-INF, 'f9fc00'];

        yield 'smallest normal value' => [6.103515625e-5, 'f90400'];
        yield 'largest subnormal value' => [6.097555160522461e-5, 'f903ff'];
        yield 'smallest subnormal value' => [5.960464477539063e-8, 'f90001'];
        yield 'subnormal tie rounds to the even neighbour' => [8.940696716308594e-8, 'f90002'];
        yield 'half of the smallest subnormal is a tie towards zero' => [2.9802322387695312e-8, 'f90000'];
        yield 'just above half of the smallest subnormal rounds up' => [2.980232238769532e-8, 'f90001'];
        yield 'far below the smallest subnormal keeps its sign' => [-1.0e-10, 'f98000'];

        yield 'rounding carries into the exponent' => [2047.5, 'f96800'];
        yield 'one tenth' => [0.1, 'f92e66'];
        yield 'minus one tenth' => [-0.1, 'f9ae66'];
        yield 'pi' => [M_PI, 'f94248'];
    }

    #[Test]
    public function nanIsEncodedInItsCanonicalForm(): void
    {
        $object = HalfPrecisionFloatObject::createFromFloat(NAN);

        static::assertSame('f97e00', bin2hex((string) $object));
    }

    #[Test]
    #[DataProvider('valuesAndTheirEncodings')]
    public function anEncodedValueNormalizesBackToItself(float $value, string $expectedHex): void
    {
        $object = HalfPrecisionFloatObject::createFromFloat($value);
        $normalized = $object->normalize();

        static::assertSame($expectedHex, bin2hex((string) HalfPrecisionFloatObject::createFromFloat($normalized)));
    }
}
