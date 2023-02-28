<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\Tag\Base64Tag;
use CBOR\Tag\Base64UrlTag;
use CBOR\Tag\CBORTag;
use CBOR\Tag\UriTag;
use CBOR\TextStringObject;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SimpleTagsTest extends TestCase
{
    #[Test]
    public function createValidUriTag(): void
    {
        $tag = UriTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::LENGTH_1_BYTE, $tag->getAdditionalInformation());
        static::assertSame(hex2bin(dechex(CBORObject::TAG_URI)), $tag->getData());
    }

    #[Test]
    public function createInvalidUriTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        UriTag::create(ByteStringObject::create('Text'));
    }

    #[Test]
    public function createValidBase64Tag(): void
    {
        $tag = Base64Tag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::LENGTH_1_BYTE, $tag->getAdditionalInformation());
        static::assertSame(hex2bin(dechex(CBORObject::TAG_BASE64)), $tag->getData());
    }

    #[Test]
    public function createInvalidBase64Tag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        Base64Tag::create(ByteStringObject::create('Text'));
    }

    #[Test]
    public function createValidBase64UrlTag(): void
    {
        $tag = Base64UrlTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::LENGTH_1_BYTE, $tag->getAdditionalInformation());
        static::assertSame(hex2bin(dechex(CBORObject::TAG_BASE64_URL)), $tag->getData());
    }

    #[Test]
    public function createInvalidBase64UrlTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        Base64UrlTag::create(ByteStringObject::create('Text'));
    }

    #[Test]
    public function createValidCBORTag(): void
    {
        $tag = CBORTag::create(ByteStringObject::create('Text'));

        static::assertInstanceOf(ByteStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::LENGTH_2_BYTES, $tag->getAdditionalInformation());
        static::assertSame(hex2bin(dechex(CBORObject::TAG_CBOR)), $tag->getData());
    }
}
