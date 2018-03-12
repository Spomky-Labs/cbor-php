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

final class NullObject extends Base
{
    /**
     * @param int         $additionalInformation
     * @param null|string $data
     *
     * @return NullObject
     */
    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData()
    {
    }
}
