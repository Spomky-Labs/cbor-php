<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
use CBOR\Utils;
use InvalidArgumentException;
use Iterator;
use const PHP_INT_MAX;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use function sprintf;

/**
 * Utils::binToInt() reads a head argument, which the decoder does for every length and count of 24 or more.
 *
 * It used to hand every one of them to brick/math. The arguments that fit a PHP integer are now built from their
 * bytes; brick/math is kept for the ones above PHP_INT_MAX, which still have to be refused with the documented
 * message rather than silently wrapping negative.
 *
 * @internal
 */
final class BinToIntTest extends CBORTestCase
{
    #[DataProvider('getArgument')]
    #[Test]
    public function anArgumentWithinRangeIsRead(string $payload, int $expected): void
    {
        static::assertSame($expected, Utils::binToInt(hex2bin($payload)));
    }

    public static function getArgument(): Iterator
    {
        yield 'one byte, zero' => ['00', 0];
        yield 'one byte' => ['18', 24];
        yield 'one byte, largest' => ['ff', 255];
        yield 'two bytes' => ['0100', 256];
        yield 'two bytes, largest' => ['ffff', 65_535];
        yield 'four bytes' => ['00010000', 65_536];
        yield 'four bytes, largest' => ['ffffffff', 4_294_967_295];
        yield 'eight bytes' => ['0000000100000000', 4_294_967_296];
        yield 'eight bytes, PHP_INT_MAX' => ['7fffffffffffffff', PHP_INT_MAX];
        yield 'leading zero bytes' => ['0000000000000001', 1];
    }

    /**
     * PHP_INT_MAX + 1 is where the byte-wise read wraps negative and brick/math has to take over.
     */
    #[DataProvider('getOutOfRangeArgument')]
    #[Test]
    public function anArgumentAbovePhpIntMaxIsRefused(string $payload, string $expectedValueInMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Out of range. "%s" cannot be represented as a PHP integer.',
            $expectedValueInMessage
        ));
        Utils::binToInt(hex2bin($payload));
    }

    public static function getOutOfRangeArgument(): Iterator
    {
        yield 'PHP_INT_MAX + 1' => ['8000000000000000', '9223372036854775808'];
        yield 'largest 64-bit argument' => ['ffffffffffffffff', '18446744073709551615'];
    }

    /**
     * The decoder surfaces the same refusal for a byte string announcing an unrepresentable length.
     */
    #[Test]
    public function aLengthAbovePhpIntMaxIsRefusedByTheDecoder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Out of range. "18446744073709551615" cannot be represented as a PHP integer.'
        );
        $this->getDecoder()
            ->decode(StringStream::create(hex2bin('5bffffffffffffffff')))
        ;
    }
}
