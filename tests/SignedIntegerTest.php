<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\NegativeIntegerObject;
use CBOR\StringStream;

/**
 * @internal
 */
final class SignedIntegerTest extends CBORTestCase
{
    /**
     * @test
     * @dataProvider getValidValue
     */
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

    public function getValidValue(): array
    {
        return [
            [-12345678, '-12345678', 1, 26],
            [-255, '-255', 1, 24],
            [-254, '-254', 1, 24],
            [-65535, '-65535', 1, 25],
            [-18, '-18', 1, 17],
        ];
    }

    /**
     * @test
     */
    public function ceateOnNegativeValue(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The value must be a negative integer.');
        NegativeIntegerObject::create(1);
    }

    /**
     * @test
     */
    public function createOnOutOfRangeValue(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'Out of range. Please use NegativeBigIntegerTag tag with ByteStringObject object instead.'
        );
        NegativeIntegerObject::create(-4294967297);
    }

    /**
     * @test
     * @dataProvider getDataSet
     */
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

    public function getDataSet(): array
    {
        return [
            ['20', '-1'], ['29', '-10'], ['3863', '-100'], ['3903e7', '-1000'], [
                'c349010000000000000000',
                '-18446744073709551617',
            ], ['3bffffffffffffffff', '-18446744073709551616'],
        ];
    }
}
