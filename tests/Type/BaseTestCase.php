<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 * Copyright (c) 2018-2020 Spomky-Labs
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace CBOR\Test\Type;

use CBOR\Decoder;
use CBOR\OtherObject;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag;
use CBOR\Tag\TagObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BaseTestCase extends TestCase
{
    /**
     * @var Decoder|null
     */
    private $decoder;

    protected function getDecoder(): Decoder
    {
        if (null === $this->decoder) {
            $otherObjectManager = new OtherObjectManager();
            $otherObjectManager->add(OtherObject\BreakObject::class);
            $otherObjectManager->add(OtherObject\SimpleObject::class);
            $otherObjectManager->add(OtherObject\FalseObject::class);
            $otherObjectManager->add(OtherObject\TrueObject::class);
            $otherObjectManager->add(OtherObject\NullObject::class);
            $otherObjectManager->add(OtherObject\UndefinedObject::class);
            $otherObjectManager->add(OtherObject\HalfPrecisionFloatObject::class);
            $otherObjectManager->add(OtherObject\SinglePrecisionFloatObject::class);
            $otherObjectManager->add(OtherObject\DoublePrecisionFloatObject::class);

            $tagObjectManager = new TagObjectManager();
            $tagObjectManager->add(Tag\DatetimeTag::class);
            $tagObjectManager->add(Tag\TimestampTag::class);
            $tagObjectManager->add(Tag\PositiveBigIntegerTag::class);
            $tagObjectManager->add(Tag\NegativeBigIntegerTag::class);
            $tagObjectManager->add(Tag\DecimalFractionTag::class);
            $tagObjectManager->add(Tag\BigFloatTag::class);
            $tagObjectManager->add(Tag\Base64UrlEncodingTag::class);
            $tagObjectManager->add(Tag\Base64EncodingTag::class);
            $tagObjectManager->add(Tag\Base16EncodingTag::class);

            $this->decoder = new Decoder(
                $tagObjectManager,
                $otherObjectManager
            );
        }

        return $this->decoder;
    }
}
