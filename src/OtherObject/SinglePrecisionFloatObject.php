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
     * {@inheritdoc}
     */
    public static function supportedAdditionalInformation(): array
    {
        return [26];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromLoadedData(int $additionalInformation, ?string $data): Base
    {
        return new self($additionalInformation, $data);
    }

    /**
     * @param string $value
     *
     * @return SinglePrecisionFloatObject
     */
    public static function create(string $value): self
    {
        if (mb_strlen($value, '8bit') !== 4) {
            throw new \InvalidArgumentException('The value is not a valid single precision floating point');
        }

        return new self(26, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getNormalizedData(bool $ignoreTags = false)
    {
        $single = gmp_intval(gmp_init(bin2hex($this->data), 16));
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
        $single = gmp_intval(gmp_init(bin2hex($this->data), 16));

        return ($single >> 23) & 0xff;
    }

    /**
     * @return int
     */
    public function getMantissa(): int
    {
        $single = gmp_intval(gmp_init(bin2hex($this->data), 16));

        return $single & 0x7fffff;
    }

    /**
     * @return int
     */
    public function getSign(): int
    {
        $single = gmp_intval(gmp_init(bin2hex($this->data), 16));

        return $single >> 31 ? -1 : 1;
    }
}
