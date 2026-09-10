<?php

declare(strict_types=1);

namespace CBOR\Test;

use CBOR\StringStream;
use CBOR\Tag\CBORTag;
use CBOR\Tag\SelfDescribeCBORTag;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test cases for SelfDescribeCBORTag (Tag 55799)
 *
 * @see https://datatracker.ietf.org/doc/html/rfc8949#section-3.4.6
 * @internal
 */
final class SelfDescribeCBORTagTest extends CBORTestCase
{
    #[Test]
    public function selfDescribeCBORTagCanBeCreated(): void
    {
        $innerObject = UnsignedIntegerObject::create(42);
        $tag = SelfDescribeCBORTag::create($innerObject);

        static::assertInstanceOf(SelfDescribeCBORTag::class, $tag);
        static::assertSame(55799, SelfDescribeCBORTag::getTagId());
    }

    #[Test]
    public function selfDescribeCBORTagCanWrapTextString(): void
    {
        $innerObject = TextStringObject::create('Hello, CBOR!');
        $tag = SelfDescribeCBORTag::create($innerObject);

        $wrapped = $tag->getCBORObject();
        static::assertInstanceOf(TextStringObject::class, $wrapped);
        static::assertSame('Hello, CBOR!', $wrapped->normalize());
    }

    #[Test]
    public function selfDescribeCBORTagCanWrapInteger(): void
    {
        $innerObject = UnsignedIntegerObject::create(123);
        $tag = SelfDescribeCBORTag::create($innerObject);

        $wrapped = $tag->getCBORObject();
        static::assertInstanceOf(UnsignedIntegerObject::class, $wrapped);
        static::assertSame('123', $wrapped->normalize());
    }

    #[Test]
    public function selfDescribeCBORTagGetTagIdReturns55799(): void
    {
        static::assertSame(55799, SelfDescribeCBORTag::getTagId());
    }

    #[Test]
    public function selfDescribeCBORTagCanBeEncodedAndDecoded(): void
    {
        $innerObject = TextStringObject::create('CBOR');
        $tag = SelfDescribeCBORTag::create($innerObject);

        $encoded = (string) $tag;
        static::assertNotEmpty($encoded);

        // Verify it starts with the self-describe tag marker (0xd9d9f7)
        $hex = bin2hex($encoded);
        static::assertStringStartsWith('d9d9f7', $hex);
    }

    #[Test]
    public function selfDescribeCBORTagPreservesInnerObject(): void
    {
        $originalValue = 'Test Value';
        $innerObject = TextStringObject::create($originalValue);
        $tag = SelfDescribeCBORTag::create($innerObject);

        $retrieved = $tag->getCBORObject();
        static::assertSame($originalValue, $retrieved->normalize());
    }

    /**
     * SelfDescribeCBORTag is deprecated in favour of CBORTag, which is the class the default decoder
     * registers for tag 55799. The two must stay byte-for-byte interchangeable so that migrating is safe.
     */
    #[Test]
    public function selfDescribeCBORTagEncodesExactlyLikeTheCBORTagItIsDeprecatedInFavourOf(): void
    {
        $innerObject = TextStringObject::create('CBOR');

        static::assertSame(
            (string) CBORTag::create($innerObject),
            (string) SelfDescribeCBORTag::create($innerObject)
        );
    }

    #[Test]
    public function aSelfDescribeCBORTagIsDecodedBackAsACBORTag(): void
    {
        $tag = SelfDescribeCBORTag::create(TextStringObject::create('CBOR'));

        $object = $this->getDecoder()
            ->decode(StringStream::create((string) $tag));

        static::assertInstanceOf(CBORTag::class, $object);
        static::assertSame('CBOR', $object->normalize());
    }
}
