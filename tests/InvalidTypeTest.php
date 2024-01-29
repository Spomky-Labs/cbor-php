<?php

declare(strict_types=1);

namespace CBOR\Test;

use Brick\Math\Exception\IntegerOverflowException;
use CBOR\StringStream;
use InvalidArgumentException;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

/**
 * @internal
 */
final class InvalidTypeTest extends CBORTestCase
{
    #[DataProvider('getInvalidDataItems')]
    #[Test]
    public function invalidData(string $item, string $class, string $expectedError): void
    {
        $this->expectException($class);
        $this->expectExceptionMessage($expectedError);

        $stream = StringStream::create(hex2bin($item));
        $this->getDecoder()
            ->decode($stream)
        ;
    }

    /**
     * @see https://datatracker.ietf.org/doc/html/rfc8949#appendix-F.1
     */
    public static function getInvalidDataItems(): Iterator
    {
        yield [
            base_convert('00000011100', 2, 16),
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
        ];
        yield [
            base_convert('00000011101', 2, 16),
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
        ];
        yield [
            base_convert('00000011110', 2, 16),
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
        ];
        yield ['18', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['19', InvalidArgumentException::class, 'Out of range. Expected: 2, read: 0.'];
        yield ['1a', InvalidArgumentException::class, 'Out of range. Expected: 4, read: 0.'];
        yield ['1b', InvalidArgumentException::class, 'Out of range. Expected: 8, read: 0.'];
        yield ['1901', InvalidArgumentException::class, 'Out of range. Expected: 2, read: 0.'];
        yield ['1a0102', InvalidArgumentException::class, 'Out of range. Expected: 4, read: 0.'];
        yield ['1b01020304050607', InvalidArgumentException::class, 'Out of range. Expected: 8, read: 0.'];
        yield ['38', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['58', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['78', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['98', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['9a01ff00', InvalidArgumentException::class, 'Out of range. Expected: 4, read: 0.'];
        yield ['b8', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['d8', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['f8', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['f900', InvalidArgumentException::class, 'Out of range. Expected: 2, read: 0.'];
        yield ['fa0000', InvalidArgumentException::class, 'Out of range. Expected: 4, read: 0.'];
        yield ['fb000000', InvalidArgumentException::class, 'Out of range. Expected: 8, read: 0.'];
        yield ['41', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['61', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['5affffffff00', InvalidArgumentException::class, 'Out of range. Expected: 4294967295, read: 0.'];
        yield [
            '5bffffffffffffffff010203',
            IntegerOverflowException::class,
            '18446744073709551615 is out of range -9223372036854775808 to 9223372036854775807 and cannot be represented as an integer.',
        ];
        yield ['7affffffff00', InvalidArgumentException::class, 'Out of range. Expected: 4294967295, read: 0.'];
        yield [
            '7b7fffffffffffffff010203',
            InvalidArgumentException::class,
            'Out of range. Expected: 9223372036854775807, read: 0.',
        ];
        yield ['81', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['818181818181818181', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['8200', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['a1', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['a20102', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['a100', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['a2000000', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['c0', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['5f4100', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['7f6100', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['9f', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['9f0102', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['bf', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['bf01020102', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['819f', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['9f8000', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['9f9f9f9f9fffffffff', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['9f819f819f9fffffff', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield [
            '1c',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
        ];
        yield [
            '1d',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
        ];
        yield [
            '1e',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
        ];
        yield [
            '3c',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
        ];
        yield [
            '3d',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
        ];
        yield [
            '3e',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
        ];
        yield [
            '5c',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
        ];
        yield [
            '5d',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
        ];
        yield [
            '5e',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
        ];
        yield [
            '7c',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
        ];
        yield [
            '7d',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
        ];
        yield [
            '7e',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
        ];
        yield [
            '9c',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
        ];
        yield [
            '9d',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
        ];
        yield [
            '9e',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
        ];
        yield [
            'bc',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
        ];
        yield [
            'bd',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
        ];
        yield [
            'be',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
        ];
        yield [
            'dc',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
        ];
        yield [
            'dd',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
        ];
        yield [
            'de',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
        ];
        yield [
            'fc',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
        ];
        yield [
            'fd',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
        ];
        yield [
            'fe',
            InvalidArgumentException::class,
            'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
        ];
        yield [
            'f800',
            InvalidArgumentException::class,
            'Invalid simple value. Content data must be between 32 and 255.',
        ];
        yield [
            'f801',
            InvalidArgumentException::class,
            'Invalid simple value. Content data must be between 32 and 255.',
        ];
        yield [
            'f818',
            InvalidArgumentException::class,
            'Invalid simple value. Content data must be between 32 and 255.',
        ];
        yield [
            'f81f',
            InvalidArgumentException::class,
            'Invalid simple value. Content data must be between 32 and 255.',
        ];
        yield [
            '5f00ff',
            RuntimeException::class,
            'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
        ];
        yield [
            '5f21ff',
            RuntimeException::class,
            'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
        ];
        yield [
            '5f6100ff',
            RuntimeException::class,
            'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
        ];
        yield [
            '5f80ff',
            RuntimeException::class,
            'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
        ];
        yield [
            '5fa0ff',
            RuntimeException::class,
            'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
        ];
        yield ['5fc000ff', InvalidArgumentException::class, 'This tag only accepts a Byte String object.'];
        yield [
            '5fe0ff',
            RuntimeException::class,
            'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
        ];
        yield [
            '7f4100ff',
            RuntimeException::class,
            'Unable to parse the data. Infinite Text String object can only get Text String objects.',
        ];
        yield ['5f5f4100', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'];
        yield ['ffff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield [
            '7f7f6100ffff',
            RuntimeException::class,
            'Unable to parse the data. Infinite Text String object can only get Text String objects.',
        ];
        yield ['ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield ['81ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield ['8200ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield ['a1ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield ['a1ff00', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield ['a100ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield ['a20000ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield ['9f81ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield [
            '9f829f819f9fffffffff',
            InvalidArgumentException::class,
            'Cannot parse the data. No enclosing indefinite.',
        ];
        yield ['bf00ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield ['bf000000ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'];
        yield [
            '1f',
            InvalidArgumentException::class,
            'Cannot parse the data. Found infinite length for Major Type "00000" (0).',
        ];
        yield [
            '3f',
            InvalidArgumentException::class,
            'Cannot parse the data. Found infinite length for Major Type "00001" (1).',
        ];
        yield [
            'df',
            InvalidArgumentException::class,
            'Cannot parse the data. Found infinite length for Major Type "00110" (6).',
        ];
    }
}
