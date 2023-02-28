<?php

declare(strict_types=1);

namespace CBOR\Test;

use Brick\Math\Exception\IntegerOverflowException;
use CBOR\StringStream;
use InvalidArgumentException;
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
    public static function getInvalidDataItems(): array
    {
        return [
            [
                base_convert('00000011100', 2, 16),
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
            ],
            [
                base_convert('00000011101', 2, 16),
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
            ],
            [
                base_convert('00000011110', 2, 16),
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
            ],
            ['18', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['19', InvalidArgumentException::class, 'Out of range. Expected: 2, read: 0.'],
            ['1a', InvalidArgumentException::class, 'Out of range. Expected: 4, read: 0.'],
            ['1b', InvalidArgumentException::class, 'Out of range. Expected: 8, read: 0.'],
            ['1901', InvalidArgumentException::class, 'Out of range. Expected: 2, read: 0.'],
            ['1a0102', InvalidArgumentException::class, 'Out of range. Expected: 4, read: 0.'],
            ['1b01020304050607', InvalidArgumentException::class, 'Out of range. Expected: 8, read: 0.'],
            ['38', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['58', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['78', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['98', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['9a01ff00', InvalidArgumentException::class, 'Out of range. Expected: 4, read: 0.'],
            ['b8', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['d8', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['f8', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['f900', InvalidArgumentException::class, 'Out of range. Expected: 2, read: 0.'],
            ['fa0000', InvalidArgumentException::class, 'Out of range. Expected: 4, read: 0.'],
            ['fb000000', InvalidArgumentException::class, 'Out of range. Expected: 8, read: 0.'],
            ['41', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['61', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['5affffffff00', InvalidArgumentException::class, 'Out of range. Expected: 4294967295, read: 0.'],
            [
                '5bffffffffffffffff010203',
                IntegerOverflowException::class,
                '18446744073709551615 is out of range -9223372036854775808 to 9223372036854775807 and cannot be represented as an integer.',
            ],
            ['7affffffff00', InvalidArgumentException::class, 'Out of range. Expected: 4294967295, read: 0.'],
            [
                '7b7fffffffffffffff010203',
                InvalidArgumentException::class,
                'Out of range. Expected: 9223372036854775807, read: 0.',
            ],
            ['81', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['818181818181818181', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['8200', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['a1', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['a20102', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['a100', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['a2000000', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['c0', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['5f4100', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['7f6100', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['9f', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['9f0102', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['bf', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['bf01020102', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['819f', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['9f8000', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['9f9f9f9f9fffffffff', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['9f819f819f9fffffff', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            [
                '1c',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
            ],
            [
                '1d',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
            ],
            [
                '1e',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
            ],
            [
                '3c',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
            ],
            [
                '3d',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
            ],
            [
                '3e',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
            ],
            [
                '5c',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
            ],
            [
                '5d',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
            ],
            [
                '5e',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
            ],
            [
                '7c',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
            ],
            [
                '7d',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
            ],
            [
                '7e',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
            ],
            [
                '9c',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
            ],
            [
                '9d',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
            ],
            [
                '9e',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
            ],
            [
                'bc',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
            ],
            [
                'bd',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
            ],
            [
                'be',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
            ],
            [
                'dc',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
            ],
            [
                'dd',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
            ],
            [
                'de',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
            ],
            [
                'fc',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011100" (28).',
            ],
            [
                'fd',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011101" (29).',
            ],
            [
                'fe',
                InvalidArgumentException::class,
                'Cannot parse the data. Found invalid Additional Information "00011110" (30).',
            ],
            ['f800', InvalidArgumentException::class, 'Invalid simple value. Content data must be between 32 and 255.'],
            ['f801', InvalidArgumentException::class, 'Invalid simple value. Content data must be between 32 and 255.'],
            ['f818', InvalidArgumentException::class, 'Invalid simple value. Content data must be between 32 and 255.'],
            ['f81f', InvalidArgumentException::class, 'Invalid simple value. Content data must be between 32 and 255.'],
            [
                '5f00ff',
                RuntimeException::class,
                'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
            ],
            [
                '5f21ff',
                RuntimeException::class,
                'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
            ],
            [
                '5f6100ff',
                RuntimeException::class,
                'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
            ],
            [
                '5f80ff',
                RuntimeException::class,
                'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
            ],
            [
                '5fa0ff',
                RuntimeException::class,
                'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
            ],
            ['5fc000ff', InvalidArgumentException::class, 'This tag only accepts a Byte String object.'],
            [
                '5fe0ff',
                RuntimeException::class,
                'Unable to parse the data. Infinite Byte String object can only get Byte String objects.',
            ],
            [
                '7f4100ff',
                RuntimeException::class,
                'Unable to parse the data. Infinite Text String object can only get Text String objects.',
            ],
            ['5f5f4100', InvalidArgumentException::class, 'Out of range. Expected: 1, read: 0.'],
            ['ffff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            [
                '7f7f6100ffff',
                RuntimeException::class,
                'Unable to parse the data. Infinite Text String object can only get Text String objects.',
            ],
            ['ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            ['81ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            ['8200ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            ['a1ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            ['a1ff00', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            ['a100ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            ['a20000ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            ['9f81ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            [
                '9f829f819f9fffffffff',
                InvalidArgumentException::class,
                'Cannot parse the data. No enclosing indefinite.',
            ],
            ['bf00ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            ['bf000000ff', InvalidArgumentException::class, 'Cannot parse the data. No enclosing indefinite.'],
            [
                '1f',
                InvalidArgumentException::class,
                'Cannot parse the data. Found infinite length for Major Type "00000" (0).',
            ],
            [
                '3f',
                InvalidArgumentException::class,
                'Cannot parse the data. Found infinite length for Major Type "00001" (1).',
            ],
            [
                'df',
                InvalidArgumentException::class,
                'Cannot parse the data. Found infinite length for Major Type "00110" (6).',
            ],
        ];
    }
}
