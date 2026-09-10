<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
use CBOR\UnsignedIntegerObject;
use InvalidArgumentException;
use Iterator;
use const PHP_INT_MAX;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class UnsignedIntegerTest extends CBORTestCase
{
    #[DataProvider('getValidValue')]
    #[Test]
    public function createOnValidValue(
        int $intValue,
        string $expectedStringValue,
        int $expectedMajorType,
        int $expectedAdditionalInformation
    ): void {
        $unsignedInteger = UnsignedIntegerObject::create($intValue);
        static::assertSame($expectedStringValue, $unsignedInteger->getValue());
        static::assertSame($expectedMajorType, $unsignedInteger->getMajorType());
        static::assertSame($expectedAdditionalInformation, $unsignedInteger->getAdditionalInformation());
    }

    /**
     * RFC 8949 section 4.2 requires the shortest head that holds the argument, so every bound below is inclusive:
     * 255 is the last one-byte argument, 65535 the last two-byte one, and so on up to the full 64-bit range.
     */
    public static function getValidValue(): Iterator
    {
        yield [0, '0', 0, 0];
        yield [18, '18', 0, 18];
        yield [23, '23', 0, 23];
        yield [24, '24', 0, 24];
        yield [254, '254', 0, 24];
        yield [255, '255', 0, 24];
        yield [256, '256', 0, 25];
        yield [65_535, '65535', 0, 25];
        yield [65_536, '65536', 0, 26];
        yield [12_345_678, '12345678', 0, 26];
        yield [4_294_967_295, '4294967295', 0, 26];
        yield [4_294_967_296, '4294967296', 0, 27];
        yield [PHP_INT_MAX, '9223372036854775807', 0, 27];
    }

    #[DataProvider('getValidStringValue')]
    #[Test]
    public function createFromStringOnValidValue(string $value, string $expectedEncodedData): void
    {
        $unsignedInteger = UnsignedIntegerObject::createFromString($value);
        static::assertSame($expectedEncodedData, bin2hex((string) $unsignedInteger));
        static::assertSame($value, $unsignedInteger->normalize());
    }

    /**
     * createFromString() and createFromHex() reach the part of the 64-bit range that no PHP integer can hold.
     */
    public static function getValidStringValue(): Iterator
    {
        yield ['255', '18ff'];
        yield ['4294967296', '1b0000000100000000'];
        yield ['9223372036854775808', '1b8000000000000000'];
        yield ['18446744073709551615', '1bffffffffffffffff'];
    }

    #[Test]
    public function createFromHexOnLargestArgument(): void
    {
        $unsignedInteger = UnsignedIntegerObject::createFromHex('ffffffffffffffff');
        static::assertSame('1bffffffffffffffff', bin2hex((string) $unsignedInteger));
        static::assertSame('18446744073709551615', $unsignedInteger->normalize());
    }

    #[Test]
    public function createOnNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a positive integer.');
        UnsignedIntegerObject::create(-1);
    }

    #[Test]
    public function createFromStringOnNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a positive integer.');
        UnsignedIntegerObject::createFromString('-1');
    }

    /**
     * 2^64 is the first argument a head cannot carry; it needs a bignum tag.
     */
    #[Test]
    public function createOnOutOfRangeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Out of range. Please use UnsignedBigIntegerTag tag with ByteStringObject object instead.'
        );
        UnsignedIntegerObject::createFromString('18446744073709551616');
    }

    #[DataProvider('getDataSet')]
    #[Test]
    public function anUnsignedIntegerCanBeParsed(string $data, string $expectedNormalizedData): void
    {
        $stream = StringStream::create(hex2bin($data));
        $object = $this->getDecoder()
            ->decode($stream)
        ;
        static::assertSame($data, bin2hex((string) $object));
        static::assertSame($expectedNormalizedData, $object->normalize());
    }

    public static function getDataSet(): Iterator
    {
        yield ['00', '0'];
        yield ['01', '1'];
        yield ['0a', '10'];
        yield ['17', '23'];
        yield ['1818', '24'];
        yield ['1819', '25'];
        yield ['1864', '100'];
        yield ['1903e8', '1000'];
        yield ['1a000f4240', '1000000'];
        yield ['1b000000e8d4a51000', '1000000000000'];
        yield ['1bffffffffffffffff', '18446744073709551615'];
        yield ['c249010000000000000000', '18446744073709551616'];
    }
}
