<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\ListObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\OtherObject\NullObject;
use CBOR\StringStream;
use CBOR\Tag\CoseEncrypt0Tag;
use CBOR\Tag\CoseEncryptTag;
use CBOR\Tag\CoseMac0Tag;
use CBOR\Tag\CoseMacTag;
use CBOR\Tag\CoseSign1Tag;
use CBOR\Tag\CoseSignTag;
use CBOR\Tag\CwtTag;
use CBOR\Tag\TagInterface;
use CBOR\Test\CBORTestCase;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use function hex2bin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * The six COSE structures of RFC 9052 and the CWT tag of RFC 8392 that wraps them.
 *
 * @internal
 */
final class CoseTagTest extends CBORTestCase
{
    /**
     * @return iterable<string, array{string, class-string<TagInterface>}>
     */
    public static function taggedDocuments(): iterable
    {
        yield 'tag 16 (COSE_Encrypt0)' => ['d08340a043616263', CoseEncrypt0Tag::class];
        yield 'tag 17 (COSE_Mac0)' => ['d18440a04361626343746167', CoseMac0Tag::class];
        yield 'tag 18 (COSE_Sign1)' => ['d28443a10126a043666f6f43736967', CoseSign1Tag::class];
        yield 'tag 96 (COSE_Encrypt)' => ['d8608440a04361626380', CoseEncryptTag::class];
        yield 'tag 97 (COSE_Mac)' => ['d8618540a0436162634374616780', CoseMacTag::class];
        yield 'tag 98 (COSE_Sign)' => ['d8628440a04361626380', CoseSignTag::class];
        yield 'tag 61 (CWT)' => ['d83dd28443a10126a043666f6f43736967', CwtTag::class];
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
    public function aSingleSignerStructureExposesEachOfItsParts(): void
    {
        // 18([h'a10126', {}, h'666f6f', h'736967'])
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d28443a10126a043666f6f43736967')));

        static::assertInstanceOf(CoseSign1Tag::class, $object);
        static::assertSame('a10126', bin2hex($object->getProtectedHeader()->getValue()));
        static::assertSame([
            1 => '-7',
        ], $object->getProtectedHeaderAsMap()
            ->normalize());
        static::assertCount(0, $object->getUnprotectedHeader());
        static::assertInstanceOf(ByteStringObject::class, $object->getPayload());
        static::assertSame('foo', $object->getPayload()->getValue());
        static::assertSame('sig', $object->getSignature()->getValue());
    }

    /**
     * A payload may be carried out of band, which COSE spells as null. Rejecting it would turn a detached
     * signature -- the reason the structure exists -- into a decoding error.
     */
    #[Test]
    public function aDetachedPayloadIsAccepted(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d28443a10126a0f643736967')));

        static::assertInstanceOf(CoseSign1Tag::class, $object);
        static::assertInstanceOf(NullObject::class, $object->getPayload());
    }

    /**
     * COSE writes "no protected header" as an empty byte string, which is not the encoding of an empty map.
     */
    #[Test]
    public function anAbsentProtectedHeaderIsReadAsAnEmptyMap(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d28440a043666f6f43736967')));

        static::assertInstanceOf(CoseSign1Tag::class, $object);
        static::assertCount(0, $object->getProtectedHeaderAsMap());
    }

    #[Test]
    public function aStructureWithTheWrongNumberOfItemsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid CoseSign1 object. The list shall have 4 items.');
        CoseSign1Tag::create(ListObject::create([
            ByteStringObject::create(''),
            MapObject::create(),
            ByteStringObject::create('foo'),
        ]));
    }

    #[Test]
    public function aStructureWhoseSignatureIsNotAByteStringIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid CoseSign1 object. The item 4 shall be a Byte String object.');
        CoseSign1Tag::create(ListObject::create([
            ByteStringObject::create(''),
            MapObject::create(),
            ByteStringObject::create('foo'),
            TextStringObject::create('sig'),
        ]));
    }

    #[Test]
    public function aStructureThatIsNotAnArrayIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not a valid CoseMac0 object. Expected a List object.');
        CoseMac0Tag::create(MapObject::create());
    }

    #[Test]
    public function aStructureBuiltFromItsPartsDecodesBackIntoItself(): void
    {
        $protectedHeader = MapObject::create()
            ->add(UnsignedIntegerObject::create(1), NegativeIntegerObject::create(-7));
        $tag = CoseSign1Tag::createFromComponents(
            $protectedHeader,
            MapObject::create(),
            ByteStringObject::create('foo'),
            ByteStringObject::create('sig')
        );

        $object = $this->getDecoder()
            ->decode(StringStream::create((string) $tag));

        static::assertInstanceOf(CoseSign1Tag::class, $object);
        static::assertSame([
            1 => '-7',
        ], $object->getProtectedHeaderAsMap()
            ->normalize());
        static::assertSame('foo', $object->getPayload()->getValue());
    }

    #[Test]
    public function aMultipleRecipientStructureExposesItsRecipients(): void
    {
        // 96([h'', {}, h'abc', [ ]])
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d8608440a04361626380')));

        static::assertInstanceOf(CoseEncryptTag::class, $object);
        static::assertSame('abc', $object->getCiphertext()->getValue());
        static::assertCount(0, $object->getRecipients());
    }

    #[Test]
    public function aMacStructureExposesItsTagAndItsRecipients(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d8618540a0436162634374616780')));

        static::assertInstanceOf(CoseMacTag::class, $object);
        static::assertSame('abc', $object->getPayload()->getValue());
        static::assertSame('tag', $object->getTag()->getValue());
        static::assertCount(0, $object->getRecipients());
    }

    #[Test]
    public function aWebTokenAcceptsAnUnsecuredClaimsSet(): void
    {
        $object = $this->getDecoder()
            ->decode(StringStream::create((string) hex2bin('d83da10102')));

        static::assertInstanceOf(CwtTag::class, $object);
        static::assertSame([
            1 => '2',
        ], $object->normalize());
    }

    #[Test]
    public function aWebTokenThatIsNeitherAClaimsSetNorACoseStructureIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Map object, a List object or a COSE tag.');
        CwtTag::create(TextStringObject::create('not a token'));
    }
}
