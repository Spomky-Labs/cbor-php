<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\ByteStringObject;
use CBOR\IndefiniteLengthListObject;
use CBOR\IndefiniteLengthMapObject;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\OtherObject\UndefinedObject;
use CBOR\StringStream;
use CBOR\Tag\GenericTag;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use function sprintf;
use stdClass;

/**
 * @internal
 */
final class RobustnessTest extends CBORTestCase
{
    #[Test]
    #[DataProvider('invalidListItems')]
    public function aListRejectsItemsThatAreNotCBORObjects(mixed $item, string $expectedType): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid item at index "0". Expected a CBORObject, got "%s".', $expectedType));

        ListObject::create([$item]);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidListItems(): iterable
    {
        yield 'an integer' => [1, 'int'];
        yield 'a string' => ['abc', 'string'];
        yield 'null' => [null, 'null'];
        yield 'an array' => [[], 'array'];
        yield 'an unrelated object' => [new stdClass(), 'stdClass'];
    }

    #[Test]
    public function theOffendingIndexIsReported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid item at index "1". Expected a CBORObject, got "string".');

        ListObject::create([TextStringObject::create('Hello'), 'abc']);
    }

    #[Test]
    public function aListOfCBORObjectsIsAccepted(): void
    {
        $object = ListObject::create([TextStringObject::create('Hello'), UnsignedIntegerObject::create(1)]);

        static::assertCount(2, $object);
        static::assertSame(['Hello', '1'], $object->normalize());
    }

    #[Test]
    public function readingPastTheEndOfTheStreamReportsHowManyBytesWereActuallyRead(): void
    {
        $stream = StringStream::create('abc');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Out of range. Expected: 10, read: 3.');

        $stream->read(10);
    }

    #[Test]
    public function readingPastTheEndOfAnEmptyStreamReportsZeroBytes(): void
    {
        $stream = StringStream::create('');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Out of range. Expected: 4, read: 0.');

        $stream->read(4);
    }

    #[Test]
    public function readingPastTheEndOfALongStreamReportsHowManyBytesWereActuallyRead(): void
    {
        // More than the 1024 byte chunk the stream reads at a time, so the count spans several iterations.
        $stream = StringStream::create(str_repeat('a', 2000));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Out of range. Expected: 3000, read: 2000.');

        $stream->read(3000);
    }

    #[Test]
    public function theUndefinedSimpleValueNormalizesToNull(): void
    {
        static::assertNull(UndefinedObject::create()->normalize());
    }

    #[Test]
    public function anUnknownTagNormalizesToItsContent(): void
    {
        /** @var GenericTag $tag */
        $tag = GenericTag::createFromLoadedData(24, "\x50", TextStringObject::create('Hello'));

        static::assertSame('Hello', $tag->normalize());
    }

    #[Test]
    public function aListNormalizesItsUndefinedAndUnknownTagLeavesToNativeValues(): void
    {
        $object = ListObject::create([
            UndefinedObject::create(),
            GenericTag::createFromLoadedData(24, "\x50", UnsignedIntegerObject::create(1)),
        ]);

        static::assertSame([null, '1'], $object->normalize());
    }

    #[Test]
    public function anIndefiniteLengthListNormalizesItsUndefinedAndUnknownTagLeavesToNativeValues(): void
    {
        $object = IndefiniteLengthListObject::create(
            UndefinedObject::create(),
            GenericTag::createFromLoadedData(24, "\x50", UnsignedIntegerObject::create(1)),
        );

        static::assertSame([null, '1'], $object->normalize());
    }

    #[Test]
    public function aMapNormalizesItsUndefinedAndUnknownTagLeavesToNativeValues(): void
    {
        $object = MapObject::create()
            ->add(TextStringObject::create('undefined'), UndefinedObject::create())
            ->add(
                TextStringObject::create('tag'),
                GenericTag::createFromLoadedData(24, "\x50", ByteStringObject::create('Hello'))
            )
        ;

        static::assertSame([
            'undefined' => null,
            'tag' => 'Hello',
        ], $object->normalize());
    }

    #[Test]
    public function anIndefiniteLengthMapNormalizesItsUndefinedAndUnknownTagLeavesToNativeValues(): void
    {
        $object = IndefiniteLengthMapObject::create()
            ->add(TextStringObject::create('undefined'), UndefinedObject::create())
            ->add(
                TextStringObject::create('tag'),
                GenericTag::createFromLoadedData(24, "\x50", ByteStringObject::create('Hello'))
            )
        ;

        static::assertSame([
            'undefined' => null,
            'tag' => 'Hello',
        ], $object->normalize());
    }

    #[Test]
    public function anUndefinedValueSurvivesARoundTrip(): void
    {
        $decoded = $this->getDecoder()
            ->decode(StringStream::create(hex2bin('f7')))
        ;

        static::assertInstanceOf(UndefinedObject::class, $decoded);
        static::assertNull($decoded->normalize());
    }
}
