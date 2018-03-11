<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Spomky-Labs
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

class BaseTestCase extends TestCase
{
    /**
     * @var null|Decoder
     */
    private $decoder;

    /**
     * @return Decoder
     */
    protected function getDecoder(): Decoder
    {
        if (null === $this->decoder) {
            $otherObjectManager = new OtherObjectManager();
            for ($i = 0; $i < 20; $i++) {
                $otherObjectManager->add($i, OtherObject\SimpleObject::class);
            }
            $otherObjectManager->add(20, OtherObject\FalseObject::class);
            $otherObjectManager->add(21, OtherObject\TrueObject::class);
            $otherObjectManager->add(22, OtherObject\NullObject::class);
            $otherObjectManager->add(23, OtherObject\UndefinedObject::class);
            $otherObjectManager->add(24, OtherObject\SimpleValueObject::class);
            $otherObjectManager->add(25, OtherObject\HalfPrecisionFloatObject::class);
            $otherObjectManager->add(26, OtherObject\SinglePrecisionFloatObject::class);
            $otherObjectManager->add(27, OtherObject\DoublePrecisionFloatObject::class);

            $tagObjectManager = new TagObjectManager();
            $tagObjectManager->add(0, Tag\EpochTagObject::class);
            $tagObjectManager->add(1, Tag\TimestampTagObject::class);
            $tagObjectManager->add(2, Tag\PositiveBigIntegerObject::class);
            $tagObjectManager->add(3, Tag\NegativeBigIntegerObject::class);
            $tagObjectManager->add(4, Tag\DecimalFractionObject::class);
            $tagObjectManager->add(5, Tag\BigFloatObject::class);
            $tagObjectManager->add(21, Tag\Base64UrlEncodingObject::class);
            $tagObjectManager->add(22, Tag\Base64EncodingObject::class);
            $tagObjectManager->add(23, Tag\Base16EncodingObject::class);

            $this->decoder = new Decoder(
                $tagObjectManager,
                $otherObjectManager
            );
        }

        return $this->decoder;
    }
}
