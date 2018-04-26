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

namespace CBOR\Tag;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\TagObject as Base;

final class NegativeBigIntegerTag extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function createFromLoadedData(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(CBORObject $object): Base
    {
        if (!$object instanceof ByteStringObject) {
            throw new \InvalidArgumentException('This tag only accepts a Byte String object.');
        }

        return new self(3, null, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData()
    {
        $object = $this->getData();
        if (!$object instanceof ByteStringObject) {
            return $this->getData()->getNormalizedData();
        }
        $integer = gmp_init(bin2hex($object->getData()), 16);
        $minusOne = gmp_init('-1', 10);

        return gmp_strval(gmp_sub($minusOne, $integer), 10);
    }
}
