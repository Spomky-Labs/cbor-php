<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use function array_map;
use CBOR\ByteStringObject;
use CBOR\ListObject;
use CBOR\Normalizable;
use CBOR\StringStream;
use CBOR\Tag\ColumnMajorMultiDimensionalArrayTag;
use CBOR\Tag\RowMajorMultiDimensionalArrayTag;
use CBOR\Tag\TypedArray\AbstractTypedArrayTag;
use CBOR\Tag\TypedArray\Float128BigEndianArrayTag;
use CBOR\Tag\TypedArray\Uint16BigEndianArrayTag;
use CBOR\Tag\TypedArray\Uint8ArrayTag;
use CBOR\Test\CBORTestCase;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use function count;
use function hex2bin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use function str_replace;

/**
 * The typed array tags of RFC 8746 (64 to 87) and the two multi-dimensional array tags (40 and 1040) that fold
 * them into a shape.
 *
 * @internal
 */
final class TypedArrayTagTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{string, array<int, float|int|string>}>
     */
    public static function typedArrays(): iterable
    {
        yield 'tag 64 (uint8)' => ['d8404300ff80', [0, 255, 128]];
        yield 'tag 68 (uint8 clamped)' => ['d8444300ff80', [0, 255, 128]];
        yield 'tag 65 (uint16 big endian)' => ['d8414400010002', [1, 2]];
        yield 'tag 69 (uint16 little endian)' => ['d84544010002 00', [1, 2]];
        yield 'tag 66 (uint32 big endian)' => ['d84244ffffffff', [4294967295]];
        yield 'tag 70 (uint32 little endian)' => ['d84644 01000000', [1]];
        yield 'tag 67 (uint64 big endian)' => ['d84348ffffffffffffffff', ['18446744073709551615']];
        yield 'tag 71 (uint64 little endian)' => ['d84748 0100000000000000', [1]];
        yield 'tag 72 (sint8)' => ['d8484300ff80', [0, -1, -128]];
        yield 'tag 73 (sint16 big endian)' => ['d84944ffff8000', [-1, -32768]];
        yield 'tag 77 (sint16 little endian)' => ['d84d44ffff0080', [-1, -32768]];
        yield 'tag 74 (sint32 big endian)' => ['d84a44ffffffff', [-1]];
        yield 'tag 78 (sint32 little endian)' => ['d84e44ffffffff', [-1]];
        yield 'tag 75 (sint64 big endian)' => ['d84b48ffffffffffffffff', [-1]];
        yield 'tag 79 (sint64 little endian)' => ['d84f48ffffffffffffffff', [-1]];
        yield 'tag 80 (float16 big endian)' => ['d850443c004000', [1.0, 2.0]];
        yield 'tag 84 (float16 little endian)' => ['d85444003c0040', [1.0, 2.0]];
        yield 'tag 81 (float32 big endian)' => ['d851443f800000', [1.0]];
        yield 'tag 85 (float32 little endian)' => ['d85544 0000803f', [1.0]];
        yield 'tag 82 (float64 big endian)' => ['d852483ff0000000000000', [1.0]];
        yield 'tag 86 (float64 little endian)' => ['d85648000000000000f03f', [1.0]];
        yield 'an empty typed array' => ['d84140', []];
    }

    /**
     * @param array<int, float|int|string> $expected
     */
    #[DataProvider('typedArrays')]
    #[Test]
    public function aTypedArrayIsNormalizedIntoItsElements(string $data, array $expected): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin(str_replace(' ', '', $data))));

        static::assertInstanceOf(AbstractTypedArrayTag::class, $object);
        static::assertInstanceOf(Normalizable::class, $object);
        static::assertSame($expected, $object->normalize());
        static::assertCount(count($expected), $object);
    }

    #[Test]
    public function aTypedArrayWhoseLengthIsNotAWholeNumberOfElementsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('whose length is a multiple of 2');
        Uint16BigEndianArrayTag::create(ByteStringObject::create('abc'));
    }

    #[Test]
    public function aTypedArrayThatIsNotAByteStringIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Byte String object.');
        Uint8ArrayTag::create(TextStringObject::create('abc'));
    }

    /**
     * PHP has no binary128, so the tag carries the elements instead of converting them.
     */
    #[Test]
    public function aBinary128ArrayHandsBackItsElementsAsBytes(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin(
                'd85358203fff000000000000000000000000000040000000000000000000000000000000'
            )));

        static::assertInstanceOf(Float128BigEndianArrayTag::class, $object);
        static::assertNotInstanceOf(Normalizable::class, $object);
        static::assertCount(2, $object);
        static::assertSame(
            ['3fff0000000000000000000000000000', '40000000000000000000000000000000'],
            array_map('bin2hex', $object->getChunks())
        );
    }

    #[Test]
    public function aRowMajorArrayIsFoldedAlongItsLastIndexFirst(): void
    {
        // 40([[2, 3], [1, 2, 3, 4, 5, 6]])
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d8288282020386010203040506')));

        static::assertInstanceOf(RowMajorMultiDimensionalArrayTag::class, $object);
        static::assertSame([2, 3], $object->getDimensions());
        static::assertSame([['1', '2', '3'], ['4', '5', '6']], $object->normalize());
    }

    #[Test]
    public function aColumnMajorArrayIsFoldedAlongItsFirstIndexFirst(): void
    {
        // 1040([[2, 3], [1, 2, 3, 4, 5, 6]])
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d904108282020386010203040506')));

        static::assertInstanceOf(ColumnMajorMultiDimensionalArrayTag::class, $object);
        static::assertSame([['1', '3', '5'], ['2', '4', '6']], $object->normalize());
    }

    #[Test]
    public function aMultiDimensionalArrayFoldsTheTypedArrayItPointsAt(): void
    {
        // 40([[2, 2], 64(h'01020304')])
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d82882820202d8404401020304')));

        static::assertInstanceOf(RowMajorMultiDimensionalArrayTag::class, $object);
        static::assertSame([[1, 2], [3, 4]], $object->normalize());
    }

    #[Test]
    public function aThreeDimensionalArrayIsFoldedAllTheWayDown(): void
    {
        // 40([[2, 2, 2], 64(h'0102030405060708')])
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d8288283020202d840480102030405060708')));

        static::assertInstanceOf(RowMajorMultiDimensionalArrayTag::class, $object);
        static::assertSame([[[1, 2], [3, 4]], [[5, 6], [7, 8]]], $object->normalize());
    }

    #[Test]
    public function aMultiDimensionalArrayWhoseDimensionsDoNotMatchItsValuesIsRejected(): void
    {
        $tag = RowMajorMultiDimensionalArrayTag::create(ListObject::create([
            ListObject::create([UnsignedIntegerObject::create(2), UnsignedIntegerObject::create(3)]),
            ListObject::create([UnsignedIntegerObject::create(1)]),
        ]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The dimensions do not match the number of values.');
        $tag->normalize();
    }

    #[Test]
    public function aMultiDimensionalArrayWithoutDimensionsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected at least one dimension.');
        RowMajorMultiDimensionalArrayTag::create(ListObject::create([
            ListObject::create([]),
            ListObject::create([]),
        ]));
    }
}
