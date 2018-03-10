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

namespace CBOR\OtherObject;

use CBOR\OtherObject as Base;

final class SimpleValueObject extends Base
{
    /**
     * @param int         $additionalInformation
     * @param null|string $data
     *
     * @return TrueObject
     */
    public static function create(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData()
    {
        return gmp_intval(gmp_init(bin2hex($this->getData()), 16));
    }
}
