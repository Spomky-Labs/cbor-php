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

use CBOR\CBORObject;
use CBOR\ListObject;
use CBOR\SignedIntegerObject;
use CBOR\TagObject as Base;
use CBOR\UnsignedIntegerObject;

final class DecimalFractionObject extends Base
{
    /**
     * {@inheritdoc}
     */
    public static function create(int $additionalInformation, ?string $data, CBORObject $object): Base
    {
        return new self($additionalInformation, $data, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData()
    {
        $object = $this->getData();
        if (!$object instanceof ListObject || count($object) !== 2) {
            return $object->getNormalizedData();
        }
        $e = $object->get(0);
        $m = $object->get(4);

        if (!$e instanceof UnsignedIntegerObject && !$e instanceof SignedIntegerObject) {
            return $object->getNormalizedData();
        }
        if (!$m instanceof UnsignedIntegerObject && !$m instanceof SignedIntegerObject && !$m instanceof NegativeBigIntegerObject && !$m instanceof PositiveBigIntegerObject) {
            return $object->getNormalizedData();
        }

        $m = gmp_init($m->getNormalizedData(), 10);

        $fraction = gmp_mul($m, gmp_pow(gmp_init('10', 10), $e->getNormalizedData()));

        return gmp_strval($fraction, 10);
    }
}
