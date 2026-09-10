<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\Encoder;
use CBOR\StringStream;
use Iterator;
use const PHP_INT_MAX;
use const PHP_INT_MIN;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Every PHP integer shall survive Encoder::encode() and come back unchanged, in the shortest head RFC 8949 allows.
 *
 * @internal
 */
final class IntegerRoundTripTest extends CBORTestCase
{
    #[DataProvider('getInteger')]
    #[Test]
    public function anIntegerIsEncodedInItsShortestFormAndDecodedBack(int $value, string $expectedEncodedData): void
    {
        $data = (new Encoder())->encode($value);
        static::assertSame($expectedEncodedData, bin2hex($data));

        $object = $this->getDecoder()
            ->decode(StringStream::create($data))
        ;
        static::assertSame((string) $value, $object->normalize());
    }

    public static function getInteger(): Iterator
    {
        yield [0, '00'];
        yield [23, '17'];
        yield [24, '1818'];
        yield [255, '18ff'];
        yield [256, '190100'];
        yield [65_535, '19ffff'];
        yield [65_536, '1a00010000'];
        yield [4_294_967_295, '1affffffff'];
        yield [4_294_967_296, '1b0000000100000000'];
        yield [PHP_INT_MAX, '1b7fffffffffffffff'];

        yield [-1, '20'];
        yield [-24, '37'];
        yield [-25, '3818'];
        yield [-256, '38ff'];
        yield [-257, '390100'];
        yield [-65_536, '39ffff'];
        yield [-65_537, '3a00010000'];
        yield [-4_294_967_296, '3affffffff'];
        yield [-4_294_967_297, '3b0000000100000000'];
        yield [PHP_INT_MIN, '3b7fffffffffffffff'];
    }
}
