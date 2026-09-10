<?php

declare(strict_types=1);

namespace CBOR\Test;

use Brick\Math\BigInteger;
use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\IndefiniteLengthListObject;
use CBOR\NegativeIntegerObject;
use CBOR\StringStream;
use CBOR\Tag\BigFloatTag;
use CBOR\Tag\DecimalFractionTag;
use CBOR\Tag\UnsignedBigIntegerTag;
use CBOR\UnsignedIntegerObject;
use function extension_loaded;
use function hex2bin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use function str_repeat;

/**
 * Regression tests for the tag normalization issues reported in Spomky-Labs/cbor-php#151.
 *
 * The decimal fraction (tag 4) and big float (tag 5) tags used to compute their value at a fixed bcmath scale of
 * 100 digits, so an exponent below -100 underflowed to zero and a value such as 5([-101, 2^100]) -- which is
 * exactly 0.5 -- came back as 0.4999...9366. The scale now follows the exponent, which makes every accepted value
 * exact. Alongside it, an indefinite length array is now accepted as the content of both tags.
 *
 * @internal
 */
final class TagNormalizationTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function exactValues(): iterable
    {
        // Anything below 10^-100 used to be truncated to "0." by the fixed scale.
        yield 'DecimalFraction 1 x 10^-101' => ['c482386401', '0.' . str_repeat('0', 100) . '1'];
        yield 'DecimalFraction 100000 x 10^-105' => ['c48238681a000186a0', '0.' . str_repeat('0', 99) . '1'];
        yield 'DecimalFraction 27315 x 10^-2' => ['c48221196ab3', '273.15'];
        yield 'DecimalFraction 15 x 10^-2' => ['c482210f', '0.15'];
        yield 'DecimalFraction -15 x 10^-2' => ['c482212e', '-0.15'];
        yield 'BigFloat 3 x 2^-1' => ['c5822003', '1.5'];
        yield 'BigFloat 1 x 2^-10' => ['c5822901', '0.0009765625'];
        yield 'BigFloat -3 x 2^-2' => ['c5822122', '-0.75'];

        // A result that happens to be an integer shall not carry a dangling decimal point.
        yield 'DecimalFraction 5 x 10^2' => ['c4820205', '500'];
        yield 'DecimalFraction 0 x 10^-2' => ['c4822100', '0'];
        yield 'DecimalFraction 100000 x 10^-3' => ['c482221a000186a0', '100'];
        yield 'BigFloat 3 x 2^2' => ['c5820203', '12'];
        yield 'BigFloat 1 x 2^10' => ['c5820a01', '1024'];
    }

    #[Test]
    #[DataProvider('exactValues')]
    public function aTagIsNormalizedToItsExactValue(string $payload, string $expected): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertSame($expected, $object->normalize());
    }

    /**
     * The case from the report: 2^100 x 2^-101 is exactly one half, yet the fixed scale reported a value that was
     * neither the encoded one nor a rounding of it.
     */
    #[Test]
    public function aBigFloatWithABigNumMantissaIsExact(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $tag = BigFloatTag::createFromExponentAndMantissa(
            NegativeIntegerObject::create(-101),
            self::bigNum('2', 100)
        );

        static::assertSame('0.5', $this->normalizeThroughTheDecoder($tag));
    }

    /**
     * 10^120 x 10^-101 is 10^19, an integer well above the range of a PHP integer, which the fixed scale reported
     * as zero.
     */
    #[Test]
    public function aDecimalFractionWithABigNumMantissaIsExact(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $tag = DecimalFractionTag::createFromExponentAndMantissa(
            NegativeIntegerObject::create(-101),
            self::bigNum('10', 120)
        );

        static::assertSame('1' . str_repeat('0', 19), $this->normalizeThroughTheDecoder($tag));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function indefiniteLengthContents(): iterable
    {
        // c4 9f 21 19 6ab3 ff -- 4(_[-2, 27315]), a valid array of two integers.
        yield 'DecimalFraction' => ['c49f21196ab3ff'];
        yield 'BigFloat' => ['c59f2003ff'];
    }

    #[Test]
    #[DataProvider('indefiniteLengthContents')]
    public function anIndefiniteLengthArrayIsAcceptedAsContent(string $payload): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertIsString($object->normalize());
    }

    #[Test]
    public function anIndefiniteLengthArrayIsNormalizedLikeADefiniteOne(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $content = IndefiniteLengthListObject::create(
            NegativeIntegerObject::create(-2),
            UnsignedIntegerObject::create(27315)
        );

        static::assertSame('273.15', DecimalFractionTag::create($content)->normalize());
    }

    /**
     * An indefinite length array still has to hold exactly an exponent and a mantissa.
     */
    #[Test]
    public function anIndefiniteLengthArrayOfTheWrongSizeIsRejected(): void
    {
        if (! extension_loaded('bcmath')) {
            static::markTestSkipped('bcmath extension is required');
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a ListObject object');

        DecimalFractionTag::create(IndefiniteLengthListObject::create(UnsignedIntegerObject::create(1)));
    }

    /**
     * Builds the big num tag holding base^power.
     */
    private static function bigNum(string $base, int $power): CBORObject
    {
        $value = BigInteger::of($base)
            ->power($power)
            ->toBytes(false)
        ;

        return UnsignedBigIntegerTag::create(ByteStringObject::create($value));
    }

    /**
     * Encodes the tag and decodes it again, so that the value under test is the one a document would carry.
     */
    private function normalizeThroughTheDecoder(CBORObject $object): string
    {
        $decoded = $this->getDecoder()
            ->decode(StringStream::create((string) $object))
        ;

        return $decoded->normalize();
    }
}
