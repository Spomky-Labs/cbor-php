<?php

declare(strict_types=1);

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\Tag\Base16EncodingTag;
use CBOR\Tag\Base64EncodingTag;
use CBOR\Tag\Base64UrlEncodingTag;
use CBOR\Tag\CBOREncodingTag;
use CBOR\TextStringObject;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EncodingTagsTest extends TestCase
{
    #[Test]
    public function createValidBase16EncodingTag(): void
    {
        $tag = Base16EncodingTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::TAG_ENCODED_BASE16, $tag->getAdditionalInformation());
    }

    #[Test]
    public function createValidBase64EncodingTag(): void
    {
        $tag = Base64EncodingTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::TAG_ENCODED_BASE64, $tag->getAdditionalInformation());
    }

    #[Test]
    public function createValidBase64UrlEncodingTag(): void
    {
        $tag = Base64UrlEncodingTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::TAG_ENCODED_BASE64_URL, $tag->getAdditionalInformation());
    }

    #[Test]
    public function createValidCBOREncodingTag(): void
    {
        $tag = CBOREncodingTag::create(ByteStringObject::create('Text'));

        static::assertInstanceOf(ByteStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::TAG_ENCODED_CBOR, $tag->getAdditionalInformation());
    }

    #[Test]
    public function createInvalidCBOREncodingTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Byte String object.');
        CBOREncodingTag::create(TextStringObject::create('Text'));
    }
}
