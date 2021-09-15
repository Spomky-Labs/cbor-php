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
            $otherObjectManager = OtherObjectManager::create()
                ->add(OtherObject\BreakObject::class)
                ->add(OtherObject\SimpleObject::class)
                ->add(OtherObject\FalseObject::class)
                ->add(OtherObject\TrueObject::class)
                ->add(OtherObject\NullObject::class)
                ->add(OtherObject\UndefinedObject::class)
                ->add(OtherObject\HalfPrecisionFloatObject::class)
                ->add(OtherObject\SinglePrecisionFloatObject::class)
                ->add(OtherObject\DoublePrecisionFloatObject::class)
            ;
            $tagObjectManager = TagObjectManager::create()
                ->add(Tag\DatetimeTag::class)
                ->add(Tag\TimestampTag::class)

                ->add(Tag\UnsignedBigIntegerTag::class)
                ->add(Tag\NegativeBigIntegerTag::class)

                ->add(Tag\DecimalFractionTag::class)
                ->add(Tag\BigFloatTag::class)

                ->add(Tag\Base64UrlEncodingTag::class)
                ->add(Tag\Base64EncodingTag::class)
                ->add(Tag\Base16EncodingTag::class)
                ->add(Tag\CBOREncodingTag::class)

                ->add(Tag\UriTag::class)
                ->add(Tag\Base64UrlTag::class)
                ->add(Tag\Base64Tag::class)
                ->add(Tag\MimeTag::class)

                ->add(Tag\CBORTag::class)
            ;

            $this->decoder = Decoder::create($tagObjectManager, $otherObjectManager);
        }

        return $this->decoder;
    }
}
