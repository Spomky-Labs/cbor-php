<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
use CBOR\Tag\BigFloatTag;
use CBOR\Tag\DecimalFractionTag;
use function hex2bin;
use InvalidArgumentException;
use function memory_get_peak_usage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use function sprintf;

/**
 * Regression tests for GHSA-pf28-pvhp-7mm5.
 *
 * The exponent of the BigFloat (tag 5) and DecimalFraction (tag 4) tags used to be passed to bcpow() straight from
 * the decoded document, with no upper bound. An eleven byte payload was enough to make bcpow() request 103 GB in a
 * single allocation, which terminates the process with a fatal error that no try/catch can intercept.
 *
 * The bound that answered it was 8192, which still let a six byte item expand to more than eight kilobytes. That is
 * the amplification GHSA-jfrf-557c-963v exploited, so the bound is now 1024.
 *
 * @internal
 */
final class UnboundedExponentTest extends CBORTestCase
{
    /**
     * The exact payload published in the advisory: {"a": 5([4294967295, 1])}, eleven bytes. It has to be rejected
     * before any computation happens, and it must not allocate on the way out.
     */
    #[Test]
    public function theAdvisoryPayloadIsRejectedWithoutAllocating(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('a16161c5821affffffff01')))
        ;

        $before = memory_get_peak_usage(true);
        $caught = null;

        try {
            $object->normalize();
        } catch (InvalidArgumentException $exception) {
            $caught = $exception;
        }

        static::assertInstanceOf(InvalidArgumentException::class, $caught);
        static::assertStringContainsString('The exponent is out of range', $caught->getMessage());
        static::assertLessThan(
            8 * 1024 * 1024,
            memory_get_peak_usage(true) - $before,
            'Rejecting the payload shall not allocate: the bound is checked before bcpow() is reached.'
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function outOfRangeExponents(): iterable
    {
        yield 'BigFloat, exponent 2^32-1' => ['c5821affffffff01', '4294967295'];
        yield 'BigFloat, exponent 2^31' => ['c5821a8000000001', '2147483648'];
        yield 'BigFloat, exponent just above the bound' => ['c58219040101', '1025'];
        yield 'BigFloat, negative exponent below the bound' => ['c58239040001', '-1025'];
        yield 'BigFloat, exponent at the former bound' => ['c58219200001', '8192'];
        yield 'DecimalFraction, exponent 2^32-1' => ['c4821affffffff01', '4294967295'];
        yield 'DecimalFraction, exponent just above the bound' => ['c48219040101', '1025'];
        yield 'DecimalFraction, negative exponent below the bound' => ['c48239040001', '-1025'];
        yield 'DecimalFraction, exponent at the former bound' => ['c48219200001', '8192'];
    }

    #[Test]
    #[DataProvider('outOfRangeExponents')]
    public function anOutOfRangeExponentIsRejected(string $payload, string $exponent): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('The exponent is out of range. Its absolute value shall not exceed 1024, got "%s".', $exponent)
        );

        $object->normalize();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function exponentsOnTheBound(): iterable
    {
        yield 'BigFloat, exponent exactly at the bound' => ['c58219040001'];
        yield 'BigFloat, negative exponent exactly at the bound' => ['c5823903ff01'];
        yield 'DecimalFraction, exponent exactly at the bound' => ['c48219040001'];
        yield 'DecimalFraction, negative exponent exactly at the bound' => ['c4823903ff01'];
    }

    #[Test]
    #[DataProvider('exponentsOnTheBound')]
    public function theBoundIsInclusive(string $payload): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertIsString($object->normalize());
    }

    /**
     * @return iterable<string, array{string, float}>
     */
    public static function legitimateValues(): iterable
    {
        yield 'BigFloat 3 x 2^2' => ['c5820203', 12.0];
        yield 'BigFloat 3 x 2^-1' => ['c5822003', 1.5];
        yield 'DecimalFraction 15 x 10^-2' => ['c482210f', 0.15];
        yield 'DecimalFraction 27315 x 10^-2' => ['c48221196ab3', 273.15];
    }

    #[Test]
    #[DataProvider('legitimateValues')]
    public function legitimateValuesAreStillNormalized(string $payload, float $expected): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertEqualsWithDelta($expected, (float) $object->normalize(), 0.0001);
    }

    #[Test]
    public function theBoundIsExposedOnBothTags(): void
    {
        static::assertSame(1024, BigFloatTag::MAX_ABSOLUTE_EXPONENT);
        static::assertSame(1024, DecimalFractionTag::MAX_ABSOLUTE_EXPONENT);
    }
}
