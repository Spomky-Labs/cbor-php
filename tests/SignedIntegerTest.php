<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\NegativeIntegerObject;
use CBOR\StringStream;
use InvalidArgumentException;
use Iterator;
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
        string $expectedIntValue,
        int $expectedMajorType,
        int $expectedAdditionalInformation
    ): void {
        $unsignedInteger = NegativeIntegerObject::create($intValue);
        static::assertSame($expectedIntValue, $unsignedInteger->getValue());
        static::assertSame($expectedMajorType, $unsignedInteger->getMajorType());
        static::assertSame($expectedAdditionalInformation, $unsignedInteger->getAdditionalInformation());
    }

    public static function getValidValue(): Iterator
    {
        yield [-12_345_678, '-12345678', 1, 26];
        yield [-255, '-255', 1, 24];
        yield [-254, '-254', 1, 24];
        yield [-65535, '-65535', 1, 25];
        yield [-18, '-18', 1, 17];
    }

    #[Test]
    public function ceateOnNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a negative integer.');
        NegativeIntegerObject::create(1);
    }

    #[Test]
    public function createOnOutOfRangeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Out of range. Please use NegativeBigIntegerTag tag with ByteStringObject object instead.'
        );
        NegativeIntegerObject::create(-4_294_967_297);
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
