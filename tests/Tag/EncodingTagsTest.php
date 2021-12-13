<?php

declare(strict_types=1);

/*
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Tag;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\Tag\Base16EncodingTag;
use CBOR\Tag\Base64EncodingTag;
use CBOR\Tag\Base64UrlEncodingTag;
use CBOR\Tag\CBOREncodingTag;
use CBOR\TextStringObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EncodingTagsTest extends TestCase
{
    /**
     * @test
     */
    public function createValidBase16EncodingTag(): void
    {
        $tag = Base16EncodingTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::TAG_ENCODED_BASE16, $tag->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function createValidBase64EncodingTag(): void
    {
        $tag = Base64EncodingTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::TAG_ENCODED_BASE64, $tag->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function createValidBase64UrlEncodingTag(): void
    {
        $tag = Base64UrlEncodingTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::TAG_ENCODED_BASE64_URL, $tag->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function createValidCBOREncodingTag(): void
    {
        $tag = CBOREncodingTag::create(ByteStringObject::create('Text'));

        static::assertInstanceOf(ByteStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::TAG_ENCODED_CBOR, $tag->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function createInvalidCBOREncodingTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Byte String object.');
        CBOREncodingTag::create(TextStringObject::create('Text'));
    }
}
