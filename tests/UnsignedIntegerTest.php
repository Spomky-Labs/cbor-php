<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
use CBOR\UnsignedIntegerObject;
use InvalidArgumentException;
use Iterator;
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
        string $expectedIntValue,
        int $expectedMajorType,
        int $expectedAdditionalInformation
    ): void {
        $unsignedInteger = UnsignedIntegerObject::create($intValue);
        static::assertSame($expectedIntValue, $unsignedInteger->getValue());
        static::assertSame($expectedMajorType, $unsignedInteger->getMajorType());
        static::assertSame($expectedAdditionalInformation, $unsignedInteger->getAdditionalInformation());
    }

    public static function getValidValue(): Iterator
    {
        yield [12_345_678, '12345678', 0, 26];
        yield [255, '255', 0, 25];
        yield [254, '254', 0, 24];
        yield [18, '18', 0, 18];
    }

    #[Test]
    public function createOnNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a positive integer.');
        UnsignedIntegerObject::create(-1);
    }

    #[Test]
    public function createOnOutOfRangeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Out of range. Please use PositiveBigIntegerTag tag with ByteStringObject object instead.'
        );
        UnsignedIntegerObject::create(4_294_967_296);
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
