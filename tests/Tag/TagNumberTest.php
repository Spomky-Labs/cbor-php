<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\StringStream;
use CBOR\Tag;
use CBOR\Tag\CoseSign1Tag;
use CBOR\Tag\GenericTag;
use CBOR\Test\CBORTestCase;
use function hex2bin;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The tag number read back from the head, whatever the class.
 *
 * @internal
 */
final class TagNumberTest extends CBORTestCase
{
    /**
     * RFC 8949 section 3: one encoding per width, the shortest that holds the number.
     *
     * @return iterable<string, array{string, int}>
     */
    public static function heads(): iterable
    {
        yield 'in the additional information' => ['c101', 1];
        yield 'one byte' => ['d8c801', 200];
        yield 'two bytes' => ['d9d9f701', 55799];
        yield 'four bytes' => ['da0001000001', 65536];
        yield 'eight bytes' => ['db000000010000000001', 4294967296];
    }

    #[Test]
    #[DataProvider('heads')]
    public function theNumberIsReadFromTheHead(string $hex, int $number): void
    {
        $tag = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($hex)));

        static::assertInstanceOf(Tag::class, $tag);
        static::assertSame($number, $tag->getTagNumber());
    }

    /**
     * A dedicated class and a GenericTag of the same number answer the same, which getTagId() does not: a
     * GenericTag declares -1.
     */
    #[Test]
    public function aGenericTagAndADedicatedClassAgree(): void
    {
        $sign1 = ListObject::create([
            ByteStringObject::create(''),
            MapObject::create(),
            ByteStringObject::create(''),
            ByteStringObject::create(''),
        ]);
        $dedicated = CoseSign1Tag::createFromLoadedData(18, null, $sign1);
        $generic = GenericTag::createFromLoadedData(18, null, $sign1);

        static::assertSame(18, $dedicated->getTagNumber());
        static::assertSame(18, $generic->getTagNumber());
        static::assertSame(-1, GenericTag::getTagId());
    }
}
