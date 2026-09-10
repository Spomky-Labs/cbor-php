<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\StringStream;
use CBOR\Tag\BinaryMimeTag;
use CBOR\Tag\DurationTag;
use CBOR\Tag\ExplicitMapTag;
use CBOR\Tag\ExtendedTimeTag;
use CBOR\Tag\HomogeneousArrayTag;
use CBOR\Tag\IdentifierTag;
use CBOR\Tag\IpldContentIdentifierTag;
use CBOR\Tag\LanguageIndependentObjectTag;
use CBOR\Tag\LanguageTaggedStringTag;
use CBOR\Tag\PeriodTag;
use CBOR\Tag\PerlObjectTag;
use CBOR\Tag\RationalNumberTag;
use CBOR\Tag\RegexpTag;
use CBOR\Tag\SetTag;
use CBOR\Tag\ShareableTag;
use CBOR\Tag\SharedReferenceTag;
use CBOR\Tag\StringReferenceNamespaceTag;
use CBOR\Tag\StringReferenceTag;
use CBOR\Tag\TagInterface;
use CBOR\Tag\UuidTag;
use CBOR\Test\CBORTestCase;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use function hex2bin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The tags of the IANA registry that this library implements outside of RFC 8949, the typed arrays, the COSE
 * structures and the address tags -- each of which has a test case of its own.
 *
 * @internal
 */
final class RegistryTagsTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{string, class-string<TagInterface>}>
     */
    public static function taggedDocuments(): iterable
    {
        yield 'tag 25 (string reference)' => ['d81901', StringReferenceTag::class];
        yield 'tag 26 (perl object)' => ['d81a8263466f6f01', PerlObjectTag::class];
        yield 'tag 27 (language independent object)' => ['d81b8263466f6f01', LanguageIndependentObjectTag::class];
        yield 'tag 28 (shareable)' => ['d81c01', ShareableTag::class];
        yield 'tag 29 (shared reference)' => ['d81d00', SharedReferenceTag::class];
        yield 'tag 30 (rational number)' => ['d81e820304', RationalNumberTag::class];
        yield 'tag 35 (regular expression)' => ['d82363616263', RegexpTag::class];
        yield 'tag 37 (uuid)' => ['d82550000102030405060708090a0b0c0d0e0f', UuidTag::class];
        yield 'tag 38 (language tagged string)' => ['d8268262656e63616263', LanguageTaggedStringTag::class];
        yield 'tag 39 (identifier)' => ['d82701', IdentifierTag::class];
        yield 'tag 41 (homogeneous array)' => ['d82983010203', HomogeneousArrayTag::class];
        yield 'tag 42 (IPLD content identifier)' => ['d82a43000102', IpldContentIdentifierTag::class];
        yield 'tag 256 (string reference namespace)' => ['d9010001', StringReferenceNamespaceTag::class];
        yield 'tag 257 (binary MIME message)' => ['d9010143616263', BinaryMimeTag::class];
        yield 'tag 258 (set)' => ['d9010283010203', SetTag::class];
        yield 'tag 259 (explicit map)' => ['d90103a10102', ExplicitMapTag::class];
        yield 'tag 1001 (extended time)' => ['d903e9a1011a05f5e100', ExtendedTimeTag::class];
        yield 'tag 1002 (duration)' => ['d903eaa101183c', DurationTag::class];
        yield 'tag 1003 (period)' => ['d903eb820102', PeriodTag::class];
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

    #[Test]
    public function aUuidIsNormalizedToItsCanonicalForm(): void
    {
        $tag = UuidTag::createFromUuidString('01234567-89AB-CDEF-0123-456789ABCDEF');

        static::assertSame('01234567-89ab-cdef-0123-456789abcdef', $tag->normalize());
        static::assertSame(
            'd825500123456789abcdef0123456789abcdef',
            bin2hex((string) $tag)
        );
    }

    #[Test]
    public function aUuidThatIsNotSixteenBytesIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a 16 byte Byte String object.');
        UuidTag::create(ByteStringObject::create('too short'));
    }

    #[Test]
    public function aTextThatIsNotAUuidIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value is not a valid UUID.');
        UuidTag::createFromUuidString('not a uuid');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rationalNumbers(): iterable
    {
        yield 'already in lowest terms' => ['d81e820304', '3/4'];
        yield 'reduced' => ['d81e820604', '3/2'];
        yield 'integral' => ['d81e820402', '2'];
        yield 'zero' => ['d81e820003', '0'];
        yield 'negative numerator' => ['d81e822004', '-1/4'];
        // 30([2, 18446744073709551617]), i.e. a denominator beyond PHP_INT_MAX carried by tag 2.
        yield 'big denominator' => ['d81e8202c249010000000000000001', '2/18446744073709551617'];
    }

    #[DataProvider('rationalNumbers')]
    #[Test]
    public function aRationalNumberIsNormalizedInLowestTerms(string $data, string $expected): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin($data)));

        static::assertInstanceOf(RationalNumberTag::class, $object);
        static::assertSame($expected, $object->normalize());
    }

    #[Test]
    public function aRationalNumberWithAZeroDenominatorIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value shall not be zero.');
        RationalNumberTag::create(ListObject::create([
            UnsignedIntegerObject::create(1),
            UnsignedIntegerObject::create(0),
        ]));
    }

    #[Test]
    public function aRationalNumberWithASignedDenominatorIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid denominator.');
        RationalNumberTag::create(ListObject::create([
            UnsignedIntegerObject::create(1),
            NegativeIntegerObject::create(-2),
        ]));
    }

    #[Test]
    public function aLanguageTaggedStringExposesItsTwoParts(): void
    {
        $tag = LanguageTaggedStringTag::createFromLanguageAndText('fr-CA', 'bonjour');

        static::assertSame('fr-CA', $tag->getLanguage());
        static::assertSame('bonjour', $tag->getText());
        static::assertSame(['fr-CA', 'bonjour'], $tag->normalize());
    }

    #[Test]
    public function aSerializedObjectExposesItsClassName(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d81a8263466f6f01')));

        static::assertInstanceOf(PerlObjectTag::class, $object);
        static::assertSame('Foo', $object->getClassName());
        static::assertSame(['Foo', '1'], $object->normalize());
    }

    #[Test]
    public function aSerializedObjectWithoutAClassNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 1 item');
        PerlObjectTag::create(ListObject::create([]));
    }

    #[Test]
    public function aSetKeepsTheOrderItWasWrittenIn(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d9010283030102')));

        static::assertInstanceOf(SetTag::class, $object);
        static::assertSame(['3', '1', '2'], $object->normalize());
    }

    #[Test]
    public function aSetThatIsNotAnArrayIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a List object.');
        SetTag::create(TextStringObject::create('not an array'));
    }

    #[Test]
    public function anExplicitMapIsNormalizedAsAMap(): void
    {
        $tag = ExplicitMapTag::create(
            MapObject::create()->add(TextStringObject::create('key'), TextStringObject::create('value'))
        );

        static::assertSame([
            'key' => 'value',
        ], $tag->normalize());
    }

    #[Test]
    public function aShareableTagNormalizesTheItemItMarks(): void
    {
        $tag = ShareableTag::create(TextStringObject::create('shared'));

        static::assertSame('shared', $tag->normalize());
    }

    #[Test]
    public function aStringReferenceIsNormalizedToItsIndex(): void
    {
        $tag = StringReferenceTag::create(UnsignedIntegerObject::create(7));

        static::assertSame('7', $tag->normalize());
    }

    #[Test]
    public function aStringReferenceThatIsNotAnIndexIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts an Unsigned Integer object.');
        SharedReferenceTag::create(NegativeIntegerObject::create(-1));
    }

    #[Test]
    public function aRegularExpressionIsCarriedAsItIsWritten(): void
    {
        $tag = RegexpTag::create(TextStringObject::create('^[a-z]+$'));

        static::assertSame('^[a-z]+$', $tag->normalize());
    }

    #[Test]
    public function aRegularExpressionThatIsNotTextIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        RegexpTag::create(ByteStringObject::create('^[a-z]+$'));
    }

    #[Test]
    public function aBinaryMimeMessageIsCarriedAsBytes(): void
    {
        $tag = BinaryMimeTag::create(ByteStringObject::create("Content-Type: text/plain\r\n\r\n\xff"));

        static::assertSame("Content-Type: text/plain\r\n\r\n\xff", $tag->normalize());
    }

    #[Test]
    public function anHomogeneousArrayIsNormalizedAsAnArray(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d82983010203')));

        static::assertInstanceOf(HomogeneousArrayTag::class, $object);
        static::assertSame(['1', '2', '3'], $object->normalize());
    }

    #[Test]
    public function anExtendedTimeIsReturnedAsTheMapItIs(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d903e9a1011a05f5e100')));

        static::assertInstanceOf(ExtendedTimeTag::class, $object);
        static::assertSame([
            1 => '100000000',
        ], $object->normalize());
    }

    #[Test]
    public function anExtendedTimeThatIsNotAMapIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Map object.');
        ExtendedTimeTag::create(ListObject::create([]));
    }

    #[Test]
    public function anIdentifierNormalizesTheItemItMarks(): void
    {
        $tag = IdentifierTag::create(TextStringObject::create('name'));

        static::assertSame('name', $tag->normalize());
    }

    #[Test]
    public function aPeriodIsReturnedAsTheArrayItIs(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d903eb820102')));

        static::assertInstanceOf(PeriodTag::class, $object);
        static::assertSame(['1', '2'], $object->normalize());
    }

    #[Test]
    public function anIpldContentIdentifierIsCarriedAsBytes(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d82a43000102')));

        static::assertInstanceOf(IpldContentIdentifierTag::class, $object);
        static::assertSame("\x00\x01\x02", $object->normalize());
    }
}
