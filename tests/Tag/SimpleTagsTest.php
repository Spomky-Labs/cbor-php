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
use CBOR\Tag\Base64Tag;
use CBOR\Tag\Base64UrlTag;
use CBOR\Tag\CBORTag;
use CBOR\Tag\UriTag;
use CBOR\TextStringObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class SimpleTagsTest extends TestCase
{
    /**
     * @test
     */
    public function createValidUriTag(): void
    {
        $tag = UriTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::LENGTH_1_BYTE, $tag->getAdditionalInformation());
        static::assertSame(hex2bin(dechex(CBORObject::TAG_URI)), $tag->getData());
    }

    /**
     * @test
     */
    public function createInvalidUriTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        UriTag::create(ByteStringObject::create('Text'));
    }

    /**
     * @test
     */
    public function createValidBase64Tag(): void
    {
        $tag = Base64Tag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::LENGTH_1_BYTE, $tag->getAdditionalInformation());
        static::assertSame(hex2bin(dechex(CBORObject::TAG_BASE64)), $tag->getData());
    }

    /**
     * @test
     */
    public function createInvalidBase64Tag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        Base64Tag::create(ByteStringObject::create('Text'));
    }

    /**
     * @test
     */
    public function createValidBase64UrlTag(): void
    {
        $tag = Base64UrlTag::create(TextStringObject::create('Text'));

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::LENGTH_1_BYTE, $tag->getAdditionalInformation());
        static::assertSame(hex2bin(dechex(CBORObject::TAG_BASE64_URL)), $tag->getData());
    }

    /**
     * @test
     */
    public function createInvalidBase64UrlTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        Base64UrlTag::create(ByteStringObject::create('Text'));
    }

    /**
     * @test
     */
    public function createValidCBORTag(): void
    {
        $tag = CBORTag::create(ByteStringObject::create('Text'));

        static::assertInstanceOf(ByteStringObject::class, $tag->getValue());
        static::assertSame(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertSame(CBORObject::LENGTH_2_BYTES, $tag->getAdditionalInformation());
        static::assertSame(hex2bin(dechex(CBORObject::TAG_CBOR)), $tag->getData());
    }
}
