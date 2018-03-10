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
use CBOR\TagObject as Base;

final class EpochTagObject extends Base
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
        return \DateTimeImmutable::createFromFormat(DATE_RFC3339, $this->getData()->getNormalizedData());
    }
}
