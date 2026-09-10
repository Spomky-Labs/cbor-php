<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\Tag\DecimalFractionTag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[RequiresPhpExtension('bcmath')]
final class DecimalFractionFromFloatTest extends CBORTestCase
{
    #[Test]
    #[DataProvider('floatsAndTheirNormalizedValues')]
    public function aFloatIsConvertedToADecimalFraction(float $value, string $expected): void
    {
        $tag = DecimalFractionTag::createFromFloat($value);

        static::assertSame($expected, $tag->normalize());
    }

    /**
     * @return iterable<string, array{float, string}>
     */
    public static function floatsAndTheirNormalizedValues(): iterable
    {
        yield 'zero' => [0.0, '0'];
        yield 'a positive integer' => [1.0, '1'];
        yield 'a negative integer' => [-1.0, '-1'];
        yield 'a positive decimal' => [1.5, '1.5'];
        yield 'a negative decimal' => [-1.5, '-1.5'];
        yield 'a positive value below one' => [0.05, '0.05'];
        yield 'a negative value below one' => [-0.05, '-0.05'];
        yield 'trailing zeros are folded into the exponent' => [1500.0, '1500'];

        // A value smaller than the requested precision rounds to zero. Keeping the minus sign around used to leave
        // the mantissa as the bare string "-", which brick/math rejected with a NumberFormatException.
        yield 'a negative value that rounds to zero' => [-1.0e-12, '0'];
        yield 'a positive value that rounds to zero' => [1.0e-12, '0'];
    }

    #[Test]
    public function aNegativeValueRoundingToZeroYieldsAnUnsignedZeroMantissa(): void
    {
        $tag = DecimalFractionTag::createFromFloat(-1.0e-12);

        // 4([0, 0]): tag 4, a two element array, exponent 0 and mantissa 0.
        static::assertSame('c4820000', bin2hex((string) $tag));
    }

    #[Test]
    public function theGlobalBcmathScaleIsLeftUntouched(): void
    {
        $scale = bcscale();
        bcscale(7);

        try {
            DecimalFractionTag::createFromFloat(1.5);

            static::assertSame(7, bcscale());
        } finally {
            bcscale($scale);
        }
    }
}
