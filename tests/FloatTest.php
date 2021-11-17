<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;

/**
 * @internal
 */
final class FloatTest extends CBORTestCase
{
    /**
     * @test
     * @dataProvider getDataSet
     */
    public function aFloatCanBeParsed(string $data): void
    {
        $stream = StringStream::create(hex2bin($data));
        $object = $this->getDecoder()
            ->decode($stream)
        ;
        $object->normalize();
        static::assertSame($data, bin2hex((string) $object));
    }

    public function getDataSet(): array
    {
        return [
            ['f90000'], ['f98000'], ['f93c00'], ['fb3ff199999999999a'], ['f93e00'], [
                'f97bff',
            ], ['fa47c35000'], ['fa7f7fffff'], ['fb7e37e43c8800759c'], ['f90001'], ['f90400'], ['f9c400'], [
                'fbc010666666666666',
            ], ['f97c00'], ['f97e00'], ['f9fc00'], ['fa7f800000'], ['fa7fc00000'], ['faff800000'], [
                'fb7ff0000000000000',
            ], ['fb7ff8000000000000'], ['fbfff0000000000000'], ['c5822003'], ['c48221196ab3'],
        ];
    }
}
