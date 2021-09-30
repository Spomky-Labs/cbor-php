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

namespace CBOR\Test\Tag;

use CBOR\IndefiniteLengthTextStringObject;
use CBOR\OtherObject\BreakObject;
use CBOR\Tag\MimeTag;
use CBOR\TextStringObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class MimeTagTest extends TestCase
{
    /**
     * @test
     */
    public function createValidTagFromTextStringObject(): void
    {
        $tag = MimeTag::create(
            TextStringObject::create('text/plain')
        );
        static::assertEquals('text/plain', $tag->normalize());
    }

    /**
     * @test
     */
    public function createValidTagFromTIndefiniteLengthTextStringObject(): void
    {
        $tag = MimeTag::create(
            IndefiniteLengthTextStringObject::create()
                ->append('text')
                ->append('/')
                ->append('plain')
        );
        static::assertEquals('text/plain', $tag->normalize());
    }

    /**
     * @test
     */
    public function createInvalidTag(): void
    {
        static::expectException(InvalidArgumentException::class);
        static::expectExceptionMessage('This tag only accepts a Byte String object.');

        MimeTag::create(
            BreakObject::create()
        );
    }
}