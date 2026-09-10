<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\NegativeIntegerObject;
use CBOR\StringStream;
use InvalidArgumentException;
use Iterator;
use const PHP_INT_MIN;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class SignedIntegerTest extends CBORTestCase
{
    #[DataProvider('getValidValue')]
    #[Test]
    public function createOnValidValue(
        int $intValue,
        string $expectedStringValue,
        int $expectedMajorType,
        int $expectedAdditionalInformation
    ): void {
        $unsignedInteger = NegativeIntegerObject::create($intValue);
        static::assertSame($expectedStringValue, $unsignedInteger->getValue());
        static::assertSame($expectedMajorType, $unsignedInteger->getMajorType());
        static::assertSame($expectedAdditionalInformation, $unsignedInteger->getAdditionalInformation());
    }

    /**
     * Major type 1 encodes -1 - value, so the inclusive bounds of RFC 8949 section 4.2 land one step further from
     * zero than for major type 0: -256 is the last one-byte argument, -65536 the last two-byte one.
     */
    public static function getValidValue(): Iterator
    {
        yield [-1, '-1', 1, 0];
        yield [-18, '-18', 1, 17];
        yield [-24, '-24', 1, 23];
        yield [-25, '-25', 1, 24];
        yield [-254, '-254', 1, 24];
        yield [-255, '-255', 1, 24];
        yield [-256, '-256', 1, 24];
        yield [-257, '-257', 1, 25];
        yield [-65535, '-65535', 1, 25];
        yield [-65536, '-65536', 1, 25];
        yield [-65537, '-65537', 1, 26];
        yield [-12_345_678, '-12345678', 1, 26];
        yield [-4_294_967_296, '-4294967296', 1, 26];
        yield [-4_294_967_297, '-4294967297', 1, 27];
        yield [PHP_INT_MIN, '-9223372036854775808', 1, 27];
    }

    #[DataProvider('getValidStringValue')]
    #[Test]
    public function createFromStringOnValidValue(string $value, string $expectedEncodedData): void
    {
        $negativeInteger = NegativeIntegerObject::createFromString($value);
        static::assertSame($expectedEncodedData, bin2hex((string) $negativeInteger));
        static::assertSame($value, $negativeInteger->normalize());
    }

    /**
     * createFromString() reaches the part of the 64-bit range that no PHP integer can hold.
     */
    public static function getValidStringValue(): Iterator
    {
        yield ['-256', '38ff'];
        yield ['-4294967297', '3b0000000100000000'];
        yield ['-9223372036854775809', '3b8000000000000000'];
        yield ['-18446744073709551616', '3bffffffffffffffff'];
    }

    #[Test]
    public function createOnNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a negative integer.');
        NegativeIntegerObject::create(1);
    }

    #[Test]
    public function createOnZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a negative integer.');
        NegativeIntegerObject::create(0);
    }

    /**
     * -2^64 - 1 is the first value a head cannot carry; it needs a bignum tag.
     */
    #[Test]
    public function createOnOutOfRangeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Out of range. Please use NegativeBigIntegerTag tag with ByteStringObject object instead.'
        );
        NegativeIntegerObject::createFromString('-18446744073709551617');
    }

    #[DataProvider('getDataSet')]
    #[Test]
    public function anUnsignedIntegerCanBeEncodedAndDecoded(string $data, string $expectedNormalizedData): void
    {
        $stream = StringStream::create(hex2bin($data));
        $object = $this->getDecoder()
            ->decode($stream)
        ;
        $object->normalize();
        static::assertSame($data, bin2hex((string) $object));
        static::assertSame($expectedNormalizedData, $object->normalize());
    }

    public static function getDataSet(): Iterator
    {
        yield ['20', '-1'];
        yield ['29', '-10'];
        yield ['3863', '-100'];
        yield ['3903e7', '-1000'];
        yield ['c349010000000000000000', '-18446744073709551617'];
        yield ['3bffffffffffffffff', '-18446744073709551616'];
    }
}
