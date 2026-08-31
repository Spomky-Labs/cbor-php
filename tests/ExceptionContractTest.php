<?php

declare(strict_types=1);

namespace CBOR\Test;

use Brick\Math\Exception\MathException;
use CBOR\StringStream;
use CBOR\Utils;
use function hex2bin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

/**
 * Regression tests for GHSA-pjwm-422x-vvh5.
 *
 * Two decoding paths used to raise exceptions outside the error contract of this library. Eight byte length, count
 * and tag headers reached BigInteger::toInt() and produced a Brick\Math IntegerOverflowException; empty bignum
 * payloads reached BigInteger::fromBase() and produced a NumberFormatException, because the guard meant to stop
 * them was an assert(), which is compiled out under the production default zend.assertions=-1.
 *
 * Every exception assertion in this test suite expects InvalidArgumentException, and that is what callers guard
 * against, so both paths must raise it too.
 *
 * @internal
 */
final class ExceptionContractTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function headersExceedingThePhpIntegerRange(): iterable
    {
        yield 'byte string of 2^64-1 bytes' => ['5bffffffffffffffff'];
        yield 'text string of 2^64-1 bytes' => ['7bffffffffffffffff'];
        yield 'list of 2^64-1 items' => ['9bffffffffffffffff'];
        yield 'map of 2^63 entries' => ['bb8000000000000000'];
        yield 'tag number 2^64-1' => ['dbffffffffffffffff00'];
    }

    #[Test]
    #[DataProvider('headersExceedingThePhpIntegerRange')]
    public function aHeaderExceedingThePhpIntegerRangeIsRejected(string $payload): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be represented as a PHP integer');

        $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyBigNumPayloads(): iterable
    {
        yield 'unsigned big num, tag 2' => ['c240'];
        yield 'negative big num, tag 3' => ['c340'];
    }

    /**
     * This is the case the removed assert() was supposed to cover. Under zend.assertions=-1, which is the production
     * default, the statement was gone at compile time and the empty string reached Brick\Math untouched.
     */
    #[Test]
    #[DataProvider('emptyBigNumPayloads')]
    public function anEmptyBigNumPayloadIsRejected(string $payload): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value shall not be empty.');

        $object->normalize();
    }

    /**
     * The point of the fix is not merely that something is thrown, but that what is thrown belongs to this library's
     * contract rather than to Brick\Math.
     */
    #[Test]
    #[DataProvider('headersExceedingThePhpIntegerRange')]
    public function noBrickMathExceptionEscapesTheDecoder(string $payload): void
    {
        $caught = null;

        try {
            $this->getDecoder()
                ->decode(StringStream::create((string) hex2bin($payload)))
            ;
        } catch (Throwable $throwable) {
            $caught = $throwable;
        }

        static::assertInstanceOf(InvalidArgumentException::class, $caught);
        static::assertNotInstanceOf(MathException::class, $caught);
    }

    #[Test]
    public function anEmptyValueIsRejectedByTheHexHelpers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value shall not be empty.');

        Utils::hexToBigInteger('');
    }

    #[Test]
    public function anInvalidHexadecimalValueIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid hexadecimal value');

        Utils::hexToBigInteger('nothexadecimal');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function payloadsThatMustStillDecode(): iterable
    {
        yield 'largest length that fits a PHP integer, unsatisfied by the stream' => ['5b7fffffffffffffff', 'Out of range. Expected'];
    }

    #[Test]
    #[DataProvider('payloadsThatMustStillDecode')]
    public function aLengthWithinRangeStillFailsOnTheStreamRatherThanOnTheHeader(
        string $payload,
        string $message
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function legitimateBigNums(): iterable
    {
        yield 'unsigned big num 18446744073709551616' => ['c249010000000000000000', '18446744073709551616'];
        yield 'negative big num -18446744073709551617' => ['c349010000000000000000', '-18446744073709551617'];
        yield 'unsigned big num 0' => ['c24100', '0'];
    }

    #[Test]
    #[DataProvider('legitimateBigNums')]
    public function legitimateBigNumsAreStillNormalized(string $payload, string $expected): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($payload)))
        ;

        static::assertSame($expected, $object->normalize());
    }
}
