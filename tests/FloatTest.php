<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
final class FloatTest extends CBORTestCase
{
    #[DataProvider('getDataSet')]
    #[Test]
    public function aFloatCanBeParsed(string $data): void
    {
        $stream = StringStream::create(hex2bin($data));
        $object = $this->getDecoder()
            ->decode($stream)
        ;
        $object->normalize();
        static::assertSame($data, bin2hex((string) $object));
    }

    public static function getDataSet(): Iterator
    {
        yield ['f90000'];
        yield ['f98000'];
        yield ['f93c00'];
        yield ['fb3ff199999999999a'];
        yield ['f93e00'];
        yield ['f97bff'];
        yield ['fa47c35000'];
        yield ['fa7f7fffff'];
        yield ['fb7e37e43c8800759c'];
        yield ['f90001'];
        yield ['f90400'];
        yield ['f9c400'];
        yield ['fbc010666666666666'];
        yield ['f97c00'];
        yield ['f97e00'];
        yield ['f9fc00'];
        yield ['fa7f800000'];
        yield ['fa7fc00000'];
        yield ['faff800000'];
        yield ['fb7ff0000000000000'];
        yield ['fb7ff8000000000000'];
        yield ['fbfff0000000000000'];
        yield ['c5822003'];
        yield ['c48221196ab3'];
    }
}
