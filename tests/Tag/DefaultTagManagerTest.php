<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\CBORObject;
use CBOR\Normalizable;
use CBOR\StringStream;
use CBOR\Tag\Base16EncodingTag;
use CBOR\Tag\Base64EncodingTag;
use CBOR\Tag\Base64Tag;
use CBOR\Tag\Base64UrlEncodingTag;
use CBOR\Tag\Base64UrlTag;
use CBOR\Tag\BigFloatTag;
use CBOR\Tag\CBOREncodingTag;
use CBOR\Tag\CBORTag;
use CBOR\Tag\DatetimeTag;
use CBOR\Tag\DecimalFractionTag;
use CBOR\Tag\GenericTag;
use CBOR\Tag\MimeTag;
use CBOR\Tag\NegativeBigIntegerTag;
use CBOR\Tag\TagInterface;
use CBOR\Tag\TagManager;
use CBOR\Tag\TimestampTag;
use CBOR\Tag\UnsignedBigIntegerTag;
use CBOR\Tag\UriTag;
use CBOR\Test\CBORTestCase;
use CBOR\TextStringObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The default tag manager used by the decoder must resolve every built-in tag class by its registered
 * tag number, never by the position of the class in the list it was built from.
 *
 * @see https://github.com/Spomky-Labs/cbor-php/issues/149
 *
 * @internal
 */
final class DefaultTagManagerTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{class-string<TagInterface>, int}>
     */
    public static function builtInTags(): iterable
    {
        yield 'standard datetime' => [DatetimeTag::class, CBORObject::TAG_STANDARD_DATETIME];
        yield 'epoch datetime' => [TimestampTag::class, CBORObject::TAG_EPOCH_DATETIME];
        yield 'unsigned big num' => [UnsignedBigIntegerTag::class, CBORObject::TAG_UNSIGNED_BIG_NUM];
        yield 'negative big num' => [NegativeBigIntegerTag::class, CBORObject::TAG_NEGATIVE_BIG_NUM];
        yield 'decimal fraction' => [DecimalFractionTag::class, CBORObject::TAG_DECIMAL_FRACTION];
        yield 'big float' => [BigFloatTag::class, CBORObject::TAG_BIG_FLOAT];
        yield 'encoded base64url' => [Base64UrlEncodingTag::class, CBORObject::TAG_ENCODED_BASE64_URL];
        yield 'encoded base64' => [Base64EncodingTag::class, CBORObject::TAG_ENCODED_BASE64];
        yield 'encoded base16' => [Base16EncodingTag::class, CBORObject::TAG_ENCODED_BASE16];
        yield 'encoded cbor' => [CBOREncodingTag::class, CBORObject::TAG_ENCODED_CBOR];
        yield 'uri' => [UriTag::class, CBORObject::TAG_URI];
        yield 'base64url' => [Base64UrlTag::class, CBORObject::TAG_BASE64_URL];
        yield 'base64' => [Base64Tag::class, CBORObject::TAG_BASE64];
        yield 'mime' => [MimeTag::class, CBORObject::TAG_MIME];
        yield 'self-described cbor' => [CBORTag::class, CBORObject::TAG_CBOR];
    }

    /**
     * @param class-string<TagInterface> $class
     */
    #[DataProvider('builtInTags')]
    #[Test]
    public function theTagManagerResolvesEachBuiltInTagByItsTagNumber(string $class, int $tagId): void
    {
        $manager = TagManager::create([
            DatetimeTag::class,
            TimestampTag::class,
            UnsignedBigIntegerTag::class,
            NegativeBigIntegerTag::class,
            DecimalFractionTag::class,
            BigFloatTag::class,
            Base64UrlEncodingTag::class,
            Base64EncodingTag::class,
            Base16EncodingTag::class,
            CBOREncodingTag::class,
            UriTag::class,
            Base64UrlTag::class,
            Base64Tag::class,
            MimeTag::class,
            CBORTag::class,
        ]);

        static::assertSame($class, $manager->getClassForValue($tagId));
    }

    #[Test]
    public function theTagManagerFallsBackToTheGenericTagForUnknownTagNumbers(): void
    {
        $manager = TagManager::create([UriTag::class]);

        static::assertSame(GenericTag::class, $manager->getClassForValue(9));
    }

    /**
     * @return iterable<string, array{string, class-string<TagInterface>}>
     */
    public static function taggedDocuments(): iterable
    {
        yield 'tag 0 (standard datetime)' => [
            'c07818323031332d30332d32315432303a30343a30302b30303a3030',
            DatetimeTag::class,
        ];
        yield 'tag 1 (epoch datetime)' => ['c11a514b67b0', TimestampTag::class];
        yield 'tag 2 (unsigned big num)' => ['c249010000000000000000', UnsignedBigIntegerTag::class];
        yield 'tag 3 (negative big num)' => ['c349010000000000000000', NegativeBigIntegerTag::class];
        yield 'tag 4 (decimal fraction)' => ['c48221196ab3', DecimalFractionTag::class];
        yield 'tag 5 (big float)' => ['c5822003', BigFloatTag::class];
        yield 'tag 21 (encoded base64url)' => ['d5443132ff33', Base64UrlEncodingTag::class];
        yield 'tag 22 (encoded base64)' => ['d6443132ff33', Base64EncodingTag::class];
        yield 'tag 23 (encoded base16)' => ['d7443132ff33', Base16EncodingTag::class];
        yield 'tag 24 (encoded cbor)' => ['d818456449455446', CBOREncodingTag::class];
        yield 'tag 32 (uri)' => ['d82076687474703a2f2f7777772e6578616d706c652e636f6d', UriTag::class];
        yield 'tag 33 (base64url)' => ['d8216441414141', Base64UrlTag::class];
        yield 'tag 34 (base64)' => ['d8226441414141', Base64Tag::class];
        yield 'tag 36 (mime)' => ['d8246a746578742f706c61696e', MimeTag::class];
        yield 'tag 55799 (self-described cbor)' => ['d9d9f701', CBORTag::class];
    }

    /**
     * @param class-string<TagInterface> $class
     */
    #[DataProvider('taggedDocuments')]
    #[Test]
    public function theDecoderCreatesTheTagClassRegisteredForTheTagNumber(string $data, string $class): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($data)));

        static::assertInstanceOf($class, $object);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function unassignedTags(): iterable
    {
        yield 'tag 9' => ['c900', 9];
        yield 'tag 10' => ['ca00', 10];
        yield 'tag 13' => ['cd00', 13];
    }

    /**
     * Tags 9, 10 and 13 are unassigned. As long as handler classes were resolved by list position, these
     * documents were routed to byte string only handlers and rejected.
     */
    #[DataProvider('unassignedTags')]
    #[Test]
    public function unassignedTagsAreDecodedAsGenericTags(string $data, int $tagId): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($data)));

        static::assertInstanceOf(GenericTag::class, $object);
        static::assertSame($tagId, $object->getAdditionalInformation());
    }

    #[Test]
    public function aSelfDescribedDocumentIsNormalizable(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d9d9f76474657874')));

        static::assertInstanceOf(CBORTag::class, $object);
        static::assertInstanceOf(Normalizable::class, $object);
        static::assertSame('text', $object->normalize());
    }

    #[Test]
    public function anEncodedUriTagIsDecodedBackIntoAUriTag(): void
    {
        $tag = UriTag::create(TextStringObject::create('http://www.example.com'));

        $object = $this->getDecoder()
            ->decode(StringStream::create((string) $tag));

        static::assertInstanceOf(UriTag::class, $object);
        static::assertSame('http://www.example.com', $object->normalize());
    }
}
