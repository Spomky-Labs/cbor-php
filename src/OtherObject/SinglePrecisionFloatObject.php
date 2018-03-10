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

final class SinglePrecisionFloatObject extends Base
{
    /**
     * @param int         $additionalInformation
     * @param null|string $data
     *
     * @return FalseObject
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
        $single = gmp_intval(gmp_init(bin2hex($this->getData()), 16));
        $exp = ($single >> 23) & 0xff;
        $mant = $single & 0x7fffff;

        if ($exp === 0) {
            $val = $mant * pow(2, -(126 + 23));
        } elseif ($exp !== 0b11111111) {
            $val = ($mant + (1 << 23)) * pow(2, $exp - (127 + 23));
        } else {
            $val = $mant === 0 ? INF : NAN;
        }

        return $single >> 31 ? -$val : $val;
    }

    /**
     * @return int
     */
    public function getExponent(): int
    {
        $single = gmp_intval(gmp_init(bin2hex($this->getData()), 16));

        return ($single >> 23) & 0xff;
    }

    /**
     * @return int
     */
    public function getMantissa(): int
    {
        $single = gmp_intval(gmp_init(bin2hex($this->getData()), 16));

        return $single & 0x7fffff;
    }

    /**
     * @return int
     */
    public function getSign(): int
    {
        $single = gmp_intval(gmp_init(bin2hex($this->getData()), 16));

        return $single >> 31 ? -1 : 1;
    }
}
