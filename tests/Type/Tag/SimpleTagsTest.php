<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Type\Tag;

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
        $tag = UriTag::create(
            TextStringObject::create('Text')
        );

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertEquals(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertEquals(CBORObject::TAG_URI, $tag->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function createInvalidUriTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        UriTag::create(
            ByteStringObject::create('Text')
        );
    }

    /**
     * @test
     */
    public function createValidBase64Tag(): void
    {
        $tag = Base64Tag::create(
            TextStringObject::create('Text')
        );

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertEquals(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertEquals(CBORObject::TAG_BASE64, $tag->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function createInvalidBase64Tag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        Base64Tag::create(
            ByteStringObject::create('Text')
        );
    }

    /**
     * @test
     */
    public function createValidBase64UrlTag(): void
    {
        $tag = Base64UrlTag::create(
            TextStringObject::create('Text')
        );

        static::assertInstanceOf(TextStringObject::class, $tag->getValue());
        static::assertEquals(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertEquals(CBORObject::TAG_BASE64_URL, $tag->getAdditionalInformation());
    }

    /**
     * @test
     */
    public function createInvalidBase64UrlTag(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This tag only accepts a Text String object.');
        Base64UrlTag::create(
            ByteStringObject::create('Text')
        );
    }

    /**
     * @test
     */
    public function createValidCBORTag(): void
    {
        $tag = CBORTag::create(
            ByteStringObject::create('Text')
        );

        static::assertInstanceOf(ByteStringObject::class, $tag->getValue());
        static::assertEquals(CBORObject::MAJOR_TYPE_TAG, $tag->getMajorType());
        static::assertEquals(CBORObject::TAG_CBOR, $tag->getAdditionalInformation());
    }
}